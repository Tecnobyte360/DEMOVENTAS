<?php

namespace App\Livewire\Inventario;

use App\Models\Bodega;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InventarioPorBodega extends Component
{
    use WithPagination;

    public string $q = '';
    public ?int $bodegaId = null;
    public string $stockFiltro = 'todos'; // todos | con | sin | bajo
    public bool $soloActivas = true;

    protected $queryString = [
        'q'           => ['except' => ''],
        'bodegaId'    => ['except' => null],
        'stockFiltro' => ['except' => 'todos'],
    ];

    public function updating($prop): void
    {
        if (in_array($prop, ['q', 'bodegaId', 'stockFiltro', 'soloActivas'], true)) {
            $this->resetPage();
        }
    }

    public function limpiar(): void
    {
        $this->reset(['q', 'bodegaId', 'stockFiltro']);
        $this->soloActivas = true;
        $this->resetPage();
    }

    /** Resumen por bodega: unidades, valor $, productos distintos. */
    public function getResumenPorBodegaProperty()
    {
        $rows = DB::table('producto_bodega as pb')
            ->join('bodegas as b', 'b.id', '=', 'pb.bodega_id')
            ->when($this->soloActivas, fn ($q) => $q->where('b.activo', 1))
            ->select(
                'b.id',
                'b.nombre',
                'b.ubicacion',
                'b.activo',
                DB::raw('COUNT(DISTINCT pb.producto_id) as productos'),
                DB::raw('COALESCE(SUM(pb.stock), 0) as unidades'),
                DB::raw('COALESCE(SUM(pb.stock * pb.costo_promedio), 0) as valor'),
            )
            ->groupBy('b.id', 'b.nombre', 'b.ubicacion', 'b.activo')
            ->orderBy('b.nombre')
            ->get();

        return $rows;
    }

    public function getTotalesGlobalesProperty(): array
    {
        $r = $this->resumenPorBodega;
        return [
            'bodegas'   => $r->count(),
            'productos' => (int) $r->sum('productos'),
            'unidades'  => (float) $r->sum('unidades'),
            'valor'     => (float) $r->sum('valor'),
        ];
    }

    public function render()
    {
        $detalle = DB::table('producto_bodega as pb')
            ->join('productos as p', 'p.id', '=', 'pb.producto_id')
            ->join('bodegas as b', 'b.id', '=', 'pb.bodega_id')
            ->when($this->soloActivas, fn ($q) => $q->where('b.activo', 1))
            ->when($this->bodegaId, fn ($q) => $q->where('pb.bodega_id', $this->bodegaId))
            ->when($this->q !== '', function ($q) {
                $term = '%' . $this->q . '%';
                $q->where(function ($w) use ($term) {
                    $w->where('p.nombre', 'like', $term)
                      ->orWhere('p.descripcion', 'like', $term)
                      ->orWhere('p.id', 'like', $term);
                });
            })
            ->when($this->stockFiltro === 'con', fn ($q) => $q->where('pb.stock', '>', 0))
            ->when($this->stockFiltro === 'sin', fn ($q) => $q->where('pb.stock', '<=', 0))
            ->when($this->stockFiltro === 'bajo', fn ($q) => $q
                ->whereColumn('pb.stock', '<=', 'pb.stock_minimo')
                ->where('pb.stock_minimo', '>', 0))
            ->select(
                'pb.id as pb_id',
                'p.id as producto_id',
                'p.nombre as producto',
                'b.id as bodega_id',
                'b.nombre as bodega',
                'pb.stock',
                'pb.stock_minimo',
                'pb.stock_maximo',
                'pb.costo_promedio',
                'pb.ultimo_costo',
                DB::raw('(pb.stock * pb.costo_promedio) as valor'),
            )
            ->orderBy('b.nombre')
            ->orderBy('p.nombre')
            ->paginate(25);

        return view('livewire.inventario.inventario-por-bodega', [
            'bodegas'           => Bodega::orderBy('nombre')->get(),
            'detalle'           => $detalle,
            'resumenPorBodega'  => $this->resumenPorBodega,
            'totales'           => $this->totalesGlobales,
        ]);
    }
}
