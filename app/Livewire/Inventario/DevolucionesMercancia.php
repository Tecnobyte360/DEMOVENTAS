<?php

namespace App\Livewire\Inventario;

use App\Models\Devoluciones\Devolucion;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Masmerise\Toaster\PendingToast;

class DevolucionesMercancia extends Component
{
    public $filtroDesde;
    public $filtroHasta;
    public $filtroProducto = '';
    public $filtroBodega = '';

    public function filtrar()
    {
        if (empty($this->filtroDesde) && empty($this->filtroHasta) && empty($this->filtroProducto) && empty($this->filtroBodega)) {
            PendingToast::create()
                ->error()
                ->message('Ingresa al menos un filtro antes de aplicar.')
                ->duration(5000);
            return;
        }

        PendingToast::create()
            ->success()
            ->message(' Filtro aplicado correctamente.')
            ->duration(5000);
    }

    public function render()
    {
        $devoluciones = Devolucion::with(['detalles.producto', 'detalles.bodega', 'ruta', 'usuario'])
            ->when($this->filtroDesde, fn($q) => $q->whereDate('fecha', '>=', $this->filtroDesde))
            ->when($this->filtroHasta, fn($q) => $q->whereDate('fecha', '<=', $this->filtroHasta))
            ->orderByDesc('fecha')
            ->get()
            ->filter(function ($devolucion) {
                return $devolucion->detalles->contains(function ($detalle) {
                    $productoNombre = strtolower($detalle->producto->nombre ?? '');
                    $bodegaNombre = strtolower($detalle->bodega->nombre ?? '');
                    return str_contains($productoNombre, strtolower($this->filtroProducto))
                        && str_contains($bodegaNombre, strtolower($this->filtroBodega));
                });
            });

        return view('livewire.inventario.devoluciones-mercancia', compact('devoluciones'));
    }
}
