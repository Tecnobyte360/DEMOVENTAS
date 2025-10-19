<?php

namespace App\Livewire\Facturas;


use Livewire\Component;
use Livewire\WithPagination;
use App\Models\NotaCredito;

class ListaNotasCredito extends Component
{
    use WithPagination;

    public string $q = '';
    public string $estado = 'todas';
    public int $perPage = 12;

    public bool $showPreview = false;
    public ?int $previewId = null;

    protected $queryString = ['q','estado','page','perPage'];
    protected $listeners = ['refrescar-lista-notas' => '$refresh'];

    public function updatingQ(){ $this->resetPage(); }
    public function updatingEstado(){ $this->resetPage(); }
    public function updatingPerPage(){ $this->resetPage(); }

    /** Abrir nota crédito en el formulario de edición */
    public function abrir(int $id): void
    {
        $this->dispatch('abrir-nota-credito', id: $id)
             ->to(\App\Livewire\Facturas\NotaCreditoForm::class);
    }

    /** Abrir modal para enviar por correo */
    // public function enviarPorCorreo(int $id): void
    // {
    //     $this->dispatch('abrir-enviar-nota-credito', id: $id)
    //          ->to(\App\Livewire\Facturas\EnviarNotaCredito::class);
    // }

    /** Vista previa embebida */
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
        $q = NotaCredito::query()->with('cliente','serie')->latest('id');

        if (trim($this->q) !== '') {
            $s = '%'.trim($this->q).'%';
            $q->where(function($qq) use ($s){
                $qq->where('numero','like',$s)
                   ->orWhere('prefijo','like',$s)
                   ->orWhere('estado','like',$s)
                   ->orWhereHas('cliente', function($c) use ($s){
                       $c->where('razon_social','like',$s)
                         ->orWhere('nit','like',$s);
                   });
            });
        }

        if ($this->estado !== 'todas') {
            $q->where('estado', $this->estado);
        }

        $items = $q->paginate($this->perPage);

        return view('livewire.Factura.lista-notas-credito', compact('items'));
    }
}
