<?php

namespace App\Livewire\Facturas\FacturaCompra;

use App\Models\Factura\Factura;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListaFacturasCompra extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtros
    public string $search = '';
    public int    $perPage = 10;
    public ?int   $proveedor_id = null;
    public ?int   $serie_id     = null;
    public ?string $estado      = null;
    public ?string $desde       = null;
    public ?string $hasta       = null;

    // Orden
    public string $sortField = 'fecha';
    public string $sortDir   = 'desc';

    // ✅ AGREGAR ESTAS PROPIEDADES PARA EL MODAL DE PREVIEW
    public bool $showPreview = false;
    public ?int $previewId = null;

    #[On('refrescar-lista-facturas')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search','proveedor_id','serie_id','estado','desde','hasta','perPage'])) {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    // ✅ AGREGAR ESTOS MÉTODOS PARA EL MODAL DE PREVIEW
    public function preview(int $id): void
    {
        $factura = Factura::find($id);
        
        if (!$factura) {
            $this->dispatch('notificacion', [
                'tipo' => 'error',
                'mensaje' => 'Factura no encontrada'
            ]);
            return;
        }

        $this->previewId = $id;
        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewId = null;
    }

    public function render()
    {
        // === Obtener el ID del tipo de documento 'facturacompra' ===
        $idTipo = \App\Models\TiposDocumento\TipoDocumento::where('codigo', 'facturacompra')->value('id');

        $q = Factura::query()
            ->with(['socioNegocio','serie'])
            // Solo facturas de compra (filtra por tipo_documento_id)
            ->whereHas('serie', function (Builder $s) use ($idTipo) {
                $s->where('tipo_documento_id', $idTipo);
            });

        // === Filtro de búsqueda ===
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

        // === Otros filtros ===
        if ($this->estado && $this->estado !== 'todas') {
            $q->where('estado', $this->estado);
        }
        if ($this->serie_id) {
            $q->where('serie_id', $this->serie_id);
        }
        if ($this->proveedor_id) {
            $q->where('socio_negocio_id', $this->proveedor_id);
        }
        if ($this->desde) {
            $q->whereDate('fecha', '>=', $this->desde);
        }
        if ($this->hasta) {
            $q->whereDate('fecha', '<=', $this->hasta);
        }

        // === Orden y paginación ===
        $items = $q->orderBy($this->sortField, $this->sortDir)
                   ->paginate($this->perPage);

        return view('livewire.facturas.lista-facturas', compact('items'));
    }
}
