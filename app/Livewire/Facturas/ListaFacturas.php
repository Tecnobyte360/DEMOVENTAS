<?php

namespace App\Livewire\Facturas;

use App\Livewire\MapaRelacion\MapaRelaciones;
use App\Models\Factura\Factura;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
class ListaFacturas extends Component
{
    use WithPagination;

    public string $q = '';
    public string $estado = 'todas';
    public int $perPage = 12;

    public bool $showPreview = false;
    public ?int $previewId = null;

    protected $queryString = ['q','estado','page','perPage'];
    protected $listeners = ['refrescar-lista-facturas' => '$refresh'];

    public function updatingQ()      { $this->resetPage(); }
    public function updatingEstado() { $this->resetPage(); }
    public function updatingPerPage(){ $this->resetPage(); }

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

    public function preview(int $id): void
    {
        $this->previewId   = $id;
        $this->showPreview = true;
    }

    public function closePreview(): void
    {
        $this->reset(['showPreview','previewId']);
    }

    public function registrarPago(int $id): void
    {
        $this->dispatch('abrir-modal-pago', facturaId: $id)
             ->to(\App\Livewire\Facturas\PagosFactura::class);
    }

    // ✅ enviar la clave 'facturaId' (coincide con el argumento del listener)
    public function abrirMapa(int $id): void
    {
        $this->dispatch('abrir-mapa', facturaId: $id)
             ->to(MapaRelaciones::class);
    }
public function render()
{
    $q = \App\Models\Factura\Factura::query()
        ->with(['cliente','serie'])
        // Solo facturas de COMPRA: la serie pertenece a un tipo con código 'facturacompra'
        ->whereHas('serie.tipo', fn($t) => $t->where('codigo', 'factura'))
        ->latest('id');

    if (trim($this->q) !== '') {
        $s = '%'.trim($this->q).'%';
        $q->where(function ($qq) use ($s) {
            $qq->where('numero', 'like', $s)
               ->orWhere('prefijo', 'like', $s)
               ->orWhere('estado', 'like', $s)
               ->orWhereHas('cliente', fn ($c) =>
                    $c->where('razon_social','like',$s)->orWhere('nit','like',$s)
               );
        });
    }

    if (!empty($this->estado) && $this->estado !== 'todas') {
        $q->where('estado', $this->estado);
    }
    if (!empty($this->serie_id))       $q->where('serie_id', (int)$this->serie_id);
    if (!empty($this->proveedor_id))   $q->where('socio_negocio_id', (int)$this->proveedor_id);
    if (!empty($this->desde))          $q->whereDate('fecha', '>=', $this->desde);
    if (!empty($this->hasta))          $q->whereDate('fecha', '<=', $this->hasta);

    $items = $q->paginate($this->perPage);

    return view('livewire.facturas.lista-facturas', compact('items'));
}


}
