

    <div class="p-10 bg-gradient-to-br from-violet-200 via-white to-purple-100 dark:from-gray-900 dark:via-gray-800 dark:to-black rounded-3xl shadow-2xl space-y-12">

        {{-- Encabezado --}}
    <header class="relative text-center py-4 px-4 bg-gradient-to-r from-gray-100 to-gray-300 rounded-3xl shadow-md">
        <div class="flex flex-col items-center space-y-1">
            <div class="bg-gray-800 text-white p-2 rounded-full shadow">
                <i class="fas fa-truck-loading text-xl animate-bounce"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 tracking-wide">
                Mis Rutas Asignadas
            </h2>
            <p class="text-xs text-gray-600 tracking-wide">
                {{ \Carbon\Carbon::now('America/Bogota')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
            </p>
        </div>
        <div class="absolute top-0 left-0 w-2 h-full bg-white rounded-l-3xl opacity-10"></div>
        <div class="absolute bottom-0 right-0 w-2 h-full bg-white rounded-r-3xl opacity-10"></div>
    </header>
    @if (session()->has('message'))
        @php
            $type = session('type', 'success'); // por defecto es success
            $styles = [
                'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                'error'   => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            ];
            $icons = [
                'success' => 'fas fa-check-circle',
                'info'    => 'fas fa-info-circle',
                'warning' => 'fas fa-exclamation-triangle',
                'error'   => 'fas fa-times-circle',
            ];
        @endphp

        <div class="mb-6 p-4 rounded-xl shadow-md flex items-start gap-3 {{ $styles[$type] }}">
            <i class="{{ $icons[$type] }} text-xl mt-1"></i>
            <div class="text-sm font-medium">
                {{ session('message') }}
            </div>
        </div>
    @endif

    @if($modalPedido)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 p-2 sm:p-4">
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto 
                    bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-4 sm:p-6 border dark:border-gray-700">

            {{-- ======================================================= --}}
            {{--                       ENCABEZADO                      --}}
            {{-- ======================================================= --}}
            <div class="flex justify-between items-center border-b pb-3">
                <h2 class="text-base font-bold text-gray-800 dark:text-white">
                    <i class="fas fa-clipboard-list text-green-500 mr-2"></i> Nuevo Pedido
                </h2>
                <button wire:click="$set('modalPedido', false)" class="text-gray-400 hover:text-red-500">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>

            {{-- ======================================================= --}}
            {{--                        CLIENTE                         --}}
            {{-- ======================================================= --}}
            <div class="mt-4 space-y-2" x-data="{ open: false }">
                
            <div class="mt-4">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Cliente</label>

                    <div wire:ignore x-data x-init="
                        tom = new TomSelect($refs.select, {
                        placeholder: 'Selecciona un cliente...',
                        allowEmptyOption: true,
                        create: false,
                        onChange: value => @this.set('socioNegocioId', value),
                        });
                        // Si Livewire actualiza la lista, recargamos el control
                        Livewire.hook('message.processed', () => tom.refreshOptions(false));
                    ">
                        <select x-ref="select" class="w-full" >
                        <option value="">-- Selecciona un cliente --</option>
                        @foreach($clientesFiltrados as $cliente)
                            <option value="{{ $cliente->id }}">
                            {{ $cliente->razon_social }} ({{ $cliente->nit }})
                            </option>
                        @endforeach
                        </select>
                    </div>

                    @error('socioNegocioId')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                    </div>


                {{-- Solo se muestra cuando el input recibe foco --}}
                <div 
                    x-show="open" 
                    @click.outside="open = false" 
                    class="bg-white dark:bg-gray-900 border mt-1 rounded shadow text-xs max-h-40 overflow-y-auto"
                >
                    @foreach($clientesFiltrados as $cliente)
                        <button 
                            type="button" 
                            @click="
                                $wire.set('socioNegocioId', {{ $cliente->id }});
                                $wire.set('busquedaCliente', '{{ addslashes($cliente->razon_social) }}');
                                $wire.set('clientesFiltrados', []);
                                open = false;
                            "
                            class="block w-full text-left px-3 py-1 hover:bg-green-100 dark:hover:bg-green-800"
                        >
                            <span class="font-semibold">{{ $cliente->razon_social }}</span>
                            <span class="text-gray-500 text-[10px]">({{ $cliente->nit }})</span>
                        </button>
                    @endforeach

                    @if($clientesFiltrados->isEmpty())
                        <div class="px-3 py-2 text-gray-400 italic">No se encontraron resultados.</div>
                    @endif
                </div>

                @error('socioNegocioId') 
                    <span class="text-red-500 text-xs">{{ $message }}</span> 
                @enderror
            </div>

            {{-- ======================================================= --}}
            {{--                  ADVERTENCIA DE DEUDAS                 --}}
            {{-- ======================================================= --}}
            @if($clienteTieneDeudas)
                <div class="mt-2 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 p-3 rounded-xl text-xs flex flex-col gap-1 animate-fade-in">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle mt-0.5"></i>
                        <span>Este cliente tiene pedidos a crédito con saldo pendiente.</span>
                    </div>
                    <div class="text-xs font-semibold text-yellow-700 dark:text-yellow-200 pl-6">
                        Total pendiente: ${{ number_format($valorPendiente, 0, ',', '.') }}
                    </div>
                </div>
            @endif

            {{-- ======================================================= --}}
            {{--                    TABLA DE PRODUCTOS                  --}}
            {{-- ======================================================= --}}
            <div class="mt-6">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white mb-2">Productos en el pedido</h3>
            @if(count($pedidoItems) > 0)
        <div class="overflow-auto rounded border dark:border-gray-700">
            <table class="min-w-full text-xs table-auto border-collapse border dark:border-gray-700">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700 text-left">
                        <th class="p-2 border dark:border-gray-700">Producto</th>
                        <th class="p-2 border dark:border-gray-700 text-center">Cantidad</th>
                        <th class="p-2 border dark:border-gray-700 text-center">Tipo</th>
                        <th class="p-2 border dark:border-gray-700 text-center">Lista de Precio</th>
                        <th class="p-2 border dark:border-gray-700 text-right">Precio Unit.</th>
                        <th class="p-2 border dark:border-gray-700 text-right">Subtotal</th>
                        <th class="p-2 border dark:border-gray-700">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedidoItems as $clave => $item)
                        <tr>
                                    {{-- 1) Nombre del producto --}}
                                    <td class="p-2 border dark:border-gray-700">{{ $item['nombre'] }}</td>

                                    {{-- 2) Cantidad + validación de stock --}}
                                    <td class="p-2 border dark:border-gray-700 text-center">
                                        @php
                                            $cantidad   = $item['cantidad'] ?? 0;
                                            $stock      = $item['cantidad_disponible'] ?? 0;
                                            $esInvalido = $cantidad > $stock;
                                        @endphp

                                        <input
                                            type="number"
                                            min="1"
                                            wire:model.lazy="pedidoItems.{{ $clave }}.cantidad"
                                            class="w-16 text-center px-2 py-1 border rounded 
                                                dark:bg-gray-800 dark:text-white 
                                                {{ $esInvalido ? 'border-red-500 ring-1 ring-red-300' : 'border-gray-300 dark:border-gray-600' }}"
                                        >

                                        <div class="text-[10px] mt-0.5 {{ $esInvalido ? 'text-red-500 font-semibold' : 'text-gray-500' }}">
                                            {{ $esInvalido ? 'Cantidad supera el stock' : 'Stock: ' . $stock }}
                                        </div>

                                        @error("pedidoItems.{$clave}.cantidad")
                                            <span class="text-red-500 text-xs">{{ $message }}</span>
                                        @enderror
                                    </td>

                                    {{-- 3) Tipo (normal / devolucion) --}}
                                    <td class="p-2 border dark:border-gray-700 text-center">
                                        <select 
                                            wire:model.lazy="pedidoItems.{{ $clave }}.tipo"
                                            class="text-xs px-1 py-1 border rounded dark:bg-gray-800 dark:text-white"
                                        >
                                            <option value="normal">Normal</option>
                                            <option value="devolucion">Devolución</option>
                                        </select>
                                    </td>

                                    {{-- 4) LISTA DE PRECIO: select precio normal o lista --}}
                                    <td class="p-2 border dark:border-gray-700 text-center">
                                        <select
                                            wire:model.lazy="pedidoItems.{{ $clave }}.precio_lista_id"
                                            wire:change="actualizarPrecioUnitario('{{ $clave }}')"
                                            class="text-xs px-1 py-1 border rounded dark:bg-gray-800 dark:text-white"
                                        >
                                            {{-- Opción “Precio normal (valor base)” --}}
                                            <option value="">
                                                Precio normal (${{ number_format($item['precio_base'], 0, ',', '.') }})
                                            </option>

                                            {{-- Opciones de listas disponibles --}}
                                            @foreach($item['listas_disponibles'] as $listaId => $listaNombre)
                                                @php
                                                    $valorLista = number_format(
                                                        \App\Models\Productos\PrecioProducto::find($listaId)->valor ?? 0, 
                                                        0, ',', '.'
                                                    );
                                                @endphp
                                                <option value="{{ $listaId }}">
                                                    {{ $listaNombre }} (${{ $valorLista }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>

                                    {{-- 5) Precio Unitario --}}
                                    <td class="p-2 border dark:border-gray-700 text-right">
                                        ${{ number_format($item['precio_unitario'], 0, ',', '.') }}
                                    </td>

                                    {{-- 6) Subtotal = cantidad * precio_unitario --}}
                                    <td class="p-2 border dark:border-gray-700 text-right">
                                        ${{ number_format($item['cantidad'] * $item['precio_unitario'], 0, ',', '.') }}
                                    </td>

                                    {{-- 7) Acciones --}}
                                    <td class="p-2 border dark:border-gray-700 text-right">
                                        <button 
                                            wire:click="eliminarProducto('{{ $clave }}')" 
                                            class="text-red-500 hover:text-red-700"
                                        >
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-xs text-gray-500 italic mt-2">No hay productos agregados al pedido.</p>
            @endif

            </div>

            {{-- ======================================================= --}}
            {{--                AGREGAR PRODUCTO (CON DATALIST)          --}}
            {{-- ======================================================= --}}
    <div class="mt-6">
    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
        Agregar Producto
    </label>

    <div wire:ignore
        x-data
        x-init="
            productoTom = new TomSelect($refs.productSelect, {
            plugins: ['dropdown_input'],
            placeholder: 'Buscar producto...',
            allowEmptyOption: true,
            create: false,
            onChange: value => {
                if (value) {
                // <-- aquí llamamos con $wire, no con @this
                $wire.call('agregarProductoDesdeSelect', value);
                productoTom.clear();
                }
            }
            });
            Livewire.hook('message.processed', () => productoTom.refreshOptions(false));
        "
    >
        {{-- Origen de datos para TomSelect --}}
        <select x-ref="productSelect" style="display: none;">
        <option value=""></option>
        @foreach($productosDisponibles as $p)
            <option value="{{ $p['producto_id'] }}_{{ $p['bodega_id'] }}">
            {{ $p['nombre'] }} ({{ $p['bodega'] }}) — Stock: {{ $p['cantidad_disponible'] }}
            </option>
        @endforeach
        </select>
    </div>

    @error('nuevoProductoId')
        <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span>
    @enderror
    </div>



            {{-- ======================================================= --}}
            {{--                     MÉTODO DE PAGO                     --}}
            {{-- ======================================================= --}}
            <div class="mt-6">
                <label class="block text-xs font-medium">Método de Pago</label>
                <div class="flex gap-4 text-xs mt-1 flex-wrap">
                    <label><input type="radio" wire:model="tipoPago" value="contado"> Contado</label>
                    <label><input type="radio" wire:model="tipoPago" value="credito"> Crédito</label>
                    <label><input type="radio" wire:model="tipoPago" value="transferencia"> Transferencia</label>
                </div>
            </div>

          
            @if(in_array($tipoPago, ['contado','transferencia']))
                <div class="mt-2">
                    <label class="block text-xs font-medium">Monto Pagado</label>
                    <input 
                        type="number" 
                        wire:model.defer="montoPagado" 
                        step="0.01"
                        class="w-full px-3 py-1.5 rounded border border-green-400 focus:ring-green-300 
                            dark:border-gray-700 dark:bg-gray-800 dark:text-white text-xs"
                    >
                    @error('montoPagado') 
                        <span class="text-red-500 text-xs">{{ $message }}</span> 
                    @enderror
                </div>
            @endif


            {{-- ======================================================= --}}
            {{--                    TOTAL Y BOTONES                     --}}
            {{-- ======================================================= --}}
            <div class="mt-6 border-t pt-3">
                <div class="flex justify-between font-bold text-sm text-gray-800 dark:text-white">
                    <span>Total pedido:</span>
                <span>
                    $ {{ number_format(
                        collect($pedidoItems)
                            ->filter(fn($p) => ($p['tipo'] ?? 'normal') === 'normal')
                            ->sum(fn($p) => $p['cantidad'] * $p['precio_unitario']),
                        0, ',', '.'
                    ) }}
                </span>

                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button 
                        wire:click="$set('modalPedido', false)"
                        class="px-3 py-1.5 rounded bg-gray-200 hover:bg-gray-300 
                            text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white text-xs font-semibold"
                    >
                        <i class="fas fa-ban mr-1"></i> Cancelar
                    </button>
                    <button 
                        wire:click="guardarPedido"
                        class="px-4 py-1.5 rounded bg-gradient-to-r from-green-600 to-emerald-600 
                            hover:from-green-700 hover:to-emerald-700 text-white text-xs font-semibold"
                    >
                        <i class="fas fa-paper-plane mr-1"></i> Guardar
                    </button>
                </div>
            </div>

        </div>
    </div>
    @endif
    
