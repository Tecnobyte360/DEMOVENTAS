<?php
namespace App\Livewire\Indicadores;

use Livewire\Component;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Pedidos\Pedido;
use Illuminate\Support\Carbon;

class Clientes extends Component
{
    public $totalClientes = 0;
    public $clientesNuevosHoy = 0;
    public $clientesConDeuda = 0;

    public function mount()
    {
        $this->totalClientes = SocioNegocio::count();

        $this->clientesNuevosHoy = SocioNegocio::whereDate('created_at', Carbon::today())->count();

        $this->clientesConDeuda = SocioNegocio::whereHas('pedidos', function ($q) {
            $q->whereHas('pagos', function ($subq) {
                $subq->havingRaw('SUM(monto) < (SELECT SUM(cantidad * (SELECT precio FROM productos WHERE productos.id = pedido_detalles.producto_id)) FROM pedido_detalles WHERE pedido_id = pedidos.id)');
            });
        })->count();
    }

    public function render()
    {
        return view('livewire.indicadores.clientes', [
            'totalClientes' => $this->totalClientes,
            'clientesNuevosHoy' => $this->clientesNuevosHoy,
            'clientesConDeuda' => $this->clientesConDeuda,
        ]);
    }
}
