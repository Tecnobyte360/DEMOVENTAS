<?php

namespace App\Livewire\Productos;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

use App\Models\Productos\Producto;
use App\Models\Bodega;
use App\Models\Movimiento\Movimiento;

/**
 * Kardex con CPU (promedio móvil), sin requerir columna 'tipo' en DB.
 * Deriva el tipo por el signo de 'cantidad' cuando no hay columna de tipo.
 */
class KardexProducto extends Component
{
    use WithPagination;

    /** Si tienes una columna real de tipo (p.ej. 'tipo_movimiento'), colócala aquí. Si la dejas en null, se deriva por cantidad. */
    private const TIPO_COLUMN = null; // 'tipo_movimiento' | 'naturaleza' | null

    /** ==== Filtros ==== */
    public ?int $producto_id = null;
    public ?int $bodega_id   = null;
    public ?string $desde    = null;   // YYYY-MM-DD
    public ?string $hasta    = null;   // YYYY-MM-DD
    public string $buscarDoc = '';
    public int $perPage      = 25;

    /** ==== Saldos ==== */
    public float $saldoInicialCant = 0.0;
    public float $saldoInicialVal  = 0.0;
    public float $saldoFinalCant   = 0.0;
    public float $saldoFinalVal    = 0.0;

    /** ==== Catálogos ==== */
    public $productos;
    public $bodegas;

    protected $queryString = [
        'producto_id' => ['except' => null],
        'bodega_id'   => ['except' => null],
        'desde'       => ['except' => null],
        'hasta'       => ['except' => null],
        'buscarDoc'   => ['except' => ''],
        'perPage'     => ['except' => 25],
    ];

    public function mount(?int $productoId = null): void
    {
        $this->productos = Producto::orderBy('nombre')->get(['id','nombre']);
        $this->bodegas   = Bodega::orderBy('nombre')->get(['id','nombre']);

        $this->producto_id = $productoId ?: $this->producto_id;
        $this->desde = $this->desde ?: Carbon::now()->subDays(90)->toDateString();
        $this->hasta = $this->hasta ?: Carbon::now()->toDateString();
    }

    public function updating($prop): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // reset saldos
        $this->saldoInicialCant = $this->saldoInicialVal = 0.0;
        $this->saldoFinalCant   = $this->saldoFinalVal   = 0.0;