@if($mostrarFactura && $pedidoFactura)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-auto">
    <div class="print-factura bg-white p-6 rounded-xl shadow-xl w-full max-w-md text-sm relative max-h-[90vh] overflow-y-auto">

        {{-- 🔴 Botón cerrar --}}
        <button wire:click="$set('mostrarFactura', false)" class="absolute top-2 right-2 text-red-500 no-print">
            <i class="fas fa-times-circle"></i>
        </button>

        {{-- 🟣 Encabezado --}}
        <div class="text-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Factura Pedido #{{ $pedidoFactura->id }}</h2>
            <p class="text-gray-800 text-base font-bold">
                Fecha: {{ $pedidoFactura->fecha->format('d/m/Y') }}
            </p>

        </div>

        {{-- 🔵 Datos del cliente --}}
<div class="mb-4 text-base font-bold text-gray-800">
            <p><strong>Cliente:</strong> {{ $pedidoFactura->socioNegocio->razon_social }}</p>
            <p><strong>Ruta:</strong> {{ $pedidoFactura->ruta->ruta }}</p>
            <p><strong>Condición de Pago:</strong> {{ ucfirst($pedidoFactura->tipo_pago) }}</p>
        </div>

        {{-- 🟡 Calcular total excluyendo cancelados --}}
        @php
            $total = $pedidoFactura->detalles
                ->where('estado', '!=', 'cancelado')
                ->sum(fn($d) => $d->cantidad * $d->precio_unitario);
        @endphp

        {{-- 🟢 Tabla de productos con scroll --}}
        <div class="border rounded mb-2">
            <div class="max-h-64 overflow-y-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-200 text-gray-800 sticky top-0 z-10">
                        <tr>
                            <th class="p-2 text-left">Producto</th>
                            <th class="p-2 text-right">Cant</th>
                            <th class="p-2 text-right">Precio</th>
                            <th class="p-2 text-right">Subtotal</th>
                            <th class="p-2 text-center no-print">Cancelar</th>
                        </tr>
                    </thead>
                    <tbody>
    @foreach($pedidoFactura->detalles as $detalle)
        @if($detalle->estado === 'cancelado')
            {{-- 🔴 Línea cancelada --}}
            <tr class="border-t bg-red-50 text-red-500 line-through italic no-print">
                <td class="p-2 font-bold">
                    {{ $detalle->producto->nombre ?? 'Producto' }}
                    <span class="text-xs text-red-600">(Cancelado)</span>
                </td>
                <td class="p-2 text-right font-bold">{{ $detalle->cantidad }}</td>
                <td class="p-2 text-right font-bold">
                    ${{ number_format($detalle->precio_unitario, 0, ',', '.') }}
                </td>
                <td class="p-2 text-right font-bold">
                    ${{ number_format($detalle->precio_unitario * $detalle->cantidad, 0, ',', '.') }}
                </td>
                <td class="p-2 text-center">
                    <i class="fas fa-ban text-gray-400" title="Producto cancelado"></i>
                </td>
            </tr>
        @else
            {{-- 🟢 Línea normal --}}
            <tr class="border-t">
                <td class="p-2 font-bold">{{ $detalle->producto->nombre ?? 'Producto' }}</td>
                <td class="p-2 text-right font-bold">{{ $detalle->cantidad }}</td>
                <td class="p-2 text-right font-bold">
                    ${{ number_format($detalle->precio_unitario, 0, ',', '.') }}
                </td>
                <td class="p-2 text-right font-bold">
                    ${{ number_format($detalle->precio_unitario * $detalle->cantidad, 0, ',', '.') }}
                </td>
                <td class="p-2 text-center no-print">
                    <button wire:click="cancelarItem({{ $detalle->id }})"
                            class="text-red-500 hover:text-red-700"
                            title="Cancelar este producto">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </td>
            </tr>
        @endif
    @endforeach
