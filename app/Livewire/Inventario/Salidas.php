<?php

namespace App\Livewire\Inventario;

use App\Models\bodegas;
use App\Models\Inventario\SalidaMercancia;
use App\Models\Inventario\SalidaMercanciaDetalle;
use App\Models\Productos\Producto;
use App\Models\Ruta\Ruta;
use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Productos\ProductoBodega as ProductosProductoBodega;
use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Masmerise\Toaster\PendingToast;

class Salidas extends Component
{
    // Formulario de creación
    public $ruta_id, $socio_negocio_id, $fecha, $observaciones;
    public $producto_id, $bodega_id, $cantidad;

    // Estado/UX
    public $stockDisponible = null;
    public $items = [];

    // Listado
    public $salidas = [];
    public $mostrarDetalle = false;
    public $salidaSeleccionada;

    // Filtros de listado
    public $filtro_desde = null; // YYYY-MM-DD
    public $filtro_hasta = null; // YYYY-MM-DD

    public function mount()
    {
        $this->fecha = Carbon::today()->toDateString();
        $this->loadSalidas();
    }

    /** Trae TODO sin filtros (para inicio y "Limpiar") */
    public function loadSalidas(): void
    {
        try {
            $this->salidas = SalidaMercancia::with([
                'ruta','socioNegocio','detalles.producto','detalles.bodega',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();
        } catch (\Throwable $e) {
            Log::error('Error al cargar salidas', ['error' => $e->getMessage()]);
            PendingToast::create()->error()->message('Error al cargar salidas: '.$e->getMessage())->duration(4000);
        }
    }

    /** Click en "Buscar" (si no hay filtros, trae todo) */
    public function buscarSalidas(): void
    {
        if (!$this->filtro_desde && !$this->filtro_hasta) {
            $this->loadSalidas();
            return;
        }

        $desde = $this->filtro_desde ? Carbon::parse($this->filtro_desde)->startOfDay() : null;
        $hasta = $this->filtro_hasta ? Carbon::parse($this->filtro_hasta)->endOfDay()   : null;

        // Corrige si vienen invertidas
        if ($desde && $hasta && $hasta->lt($desde)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }

        try {
            $this->salidas = SalidaMercancia::with([
                    'ruta','socioNegocio','detalles.producto','detalles.bodega',
                ])
                ->when($desde && $hasta, fn($q) => $q->whereBetween('fecha', [$desde, $hasta]))
                ->when($desde && !$hasta, fn($q) => $q->where('fecha', '>=', $desde))
                ->when(!$desde && $hasta, fn($q) => $q->where('fecha', '<=', $hasta))
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $e) {
            Log::error('Error al filtrar salidas', ['error' => $e->getMessage()]);
            PendingToast::create()->error()->message('No se pudo filtrar: '.$e->getMessage())->duration(4000);
        }
    }

    /** Click en "Limpiar" */
    public function limpiarFiltros(): void
    {
        $this->reset(['filtro_desde','filtro_hasta']);
        $this->loadSalidas();
    }

    /* ===== Detalle de la salida (modal opcional) ===== */
    public function verDetalle($salidaId)
    {
        try {
            $this->salidaSeleccionada = SalidaMercancia::with([
                'ruta','socioNegocio','detalles.producto','detalles.bodega',
            ])->findOrFail($salidaId);

            $this->mostrarDetalle = true;
        } catch (\Throwable $e) {
            Log::error('Error al ver detalle de salida', ['error' => $e->getMessage()]);
            PendingToast::create()->error()->message('Error al cargar detalle: '.$e->getMessage())->duration(4000);
        }
    }

    /* ===== Items del formulario ===== */
    public function agregarItem()
    {
        $this->validate([
            'producto_id' => 'required|exists:productos,id',
            'bodega_id'   => 'required|exists:bodegas,id',
            'cantidad'    => 'required|integer|min:1',
        ]);

        $stockDisponible = ProductosProductoBodega::where('producto_id', $this->producto_id)
            ->where('bodega_id', $this->bodega_id)
            ->value('stock') ?? 0;

        if ($this->cantidad > $stockDisponible) {
            PendingToast::create()->error()->message('No hay suficiente stock en la bodega.')->duration(4000);
            return;
        }

        $producto = Producto::find($this->producto_id);
        $bodega   = bodegas::find($this->bodega_id);

        $this->items[] = [
            'producto_id'     => $this->producto_id,
            'bodega_id'       => $this->bodega_id,
            'cantidad'        => $this->cantidad,
            'producto_nombre' => $producto->nombre ?? 'Producto',
            'bodega_nombre'   => $bodega->nombre ?? 'Bodega',
        ];

        $this->reset(['producto_id','bodega_id','cantidad']);
        $this->stockDisponible = null;
        PendingToast::create()->success()->message('Producto agregado.')->duration(3000);
    }

    public function quitarItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        PendingToast::create()->info()->message('Ítem eliminado.')->duration(2500);
    }

    /* ===== Guardar salida ===== */
    public function guardarSalida()
    {
        $this->validate([
            'ruta_id'          => 'nullable|exists:rutas,id', // opcional
            'socio_negocio_id' => 'required|exists:socio_negocios,id',
            'fecha'            => 'required|date',
        ]);

        if (count($this->items) === 0) {
            PendingToast::create()->error()->message('Agrega al menos un producto.')->duration(3500);
            return;
        }

        try {
            $salida = SalidaMercancia::create([
                'ruta_id'          => $this->ruta_id ?: null, // requiere que la columna sea NULLABLE en BD
                'user_id'          => Auth::id(),
                'socio_negocio_id' => $this->socio_negocio_id,
                'fecha'            => $this->fecha,
                'observaciones'    => $this->observaciones,
            ]);

            foreach ($this->items as $item) {
                SalidaMercanciaDetalle::create([
                    'salida_mercancia_id' => $salida->id,
                    'producto_id'         => $item['producto_id'],
                    'bodega_id'           => $item['bodega_id'],
                    'cantidad'            => $item['cantidad'],
                ]);

                // Descuenta stock
                $pb = ProductosProductoBodega::where('producto_id', $item['producto_id'])
                    ->where('bodega_id', $item['bodega_id'])
                    ->first();

                if ($pb) {
                    $pb->decrement('stock', $item['cantidad']);
                }
            }

            // Limpia form y recarga listado
            $this->reset(['ruta_id','socio_negocio_id','fecha','observaciones','producto_id','bodega_id','cantidad','items']);
            $this->fecha = Carbon::today()->toDateString();
            $this->loadSalidas();

            PendingToast::create()->success()->message('Salida registrada correctamente.')->duration(5000);
        } catch (\Throwable $e) {
            Log::error('Error al guardar salida', ['error' => $e->getMessage()]);
            PendingToast::create()->error()->message('Error al guardar: '.$e->getMessage())->duration(6000);
        }
    }

    /* ===== Helpers de stock ===== */
    public function updatedProducto_id() { $this->consultarStock(); }
    public function updatedBodega_id()   { $this->consultarStock(); }

    public function consultarStock()
    {
        try {
            if ($this->producto_id && $this->bodega_id) {
                $this->stockDisponible = ProductosProductoBodega::where('producto_id', $this->producto_id)
                    ->where('bodega_id', $this->bodega_id)
                    ->value('stock') ?? 0;
            } else {
                $this->stockDisponible = null;
            }
        } catch (\Throwable $e) {
            Log::error('Error al consultar stock', ['error' => $e->getMessage()]);
            $this->stockDisponible = null;
            PendingToast::create()->error()->message('Error al consultar stock: '.$e->getMessage())->duration(4000);
        }
    }

    public function render()
    {
        return view('livewire.inventario.salida-mercancia', [
            'rutas'     => Ruta::all(),
            'socios'    => SocioNegocio::all(),
            'productos' => Producto::all(),
            'bodegas'   => bodegas::all(),
        ]);
    }
}
