<?php

namespace App\Livewire\Cotizaciones;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\cotizaciones\cotizacione;
use Masmerise\Toaster\PendingToast;

class ListaCotizaciones extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';
    public string $estado = 'todas';
    public int $perPage = 12;

    protected $queryString = ['search','estado','page','perPage'];

    public function updatingSearch(){ $this->resetPage(); }
    public function updatingEstado(){ $this->resetPage(); }
    public function updatingPerPage(){ $this->resetPage(); }

    /**
     * ✅ Abrir cotización = navegar al formulario (editar)
     */
    public function abrir(int $id)
    {
        return redirect()->route('cotizaciones.edit', $id);
    }

    /**
     * ✅ Abrir modal de envío (componente global montado abajo)
     */
    public function enviar(int $id): void
    {
        $this->dispatch('abrir-modal-enviar', cotizacionId: $id)
            ->to(\App\Livewire\Cotizaciones\EnviarCotizacionCorreo::class);
    }

    /**
     * ✅ PDF desde la LISTA: aquí SIEMPRE se recibe $id
     * (porque este componente NO tiene $this->cotizacion)
     */
   public function pdf(int $id)
{
    return redirect()->route('cotizaciones.pdf', $id);
}

    public function render()
    {
        $q = cotizacione::query()
            ->with('cliente')
            ->latest('id');

        if (trim($this->search) !== '') {
            $s = '%'.trim($this->search).'%';

            $q->where(function ($qq) use ($s) {
                $qq->where('id', 'like', $s)
                    ->orWhere('estado', 'like', $s)
                    ->orWhereHas('cliente', function ($c) use ($s) {
                        $c->where('razon_social', 'like', $s)
                          ->orWhere('nit', 'like', $s);
                    });
            });
        }

        if ($this->estado !== 'todas') {
            $q->where('estado', $this->estado);
        }

        $items = $q->paginate($this->perPage);

        return view('livewire.cotizaciones.lista-cotizaciones', compact('items'));
    }
}
