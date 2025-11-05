<?php

namespace App\Livewire\Facturas;

use App\Livewire\MapaRelacion\MapaRelaciones;
use App\Models\Factura\Factura;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class ListaFacturas extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    // Filtros visibles
    public string $q = '';
    public string $estado = 'todas';
    public int $perPage = 12;

    // Filtros opcionales
    public ?int $serie_id = null;
    public ?int $proveedor_id = null;
    public ?string $desde = null;
    public ?string $hasta = null;

    // Modal de previsualización
    public bool $showPreview = false;
    public ?int $previewId = null;

    protected $queryString = [
        'q' => ['except' => ''],
        'estado' => ['except' => 'todas'],
        'page' => ['except' => 1],
        'perPage' => ['except' => 12]
    ];

    /** Reset de página al cambiar filtros */
    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    /** Acciones */
    public function abrir(int $id): void
    {
        $this->dispatch('abrir-factura', id: $id)
             ->to(\App\Livewire\Facturas\FacturaForm::class);
    }

    public function enviarPorCorreo(int $id): void
    {
        $this->dispatch('abrir-enviar-factura', id: $id)
             ->to(\App\Livewire\Facturas\EnviarFactura::class);
    }

    public function registrarPago(int $id): void
    {
        $this->dispatch('abrir-modal-pago', facturaId: $id)
             ->to(\App\Livewire\Facturas\PagosFactura::class);
    }

    public function abrirMapa(int $id): void
    {
        $this->dispatch('abrir-mapa', facturaId: $id)
             ->to(MapaRelaciones::class);
    }

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

    #[On('refrescar-lista-facturas')]
    public function refrescarLista(): void
    {
        $this->reset(['q', 'estado', 'serie_id', 'proveedor_id', 'desde', 'hasta']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Factura::query()
            ->with(['cliente', 'serie'])
            ->whereHas('serie.tipo', fn($t) => $t->where('codigo', 'factura'))
            ->latest('id');

        // Filtro de búsqueda
        if (trim($this->q) !== '') {
            $searchTerm = '%' . trim($this->q) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('numero', 'like', $searchTerm)
                  ->orWhere('prefijo', 'like', $searchTerm)
                  ->orWhere('estado', 'like', $searchTerm)
                  ->orWhereHas('cliente', function ($c) use ($searchTerm) {
                      $c->where('razon_social', 'like', $searchTerm)
                        ->orWhere('nit', 'like', $searchTerm);
                  });
            });
        }

        // Filtros adicionales
        if ($this->estado !== 'todas') {
            $query->where('estado', $this->estado);
        }

        if (!empty($this->serie_id)) {
            $query->where('serie_id', $this->serie_id);
        }

        if (!empty($this->proveedor_id)) {
            $query->where('socio_negocio_id', $this->proveedor_id);
        }

        if (!empty($this->desde)) {
            $query->whereDate('fecha', '>=', $this->desde);
        }

        if (!empty($this->hasta)) {
            $query->whereDate('fecha', '<=', $this->hasta);
        }

        $items = $query->paginate($this->perPage);

        return view('livewire.facturas.lista-facturas', [
            'items' => $items,
        ]);


    }
    
}