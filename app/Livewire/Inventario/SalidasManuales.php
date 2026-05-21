<?php
namespace App\Livewire\Inventario;

use App\Models\Bodega;
use App\Models\Inventario\SalidaManual;
use App\Models\Inventario\SalidaManualDetalle;
use App\Models\Productos\Producto;
use App\Models\Productos\ProductoBodega;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Masmerise\Toaster\PendingToast;

class SalidasManuales extends Component
{
    public string  $fecha         = '';
    public string  $motivo        = '';
    public string  $referencia    = '';
    public string  $observaciones = '';

    public ?int   $producto_id    = null;
    public ?int   $bodega_id      = null;
    public string $cantidad       = '';
    public string $obs_item       = '';

    public array   $items         = [];
    public ?float  $stockDisponible = null;

    public array   $salidas       = [];
    public ?string $filtro_desde  = null;
    public ?string $filtro_hasta  = null;
    public ?string $filtro_motivo = null;

    public bool  $mostrarDetalle      = false;
    public mixed $salidaSeleccionada  = null;
    public bool  $confirmarEliminar   = false;
    public ?int  $eliminarId          = null;

    public const MOTIVOS = [
        'ajuste'          => 'Ajuste de inventario',
        'merma'           => 'Merma / Perdida',
        'consumo_interno' => 'Consumo interno',
        'dano'            => 'Danio / Deterioro',
        'transferencia'   => 'Transferencia',
        'otro'            => 'Otro',
    ];

    public function mount(): void
    {
        $this->fecha = Carbon::today()->toDateString();
        $this->loadSalidas();
    }

    public function loadSalidas(): void
    {
        try {
            $q = SalidaManual::with(['user','detalles.producto','detalles.bodega'])
                ->orderByDesc('fecha')->orderByDesc('id');
            if ($this->filtro_desde)  $q->where('fecha', '>=', Carbon::parse($this->filtro_desde)->startOfDay());
            if ($this->filtro_hasta)  $q->where('fecha', '<=', Carbon::parse($this->filtro_hasta)->endOfDay());
            if ($this->filtro_motivo) $q->where('motivo', $this->filtro_motivo);
            $this->salidas = $q->get()->toArray();
        } catch (\Throwable $e) {
            Log::error('SalidasManuales::load', ['e' => $e->getMessage()]);
        }
    }

    public function buscar(): void  { $this->loadSalidas(); }
    public function limpiar(): void { $this->reset(['filtro_desde','filtro_hasta','filtro_motivo']); $this->loadSalidas(); }

    public function updatedProductoId(): void { $this->consultarStock(); }
    public function updatedBodegaId(): void   { $this->consultarStock(); }

    public function consultarStock(): void
    {
        if ($this->producto_id && $this->bodega_id) {
            $this->stockDisponible = (float)(ProductoBodega::where('producto_id', $this->producto_id)
                ->where('bodega_id', $this->bodega_id)->value('stock') ?? 0);
        } else {
            $this->stockDisponible = null;
        }
    }

    public function agregarItem(): void
    {
        $this->validate([
            'producto_id' => 'required|exists:productos,id',
            'bodega_id'   => 'required|exists:bodegas,id',
            'cantidad'    => 'required|numeric|min:0.001',
        ]);

        $stock = (float)(ProductoBodega::where('producto_id', $this->producto_id)
            ->where('bodega_id', $this->bodega_id)->value('stock') ?? 0);

        if ((float)$this->cantidad > $stock) {
            PendingToast::create()->error()->message("Stock insuficiente. Disponible: {$stock}")->duration(4000);
            return;
        }

        $producto = Producto::find($this->producto_id);
        $bodega   = Bodega::find($this->bodega_id);

        $this->items[] = [
            'producto_id'     => $this->producto_id,
            'bodega_id'       => $this->bodega_id,
            'cantidad'        => (float)$this->cantidad,
            'obs_item'        => $this->obs_item,
            'producto_nombre' => $producto->nombre ?? '--',
            'bodega_nombre'   => $bodega->nombre   ?? '--',
            'stock_disp'      => $stock,
        ];

        $this->reset(['producto_id','bodega_id','cantidad','obs_item']);
        $this->stockDisponible = null;
        PendingToast::create()->success()->message('Producto agregado.')->duration(2500);
    }

