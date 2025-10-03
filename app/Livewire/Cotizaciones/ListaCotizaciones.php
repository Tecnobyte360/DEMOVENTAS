<?php

namespace App\Livewire\Cotizaciones;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\cotizaciones\cotizacione;

class ListaCotizaciones extends Component
{
    use WithPagination;

    public string $search = '';
    public string $estado = 'todas';
    public int $perPage = 12;

    protected $queryString = ['search','estado','page','perPage'];

    public function updatingSearch(){ $this->resetPage(); }
    public function updatingEstado(){ $this->resetPage(); }
    public function updatingPerPage(){ $this->resetPage(); }

    public function abrir(int $id): void
    {
        $this->dispatch('abrir-cotizacion', id: $id)
             ->to(\App\Livewire\Cotizaciones\Cotizacion::class);
    }

    /** Acciones → abrir modal de envío del hijo */
    public function enviar(int $id): void
    {
        $this->dispatch('abrir-modal-enviar', cotizacionId: $id)
             ->to(\App\Livewire\Cotizaciones\EnviarCotizacionCorreo::class);
    }

    public function render()
    {
        $q = cotizacione::query()->with('cliente')->latest('id');

        if (trim($this->search) !== '') {
            $s = '%'.trim($this->search).'%';
            $q->where(function($qq) use ($s){
                $qq->where('id','like',$s)
                   ->orWhere('estado','like',$s)
                   ->orWhereHas('cliente', function($c) use ($s){
                       $c->where('razon_social','like',$s)
                         ->orWhere('nit','like',$s);
                   });
            });
        }

        if ($this->estado !== 'todas') $q->where('estado', $this->estado);

        $items = $q->paginate($this->perPage);
        return view('livewire.cotizaciones.lista-cotizaciones', compact('items'));
    }
}
