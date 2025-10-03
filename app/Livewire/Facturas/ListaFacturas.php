<?php

// app/Livewire/Facturas/ListaFacturas.php

namespace App\Livewire\Facturas;

use App\Models\Factura\factura;
use Livewire\Component;
use Livewire\WithPagination;

class ListaFacturas extends Component
{
    use WithPagination;

    public string $q = '';
    public string $estado = 'todas';
    public int $perPage = 12;

    // 👇 NUEVO: control del modal de vista previa
    public bool $showPreview = false;
    public ?int $previewId = null;

    protected $queryString = ['q','estado','page','perPage'];
    protected $listeners = ['refrescar-lista-facturas' => '$refresh'];

    public function updatingQ(){ $this->resetPage(); }
    public function updatingEstado(){ $this->resetPage(); }
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

    // 👇 NUEVO: abrir/cerrar previsualización
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
        $q = factura::query()->with('cliente','serie')->latest('id');

        if (trim($this->q) !== '') {
            $s = '%'.trim($this->q).'%';
            $q->where(function($qq) use ($s){
                $qq->where('numero','like',$s)
                   ->orWhere('prefijo','like',$s)
                   ->orWhere('estado','like',$s)
                   ->orWhereHas('cliente', function($c) use ($s){
                       $c->where('razon_social','like',$s)->orWhere('nit','like',$s);
                   });
            });
        }

        if ($this->estado !== 'todas') {
            $q->where('estado', $this->estado);
        }

        $items = $q->paginate($this->perPage);
        return view('livewire.facturas.lista-facturas', compact('items'));
    }
}
