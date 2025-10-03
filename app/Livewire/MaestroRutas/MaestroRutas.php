<?php

namespace App\Livewire\MaestroRutas;

use Livewire\Component;
use App\Models\User;
use App\Models\Ruta\Ruta;
use App\Models\Vehiculo\Vehiculo;
use App\Models\Productos\Producto;
use App\Models\Productos\ProductoBodega;
use App\Models\bodegas;
use App\Models\InventarioRuta\InventarioRuta;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Masmerise\Toaster\PendingToast;
use Illuminate\Support\Facades\Log;

class MaestroRutas extends Component
{
    public $rutas;
    public $vehiculos;
    public $conductores;
    public $productos;
    public $bodegas;
    public $inventarioVista = [];
    public $rutaVistaId = null;
    public $mostrarModalConfirmacion = false;
    public $rutaAEliminarId = null;
    public $vehiculo_id;
    public $ruta;
    public $fecha_salida;
    public $conductor_ids = [];
    public $ruta_id;
    public $isEdit = false;

    public $producto_id;
    public $bodega_id;
    public $cantidad_asignada;
    public $stockDisponible = null;
    public $asignaciones = [];

    public $erroresFormulario = false;

    public function mount()
    {
        $this->vehiculos   = Vehiculo::where('estado', 'activo')->get();
        $this->conductores = User::all();
        $this->productos   = Producto::all();
        $this->bodegas     = bodegas::all();
        $this->setDefaultBodegaIfEmpty();

        $this->cargarRutas();
    }


    public function cargarRutas()
    {
        $this->rutas = Ruta::with(['vehiculo', 'conductores'])->latest()->get();
    }


    private function getDefaultBodegaId(): ?int
    {

        $default = $this->bodegas->first();

        return $default?->id;
    }

    private function setDefaultBodegaIfEmpty(): void
    {
        if (empty($this->bodega_id)) {
            $this->bodega_id = $this->getDefaultBodegaId();
        }
    }

