<?php

namespace App\Livewire\Indicadores;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Ajusta estos imports a tus modelos reales si existen:
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

    // (Opcional) refrescar desde otros componentes
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
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];
    }

    public function loadData(): void
    {
        $start = Carbon::create($this->year, 1, 1)->startOfDay();
        $end   = Carbon::create($this->year, 12, 31)->endOfDay();

        $months = $this->months();

        // ==========================
        // 1) FACTURAS (VENTAS)
        // ==========================
        $qFacturas = Factura::query()
            ->selectRaw('MONTH(fecha) as mes, SUM(total) as total')
            ->whereBetween('fecha', [$start, $end])
            ->whereNotIn('estado', ['anulada']); // ajusta si manejas otros estados
        if ($this->empresa_id) $qFacturas->where('empresa_id', $this->empresa_id);

        $facturasPorMes = $qFacturas
            ->groupByRaw('MONTH(fecha)')
            ->pluck('total', 'mes')
            ->map(fn($v) => (float) $v)
            ->toArray();

        // ==========================
        // 2) NOTAS CRÉDITO (VENTA)
        // ==========================
        // Ajusta columnas/tabla según tu NotaCredito real:
        // - fecha
        // - total
        // - estado
        // - empresa_id (si aplica)
        $qNCVenta = NotaCredito::query()
            ->selectRaw('MONTH(fecha) as mes, SUM(total) as total')
            ->whereBetween('fecha', [$start, $end]);

        // Si tu NC tiene estados:
        if (SchemaHasColumn('notas_credito', 'estado')) {
            $qNCVenta->whereNotIn('estado', ['anulada']);
        }
        // Si tu NC tiene empresa_id:
        if ($this->empresa_id && SchemaHasColumn('notas_credito', 'empresa_id')) {
            $qNCVenta->where('empresa_id', $this->empresa_id);
        }

        $ncVentaPorMes = $qNCVenta
            ->groupByRaw('MONTH(fecha)')
            ->pluck('total', 'mes')
            ->map(fn($v) => (float) $v)
            ->toArray();

        // ==========================
        // 3) GASTOS (EGRESOS)  ✅ ENCHUFAR
        // ==========================
        // Reemplaza 'gastos'/'fecha'/'total' por tus tablas reales:
        $gastosPorMes = $this->sumByMonth(
            table: 'gastos',           // <- AJUSTA
            dateColumn: 'fecha',       // <- AJUSTA
            amountColumn: 'total',     // <- AJUSTA
            start: $start,
            end: $end,
            empresaColumn: 'empresa_id' // <- AJUSTA o null
        );

        // ==========================
        // 4) COMPRAS (EGRESOS) ✅ ENCHUFAR
        // ==========================
        // Reemplaza 'compras'/'fecha'/'total' por tus tablas reales:
        $comprasPorMes = $this->sumByMonth(
            table: 'compras',          // <- AJUSTA
            dateColumn: 'fecha',       // <- AJUSTA
            amountColumn: 'total',     // <- AJUSTA
            start: $start,
            end: $end,
            empresaColumn: 'empresa_id' // <- AJUSTA o null
        );

        // ==========================
        // 5) NOTAS CRÉDITO COMPRA (RESTAN EGRESOS) ✅ ENCHUFAR
        // ==========================
        $ncCompraPorMes = $this->sumByMonth(
            table: 'notas_credito_compra', // <- AJUSTA
            dateColumn: 'fecha',           // <- AJUSTA
            amountColumn: 'total',         // <- AJUSTA
            start: $start,
            end: $end,
            empresaColumn: 'empresa_id'    // <- AJUSTA o null
        );

        // ==========================
        // Construcción final arrays
        // ==========================
        $labels  = [];
        $ingArr  = [];
        $egrArr  = [];
        $netArr  = [];

        $tIng = 0.0;
        $tEgr = 0.0;

        foreach ($months as $m => $label) {
            $labels[] = $label;

            $fact = (float) ($facturasPorMes[$m] ?? 0);
            $ncV  = (float) ($ncVentaPorMes[$m] ?? 0);

            $ing = max($fact - $ncV, 0);

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

        $this->labels = $labels;
        $this->ingresos = $ingArr;
        $this->egresos  = $egrArr;
        $this->neto     = $netArr;

        $this->totalIngresos = round($tIng, 2);
        $this->totalEgresos  = round($tEgr, 2);
        $this->totalNeto     = round($tIng - $tEgr, 2);
    }

    /**
     * Suma por mes una tabla genérica.
     * Devuelve: [mes => total]
     */
    private function sumByMonth(
        string $table,
        string $dateColumn,
        string $amountColumn,
        Carbon $start,
        Carbon $end,
        ?string $empresaColumn = null
    ): array {
        // Si la tabla no existe, devolvemos 0s sin romper el indicador.
        if (!SchemaHasTable($table)) return [];

        $q = DB::table($table)
            ->selectRaw("MONTH($dateColumn) as mes, SUM($amountColumn) as total")
            ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()]);

        if ($this->empresa_id && $empresaColumn && SchemaHasColumn($table, $empresaColumn)) {
            $q->where($empresaColumn, $this->empresa_id);
        }

        return $q->groupByRaw("MONTH($dateColumn)")
            ->pluck('total', 'mes')
            ->map(fn($v) => (float) $v)
            ->toArray();
    }

    public function render()
    {
        // Tu blade ya usa estas variables, perfecto:
        return view('livewire.indicadores.ingresos-vs-egresos', [
            'labels'       => $this->labels,
            'ingresos'     => $this->ingresos,
            'egresos'      => $this->egresos,
            'neto'         => $this->neto,
            'totalIngresos'=> $this->totalIngresos,
            'totalEgresos' => $this->totalEgresos,
            'totalNeto'    => $this->totalNeto,
        ]);
    }
}

/**
 * Helpers pequeños para evitar reventar si no tienes tablas/columnas aún.
 * Los dejo acá para que copies/pegues rápido; si ya tienes helpers propios, bórralos.
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
