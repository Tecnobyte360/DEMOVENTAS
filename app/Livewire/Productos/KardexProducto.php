<?php

namespace App\Livewire\Productos;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

use App\Models\Productos\Producto;
use App\Models\Bodega;
use App\Models\Movimiento\Movimiento;

class KardexProducto extends Component
{
    use WithPagination;

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

    /** ==== Cache de columnas resueltas ==== */
    private array $cols = [];

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
        $this->productos = Producto::orderBy('nombre')->get(['id', 'nombre']);
        $this->bodegas   = Bodega::orderBy('nombre')->get(['id', 'nombre']);

        $this->producto_id = $productoId ?: $this->producto_id;
        $this->desde = $this->desde ?: Carbon::now()->subDays(90)->toDateString();
        $this->hasta = $this->hasta ?: Carbon::now()->toDateString();

        $this->resolveColumns();
    }

    public function updating($prop): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // reinicia saldos en cada render
        $this->saldoInicialCant = $this->saldoInicialVal = 0.0;
        $this->saldoFinalCant   = $this->saldoFinalVal   = 0.0;

        if ($this->producto_id) {
            $this->calcularSaldoInicial();
            $filas = $this->kardexEnRangoPaginado();
        } else {
            $filas = new LengthAwarePaginator(
                collect(), 0, $this->perPage, 1,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        return view('livewire.productos.kardex-producto', compact('filas'));
    }

    /* =======================================================
     * RESOLUCIÓN DE COLUMNAS (dinámica)
     * ======================================================= */
    private function resolveColumns(): void
    {
        $table = (new Movimiento)->getTable(); // normalmente 'movimientos'

        $pick = function (array $cands) use ($table): ?string {
            foreach ($cands as $c) {
                if (Schema::hasColumn($table, $c)) return $c;
            }
            return null;
        };

        // Asegura que TODAS las claves existan
        $this->cols = array_merge([
            'fecha'    => null,
            'producto' => null,
            'bodega'   => null,
            'cantidad' => null,
            'entrada'  => null,
            'salida'   => null,
            'signo'    => null,
            'total'    => null,
            'cu'       => null,
            'doc_tipo' => null,
            'doc_id'   => null,
            'ref'      => null,
        ], [
            'fecha'     => $pick(['fecha','mov_fecha','fecha_movimiento','created_at']),
            'producto'  => $pick(['producto_id','item_id']),
            'bodega'    => $pick(['bodega_id','almacen_id','warehouse_id']),
            'cantidad'  => $pick(['cantidad','qty','cantidad_total','cantidad_um','cantidad_mov']),
            'entrada'   => $pick(['entrada','cantidad_entrada','qty_in','ingreso']),
            'salida'    => $pick(['salida','cantidad_salida','qty_out','egreso']),
            'signo'     => $pick(['signo','direction']),
            'total'     => $pick(['total','valor_total','monto','importe_total']),
            'cu'        => $pick(['costo_unitario','cpu','costo_promedio','costo','valor_unitario']),
            'doc_tipo'  => $pick(['doc_tipo','documento_tipo','tipo_doc']),
            'doc_id'    => $pick(['doc_id','documento_id','num_doc','numero_doc']),
            'ref'       => $pick(['ref','referencia','observacion','detalle']),
        ]);

        // Fallbacks obligatorios
        if (!$this->cols['fecha'])    $this->cols['fecha']    = 'created_at';
        if (!$this->cols['producto']) $this->cols['producto'] = 'producto_id';
    }

    /** Getter seguro para columnas (con fallback adicional opcional) */
    private function col(string $k, ?string $fallback = null): string
    {
        if (!array_key_exists($k, $this->cols) || !$this->cols[$k]) {
            return $fallback ?? match ($k) {
                'fecha'    => 'created_at',
                'producto' => 'producto_id',
                default    => $k,
            };
        }
        return $this->cols[$k];
    }

    /* =======================================================
     * CÁLCULOS
     * ======================================================= */
    private function calcularSaldoInicial(): void
    {
        $hasta = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;

        $q = Movimiento::query()
            ->where($this->col('producto'), $this->producto_id);

        if ($this->bodega_id && $this->cols['bodega']) {
            $q->where($this->col('bodega'), $this->bodega_id);
        }
        if ($hasta) {
            $q->where($this->col('fecha'), '<', $hasta);
        }

        $select = array_filter([
            $this->cols['entrada']  ? $this->col('entrada')  : null,
            $this->cols['salida']   ? $this->col('salida')   : null,
            $this->cols['cantidad'] ? $this->col('cantidad') : null,
            $this->cols['signo']    ? $this->col('signo')    : null,
            $this->cols['total']    ? $this->col('total')    : null,
            $this->cols['cu']       ? $this->col('cu')       : null,
        ]);
        if (!$select) $select = ['id'];

        $movs = $q->get($select);

        $cant = 0.0; $val = 0.0;
        foreach ($movs as $m) {
            [$tipo, $c, $t, $cu] = $this->extractMovimiento($m);
            if ($tipo === 'ENTRADA') {
                $cant += $c; $val += $t;
            } else {
                $cpu = $cant > 0 ? ($val / max($cant, 1e-9)) : 0.0;
                $cant -= $c; $val -= $c * $cpu;
                if ($cant < 1e-9) { $cant = 0.0; $val = 0.0; }
            }
        }

        $this->saldoInicialCant = round($cant, 6);
        $this->saldoInicialVal  = round($val, 6);
    }

    private function kardexEnRangoPaginado()
    {
        $desde = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;
        $hasta = $this->hasta ? Carbon::parse($this->hasta)->endOfDay()   : null;

        $q = Movimiento::query()
            ->where($this->col('producto'), $this->producto_id);

        if ($this->bodega_id && $this->cols['bodega']) $q->where($this->col('bodega'), $this->bodega_id);
        if ($desde) $q->where($this->col('fecha'), '>=', $desde);
        if ($hasta) $q->where($this->col('fecha'), '<=', $hasta);

        if ($this->buscarDoc) {
            $txt = '%' . Str::of($this->buscarDoc)->trim() . '%';
            $q->where(function ($x) use ($txt) {
                if ($this->cols['doc_tipo']) $x->orWhere($this->col('doc_tipo'), 'like', $txt);
                if ($this->cols['doc_id'])   $x->orWhere($this->col('doc_id'),   'like', $txt);
                if ($this->cols['ref'])      $x->orWhere($this->col('ref'),      'like', $txt);
            });
        }

        $q->orderBy($this->col('fecha'))->orderBy('id');

        $select = array_values(array_filter([
            'id',
            $this->col('fecha'),
            $this->cols['bodega']   ? $this->col('bodega')   : null,
            $this->cols['entrada']  ? $this->col('entrada')  : null,
            $this->cols['salida']   ? $this->col('salida')   : null,
            $this->cols['cantidad'] ? $this->col('cantidad') : null,
            $this->cols['signo']    ? $this->col('signo')    : null,
            $this->cols['cu']       ? $this->col('cu')       : null,
            $this->cols['total']    ? $this->col('total')    : null,
            $this->cols['doc_tipo'] ? $this->col('doc_tipo') : null,
            $this->cols['doc_id']   ? $this->col('doc_id')   : null,
            $this->cols['ref']      ? $this->col('ref')      : null,
        ]));
        $paginator = $q->paginate($this->perPage, $select);

        // IDs para “replay” (CPU correcto en páginas intermedias)
        $idsOrdenados  = (clone $q)->pluck('id');
        $firstModel    = $paginator->items()[0] ?? null;
        $firstIdPagina = $firstModel->id ?? null;
        $idxInicio     = $firstIdPagina ? $idsOrdenados->search($firstIdPagina) : 0;
        $idsPrev       = $idxInicio > 0 ? $idsOrdenados->slice(0, $idxInicio)->values() : collect();

        [$saldoCant, $saldoVal] = [$this->saldoInicialCant, $this->saldoInicialVal];

        if ($idsPrev->isNotEmpty()) {
            $prevSelect = array_values(array_filter([
                'id',
                $this->col('fecha'),
                $this->cols['entrada']  ? $this->col('entrada')  : null,
                $this->cols['salida']   ? $this->col('salida')   : null,
                $this->cols['cantidad'] ? $this->col('cantidad') : null,
                $this->cols['signo']    ? $this->col('signo')    : null,
                $this->cols['cu']       ? $this->col('cu')       : null,
                $this->cols['total']    ? $this->col('total')    : null,
            ]));

            $prevMovs = Movimiento::whereIn('id', $idsPrev)
                ->orderBy($this->col('fecha'))->orderBy('id')
                ->get($prevSelect);

            foreach ($prevMovs as $m) {
                [$tipo, $c, $t, $cu] = $this->extractMovimiento($m);

                if ($tipo === 'ENTRADA') {
                    $saldoCant += $c; $saldoVal += $t;
                } else {
                    $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;
                    $saldoCant -= $c; $saldoVal -= $c * $cpu;
                    if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
                }
            }
        }

        $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;

        // Transformar ítems de la página
        $fechaCol = $this->col('fecha');
        $bodCol   = $this->cols['bodega'] ? $this->col('bodega') : null;

        $items = collect($paginator->items())->map(function ($m) use (&$saldoCant, &$saldoVal, &$cpu, $fechaCol, $bodCol) {
            [$tipo, $c, $t, $cu] = $this->extractMovimiento($m);

            $entrada = null; $salida = null;
            if ($tipo === 'ENTRADA') {
                $entrada   = $c;
                $saldoCant += $c;
                $saldoVal  += $t;
            } else {
                $salida      = $c;
                $valorSalida = $c * $cpu;
                $saldoCant  -= $c;
                $saldoVal   -= $valorSalida;
                if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
            }

            $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;

            $bodegaNombre = $bodCol
                ? optional($this->bodegas->firstWhere('id', $m->{$bodCol}))->nombre
                : null;

            $doc = trim(
                ($this->cols['doc_tipo'] && $m->{$this->col('doc_tipo')} ? $m->{$this->col('doc_tipo')} : '') .
                ' ' .
                ($this->cols['doc_id']   && $m->{$this->col('doc_id')}   ? '#'.$m->{$this->col('doc_id')} : '')
            );
            if ($this->cols['ref'] && !empty($m->{$this->col('ref')})) {
                $doc .= ' (' . $m->{$this->col('ref')} . ')';
            }
            $doc = trim($doc) ?: '—';

            $fechaVal = $m->{$fechaCol};
            $fechaStr = $fechaVal instanceof \Illuminate\Support\Carbon || $fechaVal instanceof Carbon
                ? $fechaVal->format('Y-m-d H:i')
                : (string) $fechaVal;

            return [
                'id'         => $m->id,
                'fecha'      => $fechaStr,
                'bodega'     => $bodegaNombre,
                'tipo'       => $tipo,               // ENTRADA | SALIDA
                'doc'        => $doc,
                'entrada'    => $entrada,
                'salida'     => $salida,
                'costo_unit' => $tipo === 'SALIDA' ? $cpu : ($cu ?: null),
                'saldo_cant' => round($saldoCant, 6),
                'saldo_val'  => round($saldoVal, 2),
                'saldo_cpu'  => $saldoCant > 0 ? round($saldoVal / max($saldoCant, 1e-9), 6) : null,
            ];
        });

        // Saldos finales en última página
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
     * Extrae tipo, cantidad positiva, total y costo unitario de un registro según las columnas disponibles.
     * Devuelve: [string $tipo, float $cantidadPos, float $total, float $costoUnit]
     */
    private function extractMovimiento($m): array
    {
        $entrada  = $this->cols['entrada']  ? (float) ($m->{$this->col('entrada')}  ?? 0) : null;
        $salida   = $this->cols['salida']   ? (float) ($m->{$this->col('salida')}   ?? 0) : null;
        $cantidad = $this->cols['cantidad'] ? (float) ($m->{$this->col('cantidad')} ?? 0) : null;
        $signo    = $this->cols['signo']    ? (float) ($m->{$this->col('signo')}    ?? 0) : null;

        if (!is_null($entrada) || !is_null($salida)) {
            $entrada = max(0.0, (float) $entrada);
            $salida  = max(0.0, (float) $salida);

            if ($entrada > 0 && $salida == 0) { $tipo = 'ENTRADA'; $c = $entrada; }
            elseif ($salida > 0 && $entrada == 0) { $tipo = 'SALIDA'; $c = $salida; }
            else { // si ambos, prioriza el mayor
                if ($entrada >= $salida) { $tipo = 'ENTRADA'; $c = $entrada; }
                else { $tipo = 'SALIDA'; $c = $salida; }
            }
        }
        elseif (!is_null($cantidad) && !is_null($signo)) {
            $tipo = ((int) $signo >= 0) ? 'ENTRADA' : 'SALIDA';
            $c    = abs($cantidad);
        }
        elseif (!is_null($cantidad)) {
            $tipo = ($cantidad >= 0) ? 'ENTRADA' : 'SALIDA';
            $c    = abs($cantidad);
        }
        else {
            $tipo = 'ENTRADA'; $c = 0.0;
        }

        $cu = $this->cols['cu']    ? (float) ($m->{$this->col('cu')}    ?? 0) : 0.0;
        $t  = $this->cols['total'] ? (float) ($m->{$this->col('total')} ?? ($c * $cu)) : ($c * $cu);

        return [$tipo, $c, $t, $cu];
    }
}