    public function guardarRuta()
    {
        $this->resetErrorBag();
        $this->erroresFormulario = false;

        try {
            $this->validate([
                'vehiculo_id' => 'required|exists:vehiculos,id',
                'ruta' => 'required|string|max:255',
                'fecha_salida' => 'required|date',
                'conductor_ids' => 'required|array|min:1',
            ]);

            if (empty($this->asignaciones)) {
                $this->erroresFormulario = true;
                PendingToast::create()
                    ->error()
                    ->message('Debe asignar al menos un producto a la ruta antes de guardarla.')
                    ->duration(7000);
                return;
            }

            foreach ($this->asignaciones as $index => $item) {
                if (
                    empty($item['producto_id']) ||
                    empty($item['bodega_id']) ||
                    empty($item['cantidad']) ||
                    !is_numeric($item['cantidad']) ||
                    $item['cantidad'] <= 0
                ) {
                    $this->erroresFormulario = true;
                    PendingToast::create()
                        ->error()
                        ->message("La asignación #" . ($index + 1) . " es inválida. Verifique producto, bodega y cantidad.")
                        ->duration(7000);
                    return;
                }
            }

            $ruta = Ruta::create([
                'vehiculo_id' => $this->vehiculo_id,
                'ruta' => $this->ruta,
                'fecha_salida' => $this->fecha_salida,
            ]);

            $ruta->conductores()->sync($this->conductor_ids);

            foreach ($this->asignaciones as $item) {
                InventarioRuta::create([
                    'ruta_id' => $ruta->id,
                    'producto_id' => $item['producto_id'],
                    'bodega_id' => $item['bodega_id'],
                    'cantidad' => $item['cantidad'],
                    'cantidad_inicial' => $item['cantidad'],
                ]);

                $pb = ProductoBodega::where('producto_id', $item['producto_id'])
                    ->where('bodega_id', $item['bodega_id'])
                    ->first();

                if ($pb && $pb->stock >= $item['cantidad']) {
                    $pb->decrement('stock', $item['cantidad']);
                }
            }

            $this->resetFormulario();
            $this->cargarRutas();

            PendingToast::create()
                ->success()
                ->message('Ruta registrada exitosamente.')
                ->duration(5000);
        } catch (\Throwable $e) {
            Log::error('Error al guardar la ruta', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al registrar la ruta: ' . $e->getMessage())
                ->duration(9000);
        }
    }


   public function actualizarRuta()
{
    try {
        $this->validate([
            'vehiculo_id'    => 'required|exists:vehiculos,id',
            'ruta'           => 'required|string|max:255',
            'fecha_salida'   => 'required|date',
            'conductor_ids'  => 'required|array|min:1',
        ]);

        $ruta = Ruta::with(['inventarios.producto', 'inventarios.bodega', 'conductores'])
            ->findOrFail($this->ruta_id);

        $hayCambiosEnRuta =
            $ruta->vehiculo_id != $this->vehiculo_id ||
            $ruta->ruta != $this->ruta ||
            $ruta->fecha_salida != $this->fecha_salida ||
            !$ruta->conductores->pluck('id')->diff($this->conductor_ids)->isEmpty();

        // Normaliza "nuevas" asignaciones (lo que el usuario quiere como cantidad_inicial)
        $nuevas = collect($this->asignaciones)
            ->map(function ($i) {
                return [
                    'producto_id' => (int) $i['producto_id'],
                    'bodega_id'   => (int) $i['bodega_id'],
                    'cantidad'    => (int) $i['cantidad'], // <- interpretado como nueva cantidad_inicial deseada
                ];
            })
            ->keyBy(fn ($i) => "{$i['producto_id']}|{$i['bodega_id']}");

        // Inventarios actuales (en BD)
        $originales = $ruta->inventarios->keyBy(fn ($i) => "{$i->producto_id}|{$i->bodega_id}");

        // Detecta cambios en asignaciones (comparando cantidad_inicial actual vs nueva deseada)
        $hayCambiosEnAsignaciones = false;
        if ($originales->count() !== $nuevas->count()) {
            $hayCambiosEnAsignaciones = true;
        } else {
            foreach ($nuevas as $k => $nuevo) {
                if (!$originales->has($k)) { $hayCambiosEnAsignaciones = true; break; }
                $inv = $originales[$k];
                if ((int)$inv->cantidad_inicial !== (int)$nuevo['cantidad']) { $hayCambiosEnAsignaciones = true; break; }
            }
        }

        if (!$hayCambiosEnRuta && !$hayCambiosEnAsignaciones) {
            PendingToast::create()->info()->message('No se detectaron cambios para actualizar.')->duration(4000);
            return;
        }

        DB::transaction(function () use ($ruta, $nuevas, $originales) {
            // 1) Actualizar cabecera y conductores
            $ruta->update([
                'vehiculo_id'  => $this->vehiculo_id,
                'ruta'         => $this->ruta,
                'fecha_salida' => $this->fecha_salida,
            ]);
            $ruta->conductores()->sync($this->conductor_ids);

            // 2) Procesar altas/bajas y nuevos items por (producto,bodega)
            foreach ($nuevas as $key => $nuevo) {
                $prodId   = $nuevo['producto_id'];
                $bodId    = $nuevo['bodega_id'];
                $newQtyIn = $nuevo['cantidad']; // nueva cantidad_inicial deseada

                if ($originales->has($key)) {
                    // Ya existe -> calcular delta vs cantidad_inicial actual
                    /** @var \App\Models\InventarioRuta\InventarioRuta $inv */
                    $inv       = $originales[$key];
                    $origQtyIn = (int) $inv->cantidad_inicial;

                    if ($newQtyIn > $origQtyIn) {
                        // AUMENTO: enviar delta adicional a la ruta
                        $delta = $newQtyIn - $origQtyIn;

                        $pb = ProductoBodega::where('producto_id', $prodId)
                            ->where('bodega_id', $bodId)
                            ->lockForUpdate()
                            ->first();

                        if (!$pb || $pb->stock < $delta) {
                            throw new \Exception("Stock insuficiente para aumentar {$delta} uds de {$inv->producto->nombre} en {$inv->bodega->nombre}.");
                        }

                        $pb->decrement('stock', $delta);
                        // Sube inicial y disponible en la ruta
                        $inv->cantidad_inicial += $delta;
                        $inv->cantidad         += $delta;
                        $inv->save();

                    } elseif ($newQtyIn < $origQtyIn) {
                        // DISMINUCIÓN: devolver delta desde la ruta a la bodega
                        $delta = $origQtyIn - $newQtyIn;

                        if ($inv->cantidad < $delta) {
                            throw new \Exception("No puedes reducir más de lo que queda en ruta. Restante: {$inv->cantidad}, intento: {$delta}.");
                        }

                        $inv->cantidad_inicial -= $delta;
                        $inv->cantidad         -= $delta;
                        $inv->save();

                        $pb = ProductoBodega::where('producto_id', $prodId)
                            ->where('bodega_id', $bodId)
                            ->lockForUpdate()
                            ->first();

                        if ($pb) { $pb->increment('stock', $delta); }
                    }

                    // Ya procesado; eliminar de $originales para luego identificar removidos
                    $originales->forget($key);

                } else {
                    // NUEVO item: crear y descontar stock total solicitado
                    $pb = ProductoBodega::where('producto_id', $prodId)
                        ->where('bodega_id', $bodId)
                        ->lockForUpdate()
                        ->first();

                    if (!$pb || $pb->stock < $newQtyIn) {
                        throw new \Exception("Stock insuficiente para asignar {$newQtyIn} uds.");
                    }

                    InventarioRuta::create([
                        'ruta_id'          => $ruta->id,
                        'producto_id'      => $prodId,
                        'bodega_id'        => $bodId,
                        'cantidad'         => $newQtyIn, // disponible en la ruta
                        'cantidad_inicial' => $newQtyIn, // acumulado asignado
                    ]);

                    $pb->decrement('stock', $newQtyIn);
                }
            }

            // 3) Ítems que estaban y ya no están -> devolver lo restante y eliminar
            foreach ($originales as $inv) {
                if ($inv->cantidad > 0) {
                    $pb = ProductoBodega::where('producto_id', $inv->producto_id)
                        ->where('bodega_id', $inv->bodega_id)
                        ->lockForUpdate()
                        ->first();
                    if ($pb) { $pb->increment('stock', $inv->cantidad); }
                }
                $inv->delete();
            }
        });

        $this->resetFormulario();
        $this->cargarRutas();

        PendingToast::create()
            ->success()
            ->message('Ruta actualizada correctamente.')
            ->duration(5000);

    } catch (\Throwable $e) {
        Log::error('Error al actualizar la ruta', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        PendingToast::create()
            ->error()
            ->message('Error al actualizar la ruta: ' . $e->getMessage())
            ->duration(9000);
    }
}

    public function updatedProductoId()
    {
        $this->consultarStock();
    }

    public function updatedBodegaId()
    {
        $this->consultarStock();
    }

    public function consultarStock()
    {
        try {
            $this->stockDisponible = null;

            if ($this->producto_id && $this->bodega_id) {
                $productoBodega = ProductoBodega::where('producto_id', $this->producto_id)
                    ->where('bodega_id', $this->bodega_id)
                    ->first();

                if ($productoBodega) {
                    $stockReal = $productoBodega->stock;

                    // Si estamos editando una ruta, no descontamos lo ya asignado a la misma
                    if ($this->isEdit) {
                        $this->stockDisponible = $stockReal;
                    } else {
                        $yaAsignado = collect($this->asignaciones)
                            ->filter(function ($item) {
                                return $item['producto_id'] == $this->producto_id &&
                                    $item['bodega_id'] == $this->bodega_id;
                            })
                            ->sum('cantidad');

                        $this->stockDisponible = max(0, $stockReal - $yaAsignado);
                    }
                } else {
                    $this->stockDisponible = 0;
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error al consultar stock disponible', [
                'producto_id' => $this->producto_id,
                'bodega_id' => $this->bodega_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al consultar el stock disponible.')
                ->duration(8000);
        }
    }


    public function agregarAsignacion()
    {
        try {
            $this->validate([
                'producto_id' => 'required|exists:productos,id',
                'bodega_id' => 'required|exists:bodegas,id',
                'cantidad_asignada' => 'required|numeric|min:1',
            ]);

            $this->consultarStock();

            if ($this->stockDisponible === null || $this->stockDisponible <= 0) {
                PendingToast::create()
                    ->error()
                    ->message('Stock insuficiente en la bodega seleccionada.')
                    ->duration(7000);
                return;
            }

            if ($this->cantidad_asignada > $this->stockDisponible) {
                PendingToast::create()
                    ->error()
                    ->message('La cantidad excede el stock disponible.')
                    ->duration(7000);
                return;
            }

            $producto = Producto::find($this->producto_id);
            $bodega = bodegas::find($this->bodega_id);

            if (!$producto || !$bodega) {
                PendingToast::create()
                    ->error()
                    ->message('Error al encontrar producto o bodega.')
                    ->duration(7000);
                return;
            }

            $indiceExistente = collect($this->asignaciones)->search(function ($asignacion) {
                return $asignacion['producto_id'] == $this->producto_id &&
                    $asignacion['bodega_id'] == $this->bodega_id;
            });

            if ($indiceExistente !== false) {
                $this->asignaciones[$indiceExistente]['cantidad'] += $this->cantidad_asignada;
            } else {
                $this->asignaciones[] = [
                    'producto_id' => $this->producto_id,
                    'producto_nombre' => $producto->nombre,
                    'bodega_id' => $this->bodega_id,
                    'bodega_nombre' => $bodega->nombre,
                    'cantidad' => $this->cantidad_asignada,
                ];
            }

            $this->reset(['producto_id', 'cantidad_asignada', 'stockDisponible']);

            PendingToast::create()
                ->success()
                ->message('Producto asignado correctamente.')
                ->duration(4000);
        } catch (\Throwable $e) {
            Log::error('Error al agregar asignación', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Ocurrió un error al agregar el producto: ' . $e->getMessage())
                ->duration(9000);
        }
    }


    public function eliminarAsignacion($index)
    {
        try {
            if (!isset($this->asignaciones[$index])) {
                PendingToast::create()
                    ->error()
                    ->message('No se encontró la asignación a eliminar.')
                    ->duration(5000);
                return;
            }

            unset($this->asignaciones[$index]);
            $this->asignaciones = array_values($this->asignaciones);

            PendingToast::create()
                ->success()
                ->message('Asignación eliminada correctamente.')
                ->duration(4000);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar asignación', [
                'index' => $index,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Ocurrió un error al eliminar la asignación.')
                ->duration(8000);
        }
    }

    public function edit($id)
    {
        try {
            $ruta = Ruta::with(['conductores', 'inventarios.producto', 'inventarios.bodega'])->findOrFail($id);

            $this->vehiculo_id = $ruta->vehiculo_id;
            $this->ruta = $ruta->ruta;
            $this->fecha_salida = $ruta->fecha_salida;
            $this->conductor_ids = $ruta->conductores->pluck('id')->toArray();
            $this->ruta_id = $ruta->id;
            $this->isEdit = true;

            $this->asignaciones = $ruta->inventarios->map(function ($inv) {
                return [
                    'producto_id' => $inv->producto_id,
                    'producto_nombre' => $inv->producto->nombre ?? 'N/A',
                    'bodega_id' => $inv->bodega_id,
                    'bodega_nombre' => $inv->bodega->nombre ?? 'N/A',
                  'cantidad' => $inv->cantidad_inicial,
                ];
            })->toArray();

            PendingToast::create()
                ->info()
                ->message('Ruta cargada para edición.')
                ->duration(3000);
        } catch (\Throwable $e) {
            Log::error('Error al cargar ruta para edición', [
                'ruta_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al cargar la ruta. Verifique si existe o si hay datos incompletos.')
                ->duration(8000);
        }
    }




    public function resetFormulario()
    {
        $this->reset([
            'vehiculo_id',
            'ruta',
            'fecha_salida',
            'conductor_ids',
            'ruta_id',
            'isEdit',
            'producto_id',
            'bodega_id',
            'cantidad_asignada',
            'stockDisponible',
            'asignaciones'
        ]);
        $this->resetErrorBag();
    }

    public function verInventario($rutaId)
    {
        try {
            if ($this->rutaVistaId === $rutaId) {
                $this->rutaVistaId = null;
                $this->inventarioVista = [];

                PendingToast::create()
                    ->info()
                    ->message('Inventario ocultado.')
                    ->duration(3000);

                return;
            }

            $this->rutaVistaId = $rutaId;

            $inventario = InventarioRuta::with(['producto', 'bodega'])
                ->where('ruta_id', $rutaId)
                ->get();

            $this->inventarioVista = $inventario->map(function ($item) {
                return [
                    'producto'           => $item->producto->nombre ?? 'Producto eliminado',
                    'bodega'             => $item->bodega->nombre ?? 'Bodega eliminada',
                    'cantidad_asignada'  => $item->cantidad_inicial,
                    'cantidad_restante'  => $item->cantidad,
                    'cantidad_devuelta'  => $item->cantidad_devuelta,
                ];
            })->toArray();




            PendingToast::create()
                ->info()
                ->message('Inventario cargado correctamente.')
                ->duration(3000);
        } catch (\Throwable $e) {
            Log::error('Error al consultar inventario de ruta', [
                'ruta_id' => $rutaId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Ocurrió un error al consultar el inventario de la ruta.')
                ->duration(8000);
        }
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName, [
            'vehiculo_id' => 'required|exists:vehiculos,id',
            'ruta' => 'required|string|max:255',
            'fecha_salida' => 'required|date',
            'conductor_ids' => 'required|array|min:1',
            'producto_id' => 'required|exists:productos,id',
            'bodega_id' => 'required|exists:bodegas,id',
            'cantidad_asignada' => 'required|numeric|min:1',
        ]);
    }

    public function confirmarEliminacion($id)
    {
        $this->rutaAEliminarId = $id;
        $this->mostrarModalConfirmacion = true;
    }

    public function eliminarRutaConfirmada()
    {
        $id = $this->rutaAEliminarId;

        try {
            $ruta = Ruta::with('inventarios')->findOrFail($id);

            foreach ($ruta->inventarios as $inventario) {
                $pb = ProductoBodega::where('producto_id', $inventario->producto_id)
                    ->where('bodega_id', $inventario->bodega_id)
                    ->first();

                if ($pb) {
                    $pb->increment('stock', $inventario->cantidad);
                }

                $inventario->delete();
            }

            $ruta->conductores()->detach();
            $ruta->delete();

            $this->cargarRutas();

            $this->mostrarModalConfirmacion = false;
            $this->rutaAEliminarId = null;

            \Masmerise\Toaster\PendingToast::create()
                ->success()
                ->message('Ruta eliminada correctamente.')
                ->duration(4000);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar ruta', [
                'ruta_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->mostrarModalConfirmacion = false;

            \Masmerise\Toaster\PendingToast::create()
                ->error()
                ->message('Ocurrió un error al eliminar la ruta.')
                ->duration(8000);
        }
    }




    public function render()
    {
        return view('livewire.maestro-rutas.maestro-rutas', [
            'rutas' => Ruta::with(['vehiculo', 'conductores'])->latest()->paginate(5)
        ]);
    }
}