    public function quitarItem(int $idx): void
    {
        unset($this->items[$idx]);
        $this->items = array_values($this->items);
    }

    public function guardar(): void
    {
        $this->validate([
            'fecha'  => 'required|date',
            'motivo' => 'required|in:' . implode(',', array_keys(self::MOTIVOS)),
        ], [
            'motivo.required' => 'Selecciona el motivo de la salida.',
        ]);

        if (empty($this->items)) {
            PendingToast::create()->error()->message('Agrega al menos un producto.')->duration(3500);
            return;
        }

        try {
            DB::transaction(function () {
                $salida = SalidaManual::create([
                    'user_id'       => Auth::id(),
                    'fecha'         => $this->fecha,
                    'motivo'        => $this->motivo,
                    'referencia'    => $this->referencia ?: null,
                    'observaciones' => $this->observaciones ?: null,
                ]);

                foreach ($this->items as $item) {
                    SalidaManualDetalle::create([
                        'salida_manual_id' => $salida->id,
                        'producto_id'      => $item['producto_id'],
                        'bodega_id'        => $item['bodega_id'],
                        'cantidad'         => $item['cantidad'],
                        'observacion'      => $item['obs_item'] ?: null,
                    ]);

                    ProductoBodega::where('producto_id', $item['producto_id'])
                        ->where('bodega_id', $item['bodega_id'])
                        ->decrement('stock', $item['cantidad']);
                }
            });

            $this->reset(['motivo','referencia','observaciones','items','producto_id','bodega_id','cantidad','obs_item']);
            $this->fecha = Carbon::today()->toDateString();
            $this->stockDisponible = null;
            $this->loadSalidas();

            PendingToast::create()->success()->message('Salida manual registrada correctamente.')->duration(5000);
        } catch (\Throwable $e) {
            Log::error('SalidasManuales::guardar', ['e' => $e->getMessage()]);
            PendingToast::create()->error()->message('Error: ' . $e->getMessage())->duration(6000);
        }
    }

    public function verDetalle(int $id): void
    {
        $this->salidaSeleccionada = SalidaManual::with(['user','detalles.producto','detalles.bodega'])->find($id);
        $this->mostrarDetalle = true;
    }

    public function cerrarDetalle(): void
    {
        $this->mostrarDetalle = false;
        $this->salidaSeleccionada = null;
    }

    public function pedirEliminar(int $id): void { $this->eliminarId = $id; $this->confirmarEliminar = true; }
    public function cancelarEliminar(): void     { $this->eliminarId = null; $this->confirmarEliminar = false; }

    public function eliminar(): void
    {
        try {
            $salida = SalidaManual::with('detalles')->findOrFail($this->eliminarId);
            DB::transaction(function () use ($salida) {
                foreach ($salida->detalles as $det) {
                    ProductoBodega::where('producto_id', $det->producto_id)
                        ->where('bodega_id', $det->bodega_id)
                        ->increment('stock', $det->cantidad);
                }
                $salida->delete();
            });
            $this->cancelarEliminar();
            $this->loadSalidas();
            PendingToast::create()->success()->message('Salida eliminada y stock restaurado.')->duration(4000);
        } catch (\Throwable $e) {
            Log::error('SalidasManuales::eliminar', ['e' => $e->getMessage()]);
            PendingToast::create()->error()->message('Error al eliminar: ' . $e->getMessage())->duration(5000);
        }
    }

    public function render()
    {
        return view('livewire.inventario.salidas-manuales', [
            'productos' => Producto::where('activo', 1)->orderBy('nombre')->get(),
            'bodegas'   => Bodega::orderBy('nombre')->get(),
            'motivos'   => self::MOTIVOS,
        ]);
    }
}
