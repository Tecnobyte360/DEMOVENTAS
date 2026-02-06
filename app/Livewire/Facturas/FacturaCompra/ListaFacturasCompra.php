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

    // =========================
    // FILTROS
    // =========================
    public string $search = '';
    public int    $perPage = 10;
    public ?int   $proveedor_id = null;
    public ?int   $serie_id     = null;
    public ?string $estado      = null;
    public ?string $desde       = null;
    public ?string $hasta       = null;

    // =========================
    // ORDEN
    // =========================
    public string $sortField = 'fecha';
    public string $sortDir   = 'desc';

    // =========================
    // MODAL PREVIEW
    // =========================
    public bool $showPreview = false;
    public ?int $previewId = null;

    // =========================
    // MODAL EDITAR (para evitar error "abrir")
    // =========================
    public bool $showEdit = false;
    public ?int $editId = null;

    // Campos de edición (ajusta si necesitas más)
    public ?string $edit_fecha = null;
    public ?string $edit_estado = null;
    public ?int $edit_serie_id = null;
    public ?int $edit_proveedor_id = null;

    #[On('refrescar-lista-facturas')]
    public function refrescar(): void
    {
        $this->resetPage();
    }

    public function updating($name, $value): void
    {
        if (in_array($name, ['search','proveedor_id','serie_id','estado','desde','hasta','perPage'], true)) {
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

    // =========================
    // PREVIEW
    // =========================
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

    // =========================
    // EDITAR (método que te está faltando)
    // =========================
    public function abrir(int $id): void
    {
        $factura = Factura::find($id);

        if (!$factura) {
            $this->dispatch('notificacion', [
                'tipo' => 'error',
                'mensaje' => 'Factura no encontrada'
            ]);
            return;
        }

        $this->editId = $id;

        // Cargar campos en el formulario de edición
        $this->edit_fecha        = $factura->fecha ? \Illuminate\Support\Carbon::parse($factura->fecha)->format('Y-m-d') : null;
        $this->edit_estado       = $factura->estado;
        $this->edit_serie_id     = $factura->serie_id;
        $this->edit_proveedor_id = $factura->socio_negocio_id; // <- ajusta si tu FK se llama distinto

        $this->showEdit = true;
    }

    public function cerrarEditar(): void
    {
        $this->showEdit = false;
        $this->editId = null;

        $this->reset([
            'edit_fecha',
            'edit_estado',
            'edit_serie_id',
            'edit_proveedor_id',
        ]);

        $this->resetValidation();
    }

    public function guardarEdicion(): void
    {
        if (!$this->editId) {
            $this->dispatch('notificacion', [
                'tipo' => 'error',
                'mensaje' => 'No hay factura seleccionada para editar'
            ]);
            return;
        }

        $this->validate([
            'edit_fecha'        => ['required', 'date'],
            'edit_estado'       => ['required', 'string'],
            'edit_serie_id'     => ['nullable', 'integer'],
            'edit_proveedor_id' => ['nullable', 'integer'],
        ], [], [
            'edit_fecha' => 'fecha',
            'edit_estado' => 'estado',
            'edit_serie_id' => 'serie',
            'edit_proveedor_id' => 'proveedor',
        ]);

        $factura = Factura::find($this->editId);

        if (!$factura) {
            $this->dispatch('notificacion', [
                'tipo' => 'error',
                'mensaje' => 'Factura no encontrada'
            ]);
            $this->cerrarEditar();
            return;
        }

        $factura->update([
            'fecha'            => $this->edit_fecha,
            'estado'           => $this->edit_estado,
            'serie_id'         => $this->edit_serie_id,
            'socio_negocio_id' => $this->edit_proveedor_id, // <- ajusta si tu FK se llama distinto
        ]);

        $this->dispatch('notificacion', [
            'tipo' => 'success',
            'mensaje' => 'Factura actualizada correctamente'
        ]);

        $this->cerrarEditar();
        $this->dispatch('refrescar-lista-facturas');
    }

    public function render()
    {
        // === Obtener el ID del tipo de documento 'facturacompra' ===
        $idTipo = \App\Models\TiposDocumento\TipoDocumento::where('codigo', 'facturacompra')->value('id');

        $q = Factura::query()
            ->with(['socioNegocio','serie'])
            ->whereHas('serie', function (Builder $s) use ($idTipo) {
                $s->where('tipo_documento_id', $idTipo);
            });

        // === Filtro de búsqueda ===
        if (trim($this->search) !== '') {
            $s = '%' . trim($this->search) . '%';
            $q->where(function ($qq) use ($s) {
                $qq->where('numero', 'like', $s)
                    ->orWhere('prefijo', 'like', $s)
                    ->orWhere('estado', 'like', $s)
                    ->orWhereHas('socioNegocio', fn ($c) =>
                        $c->where('razon_social', 'like', $s)
                          ->orWhere('nit', 'like', $s)
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
