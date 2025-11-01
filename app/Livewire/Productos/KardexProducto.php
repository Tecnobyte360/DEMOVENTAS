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

    /** ==== Config/override manual ==== 
     * Si sabes cómo se llama la columna del producto en 'movimientos', ponla aquí.
     * Ejemplos: 'id_producto', 'item_id', 'articulo_id', 'producto_id'
     * Déjalo en null para autodetectar.
     */
    public ?string $colProductoOverride = null;

    /** ==== Cache de columnas resueltas ==== */
    private array $cols = [];

    /** Bandera para mostrar aviso en UI si no hay columna de producto */
    public bool $productoColumnMissing = false;

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

        $this->resolveColumns();
    }

    public function updating($prop): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->saldoInicialCant = $this->saldoInicialVal = 0.0;
        $this->saldoFinalCant   = $this->saldoFinalVal   = 0.0;

        if ($this->producto_id) {
            $this->calcularSaldoInicial();
            $filas = $this->kardexEnRangoPaginado();
        } else {
            $filas = new LengthAwarePaginator(collect(), 0, $this->perPage, 1, [
                'path' => request()->url(), 'query' => request()->query()
            ]);
        }

        return view('livewire.productos.kardex-producto', compact('filas'));
    }

    /* =======================================================
     * RESOLUCIÓN DE COLUMNAS
     * ======================================================= */
    private function resolveColumns(): void
    {
        $table = (new Movimiento)->getTable();
        $colsList = collect(Schema::getColumnListing($table))->map(fn($c)=>strtolower($c));
        $has = fn(string $c) => Schema::hasColumn($table, $c);

        $pick = function(array $cands) use ($has): ?string {
            foreach ($cands as $c) if ($c && $has($c)) return $c;
            return null;
        };

        $guessByRegex = function(array $tokens) use ($colsList): ?string {
            $base = implode('|', array_map('preg_quote', $tokens));
            $pattern = "/(?:{$base}).*?(?:_?id)?$/i";
            return $colsList->first(fn($c)=>preg_match($pattern,$c)) ?: null;
        };

        // ——— Producto: prioridad override > pick > regex
        $colProducto = $this->colProductoOverride;
        if ($colProducto && !$has($colProducto)) {
            // Si el override no existe, lo invalidamos
            $colProducto = null;
        }
        if (!$colProducto) {
            $colProducto = $pick([
                'producto_id','id_producto','product_id','id_product',
                'item_id','id_item','articulo_id','id_articulo'
            ]) ?: $guessByRegex(['producto','product','articulo','item']);
        }

        $this->cols = [
            'fecha'     => $pick(['fecha','mov_fecha','fecha_movimiento','created_at']) ?: 'created_at',
            'producto'  => $colProducto ?: null,
            'bodega'    => $pick(['bodega_id','almacen_id','warehouse_id','bodega','almacen']),
            'cantidad'  => $pick(['cantidad','qty','cantidad_total','cantidad_um','cantidad_mov']),
            'entrada'   => $pick(['entrada','cantidad_entrada','qty_in','ingreso']),
            'salida'    => $pick(['salida','cantidad_salida','qty_out','egreso']),
            'signo'     => $pick(['signo','direction']),
            'total'     => $pick(['total','valor_total','monto','importe_total']),
            'cu'        => $pick(['costo_unitario','cpu','costo_promedio','costo','valor_unitario']),
            'doc_tipo'  => $pick(['doc_tipo','documento_tipo','tipo_doc']),
            'doc_id'    => $pick(['doc_id','documento_id','num_doc','numero_doc']),
            'ref'       => $pick(['ref','referencia','observacion','detalle']),
        ];

        // bandera para UI:
        $this->productoColumnMissing = empty($this->cols['producto']);
    }

    private function col(string $k, ?string $fallback = null): string
    {
        if (!array_key_exists($k, $this->cols) || empty($this->cols[$k])) {
            return $fallback ?? match ($k) {
                'fecha' => 'created_at',
                default => $k,
            };
        }
        return $this->cols[$k];
    }
    private function hasCol(string $k): bool
    {
        return array_key_exists($k, $this->cols) && !empty($this->cols[$k]);
    }

    /* =======================================================
     * CÁLCULOS
     * ======================================================= */
    private function calcularSaldoInicial(): void
    {
        // Si no tenemos columna de producto, no intentamos filtrar por él.
        if (!$this->hasCol('producto')) {
            $this->saldoInicialCant = 0.0;
            $this->saldoInicialVal  = 0.0;
            return;
        }

        $hasta = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;

        $q = Movimiento::query()
            ->where($this->col('producto'), $this->producto_id);

        if ($this->bodega_id && $this->hasCol('bodega')) {
            $q->where($this->col('bodega'), $this->bodega_id);
        }
        if ($hasta) {
            $q->where($this->col('fecha'), '<', $hasta);
        }

        $select = array_filter([
            $this->hasCol('entrada')  ? $this->col('entrada')  : null,
            $this->hasCol('salida')   ? $this->col('salida')   : null,
            $this->hasCol('cantidad') ? $this->col('cantidad') : null,
            $this->hasCol('signo')    ? $this->col('signo')    : null,
            $this->hasCol('total')    ? $this->col('total')    : null,
            $this->hasCol('cu')       ? $this->col('cu')       : null,
        ]);
        if (!$select) $select = ['id'];

        $movs = $q->get($select);

        $cant = 0.0; $val = 0.0;
        foreach ($movs as $m) {
            [$tipo, $c, $t, $cu] = $this->extractMovimiento($m);
            if ($tipo === 'ENTRADA') { $cant += $c; $val += $t; }
            else {
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
        if (!$this->hasCol('producto')) {
            // No hay columna de producto: devolvemos paginación vacía (no rompemos)
            return new LengthAwarePaginator(collect(), 0, $this->perPage, 1, [
                'path' => request()->url(), 'query' => request()->query()
            ]);
        }

        $desde = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;
        $hasta = $this->hasta ? Carbon::parse($this->hasta)->endOfDay()   : null;

        $q = Movimiento::query()
            ->where($this->col('producto'), $this->producto_id);

        if ($this->bodega_id && $this->hasCol('bodega')) $q->where($this->col('bodega'), $this->bodega_id);
        if ($desde) $q->where($this->col('fecha'), '>=', $desde);
        if ($hasta) $q->where($this->col('fecha'), '<=', $hasta);

        if ($this->buscarDoc) {
            $txt = '%' . Str::of($this->buscarDoc)->trim() . '%';
            $q->where(function ($x) use ($txt) {
                if ($this->hasCol('doc_tipo')) $x->orWhere($this->col('doc_tipo'), 'like', $txt);
                if ($this->hasCol('doc_id'))   $x->orWhere($this->col('doc_id'),   'like', $txt);
                if ($this->hasCol('ref'))      $x->orWhere($this->col('ref'),      'like', $txt);
            });
        }

        $q->orderBy($this->col('fecha'))->orderBy('id');

        $select = array_values(array_filter([
            'id',
            $this->col('fecha'),
            $this->hasCol('bodega')   ? $this->col('bodega')   : null,
            $this->hasCol('entrada')  ? $this->col('entrada')  : null,
            $this->hasCol('salida')   ? $this->col('salida')   : null,
            $this->hasCol('cantidad') ? $this->col('cantidad') : null,
            $this->hasCol('signo')    ? $this->col('signo')    : null,
            $this->hasCol('cu')       ? $this->col('cu')       : null,
            $this->hasCol('total')    ? $this->col('total')    : null,
            $this->hasCol('doc_tipo') ? $this->col('doc_tipo') : null,
            $this->hasCol('doc_id')   ? $this->col('doc_id')   : null,
            $this->hasCol('ref')      ? $this->col('ref')      : null,
        ]));
        $paginator = $q->paginate($this->perPage, $select);

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
                $this->hasCol('entrada')  ? $this->col('entrada')  : null,
                $this->hasCol('salida')   ? $this->col('salida')   : null,
                $this->hasCol('cantidad') ? $this->col('cantidad') : null,
                $this->hasCol('signo')    ? $this->col('signo')    : null,
                $this->hasCol('cu')       ? $this->col('cu')       : null,
                $this->hasCol('total')    ? $this->col('total')    : null,
            ]));

            $prevMovs = Movimiento::whereIn('id', $idsPrev)
                ->orderBy($this->col('fecha'))->orderBy('id')
                ->get($prevSelect);

            foreach ($prevMovs as $m) {
                [$tipo, $c, $t, $cu] = $this->extractMovimiento($m);
                if ($tipo === 'ENTRADA') { $saldoCant += $c; $saldoVal += $t; }
                else {
                    $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;
                    $saldoCant -= $c; $saldoVal -= $c * $cpu;
                    if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
                }
            }
        }

        $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;

        $fechaCol = $this->col('fecha');
        $bodCol   = $this->hasCol('bodega') ? $this->col('bodega') : null;

        $items = collect($paginator->items())->map(function ($m) use (&$saldoCant, &$saldoVal, &$cpu, $fechaCol, $bodCol) {
            [$tipo, $c, $t, $cu] = $this->extractMovimiento($m);

            $entrada = null; $salida = null;
            if ($tipo === 'ENTRADA') { $entrada = $c; $saldoCant += $c; $saldoVal += $t; }
            else {
                $salida = $c; $valorSalida = $c * $cpu; $saldoCant -= $c; $saldoVal -= $valorSalida;
                if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
            }

            $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;

            $bodegaNombre = $bodCol
                ? optional($this->bodegas->firstWhere('id', $m->{$bodCol}))->nombre
                : null;

            $doc = trim(
                ($this->hasCol('doc_tipo') && $m->{$this->col('doc_tipo')} ? $m->{$this->col('doc_tipo')} : '') . ' ' .
                ($this->hasCol('doc_id')   && $m->{$this->col('doc_id')}   ? '#'.$m->{$this->col('doc_id')} : '')
            );
            if ($this->hasCol('ref') && !empty($m->{$this->col('ref')})) {
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
                'tipo'       => $tipo,
                'doc'        => $doc,
                'entrada'    => $entrada,
                'salida'     => $salida,
                'costo_unit' => $tipo === 'SALIDA' ? $cpu : ($cu ?: null),
                'saldo_cant' => round($saldoCant, 6),
                'saldo_val'  => round($saldoVal, 2),
                'saldo_cpu'  => $saldoCant > 0 ? round($saldoVal / max($saldoCant, 1e-9), 6) : null,
            ];
        });

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

    private function extractMovimiento($m): array
    {
        $entrada = $this->hasCol('entrada')  ? (float) ($m->{$this->col('entrada')} ?? 0)  : null;
        $salida  = $this->hasCol('salida')   ? (float) ($m->{$this->col('salida')}  ?? 0)  : null;
        $cantidad= $this->hasCol('cantidad') ? (float) ($m->{$this->col('cantidad')} ?? 0): null;
        $signo   = $this->hasCol('signo')    ? (float) ($m->{$this->col('signo')}    ?? 0): null;

        if (!is_null($entrada) || !is_null($salida)) {
            $entrada = max(0.0, (float) $entrada);
            $salida  = max(0.0, (float) $salida);
            if ($entrada > 0 && $salida == 0) { $tipo = 'ENTRADA'; $c = $entrada; }
            elseif ($salida > 0 && $entrada == 0) { $tipo = 'SALIDA'; $c = $salida; }
            else { ($entrada >= $salida) ? $tipo='ENTRADA' : $tipo='SALIDA'; $c = max($entrada,$salida); }
        }
        elseif (!is_null($cantidad) && !is_null($signo)) {
            $tipo = ((int)$signo >= 0) ? 'ENTRADA' : 'SALIDA';
            $c    = abs($cantidad);
        }
        elseif (!is_null($cantidad)) {
            $tipo = ($cantidad >= 0) ? 'ENTRADA' : 'SALIDA';
            $c    = abs($cantidad);
        }
        else { $tipo = 'ENTRADA'; $c = 0.0; }

        $cu = $this->hasCol('cu')    ? (float) ($m->{$this->col('cu')}    ?? 0) : 0.0;
        $t  = $this->hasCol('total') ? (float) ($m->{$this->col('total')} ?? ($c * $cu)) : ($c * $cu);

        return [$tipo, $c, $t, $cu];
    }
}
