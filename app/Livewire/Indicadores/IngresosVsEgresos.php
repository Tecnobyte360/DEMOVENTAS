<?php

namespace App\Livewire\Indicadores;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Factura\Factura;
use App\Models\NotaCredito;

class IngresosVsEgresos extends Component
{
    public int $year;
    public ?int $empresa_id = null;

    // Data para el chart
    public array $labels = [];
    public array $ingresos = [];
    public array $egresos  = [];
    public array $neto     = [];

    // KPIs
    public float $totalIngresos = 0;
    public float $totalEgresos  = 0;
    public float $totalNeto     = 0;

    protected $listeners = [
        'refresh-ingresos-egresos' => 'loadData',
    ];

    public function mount(?int $empresa_id = null, ?int $year = null): void
    {
        $this->empresa_id = $empresa_id;
        $this->year = $year ?: (int) now()->year;

        $this->loadData();
    }

    public function updatedYear(): void
    {
        $this->loadData();
    }

    public function updatedEmpresaId(): void
    {
        $this->loadData();
    }

    private function months(): array
    {
        return [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ];
    }

    public function loadData(): void
    {
        $start = Carbon::create($this->year, 1, 1)->startOfDay();
        $end   = Carbon::create($this->year, 12, 31)->endOfDay();

        $months = $this->months();

        // ==========================
        // 1) INGRESOS: misma lógica de VentasPorMes
        //    FRM suma / NCV resta
        // ==========================
        $qIngresos = DB::table('facturas as f')
            ->join('series as s', 's.id', '=', 'f.serie_id')
            ->selectRaw('MONTH(f.fecha) as mes')

            ->selectRaw("
            SUM(
                CASE
                    WHEN s.prefijo = 'FRM'
                     AND f.total > 0
                     AND f.numero IS NOT NULL
                     AND f.numero <> ''
                     AND f.estado <> 'anulada'
                    THEN f.total
                    ELSE 0
                END
            ) as facturas
        ")

            ->selectRaw("
            SUM(
                CASE
                    WHEN s.prefijo = 'NCV'
                     AND f.numero IS NOT NULL
                     AND f.numero <> ''
                     AND f.estado <> 'anulada'
                    THEN ABS(f.total)
                    ELSE 0
                END
            ) as notas_credito
        ")

            ->selectRaw("
            SUM(
                CASE
                    WHEN s.prefijo = 'FRM'
                     AND f.numero IS NOT NULL
                     AND f.numero <> ''
                     AND f.estado <> 'anulada'
                    THEN f.total
                    WHEN s.prefijo = 'NCV'
                     AND f.numero IS NOT NULL
                     AND f.numero <> ''
                     AND f.estado <> 'anulada'
                    THEN -ABS(f.total)
                    ELSE 0
                END
            ) as neto
        ")

            ->whereBetween('f.fecha', [$start, $end])
            ->whereIn('s.prefijo', ['FRM', 'NCV']);

        if ($this->empresa_id && SchemaHasColumn('facturas', 'empresa_id')) {
            $qIngresos->where('f.empresa_id', $this->empresa_id);
        }

        $ingresosPorMes = $qIngresos
            ->groupByRaw('MONTH(f.fecha)')
            ->orderByRaw('MONTH(f.fecha)')
            ->get()
            ->keyBy('mes');

        // ==========================
        // 2) GASTOS / EGRESOS
        // ==========================
        $gastosPorMes = $this->sumByMonth(
            table: 'gastos_ruta',
            dateColumn: 'created_at',
            amountColumn: 'monto',
            start: $start,
            end: $end,
            empresaColumn: null
        );

        // ==========================
        // 3) COMPRAS (si aplica)
        // ==========================
        $comprasPorMes = $this->sumByMonth(
            table: 'compras',
            dateColumn: 'fecha',
            amountColumn: 'total',
            start: $start,
            end: $end,
            empresaColumn: 'empresa_id',
            extraWhere: ['serie_id' => 13]
        );

        // ==========================
        // 4) NC COMPRA (si aplica)
        // ==========================
        $ncCompraPorMes = $this->sumByMonth(
            table: 'notas_credito_compra',
            dateColumn: 'fecha',
            amountColumn: 'total',
            start: $start,
            end: $end,
            empresaColumn: 'empresa_id',
            extraWhere: ['serie_id' => 13]
        );

        // ==========================
        // 5) Construcción final
        // ==========================
        $labels = [];
        $ingArr = [];
        $egrArr = [];
        $netArr = [];

        $tIng = 0.0;
        $tEgr = 0.0;

        foreach ($months as $m => $label) {
            $labels[] = $label;

            $rowIngresos = $ingresosPorMes->get($m);

            $ing = (float) ($rowIngresos->neto ?? 0);

            $gas = (float) ($gastosPorMes[$m] ?? 0);
            $com = (float) ($comprasPorMes[$m] ?? 0);
            $ncC = (float) ($ncCompraPorMes[$m] ?? 0);

            $egr = max(($gas + $com) - $ncC, 0);
            $net = $ing - $egr;

            $ingArr[] = round($ing, 2);
            $egrArr[] = round($egr, 2);
            $netArr[] = round($net, 2);

            $tIng += $ing;
            $tEgr += $egr;
        }

        $this->labels   = $labels;
        $this->ingresos = $ingArr;
        $this->egresos  = $egrArr;
        $this->neto     = $netArr;

        $this->totalIngresos = round($tIng, 2);
        $this->totalEgresos  = round($tEgr, 2);
        $this->totalNeto     = round($tIng - $tEgr, 2);
    }

    private function sumByMonth(
        string $table,
        string $dateColumn,
        string $amountColumn,
        Carbon $start,
        Carbon $end,
        ?string $empresaColumn = null,
        array $extraWhere = []
    ): array {
        if (!SchemaHasTable($table)) return [];
        if (!SchemaHasColumn($table, $dateColumn)) return [];
        if (!SchemaHasColumn($table, $amountColumn)) return [];

        $q = DB::table($table)
            ->selectRaw("MONTH($dateColumn) as mes, SUM($amountColumn) as total")
            ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()]);

        if ($this->empresa_id && $empresaColumn && SchemaHasColumn($table, $empresaColumn)) {
            $q->where($empresaColumn, $this->empresa_id);
        }

        foreach ($extraWhere as $col => $val) {
            if (SchemaHasColumn($table, $col)) {
                $q->where($col, $val);
            }
        }

        return $q->groupByRaw("MONTH($dateColumn)")
            ->pluck('total', 'mes')
            ->map(fn($v) => (float) $v)
            ->toArray();
    }

    public function render()
    {
        return view('livewire.indicadores.ingresos-vs-egresos', [
            'labels'        => $this->labels,
            'ingresos'      => $this->ingresos,
            'egresos'       => $this->egresos,
            'neto'          => $this->neto,
            'totalIngresos' => $this->totalIngresos,
            'totalEgresos'  => $this->totalEgresos,
            'totalNeto'     => $this->totalNeto,
        ]);
    }
}

/**
 * Helpers para evitar reventar si no tienes tablas/columnas aún.
 */
function SchemaHasTable(string $table): bool
{
    try {
        return \Illuminate\Support\Facades\Schema::hasTable($table);
    } catch (\Throwable $e) {
        return false;
    }
}

function SchemaHasColumn(string $table, string $column): bool
{
    try {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    } catch (\Throwable $e) {
        return false;
    }
}