        if ($this->producto_id) {
            $this->calcularSaldoInicial();
            $filas = $this->kardexEnRangoPaginado(); // siempre paginator
        } else {
            // paginator vacío (para que links() no falle)
            $filas = new LengthAwarePaginator(
                collect(), 0, $this->perPage, 1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        return view('livewire.productos.kardex-producto', compact('filas'));
    }

    /* =======================================================
     * CÁLCULOS
     * ======================================================= */

    private function calcularSaldoInicial(): void
    {
        $hasta = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;

        $q = Movimiento::query()
            ->where('producto_id', $this->producto_id);

        if ($this->bodega_id) $q->where('bodega_id', $this->bodega_id);
        if ($hasta) $q->where('fecha', '<', $hasta);

        // Selecciona solo columnas existentes (no pedimos 'tipo')
        $movs = $q->get(['cantidad','total','costo_unitario']);

        $cant = 0.0;
        $val  = 0.0;

        foreach ($movs as $m) {
            $tipo = $this->deriveTipo($m); // 'ENTRADA' | 'SALIDA'
            $c    = abs((float) $m->cantidad);
            $t    = (float) ($m->total ?? ($c * (float)($m->costo_unitario ?? 0)));

            if ($tipo === 'ENTRADA') {
                $cant += $c; $val += $t;
            } else { // SALIDA
                $cant -= $c; $val -= abs($t) ?: ($c * ($cant > 0 ? ($val / max($cant,1e-9)) : 0.0));
            }
        }

        if (abs($cant) < 1e-9) { $cant = 0.0; $val = 0.0; }

        $this->saldoInicialCant = round($cant, 6);
        $this->saldoInicialVal  = round($val, 6);
    }

    private function kardexEnRangoPaginado()
    {
        $desde = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;
        $hasta = $this->hasta ? Carbon::parse($this->hasta)->endOfDay()   : null;

        $q = Movimiento::query()
            ->where('producto_id', $this->producto_id);

        if ($this->bodega_id) $q->where('bodega_id', $this->bodega_id);
        if ($desde) $q->where('fecha', '>=', $desde);
        if ($hasta) $q->where('fecha', '<=', $hasta);

        if ($this->buscarDoc) {
            $txt = '%' . Str::of($this->buscarDoc)->trim() . '%';
            $q->where(function ($x) use ($txt) {
                $x->where('doc_tipo', 'like', $txt)
                  ->orWhere('doc_id', 'like', $txt)
                  ->orWhere('ref', 'like', $txt);
            });
        }

        $q->orderBy('fecha')->orderBy('id');

        // Paginamos SIN pedir 'tipo' (para no reventar en BD)
        /** @var LengthAwarePaginator $paginator */
        $paginator = $q->paginate($this->perPage, [
            'id','fecha','bodega_id','cantidad','costo_unitario','total','doc_tipo','doc_id','ref'
        ]);

        // Para CPU correcto en página N, “replay” de movimientos previos a la página actual
        $idsOrdenados  = (clone $q)->pluck('id');
        $firstModel    = $paginator->items()[0] ?? null;
        $firstIdPagina = $firstModel?->id;
        $idxInicio     = $firstIdPagina ? $idsOrdenados->search($firstIdPagina) : 0;
        $idsPrev       = $idxInicio > 0 ? $idsOrdenados->slice(0, $idxInicio)->values() : collect();

        [$saldoCant, $saldoVal] = [$this->saldoInicialCant, $this->saldoInicialVal];
        $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;

        if ($idsPrev->isNotEmpty()) {
            $prevMovs = Movimiento::whereIn('id', $idsPrev)
                ->orderBy('fecha')->orderBy('id')
                ->get(['cantidad','costo_unitario','total']);

            foreach ($prevMovs as $m) {
                $tipo = $this->deriveTipo($m);
                $c    = abs((float) $m->cantidad);
                $t    = (float) ($m->total ?? ($c * (float)($m->costo_unitario ?? 0)));

                if ($tipo === 'ENTRADA') {
                    $saldoCant += $c;
                    $saldoVal  += $t;
                } else { // SALIDA
                    $valorSalida = $c * $cpu;
                    $saldoCant  -= $c;
                    $saldoVal   -= $valorSalida;
                    if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
                }
                $cpu = $saldoCant > 0 ? $saldoVal / $saldoCant : 0.0;
            }
        }

        // Transformamos los ítems de la página y DEVOLVEMOS un nuevo paginator
        $items = collect($paginator->items())->map(function ($m) use (&$saldoCant, &$saldoVal, &$cpu) {
            $tipo = $this->deriveTipo($m);
            $c    = abs((float) $m->cantidad);
            $cu   = (float) ($m->costo_unitario ?? 0);
            $t    = (float) ($m->total ?? ($c * $cu));

            $entrada = null; $salida = null;

            if ($tipo === 'ENTRADA') {
                $entrada   = $c;
                $saldoCant += $c;
                $saldoVal  += $t;
            } else { // SALIDA
                $salida     = $c;
                $valorSalida= $c * $cpu;
                $saldoCant -= $c;
                $saldoVal  -= $valorSalida;
                if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
            }

            $cpu = $saldoCant > 0 ? $saldoVal / $saldoCant : 0.0;

            return [
                'id'         => $m->id,
                'fecha'      => optional($m->fecha)->format('Y-m-d H:i') ?? (string) $m->fecha,
                'bodega'     => optional($this->bodegas->firstWhere('id', $m->bodega_id))->nombre,
                'tipo'       => $tipo, // <- entregamos 'ENTRADA' | 'SALIDA' derivado
                'doc'        => trim(($m->doc_tipo ?? '').' #'.($m->doc_id ?? '')) . ($m->ref ? ' ('.$m->ref.')' : ''),
                'entrada'    => $entrada,
                'salida'     => $salida,
                // Para mostrar: en salidas usamos el CPU vigente; en entradas, el costo_unitario del registro
                'costo_unit' => $tipo === 'SALIDA' ? $cpu : ($cu ?: null),
                'saldo_cant' => round($saldoCant, 6),
                'saldo_val'  => round($saldoVal, 2),
                'saldo_cpu'  => $saldoCant > 0 ? round($saldoVal / max($saldoCant,1e-9), 6) : null,
            ];
        });

        // Actualizar saldos finales si estamos en última página
        if ($paginator->currentPage() === $paginator->lastPage() && $items->count()) {
            $last = $items->last();
            $this->saldoFinalCant = (float) $last['saldo_cant'];
            $this->saldoFinalVal  = (float) $last['saldo_val'];
        } else {
            $this->saldoFinalCant = 0.0;
            $this->saldoFinalVal  = 0.0;
        }

        return new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Deriva el tipo de movimiento:
     * - Si TIPO_COLUMN está configurada y existe en el modelo, la usa.
     * - Si no, define por signo de cantidad: >=0 ENTRADA, <0 SALIDA.
     */
    private function deriveTipo($mov): string
    {
        if (self::TIPO_COLUMN && isset($mov->{self::TIPO_COLUMN})) {
            $v = strtoupper((string) $mov->{self::TIPO_COLUMN});
            if (in_array($v, ['ENTRADA','E','IN'], true)) return 'ENTRADA';
            if (in_array($v, ['SALIDA','S','OUT'], true)) return 'SALIDA';
        }

        $cantidad = (float) ($mov->cantidad ?? 0);
        return $cantidad >= 0 ? 'ENTRADA' : 'SALIDA';
    }
}
