<?php

namespace App\Livewire\Productos;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

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
        $filas = $this->kardexEnRangoPaginado(); // ← ya retorna LengthAwarePaginator
    } else {
        // ← devolver SIEMPRE un paginador vacío
        $filas = new LengthAwarePaginator(
            collect(),       // items
            0,               // total
            $this->perPage,  // per page
            1,               // current page
            [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    return view('livewire.productos.kardex-producto', [
        'filas' => $filas,  // siempre paginator
    ]);
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

        $movs = $q->get(['tipo','cantidad','total','costo_unitario']);

        $cant = 0.0;
        $val  = 0.0;

        foreach ($movs as $m) {
            $c = (float) $m->cantidad;
            $t = (float) ($m->total ?? ($c * (float)($m->costo_unitario ?? 0)));

            if ($m->tipo === 'ENTRADA') {
                $cant += $c; $val += $t;
            } elseif ($m->tipo === 'SALIDA') {
                $cant -= $c; $val -= abs($t);
            } else { // AJUSTE
                $cant += $c; $val += $t;
            }
        }

        if (abs($cant) < 1e-9) { $cant = 0.0; $val = 0.0; }

        $this->saldoInicialCant = round($cant, 6);
        $this->saldoInicialVal  = round($val, 6);
    }

  private function kardexEnRangoPaginado()
{
    $desde = $this->desde ? \Carbon\Carbon::parse($this->desde)->startOfDay() : null;
    $hasta = $this->hasta ? \Carbon\Carbon::parse($this->hasta)->endOfDay()   : null;

    $q = \App\Models\Movimiento\Movimiento::query()
        ->where('producto_id', $this->producto_id);

    if ($this->bodega_id) $q->where('bodega_id', $this->bodega_id);
    if ($desde) $q->where('fecha', '>=', $desde);
    if ($hasta) $q->where('fecha', '<=', $hasta);

    if ($this->buscarDoc) {
        $txt = '%' . \Illuminate\Support\Str::of($this->buscarDoc)->trim() . '%';
        $q->where(function ($x) use ($txt) {
            $x->where('doc_tipo', 'like', $txt)
              ->orWhere('doc_id', 'like', $txt)
              ->orWhere('ref', 'like', $txt);
        });
    }

    $q->orderBy('fecha')->orderBy('id');

    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
    $paginator = $q->paginate($this->perPage, [
        'id','fecha','bodega_id','tipo','cantidad','costo_unitario','total','doc_tipo','doc_id','ref'
    ]);

    // Obtenemos IDs de todo el rango (misma query y orden)
    $idsOrdenados = (clone $q)->pluck('id');
    $first = $paginator->firstItem(); // índice 1-based, no ID
    $firstModel = $paginator->items()[0] ?? null; // primer modelo de la página
    $firstIdPagina = $firstModel?->id;
    $idxInicio     = $firstIdPagina ? $idsOrdenados->search($firstIdPagina) : 0;
    $idsPrev       = $idxInicio > 0 ? $idsOrdenados->slice(0, $idxInicio)->values() : collect();

    // Saldos previos al inicio de esta página
    [$saldoCant, $saldoVal] = [$this->saldoInicialCant, $this->saldoInicialVal];
    $cpu = $saldoCant > 0 ? ($saldoVal / max($saldoCant, 1e-9)) : 0.0;

    if ($idsPrev->isNotEmpty()) {
        $prevMovs = \App\Models\Movimiento\Movimiento::whereIn('id', $idsPrev)
            ->orderBy('fecha')->orderBy('id')
            ->get(['tipo','cantidad','costo_unitario','total']);

        foreach ($prevMovs as $m) {
            $c  = (float) $m->cantidad;
            $cu = (float) ($m->costo_unitario ?? 0);
            $t  = (float) ($m->total ?? ($c * $cu));

            if ($m->tipo === 'ENTRADA') {
                $saldoCant += $c;
                $saldoVal  += $t;
            } elseif ($m->tipo === 'SALIDA') {
                $valorSalida = $c * $cpu;
                $saldoCant  -= $c;
                $saldoVal   -= $valorSalida;
                if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
            } else { // AJUSTE
                if ($c >= 0) { // entrada
                    $saldoCant += $c;
                    $saldoVal  += $t;
                } else { // salida
                    $valorSalida = abs($c) * $cpu;
                    $saldoCant  -= abs($c);
                    $saldoVal   -= $valorSalida;
                    if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
                }
            }
            $cpu = $saldoCant > 0 ? $saldoVal / $saldoCant : 0.0;
        }
    }

    // Transformamos los ítems de la página SIN getCollection()
    $items = collect($paginator->items())->map(function ($m) use (&$saldoCant, &$saldoVal, &$cpu) {
        $c  = (float) $m->cantidad;
        $cu = (float) ($m->costo_unitario ?? 0);
        $t  = (float) ($m->total ?? ($c * $cu));

        $entrada = null; $salida = null;

        if ($m->tipo === 'ENTRADA') {
            $entrada   = $c;
            $saldoCant += $c;
            $saldoVal  += $t;
        } elseif ($m->tipo === 'SALIDA') {
            $salida     = $c;
            $valorSalida= $c * $cpu;
            $saldoCant -= $c;
            $saldoVal  -= $valorSalida;
            if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
        } else { // AJUSTE
            if ($c >= 0) {
                $entrada   = $c;
                $saldoCant += $c;
                $saldoVal  += $t;
            } else {
                $salida     = abs($c);
                $valorSalida= abs($c) * $cpu;
                $saldoCant -= abs($c);
                $saldoVal  -= $valorSalida;
                if ($saldoCant < 1e-9) { $saldoCant = 0.0; $saldoVal = 0.0; }
            }
        }

        $cpu = $saldoCant > 0 ? $saldoVal / $saldoCant : 0.0;

        return [
            'id'         => $m->id,
            'fecha'      => optional($m->fecha)->format('Y-m-d H:i') ?? (string) $m->fecha,
            'bodega'     => optional($this->bodegas->firstWhere('id', $m->bodega_id))->nombre,
            'tipo'       => $m->tipo,
            'doc'        => trim(($m->doc_tipo ?? '').' #'.($m->doc_id ?? '')) . ($m->ref ? ' ('.$m->ref.')' : ''),
            'entrada'    => $entrada,
            'salida'     => $salida,
            'costo_unit' => $m->tipo === 'SALIDA' ? $cpu : ($cu ?: null),
            'saldo_cant' => round($saldoCant, 6),
            'saldo_val'  => round($saldoVal, 2),
            'saldo_cpu'  => $saldoCant > 0 ? round($saldoVal / max($saldoCant,1e-9), 6) : null,
        ];
    });

    // Actualizar saldos finales si estamos en la última página
    if ($paginator->currentPage() === $paginator->lastPage() && $items->count()) {
        $last = $items->last();
        $this->saldoFinalCant = (float) $last['saldo_cant'];
        $this->saldoFinalVal  = (float) $last['saldo_val'];
    } else {
        $this->saldoFinalCant = 0.0;
        $this->saldoFinalVal  = 0.0;
    }

    // Devolver un nuevo LengthAwarePaginator con los ítems transformados
    return new \Illuminate\Pagination\LengthAwarePaginator(
        $items,
        $paginator->total(),
        $paginator->perPage(),
        $paginator->currentPage(),
        [
            'path'  => request()->url(),
            'query' => request()->query(),
        ]
    );
}}
