<?php

namespace App\Livewire\Facturas\FacturaCompra;

use App\Models\Factura\Factura;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Serie\Serie;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListaFacturasCompra extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros
    public string  $search = '';
    public int     $perPage = 10;
    public ?int    $proveedor_id = null;
    public ?int    $serie_id     = null;
    public ?string $estado       = null;   // '', 'borrador', 'emitida', 'cerrado', 'anulada'
    public ?string $desde        = null;   // YYYY-MM-DD
    public ?string $hasta        = null;   // YYYY-MM-DD

    // Orden
    public string $sortField = 'fecha';    // 'fecha' | 'numero' | 'total'
    public string $sortDir   = 'desc';     // 'asc' | 'desc'

    #[On('refrescar-lista-facturas')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    /** Reset de página al cambiar cualquier filtro relevante */
    public function updating($name, $value): void
    {
        if (in_array($name, ['search','proveedor_id','serie_id','estado','desde','hasta','perPage'])) {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if (!in_array($field, ['fecha','numero','total'])) {
            return;
        }
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir   = 'asc';
        }
        $this->resetPage();
    }

    public function render()
    {
        // ID del tipo de documento 'facturacompra'
        $idTipo = TipoDocumento::where('codigo', 'facturacompra')->value('id');

        $q = Factura::query()
            ->with(['socioNegocio','serie'])
            // solo facturas de compra (por tipo en la serie)
            ->whereHas('serie', function (Builder $s) use ($idTipo) {
                $s->where('tipo_documento_id', $idTipo);
            });

        // Búsqueda general
        if (trim($this->search) !== '') {
            $s = '%'.trim($this->search).'%';
            $q->where(function ($qq) use ($s) {
                $qq->where('numero',  'like', $s)
                   ->orWhere('prefijo','like', $s)
                   ->orWhere('estado', 'like', $s)
                   ->orWhereHas('socioNegocio', fn ($c) =>
                        $c->where('razon_social','like',$s)
                          ->orWhere('nit','like',$s)
                   );
            });
        }

        // Filtros específicos
        if ($this->estado)            $q->where('estado', $this->estado);
        if ($this->serie_id)          $q->where('serie_id', $this->serie_id);
        if ($this->proveedor_id)      $q->where('socio_negocio_id', $this->proveedor_id);
        if ($this->desde)             $q->whereDate('fecha', '>=', $this->desde);
        if ($this->hasta)             $q->whereDate('fecha', '<=', $this->hasta);

        // Orden y paginación
        $items = $q->orderBy($this->sortField, $this->sortDir)
                   ->paginate($this->perPage);

        // Catálogos para los selects
        $proveedores = SocioNegocio::proveedores()
            ->orderBy('razon_social')->get(['id','razon_social','nit']);

        $series = Serie::query()
            ->with('tipo:id,codigo')
            ->where('tipo_documento_id', $idTipo)
            ->orderBy('nombre')
            ->get(['id','nombre','prefijo','tipo_documento_id']);

        return view('livewire.facturas.lista-facturas', [
            'items'       => $items,
            'proveedores' => $proveedores,
            'series'      => $series,
        ]);
    }
}