</tbody>

                </table>
            </div>
        </div>

        {{-- 🧮 Total --}}
        <div class="text-right text-sm font-bold text-gray-800 border-t pt-2">
            Total: ${{ number_format($total, 0, ',', '.') }}
        </div>

        {{-- 💬 Agradecimiento --}}
    <div class="agradecimiento mt-4 text-center text-xl font-extrabold text-gray-800 print:text-center">
    ¡Gracias por su compra!
</div>


        {{-- ⚙️ Acciones --}}
        <div class="mt-4 flex justify-between no-print">
            <button wire:click="cancelarPedidoCompleto"
                    class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded text-xs shadow">
                <i class="fas fa-ban mr-1"></i> Cancelar Pedido
            </button>

            <button onclick="window.print()"
                    class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs shadow">
                <i class="fas fa-print mr-1"></i> Imprimir
            </button>
        </div>
    </div>
</div>
@endif





    @if($rutas->isEmpty())
        <div class="text-center text-lg text-gray-500 dark:text-gray-400 italic mt-8 animate-fade-in">
            💤 No tienes rutas asignadas para hoy.
        </div>
    @else
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 animate-fade-in-up">

        {{-- COLUMNA IZQUIERDA: TARJETAS DE RUTA --}}
        <div class="space-y-10">
            @foreach($rutas as $ruta)
                <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 rounded-3xl shadow-2xl p-6 hover:shadow-violet-500/40 transform transition duration-300 hover:-translate-y-1 hover:scale-[1.02] flex flex-col justify-between space-y-6">
                    {{-- ENCABEZADO --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="bg-violet-200 dark:bg-violet-700 p-3 rounded-full shadow ring-2 ring-violet-400/30">
                                <i class="fas fa-truck text-violet-700 dark:text-white text-2xl animate-pulse"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white">Ruta</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Vehículo: <span class="font-semibold">{{ $ruta->vehiculo->placa ?? '-' }}</span>
                                </p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ \Carbon\Carbon::parse($ruta->fecha_salida)->format('d/m/Y') }}
                        </span>
                    </div>

                    {{-- DETALLES --}}
                    <div class="space-y-3">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ruta asignada</p>
                            <p class="text-base text-gray-800 dark:text-gray-200">{{ $ruta->ruta }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Conductores</p>
                            @if($ruta->conductores->count())
                                <ul class="list-disc list-inside text-sm text-gray-700 dark:text-gray-300 mt-1">
                                    @foreach($ruta->conductores as $conductor)
                                        <li>{{ $conductor->name }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="italic text-gray-400">Sin conductores asignados</p>
                            @endif
                        </div>
                    </div>

                    {{-- ACCIONES --}}
                @foreach($rutas as $ruta)
                        @php
                            // 1) Obtenemos el registro del conductor logueado
                            $conductor  = $ruta->conductores->firstWhere('id', auth()->id());
                            // 2) ¿Ya está aprobado?
                            $yaAprobada = $conductor?->pivot->aprobada ?? false;
                        @endphp

                        {{-- … el resto de la tarjeta … --}}

                        <div class="mt-4 space-y-4">
                            <div class="grid grid-cols-2 gap-3 text-sm font-semibold">
                                <button wire:click="verInventario({{ $ruta->id }})"
                                    class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-violet-500 hover:bg-violet-600 text-white shadow-md transition-all duration-200">
                                    <i class="fas fa-box-open"></i>
                                    {{ $rutaVistaId === $ruta->id ? 'Ocultar' : 'Inventario' }}
                                </button>
                                <button wire:click="iniciarPedido({{ $ruta->id }})"
                                    class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-green-500 hover:bg-green-600 text-white shadow-md transition-all duration-200">
                                    <i class="fas fa-shopping-cart"></i> Pedido
                                </button>
                                <button wire:click="abrirModalGasto({{ $ruta->id }})"
                                    class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-yellow-400 hover:bg-yellow-500 text-gray-900 shadow-md transition-all duration-200">
                                    <i class="fas fa-money-check-alt"></i> Gasto
                                </button>
                                <button wire:click="confirmarDevolverInventario({{ $ruta->id }})"
                                    class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-gray-500 hover:bg-gray-600 text-white shadow-md transition-all duration-200">
                                    <i class="fas fa-warehouse"></i> Devolver
                                </button>
                            </div>

                            <div class="pt-2">
                                @if (! $yaAprobada)
                                    <button wire:click="aprobarRuta({{ $ruta->id }})"
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-lg transition-all duration-200">
                                        <i class="fas fa-check-circle"></i> Aprobar Ruta
                                    </button>
                                @else
                                    <button disabled
                                        class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-full bg-gray-400 text-white text-sm font-semibold opacity-60 cursor-not-allowed">
                                        <i class="fas fa-check-circle"></i> Ruta Aprobada
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach


                    @if($rutaVistaId === $ruta->id && is_iterable($inventarioVista))
                        @php $agrupadoPorBodega = collect($inventarioVista)->groupBy('bodega'); @endphp
                        <div class="mt-6 p-5 bg-gray-50 dark:bg-gray-800 rounded-2xl border">
                            <h4 class="text-md font-bold text-gray-800 dark:text-white mb-2">📦 Inventario por Bodega</h4>
                            @foreach($agrupadoPorBodega as $bodega => $items)
                                <div class="bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm border">
                                    <h5 class="text-sm font-bold text-indigo-600 dark:text-indigo-300 mb-2 flex items-center gap-2">
                                        <i class="fas fa-warehouse"></i> {{ $bodega }}
                                    </h5>
                                    <ul class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                        @foreach($items as $item)
                                            <li class="flex justify-between">
                                                <span>{{ $item['producto'] }}</span>
                                                <span class="font-bold text-violet-600 dark:text-violet-400">{{ $item['cantidad'] }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @if($modalGasto)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-6 border dark:border-gray-700">
            <div class="flex justify-between items-center border-b pb-2 mb-4">
                <h2 class="text-base font-bold text-gray-800 dark:text-white">
                    <i class="fas fa-coins text-yellow-500 mr-2"></i> Registrar Gasto en Ruta
                </h2>
                <button wire:click="$set('modalGasto', false)" class="text-gray-400 hover:text-red-500">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>

            <form wire:submit.prevent="guardarGasto" class="space-y-4 text-sm">
            <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Tipo de Gasto</label>
                <select wire:model.defer="tipoGastoId"
                        class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs">
                    <option value="">-- Selecciona un tipo --</option>
                    @foreach($tiposGasto as $gasto)
                        <option value="{{ $gasto->id }}">{{ $gasto->nombre }}</option>
                    @endforeach
                </select>
                @error('tipoGastoId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                    @error('tipoGastoId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>



                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Monto</label>
                    <input type="number" step="0.01" wire:model.defer="montoGasto"
                        class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs"
                        placeholder="Ej. 15000">
                    @error('montoGasto') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Observaciones (opcional)</label>
                    <textarea wire:model.defer="observacionGasto"
                            class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-xs"
                            rows="2" placeholder="Ej. Gasto adicional por..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button wire:click="$set('modalGasto', false)"
                            class="px-4 py-1.5 rounded-lg bg-gray-300 hover:bg-gray-400 text-gray-800 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 text-xs">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-1.5 rounded-lg bg-gray-500 hover:bg-gray-600 text-white text-xs font-semibold shadow">
                        <i class="fas fa-save mr-1"></i> Guardar Gasto
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

        {{-- MODAL CONFIRMACIÓN DEVOLUCIÓN --}}
        @if($confirmarDevolucion)
            <div class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center">
                <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow-xl w-full max-w-md border dark:border-gray-700 text-center">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-2">¿Confirmar devolución?</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">¿Estás seguro que deseas devolver el inventario a las bodegas?</p>

                    <div class="mt-5 flex justify-center gap-4">
                        <button wire:click="devolverInventario({{ $rutaADevolver }}); $set('confirmarDevolucion', false)"
                                class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white shadow">
                            Sí, devolver
                        </button>
                        <button wire:click="$set('confirmarDevolucion', false)"
                                class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400 text-gray-800 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- COLUMNA DERECHA: TABLA DE PEDIDOS --}}
    <div class="bg-white dark:bg-gray-900 p-6 rounded-2xl shadow-xl border border-gray-300 dark:border-gray-700 h-fit">

        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-list-alt"></i> Pedidos Realizados
        </h2>

        {{-- Tabs --}}
        <div class="flex gap-4 mb-4 text-xs font-semibold">
            <button wire:click="$set('tabActivo', 'pedidos')"
                class="{{ $tabActivo === 'pedidos' ? 'text-green-600 underline' : 'text-gray-500 hover:text-green-500' }}">
                🧾 Pedidos del Día actual
            </button>
            <button wire:click="$set('tabActivo', 'deudas')"
                class="{{ $tabActivo === 'deudas' ? 'text-green-600 underline' : 'text-gray-500 hover:text-green-500' }}">
                💰 Clientes con deuda dia actual
            </button>
            <button wire:click="$set('tabActivo', 'deudasgenerales')"
                class="{{ $tabActivo === 'deudasgenerales' ? 'text-green-600 underline' : 'text-gray-500 hover:text-green-500' }}">
                💰 Clientes con Deuda general
            </button>
            <button wire:click="$set('tabActivo', 'devoluciones')"
                class="{{ $tabActivo === 'devoluciones' ? 'text-green-600 underline' : 'text-gray-500 hover:text-green-500' }}">
                🔁 Devoluciones Realizadas
            </button>
        </div>

        {{-- ====================== TAB: PEDIDOS ====================== --}}
@if($tabActivo === 'deudasgenerales')
    <div class="mb-8 mt-6">
        <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-200 mb-4 flex items-center space-x-2">
            <i class="fas fa-money-bill-wave text-green-500"></i>
            <span>Clientes con saldo pendiente por crédito</span>
        </h3>

        {{-- 🔍 Filtro por cliente --}}
        <div class="max-w-md mb-6">
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Filtrar por cliente</label>
            <div wire:ignore x-data x-init="
                tom = new TomSelect($refs.select, {
                    placeholder: 'Selecciona un cliente...',
                    allowEmptyOption: true,
                    create: false,
                    onChange: value => @this.set('socioNegocioId', value),
                });
                Livewire.hook('message.processed', () => tom.refreshOptions(false));
            ">
                <select x-ref="select" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    <option value="">-- Todos los clientes --</option>
                    @foreach($clientesFiltrados as $cliente)
                        <option value="{{ $cliente['id'] }}">
                            {{ $cliente['razon_social'] }} ({{ $cliente['nit'] }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Mostrar solo los pedidos si hay cliente seleccionado --}}
        @if($socioNegocioId && $clienteSeleccionado)
            <div class="bg-indigo-50 dark:bg-indigo-950 border border-indigo-300 dark:border-indigo-700 rounded-2xl p-6 shadow-xl mt-4">
                <div class="mb-2 flex items-center space-x-2">
                    <i class="fas fa-clipboard-list text-indigo-600"></i>
                    <h4 class="text-lg font-bold text-indigo-700 dark:text-indigo-300">
                        Pedidos pendientes de {{ $clienteSeleccionado->razon_social }}
                    </h4>
                </div>

                @if(empty($pedidosSocio))
                    <p class="italic text-gray-500 dark:text-gray-400">Este cliente no tiene pedidos pendientes.</p>
                @else
                    <div class="overflow-x-auto rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 shadow">
                       <table class="min-w-full text-xs text-gray-700 dark:text-gray-300">
                            <thead class="bg-indigo-100 dark:bg-indigo-800 text-gray-800 dark:text-gray-100 uppercase">
                                <tr>
                                    <th class="p-2"># Pedido</th>
                                    <th class="p-2">Fecha</th>
                                    <th class="p-2">Ruta</th>
                                    <th class="p-2">Usuario</th>
                                    <th class="p-2 text-right">Saldo Pedido</th>
                                    <th class="p-2 text-center">Acción</th> <!-- NUEVA -->
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pedidosSocio as $pedido)
                                    <tr class="border-t dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-800">
                                        <td class="p-2 font-semibold">{{ $pedido['id'] }}</td>
                                        <td class="p-2">{{ $pedido['fecha'] }}</td>
                                        <td class="p-2">{{ $pedido['ruta'] }}</td>
                                        <td class="p-2">{{ $pedido['usuario'] }}</td>
                                        <td class="p-2 text-right text-green-700 dark:text-green-400 font-semibold">
                                            ${{ $pedido['total'] }}
                                        </td>
                                        <td>
                                        @if($pedido['monto_pendiente'] > 0)
                                                    <button wire:click="abrirModalPago({{ $pedido['id'] }})"
                                                        class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-xl shadow transition">
                                                        <i class="fas fa-credit-card mr-1"></i> Pago
                                                    </button>
                                                @endif

                                        </td>
                                      
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>
                @endif
            </div>
        @elseif(!$socioNegocioId)
            <p class="text-sm text-gray-500 italic">Selecciona un cliente para ver sus pedidos pendientes por crédito.</p>
        @endif
    </div>
@endif




        @if($tabActivo === 'pedidos')
            <div class="flex justify-between items-center mb-4 gap-4">
                <input type="text" wire:model.defer="busquedaPedido" placeholder="Buscar cliente o tipo de pago..."
                    class="w-full sm:w-1/2 px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-green-400">

                <button wire:click="$refresh"
                        class="px-4 py-2 text-xs font-semibold bg-green-500 hover:bg-green-600 text-white rounded-xl shadow-md transition">
                    Buscar
                </button>
            </div>

            @php $pedidos = $this->pedidos; @endphp

            @if($pedidos->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No hay pedidos registrados hoy.</p>
          @else
    <div class="overflow-x-auto">
       
        <div class="max-h-[400px] overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-700 shadow">
            <table class="min-w-full text-xs text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-100 dark:bg-gray-800 text-left text-xs uppercase tracking-wider">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">Cliente</th>
                        <th class="p-3">Tipo</th>
                        <th class="p-3">Acciones</th>
                    </tr>
                </thead>
               <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
    @foreach($pedidos as $pedido)
        @php
            $tieneCancelados   = $pedido->detalles->contains('estado', 'cancelado');
            $pedidoCancelado   = $pedido->estado === 'cancelado';

            $total = $pedido->detalles
                ->where('estado', '!=', 'cancelado')
                ->sum(function ($d) {
                    $precio = $d->precio_aplicado ?? $d->precio_unitario;
                    return $d->cantidad * floatval($precio);
                });

            $pagado = $pedido->pagos->sum('monto');
            $pendiente = $pedido->tipo_pago === 'credito' ? $total - $pagado : 0;

            $tieneLista = $pedido->detalles->contains(function ($d) {
                return !is_null($d->precio_aplicado);
            });
        @endphp

        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition {{ $pedidoCancelado ? 'bg-red-100 dark:bg-red-900/30' : '' }}">
            <td class="p-3 font-semibold">{{ $pedido->id }}</td>

            <td class="p-3">
                {{ $pedido->socioNegocio->razon_social ?? '-' }}

                @if($pedidoCancelado)
                    <div class="mt-1 text-xs font-semibold text-red-600 flex items-center gap-1">
                        <i class="fas fa-ban"></i> Pedido cancelado completamente
                    </div>
                @elseif($tieneCancelados)
                    <div class="mt-1 text-xs font-semibold text-yellow-600 flex items-center gap-1">
                        <i class="fas fa-exclamation-triangle"></i> Tiene productos cancelados
                    </div>
                @elseif($pendiente > 0)
                    <div class="mt-1 text-xs font-semibold text-orange-600 flex items-center gap-1">
                        <i class="fas fa-dollar-sign"></i> Saldo pendiente: ${{ number_format($pendiente, 0, ',', '.') }}
                        @if($tieneLista)
                            <span class="ml-1 text-[10px] italic text-blue-600">(lista aplicada)</span>
                        @endif
                    </div>
                @endif
            </td>

            <td class="p-3 capitalize">{{ $pedido->tipo_pago }}</td>

            <td class="p-3">
                <button wire:click="imprimirFactura({{ $pedido->id }})"
                        class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                    <i class="fas fa-file-invoice"></i> Factura
                </button>
            </td>
        </tr>
    @endforeach
</tbody>

            </table>
        </div>

        <!-- Paginación fuera del scroll -->
        <div class="mt-4">
            {{ $pedidos->links('pagination::tailwind') }}
        </div>
    </div>
@endif


        {{-- ====================== TAB: DEUDAS ====================== --}}
        @elseif($tabActivo === 'deudas')
            @php $deudas = $this->pedidosConDeuda; @endphp

            @if($deudas->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No hay clientes con deuda pendiente.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-gray-700 dark:text-gray-300 rounded-xl overflow-hidden">
                        <thead class="bg-gray-100 dark:bg-gray-800 text-left text-xs uppercase tracking-wider">
                            <tr>
                                <th class="p-3">#</th>
                                <th class="p-3">Cliente</th>
                                <th class="p-3">Fecha</th>
                                <th class="p-3">Saldo Pendiente</th>
                                <th class="p-3">Usuario genero pedido</th>
                                <th class="p-3">Acciones</th>
                            </tr>
                        </thead>
                   <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
    @foreach($deudas as $pedido)
        @php
            $subtotal = $pedido->detalles
                ->where('estado', '!=', 'cancelado')
                ->sum(function ($d) {
                    $precio = $d->precio_aplicado ?? $d->precio_unitario;
                    return $d->cantidad * floatval($precio);
                });

            $pagos = $pedido->pagos->sum('monto');
            $saldoPendiente = $subtotal - $pagos;

            $tieneLista = $pedido->detalles->contains(function ($d) {
                return !is_null($d->precio_aplicado);
            });
        @endphp

        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            <td class="p-3 font-bold">{{ $pedido->id }}</td>
            <td class="p-3 font-bold">{{ $pedido->socioNegocio->razon_social ?? '-' }}</td>
            <td class="p-3 font-bold">{{ $pedido->fecha->format('Y-m-d') }}</td>
            <td class="p-3 text-red-600 font-bold">
                ${{ number_format($saldoPendiente, 0, ',', '.') }}
                @if($tieneLista)
                    <span class="ml-1 text-[10px] text-blue-600 italic">(lista aplicada)</span>
                @endif
            </td>
            <td class="p-3 font-bold">{{ $pedido->usuario->name ?? '—' }}</td>
            <td class="p-3 space-y-1 font-bold">
                <button wire:click="imprimirFactura({{ $pedido->id }})"
                        class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                    <i class="fas fa-file-invoice"></i> Factura
                </button>

                @if($saldoPendiente > 0)
                    <button 
                        type="button"
                        wire:click="abrirModalPago({{ $pedido->id }})"
                        class="inline-flex items-center text-xs font-medium px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 transition-shadow shadow-sm">
                        <i class="fas fa-credit-card mr-1"></i>Pago
                    </button>
                @endif
            </td>
        </tr>
    @endforeach
</tbody>

                    </table>
                </div>
            @endif




        {{-- ====================== TAB: DEVOLUCIONES ====================== --}}
         {{-- 💳 MODAL DE PAGO: disponible en cualquier tab --}}

    @elseif($tabActivo === 'devoluciones')
    @php 
        $devoluciones = $this->devolucionesRealizadas; 
        // Calcular total general de todas las devoluciones
        $totalGeneral = $devoluciones
            ->sum(fn($dev) => $dev->detalles
                ->sum(fn($d) => $d->cantidad * $d->precio_unitario)
            );
    @endphp

    @if($devoluciones->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 italic">
            No hay devoluciones registradas.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-gray-700 dark:text-gray-300 rounded-xl overflow-hidden">
                <thead class="bg-gray-100 dark:bg-gray-800 text-left text-xs uppercase tracking-wider">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">Cliente</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Producto</th>
                        <th class="p-3 text-right">Cant.</th>
                        <th class="p-3 text-right">Precio</th>
                        <th class="p-3 text-right">Total</th> 
                        <th class="p-3">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($devoluciones as $devolucion)
                        @php
                            // Suma de esta devolución
                            $totalDev = $devolucion->detalles
                                ->sum(fn($d) => $d->cantidad * $d->precio_unitario);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <td class="p-3 font-semibold">{{ $devolucion->id }}</td>
                            <td class="p-3">
                                {{ $devolucion->socioNegocio->razon_social ?? '-' }}
                            </td>
                            <td class="p-3">
                                {{ \Carbon\Carbon::parse($devolucion->fecha)->format('Y-m-d') }}
                            </td>
                            <td class="p-3">
                                <ul class="list-disc ml-4 space-y-1">
                                    @foreach($devolucion->detalles as $detalle)
                                        <li>{{ $detalle->producto->nombre ?? '-' }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="p-3 text-right">
                                <ul class="space-y-1">
                                    @foreach($devolucion->detalles as $detalle)
                                        <li>{{ $detalle->cantidad }} u.</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="p-3 text-right">
                                <ul class="space-y-1">
                                    @foreach($devolucion->detalles as $detalle)
                                        <li>${{ number_format($detalle->precio_unitario, 0, ',', '.') }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="p-3 text-right font-semibold">
                                ${{ number_format($totalDev, 0, ',', '.') }}
                            </td>
                            <td class="p-3">
                                <button
                                    wire:click="imprimirDevolucion({{ $devolucion->id }})"
                                    class="text-xs text-blue-600 hover:underline flex items-center gap-1"
                                >
                                    <i class="fas fa-file-invoice"></i> Ver
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                {{-- Pie de tabla con total general --}}
                <tfoot>
                    <tr class="bg-gray-200 dark:bg-gray-700 font-semibold text-gray-800 dark:text-gray-200">
                        <td colspan="6" class="px-3 py-2 text-right">Total general:</td>
                        <td class="px-3 py-2 text-right">
                            ${{ number_format($totalGeneral, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
  

  


{{-- Dentro de tu sección de “Devoluciones” --}}
@if($mostrarDevolucion && $devolucionVista)
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 no-print">
    <div
      id="printArea-{{ $devolucionVista->id }}"
      class="print-factura bg-white p-6 rounded-xl shadow-xl w-full max-w-md text-sm relative"
    >
      {{-- Cerrar --}}
      <button
        wire:click="$set('mostrarDevolucion', false)"
        class="absolute top-2 right-2 text-red-500 no-print"
      >
        <i class="fas fa-times-circle"></i>
      </button>

      {{-- Cabecera --}}
      <div class="text-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">
          Devolución #{{ $devolucionVista->id }}
        </h2>
        <p class="text-gray-500 text-xs">
          Fecha: {{ \Carbon\Carbon::parse($devolucionVista->fecha)->format('d/m/Y') }}
        </p>
      </div>

      {{-- Conductor --}}
      <div class="mb-2 text-xs text-gray-700">
  <p><strong>Cliente:</strong> {{ $devolucionVista->socioNegocio->razon_social ?? '-' }}</p>
</div>

      {{-- Medio de Pago --}}
      <div class="mb-4 text-xs text-gray-700">
        <p><strong>Medio de Pago:</strong> Devolución</p>
      </div>

     
      <table class="w-full border text-xs">
        <thead class="bg-gray-200 text-gray-800">
          <tr>
            <th class="p-2 text-left">Producto</th>
            <th class="p-2 text-right">Cant.</th>
            <th class="p-2 text-right">Precio</th>
          </tr>
        </thead>
        <tbody>
          @foreach($devolucionVista->detalles as $detalle)
            <tr>
              <td class="p-2">{{ $detalle->producto->nombre ?? '-' }}</td>
              <td class="p-2 text-right">{{ $detalle->cantidad }}</td>
              <td class="p-2 text-right">
                ${{ number_format($detalle->precio_unitario, 0, ',', '.') }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      {{-- Total de la devolución --}}
      @php
          $totalDevolucion = $devolucionVista->detalles
              ->sum(fn($d) => $d->cantidad * $d->precio_unitario);
      @endphp
      <div class="mt-2 text-xs font-semibold text-right">
        Total devolución: ${{ number_format($totalDevolucion, 0, ',', '.') }}
      </div>

      {{-- Agradecimiento --}}
      <div class="agradecimiento mt-4">
        ¡Gracias por su gestión!
      </div>


      
      {{-- Botón imprimir --}}
      <div class="mt-4 flex justify-end no-print">
        <button
          type="button"
          onclick="imprimirDiv('printArea-{{ $devolucionVista->id }}', {{ $devolucionVista->id }})"
          class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs shadow"
        >
          <i class="fas fa-print mr-1"></i> Imprimir
        </button>
      </div>
    </div>
  </div>
@endif


            </div>
        @endif
    @endif



    </div>


    </div>
    @endif
@if($modalPago && $pedidoPagoId)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl w-full max-w-md border dark:border-gray-600">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white mb-4">
                Registrar Pago - Pedido #{{ $pedidoPagoId }}
            </h2>

            {{-- Método de pago --}}
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Método de pago</label>
            <select wire:model="metodoPago"
                class="w-full px-3 py-2 mb-4 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                <option value="Contado">Contado</option>
                <option value="Transferencia">Transferencia</option>
            </select>

            {{-- Monto a pagar (solo si NO es crédito) --}}
            @if($metodoPago !== 'credito')
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Monto a pagar</label>
                <div class="relative mb-4">
                    <input type="number" step="0.01" wire:model.defer="montoPago"
                        class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm pr-10" />
                    <span class="absolute left-2 top-1.5 text-green-600 font-bold">$</span>
                    @error('montoPago') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <button wire:click="$set('modalPago', false)"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-white text-gray-700 rounded-lg text-sm">
                    Cancelar
                </button>
                <button wire:click="registrarPago"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm">
                    Confirmar Pago
                </button>
            </div>
        </div>
    </div>
@endif

    {{-- Estilos de impresión --}}
<style>
  @media print {
    body, html { margin:0; padding:0; background:#fff; }
    body * { visibility:hidden !important; }

    .print-factura, .print-factura * {
      visibility:visible !important;
    }

    .print-factura {
      position:absolute;
      top:0; left:0;
      width:80mm;
      padding:12px;
      background:#fff;
      color:#000;
      font-family:monospace, sans-serif;
      font-size:11px;
      box-shadow:none;
      line-height:1.2;
    }

    .print-factura table {
      width:100%;
      border-collapse:collapse;
      font-size:11px;
    }

    .print-factura th, .print-factura td {
      padding:4px 2px;
      border-bottom:1px dashed #ccc;
    }

    .agradecimiento {
      margin-top:15px;
      text-align:center;
      font-size:10px;
      border-top:1px dashed #000;
      padding-top:6px;
    }

    .no-print {
      display:none !important;
    }

    .print-factura .max-h-64 {
      max-height: none !important;
      overflow: visible !important;
    }
  }
</style>


{{-- Script de impresión --}}
<script>
  function imprimirDiv(id, itemId) {
    const original = document.getElementById(id);
    if (!original) {
      return alert('No se encontró el contenido para imprimir');
    }
    // Clonar y quitar .no-print
    const clone = original.cloneNode(true);
    clone.querySelectorAll('.no-print').forEach(el => el.remove());
    // Abrir ventana
    const w = window.open('', '_blank', 'width=400,height=600');
    w.document.open();
    w.document.write(`
      <!doctype html>
      <html>
        <head>
          <title>Recibo Devolución #${itemId}</title>
          <style>
            body{margin:0;padding:10px;font-family:Arial,sans-serif;font-size:12px;}
            h2{text-align:center;margin-bottom:8px;}
            p{margin:4px 0;}
            table{width:100%;border-collapse:collapse;margin-top:8px;}
            thead th{border-bottom:1px solid #333;padding-bottom:4px;font-weight:bold;}
            tbody td{padding:4px 0;border-bottom:1px dashed #999;}
            .agradecimiento{margin-top:12px;text-align:center;font-style:italic;font-size:11px;}
          </style>
        </head>
        <body>${clone.innerHTML}</body>
      </html>
    `);
    w.document.close();
    w.focus();
    w.print();
    w.close();
  }
</script>


    </div>
