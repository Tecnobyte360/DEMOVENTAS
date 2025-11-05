<?php

namespace App\Livewire\Facturas;

use App\Livewire\MapaRelacion\MapaRelaciones;
use App\Models\Factura\Factura;
use Livewire\Component;
use Livewire\WithPagination;

class ListaFacturas extends Component
{
    use WithPagination;

    /** Tailwind para la paginación */
    protected string $paginationTheme = 'tailwind';

    // Filtros visibles
    public string $q = '';
    public string $estado = 'todas';
    public int $perPage = 12;

    // Filtros opcionales
    public ?int $serie_id = null;
    public ?int $proveedor_id = null;
    public ?string $desde = null;   // YYYY-MM-DD
    public ?string $hasta = null;   // YYYY-MM-DD

    // Modal de previsualización
    public bool $showPreview = false;
    public ?int $previewId = null;

    protected $queryString = ['q','estado','page','perPage'];
    protected $listeners   = ['refrescar-lista-facturas' => '$refresh'];

    /** Reset de página al cambiar filtros */
    public function updatingQ()       { $this->resetPage(); }
    public function updatingEstado()  { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }

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
        $this->previewId   = $id;
        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->reset(['showPreview','previewId']);
    }

    public function render()
    {
        $q = Factura::query()
            ->with(['cliente','serie'])
            // Ajusta el código según sea ventas/compras
            ->whereHas('serie.tipo', fn($t) => $t->where('codigo', 'factura'))
            ->latest('id');

        if (trim($this->q) !== '') {
            $s = '%'.trim($this->q).'%';
            $q->where(function ($qq) use ($s) {
                $qq->where('numero', 'like', $s)
                   ->orWhere('prefijo', 'like', $s)
                   ->orWhere('estado', 'like', $s)
                   ->orWhereHas('cliente', fn ($c) =>
                        $c->where('razon_social','like',$s)
                          ->orWhere('nit','like',$s)
                   );
            });
        }

        if ($this->estado !== 'todas')         $q->where('estado', $this->estado);
        if (!empty($this->serie_id))           $q->where('serie_id', (int) $this->serie_id);
        if (!empty($this->proveedor_id))       $q->where('socio_negocio_id', (int) $this->proveedor_id);
        if (!empty($this->desde))              $q->whereDate('fecha', '>=', $this->desde);
        if (!empty($this->hasta))              $q->whereDate('fecha', '<=', $this->hasta);

        $items = $q->paginate($this->perPage);

        return view('livewire.facturas.lista-facturas', [
            'items'       => $items,
            'showPreview' => $this->showPreview,
            'previewId'   => $this->previewId,
        ]);
    }
}
