{{-- POS Factura - Sabor Boreal --}}
<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 p-4 pb-24">

        {{-- ===================== CATÁLOGO ===================== --}}
        <section class="lg:col-span-2 space-y-3">
            <div class="flex items-center gap-3">
                <button type="button" wire:click="limpiar"
                    class="h-11 w-11 grid place-items-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white">
                    <i class="fas fa-broom"></i>
                </button>
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" wire:model.live.debounce.300ms="busquedaProducto"
                        placeholder="Buscar Producto (Nombre, Categoría, Código). Mín. 2 caracteres"
                        class="w-full h-11 pl-11 pr-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-blue-300/40">
                </div>
            </div>

            <div class="space-y-3">
                @forelse($categorias as $cat)
                    @php
                        $productosCat = $cat->subcategorias->flatMap->productos->unique('id')->values();
                        $abierta = $categoriaAbierta === $cat->id || $busquedaProducto !== '';
                    @endphp
                    @if($busquedaProducto !== '' && $productosCat->isEmpty()) @continue @endif

                    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <button type="button" wire:click="toggleCategoria({{ $cat->id }})"
                            class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-amber-100 dark:bg-amber-900/40 grid place-items-center text-amber-700 dark:text-amber-300">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $cat->nombre }}</span>
                                <span class="text-xs text-gray-500">({{ $productosCat->count() }})</span>
                            </div>
                            <i class="fas {{ $abierta ? 'fa-chevron-up' : 'fa-chevron-down' }} text-gray-500"></i>
                        </button>

                        @if($abierta)
                            <div class="px-4 pb-4">
                                @if($productosCat->isEmpty())
                                    <p class="text-sm text-gray-400 italic py-4 text-center">Sin productos</p>
                                @else
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                        @foreach($productosCat as $p)
                                            <button type="button" wire:click="agregar({{ $p->id }})"
                                                class="group rounded-xl border border-gray-200 dark:border-gray-700 hover:border-blue-500 hover:shadow-md transition overflow-hidden bg-white dark:bg-gray-800 text-left">
                                                <div class="aspect-square bg-gray-100 dark:bg-gray-900 grid place-items-center overflow-hidden">
                                                    @if($p->imagen_path)
                                                        <img src="{{ $p->imagen_path }}" alt="{{ $p->nombre }}" class="h-full w-full object-cover group-hover:scale-105 transition">
                                                    @else
                                                        <i class="fas fa-image text-3xl text-gray-300"></i>
                                                    @endif
                                                </div>
                                                <div class="p-2">
                                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 line-clamp-2">{{ $p->nombre }}</p>
                                                    <p class="text-sm font-bold text-blue-600 dark:text-blue-400 mt-1">
                                                        $ {{ number_format((float) $p->precio, 0, ',', '.') }}
                                                    </p>
                                                </div>
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-10 text-gray-500">No hay categorías con productos.</div>
                @endforelse
            </div>
        </section>

        {{-- ===================== CARRITO ===================== --}}
        <aside class="lg:col-span-1">
            <div class="sticky top-4 space-y-3">
                <div class="rounded-2xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700 p-4">
                    <div class="flex justify-end mb-3">
                        <button type="button" wire:click="limpiar"
                            class="px-4 h-9 rounded-lg border border-blue-500 text-blue-600 hover:bg-blue-50 text-sm">
                            Limpiar
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-xs font-semibold text-gray-500 border-b pb-2">
                        <span class="col-span-2">Producto</span>
                        <span class="text-center">Cantidad</span>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[40vh] overflow-y-auto">
                        @forelse($carrito as $pid => $i)
                            <div class="grid grid-cols-3 gap-2 py-3 items-center" wire:key="cart-{{ $pid }}">
                                <div class="col-span-2">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $i['nombre'] }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        × $ {{ number_format((float) $i['precio'], 0, ',', '.') }} = $ {{ number_format((float) $i['precio'] * (float) $i['cantidad'], 0, ',', '.') }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 justify-center">
                                    <button type="button" wire:click="decrementar({{ $pid }})"
                                        class="h-8 w-8 rounded-md bg-blue-600 hover:bg-blue-700 text-white">−</button>
                                    <input type="number" min="0" step="1" value="{{ (int) $i['cantidad'] }}"
                                        wire:change="setCantidad({{ $pid }}, $event.target.value)"
                                        class="w-12 h-8 text-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white">
                                    <button type="button" wire:click="incrementar({{ $pid }})"
                                        class="h-8 w-8 rounded-md bg-blue-600 hover:bg-blue-700 text-white">+</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-center py-8 text-sm text-gray-400 italic">Aún no has agregado productos.</p>
                        @endforelse
                    </div>

                    <div class="mt-4 space-y-2">
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600 dark:text-gray-300 w-32">Descuento (%):</label>
                            <input type="number" min="0" max="100" step="0.01" wire:model.live.debounce.400ms="descuentoPct"
                                class="flex-1 h-9 px-2 text-right rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white">
                            <span class="text-gray-400">%</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600 dark:text-gray-300 w-32">Descuento ($):</label>
                            <input type="number" min="0" step="100" wire:model.live.debounce.400ms="descuentoValor"
                                class="flex-1 h-9 px-2 text-right rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white">
                            <span class="text-gray-400">$</span>
                        </div>
                    </div>

                    <p class="mt-4 text-center text-lg font-bold text-gray-800 dark:text-gray-100">
                        Total: $ {{ number_format($this->total, 0, ',', '.') }}
                    </p>
                </div>

                {{-- Cliente --}}
                <div class="rounded-2xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700 p-4">
                    <p class="text-center font-semibold text-gray-700 dark:text-gray-200 mb-3">Cliente</p>

                    @if($clienteSeleccionadoNombre)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 border border-blue-200">
                            <span class="text-sm text-blue-800 dark:text-blue-200 font-medium">{{ $clienteSeleccionadoNombre }}</span>
                            <button type="button" wire:click="quitarCliente" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @else
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="busquedaCliente"
                                placeholder="Buscar cliente"
                                class="w-full h-10 px-3 pr-10 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white">
                            <button type="button"
                                class="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8 rounded-md bg-blue-600 hover:bg-blue-700 text-white grid place-items-center">
                                <i class="fas fa-search text-xs"></i>
                            </button>

                            @if($clientesSugeridos->isNotEmpty())
                                <ul class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    @foreach($clientesSugeridos as $c)
                                        <li>
                                            <button type="button" wire:click="seleccionarCliente({{ $c->id }})"
                                                class="w-full text-left px-3 py-2 hover:bg-blue-50 dark:hover:bg-gray-700 text-sm">
                                                <span class="font-medium">{{ $c->razon_social }}</span>
                                                @if($c->nit)<span class="text-gray-500 text-xs"> · {{ $c->nit }}</span>@endif
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Observaciones --}}
                <div class="rounded-2xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700 p-4">
                    <p class="font-semibold text-gray-700 dark:text-gray-200 mb-2">Observaciones</p>
                    <textarea wire:model.lazy="observaciones" rows="2"
                        class="w-full p-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white text-sm"></textarea>
                </div>

                {{-- Cobrar --}}
                <button type="button" wire:click="guardar" wire:loading.attr="disabled"
                    class="w-full h-12 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-lg shadow-lg disabled:opacity-50">
                    <span wire:loading.remove wire:target="guardar">Cobrar / Guardar</span>
                    <span wire:loading wire:target="guardar"><i class="fas fa-spinner fa-spin"></i> Procesando…</span>
                </button>
            </div>
        </aside>
    </div>

    {{-- Barra inferior fija --}}
    <div class="fixed bottom-0 inset-x-0 bg-blue-600 text-white px-4 py-2 flex justify-end gap-6 text-sm shadow-lg">
        <span>Cant.: <strong>{{ number_format($this->cantidadTotal, 0, ',', '.') }}</strong></span>
        <span>Total: <strong>$ {{ number_format($this->total, 0, ',', '.') }}</strong></span>
    </div>
</div>
