<?php

namespace App\Livewire\Productos;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Productos\Producto;
use App\Models\Bodega;
use App\Models\Movimiento\KardexMovimiento;
use App\Models\Movimiento\ProductoCostoMovimiento;
use App\Models\TiposDocumento\TipoDocumento;

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
    
    /** ==== Opción para ver costos históricos ==== */
    public bool $verCostosHistoricos = false;

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
        'verCostosHistoricos' => ['except' => false],
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
     * CÁLCULOS
     * ======================================================= */

    /** Suma entradas y salidas (a costo promedio móvil) ANTES del rango. */
    private function calcularSaldoInicial(): void
    {
        $hasta = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;

        $q = KardexMovimiento::query()
            ->where('producto_id', $this->producto_id);

        if ($this->bodega_id) $q->where('bodega_id', $this->bodega_id);
        if ($hasta)           $q->where('fecha', '<', $hasta);

        $movs = $q->orderBy('fecha')->orderBy('id')
            ->get(['entrada','salida','cantidad','signo','costo_unitario','total']);

        $cant = 0.0; $val = 0.0;
        foreach ($movs as $m) {
            $entrada = (float)($m->entrada ?? 0);
            $salida  = (float)($m->salida  ?? 0);
            $tipo    = $entrada > 0 ? 'ENTRADA' : ($salida > 0 ? 'SALIDA' : ((int)$m->signo >= 0 ? 'ENTRADA' : 'SALIDA'));
            $c       = $tipo === 'ENTRADA' ? max($entrada, abs((float)$m->cantidad)) : max($salida,  abs((float)$m->cantidad));

            if ($tipo === 'ENTRADA') {
                $cu  = (float)($m->costo_unitario ?? 0);
                $val += $c * $cu;
                $cant += $c;
            } else {
                $cpu = $cant > 0 ? ($val / max($cant, 1e-9)) : 0.0;
                $cant -= $c;
                $val  -= $c * $cpu;
                if ($cant < 1e-9) { $cant = 0.0; $val = 0.0; }
            }
        }

        $this->saldoInicialCant = round($cant, 6);
        $this->saldoInicialVal  = round($val, 2);
    }

    /** Lista movimientos del rango y calcula saldos corridos por página. */
    private function kardexEnRangoPaginado(): LengthAwarePaginator
    {
        $desde = $this->desde ? Carbon::parse($this->desde)->startOfDay() : null;
        $hasta = $this->hasta ? Carbon::parse($this->hasta)->endOfDay()   : null;

        $q = KardexMovimiento::query()
            ->with('tipoDocumento:id,codigo,nombre')
            ->where('producto_id', $this->producto_id);

        if ($this->bodega_id) $q->where('bodega_id', $this->bodega_id);
        if ($desde)           $q->where('fecha', '>=', $desde);
        if ($hasta)           $q->where('fecha', '<=', $hasta);

        if (trim($this->buscarDoc) !== '') {
            $txt = '%' . Str::of($this->buscarDoc)->trim() . '%';
            $q->where(function ($x) use ($txt) {
                $x->orWhere('doc_id', 'like', $txt)
                  ->orWhere('ref', 'like', $txt)
                  ->orWhereHas('tipoDocumento', fn($q)=>$q->where('nombre','like',$txt)->orWhere('codigo','like',$txt));
            });
        }

        $q->orderBy('fecha')->orderBy('id');

        $paginator = $q->paginate(
            $this->perPage,
            ['id','fecha','producto_id','bodega_id','tipo_documento_id','entrada','salida','cantidad','signo','costo_unitario','total','doc_id','ref']
        );

        // Para saldos corridos correctos por página:
        $idsOrdenados  = (clone $q)->pluck('id');
        $firstModel    = $paginator->items()[0] ?? null;
        $firstIdPagina = $firstModel->id ?? null;
        $idxInicio     = $firstIdPagina ? $idsOrdenados->search($firstIdPagina) : 0;
        $idsPrev       = $idxInicio > 0 ? $idsOrdenados->slice(0, $idxInicio)->values() : collect();

        [$saldoCant, $saldoVal] = [$this->saldoInicialCant, $this->saldoInicialVal];

        if ($idsPrev->isNotEmpty()) {
            $prevMovs = KardexMovimiento::whereIn('id', $idsPrev)
                ->orderBy('fecha')->orderBy('id')
                ->get(['id','entrada','salida','cantidad','signo','costo_unitario','total']);

            foreach ($prevMovs as $m) {
                $entrada = (float)($m->entrada ?? 0);
                $salida  = (float)($m->salida  ?? 0);
                $tipo    = $entrada > 0 ? 'ENTRADA' : ($salida > 0 ? 'SALIDA' : ((int)$m->signo >= 0 ? 'ENTRADA' : 'SALIDA'));
                $c       = $tipo === 'ENTRADA' ? max($entrada, abs((float)$m->cantidad)) : max($salida,  abs((float)$m->cantidad));

                if ($tipo === 'ENTRADA') {
                    $cu  = (float)($m->costo_unitario ?? 0);
                    $saldoVal += $c * $cu;
                    $saldoCant += $c;
                } else {
                    $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;
                    $saldoCant -= $c;
                    $saldoVal  -= $c * $cpu;
                    if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
                }
            }
        }

        // Obtener datos de costo histórico si está activado
        $costosHistoricos = collect();
        if ($this->verCostosHistoricos && $paginator->items()) {
            $kardexIds = collect($paginator->items())->pluck('id');
            
            // Buscar en ProductoCostoMovimiento por los mismos criterios
            $costosQuery = ProductoCostoMovimiento::query()
                ->where('producto_id', $this->producto_id);
            
            if ($this->bodega_id) $costosQuery->where('bodega_id', $this->bodega_id);
            if ($desde)           $costosQuery->where('fecha', '>=', $desde);
            if ($hasta)           $costosQuery->where('fecha', '<=', $hasta);
            
            // Agrupar por doc_id + tipo_documento_id para hacer match
            $costosHistoricos = $costosQuery
                ->orderBy('fecha')
                ->get()
                ->keyBy(function($item) {
                    return $item->tipo_documento_id . '_' . $item->doc_id;
                });
        }

        // Construye items de la página con saldos corridos
        $items = collect($paginator->items())->map(function (KardexMovimiento $m) use (&$saldoCant, &$saldoVal, $costosHistoricos) {
            $entrada = (float)($m->entrada ?? 0);
            $salida  = (float)($m->salida  ?? 0);
            $tipo    = $entrada > 0 ? 'ENTRADA' : ($salida > 0 ? 'SALIDA' : ((int)$m->signo >= 0 ? 'ENTRADA' : 'SALIDA'));
            $c       = $tipo === 'ENTRADA' ? max($entrada, abs((float)$m->cantidad)) : max($salida,  abs((float)$m->cantidad));

            // Calcular costo promedio antes de aplicar el movimiento
            $cpu = null;
            if ($tipo === 'ENTRADA') {
                $cu = (float)($m->costo_unitario ?? 0);
                $saldoVal  += $c * $cu;
                $saldoCant += $c;
            } else {
                $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;
                $saldoCant -= $c;
                $saldoVal  -= $c * $cpu;
                if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
            }

            $cpuSaldo = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : null;

            $bodegaNombre = optional($this->bodegas->firstWhere('id', $m->bodega_id))->nombre;
            $docText = trim(
                ($m->tipoDocumento?->codigo ?? $m->tipoDocumento?->nombre ?? '') . ' ' .
                ($m->doc_id ? '#'.$m->doc_id : '')
            );
            if ($m->ref) $docText .= ' ('.$m->ref.')';
            $docText = $docText ?: '—';

            // Buscar datos de costo histórico
            $costoHistorico = null;
            if ($this->verCostosHistoricos && $costosHistoricos->isNotEmpty()) {
                $key = $m->tipo_documento_id . '_' . $m->doc_id;
                $costoHistorico = $costosHistoricos->get($key);
            }

            return [
                'id'         => $m->id,
                'fecha'      => ($m->fecha instanceof Carbon ? $m->fecha->format('Y-m-d H:i') : (string)$m->fecha),
                'bodega'     => $bodegaNombre ?: '—',
                'doc'        => $docText,
                'tipo'       => $tipo,
                'entrada'    => $tipo === 'ENTRADA' ? $c : null,
                'salida'     => $tipo === 'SALIDA'  ? $c : null,
                'costo_unit' => $tipo === 'ENTRADA'
                                ? (float)($m->costo_unitario ?? 0)
                                : ($cpu ?? 0.0),
                'saldo_cant' => round($saldoCant, 6),
                'saldo_val'  => round($saldoVal, 2),
                'saldo_cpu'  => $cpuSaldo !== null ? round($cpuSaldo, 6) : null,
                // Datos adicionales de costo histórico
                'costo_historico' => $costoHistorico ? [
                    'metodo_costeo' => $costoHistorico->metodo_costeo,
                    'costo_prom_anterior' => $costoHistorico->costo_prom_anterior,
                    'costo_prom_nuevo' => $costoHistorico->costo_prom_nuevo,
                    'ultimo_costo_anterior' => $costoHistorico->ultimo_costo_anterior,
                    'ultimo_costo_nuevo' => $costoHistorico->ultimo_costo_nuevo,
                    'tipo_evento' => $costoHistorico->tipo_evento,
                    'valor_mov' => $costoHistorico->valor_mov,
                    'costo_unit_mov' => $costoHistorico->costo_unit_mov,
                ] : null,
            ];
        });

        // Saldos finales (solo si es la última página)
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
}