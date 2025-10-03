<?php

namespace App\Livewire\Indicadores;

use Livewire\Component;
use App\Models\Pedidos\Pedido;
use Illuminate\Support\Carbon;

class Indicadores extends Component
{
    public $totalFacturado = 0;
    public $totalPagado = 0;
    public $totalPendiente = 0;
    public $totalPedidos = 0;

    public function mount()
    {
        $hoy = Carbon::today();

        $pedidos = Pedido::with(['pagos', 'detalles.producto'])
            ->whereDate('fecha', $hoy)
            ->get();

        $this->totalFacturado = $pedidos->sum(fn($p) =>
            $p->detalles->sum(fn($d) => $d->cantidad * ($d->producto->precio ?? 0))
        );

        $this->totalPagado = $pedidos->sum(fn($p) => $p->pagos->sum('monto'));

        $this->totalPendiente = $this->totalFacturado - $this->totalPagado;
        $this->totalPedidos = $pedidos->count();
    }

    public function render()
    {
        return view('livewire.indicadores.indicadores');
    }
}
