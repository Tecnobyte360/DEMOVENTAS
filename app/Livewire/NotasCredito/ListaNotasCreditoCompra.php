<?php

namespace App\Livewire\NotasCredito;

use App\Models\NotaCredito;
use App\Models\Serie\Serie;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\TiposDocumento\TipoDocumento;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ListaNotasCreditoCompra extends Component
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

    // (opcional) preview / modal
    public bool $showPreview = false;
    public ?int $previewId   = null;

    #[On('refrescar-lista-notas-credito-compra')]
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
            $this->sortDir   = 'asc';
        }
        $this->resetPage();
    }

    // opcional, si luego haces un modal con preview
    public function preview(int $id): void
    {
        $nc = NotaCredito::find($id);

        if (!$nc) {
            $this->dispatch('notificacion', [
                'tipo'    => 'error',
                'mensaje' => 'Nota crédito no encontrada',
            ]);
            return;
        }

        $this->previewId   = $id;
        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewId   = null;
    }

    public function render()
    {
        // Tipo de documento: NOTACREDITOCOMPRA
        $idTipo = TipoDocumento::whereRaw('LOWER(codigo) = ?', ['notacreditocompra'])->value('id');

        $q = NotaCredito::query()
            ->with(['socioNegocio', 'serie'])
            ->when($idTipo, function (Builder $q) use ($idTipo) {
                $q->whereHas('serie', function (Builder $s) use ($idTipo) {
                    $s->where('tipo_documento_id', $idTipo);
                });
            });

        // === Búsqueda libre ===
        if (trim($this->search) !== '') {
            $s = '%'.trim($this->search).'%';
            $q->where(function ($qq) use ($s) {
                $qq->where('numero',  'like', $s)
                   ->orWhere('prefijo','like', $s)
                   ->orWhere('estado', 'like', $s)
                   ->orWhere('motivo', 'like', $s)
                   ->orWhereHas('socioNegocio', function ($c) use ($s) {
                        $c->where('razon_social','like',$s)
                          ->orWhere('nit','like',$s);
                   });
            });
        }

        // === Filtros ===
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

        // Orden + paginación
        $items = $q->orderBy($this->sortField, $this->sortDir)
                   ->paginate($this->perPage);

        // combos
        $proveedores = SocioNegocio::orderBy('razon_social')->get();
        $series      = Serie::query()
            ->when($idTipo, fn($qq) => $qq->where('tipo_documento_id', $idTipo))
            ->orderBy('nombre')
            ->get();

        return view('livewire.notas-credito.lista-notas-credito-compra', [
            'items'       => $items,
            'proveedores' => $proveedores,
            'series'      => $series,
        ]);
    }
}
