<?php

namespace App\Livewire\RutaDisponiblesConductor;

use App\Models\Devoluciones\Devolucion;
use App\Models\Devoluciones\DevolucionDetalle;
use App\Models\Finanzas\TipoGasto;
use App\Models\Inventario\SalidaMercancia;
use App\Models\Inventario\SalidaMercanciaDetalle;
use App\Models\InventarioRuta\GastoRuta;
use Livewire\Component;
use App\Models\Ruta\Ruta;

use App\Models\SocioNegocio\SocioNegocio;
use App\Models\Pedidos\Pedido;
use App\Models\Pedidos\PedidoDetalle;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;
use App\Models\InventarioRuta\InventarioRuta;
use App\Models\Productos\PrecioProducto;
use App\Models\Productos\ProductoBodega;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Masmerise\Toaster\PendingToast;
use Livewire\WithPagination;

class RutasDisponiblesConductor extends Component
{
    use WithPagination;
    public $valorPendiente = 0;
    public $tabActivo = 'pedidos';
    public $devolucionVista;
    public $mostrarDevolucion = false;
    public $rutas;
    public $rutaVistaId;
    public $inventarioVista = [];
    public $alerta = null;
    public $productosDisponibles = [];
    public $busquedaProducto = '';
    public $nuevoProductoNombre = '';
    public $nuevoProductoId     = null;
    public $modalPago = false;
    public $pedidoPagoId = null;
public $metodoPago = 'contado'; 
    public $montoPago = null;
    public $pedidoItems = [];
    public $mostrarFactura = false;
    public $pedidoFactura = null;
    public $busquedaCliente = '';
    public $clientesFiltrados = [];
    public $socioNegocioId = null;
    public $productoACambiar = null;
    public $modalPedido = false;
    public $pedidoRutaId = null;
    public $modalCambioProducto = false;
    public $buscarProducto = '';
    public $tipoPago = 'contado';
    public $montoPagado = null;
    public $pedidoCambioId = null;
    public $modalGasto = false;
    public $productoNuevoId = null;
    public $rutaGastoId = null;
    public $tipoGastoId = null;
    public $nuevoClienteTexto = '';
    public $nuevoClienteId    = null;
    public $clientesConCredito = [];
    public $montoGasto = null;
    public $observacionGasto = '';
    public $productosDelPedido = [];
    public $confirmarDevolucion = false;
    public $rutaADevolver = null;
    public $clienteTieneDeudas = false;
    public $busquedaPedido = '';
    public $filtroTipoPago = '';
    public $tiposGasto = [];
    public $clienteSeleccionadoId = null;
    public $pedidosSocio = [];
    public function updatingBusquedaPedido()
    {
        $this->resetPage();
    }

    public function updatingFiltroTipoPago()
    {
        $this->resetPage();
    }

    public function updatedTabActivo($value)
    {
        if ($value === 'deudasgenerales') {
            $this->cargarClientesConCredito();
        }
    }

    public function cargarClientesConCredito()
    {
        $this->clientesConCredito = SocioNegocio::with([
            'pedidos.pagos',
            'pedidos.detalles.producto',
            'pedidos.usuario',
            'pedidos.ruta'
        ])
            ->get()
            ->map(function ($socio) {
                $pedidosSocio = $socio->pedidos
                    ->where('tipo_pago', 'credito')
                    ->filter(function ($pedido) {
                        $total = $pedido->detalles->sum(fn($d) => $d->cantidad * floatval($d->precio_aplicado ?? $d->precio_unitario));
                        $pagado = $pedido->pagos->sum('monto');
                        return $total > $pagado;
                    })
                    ->map(function ($pedido) {
                        $total = $pedido->detalles->sum(fn($d) => $d->cantidad * floatval($d->precio_aplicado ?? $d->precio_unitario));
                        $pagado = $pedido->pagos->sum('monto');
                        $pendiente = $total - $pagado;

                        return [
                            'id'        => $pedido->id,
                            'total_raw' => $pendiente,
                        ];
                    })
                    ->values();

                $socio->creditosPendientes = $pedidosSocio;
                return $socio;
            })
            ->filter(fn($socio) => $socio->creditosPendientes->count() > 0)
            ->values();
    }




    public function mostrarPedidos($clienteId)
    {
        $this->clienteSeleccionadoId = $clienteId;

        $socio = SocioNegocio::with([
            'pedidos.pagos',
            'pedidos.usuario',
            'pedidos.ruta',
            'pedidos.detalles.producto'
        ])->find($clienteId);

        if (!$socio) {
            $this->pedidosSocio = [];
            return;
        }

        $this->pedidosSocio = $socio->pedidos
            ->where('tipo_pago', 'credito')
            ->filter(function ($pedido) {
                $total = optional($pedido->detalles)->sum(fn($d) => $d->cantidad * floatval($d->precio_aplicado ?? $d->precio_unitario));
                $pagado = optional($pedido->pagos)->sum('monto');
                return $total > $pagado;
            })
            ->map(function ($pedido) {
                $total = optional($pedido->detalles)->sum(fn($d) => $d->cantidad * floatval($d->precio_aplicado ?? $d->precio_unitario));
                $pagado = optional($pedido->pagos)->sum('monto');
                $pendiente = $total - $pagado;

                return [
                    'id' => $pedido->id,
                    'fecha' => optional($pedido->fecha)->format('d/m/Y'),
                    'ruta' => $pedido->ruta->ruta ?? 'Sin ruta',
                    'usuario' => $pedido->usuario->name ?? 'Desconocido',
                    'total_raw' => $pendiente,
                    'total' => number_format($pendiente, 0, ',', '.'),
                ];
            })->values()->toArray();
    }


   public function getPedidosProperty()
{
    $usuarioId = Auth::id();

    $rutasIds = Ruta::whereDate('fecha_salida', Carbon::today())
        ->whereHas('conductores', fn($q) => $q->where('users.id', $usuarioId))
        ->pluck('id');

    return Pedido::with('socioNegocio')
        ->whereIn('ruta_id', $rutasIds)
        ->whereDate('fecha', Carbon::today())
        ->where('user_id', $usuarioId) // 🔒 solo pedidos que yo mismo hice
        ->when($this->busquedaPedido, function ($query) {
            $query->whereHas('socioNegocio', function ($q) {
                $q->where('razon_social', 'like', '%' . $this->busquedaPedido . '%')
                    ->orWhere('nit', 'like', '%' . $this->busquedaPedido . '%');
            })->orWhere('tipo_pago', 'like', '%' . $this->busquedaPedido . '%');
        })
        ->when($this->filtroTipoPago, function ($query) {
            $query->where('tipo_pago', $this->filtroTipoPago);
        })
        ->orderByDesc('created_at')
        ->paginate(10);
}



    public function mount()
    {
        try {
            $usuarioId = Auth::id();

            $this->rutas = Ruta::whereDate('fecha_salida', Carbon::today())
                ->whereHas('conductores', fn($q) => $q->where('users.id', $usuarioId))
                ->with(['vehiculo', 'conductores', 'pedidos'])
                ->get();

            $this->tiposGasto = TipoGasto::orderBy('nombre')->get();
            $this->clientesFiltrados = SocioNegocio::orderBy('razon_social')->get();

            PendingToast::create()
                ->success()
                ->message('Rutas cargadas correctamente.')
                ->duration(4000);
        } catch (\Throwable $e) {
            Log::error('Error al cargar rutas del conductor', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->rutas = collect();

            PendingToast::create()
                ->error()
                ->message('Error al cargar rutas disponibles: ' . $e->getMessage())
                ->duration(8000);
        }
    }


    public function updatedBusquedaCliente($value)
    {
        $cliente = SocioNegocio::where('razon_social', $value)
            ->orWhere('nit', $value)
            ->first();

        if ($cliente) {
            $this->socioNegocioId = $cliente->id;
            $this->clientesFiltrados = collect();
        } else {
            $this->clientesFiltrados = SocioNegocio::where('razon_social', 'like', "%{$value}%")
                ->orWhere('nit', 'like', "%{$value}%")
                ->orderBy('razon_social')
                ->limit(10)
                ->get();
        }
    }

    public function verInventario($rutaId)
    {
        if ($this->rutaVistaId === $rutaId) {
            // Si ya está abierta, la cierra
            $this->rutaVistaId = null;
            $this->inventarioVista = [];
        } else {
            // Si está cerrada o es otra, la abre
            $this->rutaVistaId = $rutaId;

            $this->inventarioVista = InventarioRuta::where('ruta_id', $rutaId)
                ->with(['producto', 'bodega'])
                ->get()
                ->map(fn($item) => [
                    'producto' => $item->producto->nombre ?? '-',
                    'bodega' => $item->bodega->nombre ?? '-',
                    'cantidad' => $item->cantidad,
                ])
                ->toArray();
        }
    }

    public function iniciarPedido($rutaId)
    {
        try {
            $usuarioId = Auth::id();

            $ruta = Ruta::with('conductores')->findOrFail($rutaId);
            $pivot = $ruta->conductores->firstWhere('id', $usuarioId)?->pivot;

            if (!$pivot || !$pivot->aprobada) {
                PendingToast::create()
                    ->error()
                    ->message('Debes aprobar la ruta antes de realizar pedidos.')
                    ->duration(5000);
                return;
            }

            $this->pedidoRutaId = $rutaId;
            $this->modalPedido  = true;
            $this->tipoPago     = 'contado';
            $this->montoPagado  = null;

            // 1) Consultamos inventario de la ruta
            $inventario = InventarioRuta::where('ruta_id', $rutaId)
                ->with(['producto', 'bodega'])
                ->get();

            // 2) Mapeamos cada item, incluyendo PRECIO BASE y LISTAS disponibles
            $this->productosDisponibles = $inventario->map(function ($item) {
                // Precio normal (base)
                $precioBase = floatval($item->producto->precio ?? 0);

                // Todas las listas para este producto
                $listas = PrecioProducto::where('producto_id', $item->producto_id)
                    ->orderBy('nombre')
                    ->pluck('nombre', 'id')
                    ->toArray();
                // Ej: [ 7 => 'Mayorista', 9 => 'Distribuidor', ... ]

                return [
                    'producto_id'         => $item->producto_id,
                    'bodega_id'           => $item->bodega_id,
                    'nombre'              => $item->producto->nombre ?? '-',
                    'bodega'              => $item->bodega->nombre ?? '-',
                    'cantidad_disponible' => $item->cantidad,
                    'precio_base'         => $precioBase,
                    'precio_unitario'     => $precioBase,
                    'listas_disponibles'  => $listas,
                ];
            })->toArray();

            $this->pedidoItems    = [];
            $this->buscarProducto = '';

            PendingToast::create()
                ->success()
                ->message('Pedido iniciado correctamente. Puedes seleccionar productos.')
                ->duration(4000);
        } catch (\Throwable $e) {
            Log::error('Error al iniciar pedido', [
                'ruta_id'  => $rutaId,
                'user_id'  => Auth::id(),
                'message'  => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al iniciar el pedido: ' . $e->getMessage())
                ->duration(8000);

            $this->productosDisponibles = [];
            $this->pedidoItems = [];
        }
    }

    public function agregarProducto($productoId, $bodegaId, $esDevolucion = false)
    {
        try {
            $tipo = $esDevolucion ? 'dev' : 'normal';
            $clave = "{$productoId}_{$bodegaId}_{$tipo}";

            // Buscamos en productosDisponibles
            $producto = collect($this->productosDisponibles)
                ->first(
                    fn($item) =>
                    $item['producto_id'] == $productoId
                        && $item['bodega_id'] == $bodegaId
                );

            if (! $producto) {
                PendingToast::create()
                    ->error()
                    ->message('Producto no encontrado en el inventario disponible.')
                    ->duration(4000);
                return;
            }

            $stockDisponible = $producto['cantidad_disponible'];

            // Sumamos cantidades ya en pedidoItems (normal + devoluciones)
            $cantidadNormal    = $this->pedidoItems["{$productoId}_{$bodegaId}_normal"]['cantidad'] ?? 0;
            $cantidadDevolucion = $this->pedidoItems["{$productoId}_{$bodegaId}_dev"]['cantidad'] ?? 0;
            $totalUsado        = $cantidadNormal + $cantidadDevolucion;

            if ($totalUsado >= $stockDisponible) {
                PendingToast::create()
                    ->warning()
                    ->message('Ya has usado todo el stock disponible entre pedido y devolución.')
                    ->duration(4000);
                return;
            }

            // Si no existe la clave, la creamos; si existe, incrementamos cantidad
            if (! isset($this->pedidoItems[$clave])) {
                $this->pedidoItems[$clave] = [
                    'producto_id'         => $productoId,
                    'bodega_id'           => $bodegaId,
                    'nombre'              => $producto['nombre'],
                    'bodega'              => $producto['bodega'],
                    'cantidad_disponible' => $stockDisponible,
                    'cantidad'            => 1,
                    'precio_base'         => $producto['precio_base'],
                    'precio_unitario'     => $producto['precio_base'],
                    'precio_lista_id'     => null,
                    'listas_disponibles'  => $producto['listas_disponibles'],
                    'tipo'                => $tipo,
                    'es_devolucion'       => $esDevolucion,
                ];
            } else {
                $this->pedidoItems[$clave]['cantidad'] += 1;
            }
        } catch (\Throwable $e) {
            Log::error('Error al agregar producto al pedido', [
                'producto_id' => $productoId,
                'bodega_id'   => $bodegaId,
                'user_id'     => Auth::id(),
                'message'     => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Ocurrió un error al agregar el producto: ' . $e->getMessage())
                ->duration(7000);
        }
    }

    public function actualizarPrecioUnitario(string $clave)
    {
        // 1) Compruebo que el ítem exista
        if (! isset($this->pedidoItems[$clave])) {
            return;
        }

        $item = $this->pedidoItems[$clave];

        // 2) Si no hay lista seleccionada (valor null/''), vuelvo a precio base
        if (empty($item['precio_lista_id'])) {
            $this->pedidoItems[$clave]['precio_unitario'] = $item['precio_base'];
            return;
        }

        // 3) Busco el precio de la lista en la BD
        $precioLista = PrecioProducto::find($item['precio_lista_id']);
        if ($precioLista && $precioLista->producto_id == $item['producto_id']) {
            $this->pedidoItems[$clave]['precio_unitario'] = floatval($precioLista->valor);
        } else {
            // Si no coincide o no existe, vuelvo a precio base y limpio el ID de la lista
            $this->pedidoItems[$clave]['precio_unitario']   = $item['precio_base'];
            $this->pedidoItems[$clave]['precio_lista_id'] = null;
        }
    }


    public function getPedidoSeleccionadoProperty()
    {
        return collect($this->pedidoItems)->filter(fn($p) => empty($p['es_devolucion']));
    }

    public function getDevolucionesSeleccionadasProperty()
    {
        return collect($this->pedidoItems)->filter(fn($p) => !empty($p['es_devolucion']));
    }

    public function restarCantidad($productoId, $bodegaId, $esDevolucion = false)
    {
        try {
            $tipo = $esDevolucion ? 'dev' : 'normal';
            $clave = "{$productoId}_{$bodegaId}_{$tipo}";

            if (!isset($this->pedidoItems[$clave])) {
                PendingToast::create()
                    ->error()
                    ->message('Este producto no está en el pedido.')
                    ->duration(4000);
                return;
            }

            $this->pedidoItems[$clave]['cantidad'] -= 1;

            if ($this->pedidoItems[$clave]['cantidad'] <= 0) {
                unset($this->pedidoItems[$clave]);

                PendingToast::create()
                    ->info()
                    ->message($esDevolucion ? 'Producto quitado de la devolución.' : 'Producto quitado del pedido.')
                    ->duration(4000);
            }
        } catch (\Throwable $e) {
            Log::error('Error al restar cantidad', [
                'producto_id' => $productoId,
                'bodega_id' => $bodegaId,
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al quitar el producto: ' . $e->getMessage())
                ->duration(6000);
        }
    }


    public function guardarPedido()
    {
        try {
            // 1) Asegurarnos de que haya ítems
            if (empty($this->pedidoItems)) {
                PendingToast::create()
                    ->error()
                    ->message('Debes agregar al menos un producto al pedido.')
                    ->duration(5000);
                return;
            }

            // 2) Separar “normales” vs “devolución”
            $itemsNormales   = collect($this->pedidoItems)
                ->filter(fn($i) => ($i['tipo'] ?? 'normal') === 'normal');
            $itemsDevolucion = collect($this->pedidoItems)
                ->filter(fn($i) => ($i['tipo'] ?? 'normal') === 'devolucion');

            // 3) Reglas básicas de validación
            $rules = [
                'socioNegocioId' => 'required|exists:socio_negocios,id',
                'tipoPago'       => 'required|in:contado,credito,transferencia',
                'montoPagado'    => in_array($this->tipoPago, ['contado', 'transferencia'])
                    && $itemsNormales->isNotEmpty()
                    ? 'required|numeric|min:0'
                    : 'nullable',
            ];

            // 4) Validar cantidades contra stock
            foreach ($this->pedidoItems as $clave => $item) {
                $stock = $item['cantidad_disponible'] ?? 0;
                $rules["pedidoItems.{$clave}.cantidad"] = "required|integer|min:1|max:{$stock}";
            }

            $this->validate($rules);

            // 5) Calcular total de normales
            $total = $itemsNormales->sum(fn($p) => $p['cantidad'] * $p['precio_unitario']);

            // 6) Verificar pago contado / transferencia
            if (
                in_array($this->tipoPago, ['contado', 'transferencia'])
                && $this->montoPagado < $total
            ) {
                PendingToast::create()
                    ->error()
                    ->message(
                        "El monto pagado (" . number_format($this->montoPagado, 0, ',', '.') . ") " .
                            "es inferior al total (" . number_format($total, 0, ',', '.') . ")."
                    )
                    ->duration(5000);
                return;
            }

            // 7) Crear pedido normal
            $pedido = null;
            if ($itemsNormales->isNotEmpty()) {
                $pedido = Pedido::create([
                    'ruta_id'          => $this->pedidoRutaId,
                    'socio_negocio_id' => $this->socioNegocioId,
                    'fecha'            => now(),
                    'user_id'          => Auth::id(),
                    'tipo_pago'        => $this->tipoPago,
                    // Aceptar 'contado' o 'transferencia' como pago inmediato
                    'valor_pagado'     => in_array($this->tipoPago, ['contado', 'transferencia'])
                        ? $this->montoPagado
                        : null,
                    'valor_credito'    => $this->tipoPago === 'credito' ? $total : null,
                ]);

                // 8) Pago automático para contado o transferencia
                if (
                    in_array($this->tipoPago, ['contado', 'transferencia'])
                    && $this->montoPagado > 0
                ) {
                    Pago::create([
                        'pedido_id'        => $pedido->id,
                        'socio_negocio_id' => $pedido->socio_negocio_id,
                        'monto'            => $this->montoPagado,
                        'fecha'            => now(),
                        'metodo_pago'      => $this->tipoPago,
                        'observaciones'    => 'Pago automático al crear pedido ' . $this->tipoPago,
                      'user_id'          => Auth::id(),
                    ]);
                }

                // 9) Detalles y ajuste de inventario
                foreach ($itemsNormales as $item) {
                    PedidoDetalle::create([
                        'pedido_id'       => $pedido->id,
                        'producto_id'     => $item['producto_id'],
                        'bodega_id'       => $item['bodega_id'],
                        'cantidad'        => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'],
                        'precio_lista_id' => $item['precio_lista_id'],
                    ]);

                    InventarioRuta::where('ruta_id', $this->pedidoRutaId)
                        ->where('producto_id', $item['producto_id'])
                        ->where('bodega_id', $item['bodega_id'])
                        ->decrement('cantidad', $item['cantidad']);
                }

                // 10) Salida de mercancía normal
                $salidaPedido = SalidaMercancia::create([
                    'ruta_id'          => $pedido->ruta_id,
                    'user_id'          => Auth::id(),
                    'socio_negocio_id' => $pedido->socio_negocio_id,
                    'fecha'            => now(),
                    'observaciones'    => 'Salida generada desde pedido #' . $pedido->id,
                ]);
                foreach ($itemsNormales as $item) {
                    SalidaMercanciaDetalle::create([
                        'salida_mercancia_id' => $salidaPedido->id,
                        'producto_id'         => $item['producto_id'],
                        'bodega_id'           => $item['bodega_id'],
                        'cantidad'            => $item['cantidad'],
                    ]);
                }
            }

            // 11) Registrar devoluciones si las hay (igual que antes)
            if ($itemsDevolucion->isNotEmpty()) {
                $devolucion = \App\Models\Devoluciones\Devolucion::create([
                    'ruta_id'          => $this->pedidoRutaId,
                    'socio_negocio_id' => $this->socioNegocioId,
                    'fecha'            => now(),
                    'user_id'          => Auth::id(),
                    'observaciones'    => 'Devolución desde pedido #' . ($pedido->id ?? '—'),
                ]);

                foreach ($itemsDevolucion as $item) {
                    \App\Models\Devoluciones\DevolucionDetalle::create([
                        'devolucion_id'   => $devolucion->id,
                        'producto_id'     => $item['producto_id'],
                        'bodega_id'       => $item['bodega_id'],
                        'cantidad'        => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'],
                    ]);

                    InventarioRuta::where('ruta_id', $this->pedidoRutaId)
                        ->where('producto_id', $item['producto_id'])
                        ->where('bodega_id', $item['bodega_id'])
                        ->decrement('cantidad', $item['cantidad']);
                }

                $salidaDev = SalidaMercancia::create([
                    'ruta_id'          => $this->pedidoRutaId,
                    'user_id'          => Auth::id(),
                    'socio_negocio_id' => $this->socioNegocioId,
                    'fecha'            => now(),
                    'observaciones'    => 'Salida de devolución desde pedido',
                ]);
                foreach ($itemsDevolucion as $item) {
                    SalidaMercanciaDetalle::create([
                        'salida_mercancia_id' => $salidaDev->id,
                        'producto_id'         => $item['producto_id'],
                        'bodega_id'           => $item['bodega_id'],
                        'cantidad'            => $item['cantidad'],
                    ]);
                }
            }

            // 12) Reset y recarga de clientes
            $this->reset([
                'modalPedido',
                'pedidoRutaId',
                'pedidoItems',
                'socioNegocioId',
                'busquedaCliente',
                'buscarProducto',
                'tipoPago',
                'montoPagado',
            ]);
            $this->clientesFiltrados = SocioNegocio::orderBy('razon_social')->get();

            // 13) Toast de éxito
            PendingToast::create()
                ->success()
                ->message('Pedido, devolución y salidas registradas correctamente.')
                ->duration(6000);
        } catch (\Throwable $e) {
            Log::error('Error al guardar pedido', [
                'user_id' => Auth::id(),
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al guardar el pedido: ' . $e->getMessage())
                ->duration(8000);
        }
    }

    public function cancelarItem($detalleId)
    {
        try {
            $detalle = \App\Models\Pedidos\PedidoDetalle::find($detalleId);

            if (! $detalle) {
                \Masmerise\Toaster\Toaster::error('Detalle no encontrado.');
                return;
            }

            if ($detalle->pedido->estado === 'cancelado') {
                \Masmerise\Toaster\Toaster::info('El pedido ya está cancelado. No se puede modificar.');
                return;
            }

            if ($detalle->estado === 'cancelado') {
                \Masmerise\Toaster\Toaster::info('Este producto ya fue cancelado anteriormente.');
                return;
            }

            // Devolver al inventario de la ruta
            $inventario = \App\Models\InventarioRuta\InventarioRuta::where('ruta_id', $detalle->pedido->ruta_id)
                ->where('producto_id', $detalle->producto_id)
                ->first();

            if ($inventario) {
                $inventario->increment('cantidad', $detalle->cantidad);
            }

            // Marcar detalle como cancelado en vez de eliminar
            $detalle->estado = 'cancelado';
            $detalle->save();

            \Masmerise\Toaster\Toaster::success('Producto cancelado y devuelto al inventario de la ruta.');

            // Refrescar el pedido
            $this->pedidoFactura = $this->pedidoFactura->fresh('detalles.producto');
        } catch (\Throwable $e) {
            \Masmerise\Toaster\Toaster::error('Error al cancelar producto: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Error cancelarItem', ['error' => $e]);
        }
    }

    public function cancelarPedidoCompleto()
    {
        try {
            if (! $this->pedidoFactura) {
                \Masmerise\Toaster\Toaster::error('Pedido no encontrado.');
                return;
            }

            if ($this->pedidoFactura->estado === 'cancelado') {
                \Masmerise\Toaster\Toaster::info('El pedido ya fue cancelado previamente.');
                return;
            }

            foreach ($this->pedidoFactura->detalles as $detalle) {
                if ($detalle->estado !== 'cancelado') {
                    \App\Models\InventarioRuta\InventarioRuta::where('ruta_id', $this->pedidoFactura->ruta_id)
                        ->where('producto_id', $detalle->producto_id)
                        ->increment('cantidad', $detalle->cantidad);

                    $detalle->estado = 'cancelado';
                    $detalle->save();
                }
            }

            $this->pedidoFactura->estado = 'cancelado';
            $this->pedidoFactura->save();

            $this->mostrarFactura = false;

            \Masmerise\Toaster\Toaster::success('Orden de venta cancelada exitosamente.');
        } catch (\Throwable $e) {
            \Masmerise\Toaster\Toaster::error('Error al cancelar el pedido: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Error cancelarPedidoCompleto', ['error' => $e]);
        }
    }







    public function guardarGasto()
    {
        try {
            $this->validate([
                'tipoGastoId' => 'required|exists:tipos_gasto,id',
                'montoGasto' => 'required|numeric|min:0.01',
            ]);

            GastoRuta::create([
                'ruta_id' => $this->rutaGastoId,
                'user_id' => Auth::id(),
                'tipo_gasto_id' => $this->tipoGastoId,
                'monto' => $this->montoGasto,
                'observacion' => $this->observacionGasto,
            ]);

            $this->reset(['modalGasto', 'rutaGastoId', 'tipoGastoId', 'montoGasto', 'observacionGasto']);

            PendingToast::create()
                ->success()
                ->message('Gasto registrado exitosamente.')
                ->duration(5000);
        } catch (\Throwable $e) {
            Log::error('Error al registrar gasto en ruta', [
                'user_id' => Auth::id(),
                'ruta_id' => $this->rutaGastoId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al registrar el gasto: ' . $e->getMessage())
                ->duration(8000);
        }
    }


    #[\Livewire\Attributes\On('abrirModalGasto')]
    public function abrirModalGasto($rutaId)
    {
        $this->rutaGastoId = $rutaId;
        $this->modalGasto = true;

        // Inicializa correctamente las variables relacionadas con el formulario
        $this->tipoGastoId = null;
        $this->montoGasto = null;
        $this->observacionGasto = '';
    }

    public function confirmarDevolverInventario($rutaId)
    {
        $this->rutaADevolver = $rutaId;
        $this->confirmarDevolucion = true;
    }
public function devolverInventario($rutaId)
{
    try {
        $inventarios = InventarioRuta::where('ruta_id', $rutaId)->get();

        foreach ($inventarios as $inv) {
            if ($inv->cantidad > 0) {
                $devuelto = $inv->cantidad;

                // Aumentar stock en la bodega
                $pb = ProductoBodega::where('producto_id', $inv->producto_id)
                    ->where('bodega_id', $inv->bodega_id)
                    ->first();

                if ($pb) {
                    $pb->increment('stock', $devuelto);
                }

                // Registrar devolución
                $inv->cantidad_devuelta += $devuelto;
                $inv->cantidad = 0;
                $inv->save();
            }
        }

        PendingToast::create()
            ->success()
            ->message('Inventario devuelto correctamente.')
            ->duration(4000);
    } catch (\Throwable $e) {
        Log::error('Error al devolver inventario de ruta', [
            'ruta_id' => $rutaId,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        PendingToast::create()
            ->error()
            ->message('Ocurrió un error al devolver el inventario.')
            ->duration(8000);
    }
}



    public function aprobarRuta($rutaId)
    {
        try {
            $usuarioId = Auth::id();

            $ruta = Ruta::findOrFail($rutaId);
            $ruta->conductores()->updateExistingPivot($usuarioId, ['aprobada' => true]);

            PendingToast::create()
                ->success()
                ->message('Ruta aprobada correctamente.')
                ->duration(5000);

            $this->mount();
        } catch (\Throwable $e) {
            Log::error('Error al aprobar la ruta', [
                'user_id' => Auth::id(),
                'ruta_id' => $rutaId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            PendingToast::create()
                ->error()
                ->message('Error al aprobar la ruta: ' . $e->getMessage())
                ->duration(8000);
        }
    }

    public function render()
    {
        return view('livewire.ruta-disponibles-conductor.rutas-disponibles-conductor', [
            'productosFiltrados' => $this->productosFiltrados,
        ]);
    }

    public function getProductosFiltradosProperty()
    {
        if (empty($this->productosDisponibles)) {
            return collect();
        }

        $productos = collect($this->productosDisponibles);
        $texto = trim($this->buscarProducto);

        if (strlen($texto) === 0) {
            return $productos;
        }

        $busqueda = strtolower($texto);

        $filtrados = $productos->filter(function ($producto) use ($busqueda) {
            $nombre  = strtolower($producto['nombre'] ?? '');
            $bodega  = strtolower($producto['bodega'] ?? '');
            return str_contains($nombre, $busqueda)
                || str_contains($bodega, $busqueda);
        });

        return $filtrados->values();
    }





    public function mostrarModalGasto($rutaId)
    {
        $this->dispatch('abrirModalGasto', $rutaId);
    }
    public function imprimirFactura($pedidoId)
    {
        $this->pedidoFactura = Pedido::with(['detalles.producto', 'socioNegocio', 'ruta'])
            ->findOrFail($pedidoId);

        $this->mostrarFactura = true;
    }

    public function imprimirDevolucion($devolucionId)
    {
        $this->devolucionVista = \App\Models\Devoluciones\Devolucion::with([
            'detalles.producto',
            'socioNegocio',
            'ruta',
        ])->findOrFail($devolucionId);

        $this->mostrarDevolucion = true;
    }

public $clienteSeleccionado;
public function updatedSocioNegocioId($value)
{
    if ($value) {
        $this->clienteSeleccionado = \App\Models\SocioNegocio\SocioNegocio::find($value);

        $this->pedidosSocio = $this->clienteSeleccionado->pedidos()
            ->where('tipo_pago', 'credito')
            ->with(['detalles', 'pagos', 'ruta', 'usuario']) // 🔄 Carga relaciones necesarias
            ->get()
            ->filter(function ($pedido) {
                return $pedido->montoPendiente() > 0;
            })
            ->map(function ($pedido) {
                $pendiente = $pedido->montoPendiente();

                return [
                    'id'              => $pedido->id,
                    'fecha'           => $pedido->fecha->format('d/m/Y'),
                    'ruta'            => optional($pedido->ruta)->nombre ?? '—',
                    'usuario'         => optional($pedido->usuario)->name ?? '—',
                    'total'           => number_format($pendiente, 0, ',', '.'),
                    'monto_pendiente' => $pendiente, // 👉 se usa para mostrar botón de pago
                ];
            })
            ->values();
    } else {
        $this->clienteSeleccionado = null;
        $this->pedidosSocio = [];
    }
}


    public function cargarClientesConDeuda()
    {
        $this->clientesConCredito = SocioNegocio::with('creditosPendientes')
            ->whereHas('creditosPendientes', function ($q) {
                $q->where('estado', 'pendiente');
            })
            ->get();
    }


    public function agregarProductoDesdeSelect($valor)
    {
        // $valor viene como "productoId_bodegaId" (por ejemplo "123_5")
        [$productoId, $bodegaId] = explode('_', $valor);

        // Busco en la colección de productosDisponibles (que ya trae precio_base y listas_disponibles)
        $producto = collect($this->productosDisponibles)->first(function ($p) use ($productoId, $bodegaId) {
            return $p['producto_id'] == $productoId && $p['bodega_id'] == $bodegaId;
        });

        if (! $producto) {
            PendingToast::create()
                ->error()
                ->message('Producto no encontrado en el inventario disponible.')
                ->duration(4000);
            return;
        }

        // Construyo una clave única para este producto+bodega en modo normal
        // (si necesitas manejar devoluciones, podrías incluir "_dev" en la clave)
        $clave = "{$productoId}_{$bodegaId}_normal";

        // Si ya existe en pedidoItems, solo dejo que luego modifique la cantidad en la tabla
        if (! isset($this->pedidoItems[$clave])) {
            $this->pedidoItems[$clave] = [
                'producto_id'         => $producto['producto_id'],
                'bodega_id'           => $producto['bodega_id'],
                'nombre'              => $producto['nombre'],
                'bodega'              => $producto['bodega'],
                'cantidad_disponible' => $producto['cantidad_disponible'],
                'cantidad'            => 1,                                 // Arranco con 1 unidad
                'precio_base'         => $producto['precio_base'],         // ¡Clave que faltaba!
                'precio_unitario'     => $producto['precio_base'],         // Inicial igual al base
                'precio_lista_id'     => null,                              // Sin lista aplicada
                'listas_disponibles'  => $producto['listas_disponibles'],   // Array [ id => 'Nombre Lista' ]
                'tipo'                => 'normal',
                'es_devolucion'       => false,
            ];
        }

        // Limpio el input de búsqueda
        $this->busquedaProducto = '';
    }





    public function eliminarProducto($clave)
    {
        unset($this->pedidoItems[$clave]);
    }


    public function agregarProductoDesdeNombre()
    {
        $texto = trim($this->nuevoProductoNombre);

        // Buscamos coincidencia exacta en "Nombre (Bodega)"
        $encontrado = collect($this->productosDisponibles)->first(function ($p) use ($texto) {
            return strtolower("{$p['nombre']} ({$p['bodega']})") === strtolower($texto);
        });

        if (! $encontrado) {
            $this->nuevoProductoId = null;
            $this->addError('nuevoProductoId', 'El producto no es válido.');
            return;
        }

        $productoId = $encontrado['producto_id'];
        $bodegaId   = $encontrado['bodega_id'];
        $clave      = "{$productoId}_{$bodegaId}_normal";

        if (! isset($this->pedidoItems[$clave])) {
            $this->pedidoItems[$clave] = [
                'producto_id'         => $encontrado['producto_id'],
                'bodega_id'           => $encontrado['bodega_id'],
                'nombre'              => $encontrado['nombre'],
                'bodega'              => $encontrado['bodega'],
                'cantidad_disponible' => $encontrado['cantidad_disponible'],
                'cantidad'            => 1,                                   // Empiezo con 1
                'precio_base'         => $encontrado['precio_unitario'],     // Ahora sí defino precio_base
                'precio_unitario'     => $encontrado['precio_unitario'],     // Inicial igual al precio normal
                'precio_lista_id'     => null,
                'listas_disponibles'  => PrecioProducto::where('producto_id', $encontrado['producto_id'])
                    ->orderBy('nombre')
                    ->pluck('nombre', 'id')
                    ->toArray(),
                'tipo'                => 'normal',
                'es_devolucion'       => false,
            ];
        } else {
            // Si ya estaba en pedidoItems, dejamos que el usuario modifique la cantidad/metadatos
            $this->pedidoItems[$clave]['cantidad'] = 1;
        }

        // Reseteo el input
        $this->nuevoProductoNombre = '';
        $this->nuevoProductoId     = null;
        $this->resetErrorBag('nuevoProductoId');
    }


    public function seleccionarClienteDesdeNombre()
    {
        $texto = trim($this->nuevoClienteTexto);

        // Buscamos coincidencia exacta en “razon_social (NIT)”
        $encontrado = SocioNegocio::all()->first(function ($c) use ($texto) {
            return strtolower("{$c->razon_social} ({$c->nit})") === strtolower($texto);
        });

        if (! $encontrado) {
            $this->nuevoClienteId = null;
            $this->addError('nuevoClienteId', 'Cliente no válido.');
            return;
        }

        $this->nuevoClienteId   = $encontrado->id;
        $this->socioNegocioId   = $encontrado->id;
        $this->busquedaCliente  = $encontrado->razon_social;
        $this->resetErrorBag('nuevoClienteId');
    }



public function getPedidosConDeudaProperty()
{
    $usuarioId = Auth::id();

    // Obtener las rutas asignadas al conductor actual
    $rutasIds = Ruta::whereHas('conductores', fn($q) => $q->where('users.id', $usuarioId))
        ->pluck('id');

    // Pedidos a crédito de hoy, creados por el usuario actual en sus rutas asignadas
    return Pedido::with(['socioNegocio', 'pagos', 'usuario'])
        ->whereIn('ruta_id', $rutasIds)
        ->where('user_id', $usuarioId)
        ->where('tipo_pago', 'credito')
        ->whereDate('fecha', Carbon::today()) // 🔴 Filtro por día actual
        ->get()
        ->filter(fn($pedido) => $pedido->montoPendiente() > 0)
        ->values();
}



    public function getDevolucionesRealizadasProperty()
    {
        $usuarioId = Auth::id();

        return \App\Models\Devoluciones\Devolucion::with(['detalles.producto', 'socioNegocio'])
            ->where('user_id', $usuarioId)
            ->whereDate('fecha', Carbon::today())
            ->orderByDesc('fecha')
            ->get();
    }

    public function abrirModalPago($pedidoId)
    {
        $this->pedidoPagoId = $pedidoId;
        $this->modalPago = true;
        $this->montoPago = null;
    }

   public function registrarPago()
{
    $this->validate([
        'montoPago' => 'required|numeric|min:1',
    ]);

    $pedido = Pedido::with('detalles')->findOrFail($this->pedidoPagoId);

    $totalPedido = $pedido->detalles
        ->sum(fn($detalle) => $detalle->cantidad * $detalle->precio_unitario);

    if ($pedido->tipo_pago === 'contado' && $this->montoPago < $totalPedido) {
        PendingToast::create()
            ->error()
            ->message("El monto ingresado (" . number_format($this->montoPago, 0, ',', '.') . 
                ") es inferior al total del pedido (" . number_format($totalPedido, 0, ',', '.') . ").")
            ->duration(5000);
        return;
    }

   Pago::create([
    'pedido_id'        => $pedido->id,
    'socio_negocio_id' => $pedido->socio_negocio_id,
    'monto'            => $this->montoPago,
    'fecha'            => now(),
    'metodo_pago'      => $this->metodoPago,
    'observaciones'    => 'Pago registrado desde vista conductor',
'user_id' => Auth::id(),

]);


$this->reset(['modalPago', 'pedidoPagoId', 'montoPago', 'metodoPago']);


    // 🔁 Refrescar datos según el tab
    if ($this->tabActivo === 'deudasgenerales' && $this->socioNegocioId) {
        $this->updatedSocioNegocioId($this->socioNegocioId);
    } elseif ($this->tabActivo === 'deudas') {
        $this->getPedidosConDeudaProperty(); // recalcula en render
    }

    // 🔍 Calcular saldo restante
    $pagadoTotal = $pedido->pagos()->sum('monto');
    $pendiente   = $totalPedido - $pagadoTotal;

    PendingToast::create()
        ->success()
        ->message('Pago registrado. Saldo pendiente: $' . number_format($pendiente, 0, ',', '.'))
        ->duration(6000);
}

}
