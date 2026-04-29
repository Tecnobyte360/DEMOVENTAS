{{-- POS Factura - Sabor Boreal (estilo Restro POS) --}}
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="grid grid-cols-1 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-4">

        {{-- ===================== CATÁLOGO ===================== --}}
        <section class="lg:col-span-2 xl:col-span-3 space-y-4">

            {{-- Top bar: search + acciones --}}
            <div class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-3">
                <div class="relative flex-1">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" wire:model.live.debounce.300ms="busquedaProducto"
                        placeholder="Buscar productos..."
                        class="w-full h-11 pl-11 pr-4 rounded-xl bg-gray-100 dark:bg-gray-700 dark:text-white border-0 focus:outline-none focus:ring-2 focus:ring-orange-300">
                </div>
                <button type="button" wire:click="limpiar" title="Limpiar"
                    class="h-11 w-11 grid place-items-center rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-600">
                    <i class="fas fa-sync"></i>
                </button>
            </div>

            {{-- Tabs: subcategorías + Pendientes --}}
            <div class="flex items-center gap-2 overflow-x-auto bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-2 no-scrollbar">
                <button type="button" wire:click="verPendientes"
                    class="shrink-0 px-4 h-9 rounded-lg text-sm font-medium transition flex items-center gap-2
                        {{ $vista === 'pendientes'
                            ? 'bg-orange-50 text-orange-600 ring-1 ring-orange-300'
                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-clipboard-list"></i>
                    Pendientes
                    @if($ordenesPendientes->count() > 0)
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-orange-500 text-white font-bold">{{ $ordenesPendientes->count() }}</span>
                    @endif
                </button>

                <span class="h-6 w-px bg-gray-200 dark:bg-gray-700 mx-1"></span>

                @foreach($categorias as $cat)
                    @php $activa = $vista === 'productos' && $categoriaActiva === $cat->id && $busquedaProducto === ''; @endphp
                    <button type="button" wire:click="setCategoria({{ $cat->id }})"
                        class="shrink-0 px-4 h-9 rounded-lg text-sm font-medium transition
                            {{ $activa
                                ? 'bg-orange-50 text-orange-600 ring-1 ring-orange-300'
                                : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                        {{ $cat->nombre }}
                    </button>
                @endforeach
            </div>

            @if($vista === 'pendientes')
                {{-- Grid de órdenes pendientes (estilo ticket POS) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @forelse($ordenesPendientes as $op)
                        @php $editando = $facturaEditandoId === $op->id; @endphp
                        <button type="button" wire:click="cargarOrden({{ $op->id }})"
                            class="text-left rounded-2xl overflow-hidden hover:shadow-xl transition group"
                            style="display:flex; flex-direction:column; width:100%; background:white; border:2px solid {{ $editando ? '#fb923c' : '#e5e7eb' }}; box-shadow:0 4px 12px rgba(0,0,0,.05); {{ $editando ? 'box-shadow:0 0 0 4px #fed7aa;' : '' }}">
                            {{-- Header --}}
                            <div style="background:linear-gradient(135deg,#fb923c,#f97316); padding:14px 16px; display:flex; align-items:center; justify-content:space-between; color:white;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="height:40px; width:40px; border-radius:12px; background:rgba(255,255,255,.25); display:grid; place-items:center;">
                                        <i class="fas fa-receipt" style="font-size:18px;"></i>
                                    </div>
                                    <div>
                                        <p style="font-size:10px; text-transform:uppercase; letter-spacing:.05em; opacity:.85; margin:0;">Orden</p>
                                        <p style="font-size:20px; font-weight:800; margin:0; line-height:1;">#{{ $op->id }}</p>
                                    </div>
                                </div>
                                <span style="font-size:10px; font-weight:700; padding:4px 10px; border-radius:9999px; background:rgba(255,255,255,.95); color:#c2410c;">
                                    <i class="fas fa-clock"></i> Pendiente
                                </span>
                            </div>

                            {{-- Body --}}
                            <div style="padding:14px 16px;">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                                    <i class="fas fa-user-circle" style="color:#9ca3af; font-size:14px;"></i>
                                    <span style="font-size:13px; font-weight:600; color:#1f2937; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                        {{ $op->cliente?->razon_social ?? 'Sin cliente' }}
                                    </span>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px; color:#6b7280; font-size:11px;">
                                    <i class="fas fa-calendar-alt"></i>
                                    {{ \Illuminate\Support\Carbon::parse($op->fecha)->format('d M Y') }}
                                </div>
                                @if($op->notas)
                                    <p style="margin-top:10px; padding:8px 10px; background:#fff7ed; border-left:3px solid #fb923c; border-radius:6px; font-size:11px; color:#7c2d12; font-style:italic;">"{{ $op->notas }}"</p>
                                @endif
                            </div>

                            {{-- Footer --}}
                            <div style="background:#f9fafb; padding:12px 16px; display:flex; align-items:center; justify-content:space-between; border-top:1px dashed #e5e7eb;">
                                <span style="font-size:11px; color:#6b7280; text-transform:uppercase; letter-spacing:.05em;">Total</span>
                                <span style="font-size:20px; font-weight:800; color:#059669;">
                                    $ {{ number_format((float)$op->total, 0, ',', '.') }}
                                </span>
                            </div>

                            <div style="background:#f97316; color:white; padding:8px; text-align:center; font-size:12px; font-weight:600;">
                                <i class="fas fa-edit"></i> Toca para editar / cobrar
                            </div>
                        </button>
                    @empty
                        <div class="col-span-full text-center py-16 text-gray-400">
                            <i class="fas fa-clipboard-check text-5xl mb-3"></i>
                            <p class="text-base font-medium">No hay órdenes pendientes de cobro.</p>
                            <button type="button" wire:click="verProductos"
                                class="mt-4 px-4 h-10 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold">
                                Volver al catálogo
                            </button>
                        </div>
                    @endforelse
                </div>
            @else
            {{-- Grid simple de productos --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse($productos as $p)
                    <button type="button" wire:click="agregar({{ $p->id }})"
                        class="group rounded-2xl bg-white dark:bg-gray-800 shadow hover:shadow-lg border border-gray-100 dark:border-gray-700 hover:border-orange-300 transition"
                        style="display:flex; flex-direction:column; align-items:stretch; min-height:220px; padding:12px;">
                        <div style="display:flex; justify-content:center;">
                            <div class="rounded-full bg-gray-50 dark:bg-gray-900 ring-1 ring-gray-100 dark:ring-gray-700"
                                style="height:96px; width:96px; display:grid; place-items:center; overflow:hidden;">
                                @if($p->imagen_path)
                                    <img src="{{ $p->imagen_path }}" alt="{{ $p->nombre }}" style="height:100%; width:100%; object-fit:cover;">
                                @else
                                    <i class="fas fa-image text-2xl text-gray-300"></i>
                                @endif
                            </div>
                        </div>
                        <div class="text-gray-800 dark:text-gray-100" style="margin-top:10px; text-align:center; flex:1;">
                            <p style="font-size:0.875rem; font-weight:600; line-height:1.2; min-height:2.4rem;">{{ $p->nombre ?: '(sin nombre)' }}</p>
                            <p class="text-amber-500" style="font-size:1rem; font-weight:700; margin-top:6px;">
                                $ {{ number_format((float) $p->precio, 0, ',', '.') }}
                            </p>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-400">
                        <i class="fas fa-utensils text-4xl mb-3"></i>
                        <p>No hay productos para mostrar.</p>
                    </div>
                @endforelse
            </div>
            @endif
        </section>

        {{-- ===================== ORDEN ===================== --}}
        <aside class="lg:col-span-1">
            <div class="sticky top-4 bg-white dark:bg-gray-800 rounded-2xl shadow border border-gray-100 dark:border-gray-700 flex flex-col max-h-[calc(100vh-2rem)]">

                {{-- Header de orden --}}
                <div class="p-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        @if($facturaEditandoId)
                            <span class="text-orange-600">Editando orden #{{ $facturaEditandoId }}</span>
                        @else
                            Nueva orden
                        @endif
                    </div>
                    @if($facturaEditandoId || !empty($carrito))
                        <button type="button" wire:click="nuevaOrden"
                            class="text-xs px-2 py-1 rounded-md border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:bg-gray-50">
                            + Nueva
                        </button>
                    @endif
                </div>

                {{-- Cliente --}}
                <div class="p-3 border-b border-gray-100 dark:border-gray-700">
                    @if($clienteSeleccionadoNombre)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-orange-50 dark:bg-orange-900/30 border border-orange-200">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-user-circle text-orange-500"></i>
                                <span class="text-sm font-medium text-orange-800 dark:text-orange-200">{{ $clienteSeleccionadoNombre }}</span>
                            </div>
                            <button type="button" wire:click="quitarCliente" class="text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @else
                        <div class="relative">
                            <input type="text" wire:model.live.debounce.300ms="busquedaCliente"
                                placeholder="+ Agregar cliente"
                                class="w-full h-10 px-3 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                            @if($clientesSugeridos->isNotEmpty())
                                <ul class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    @foreach($clientesSugeridos as $c)
                                        <li>
                                            <button type="button" wire:click="seleccionarCliente({{ $c->id }})"
                                                class="w-full text-left px-3 py-2 hover:bg-orange-50 dark:hover:bg-gray-700 text-sm">
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

                {{-- Items --}}
                <div class="flex-1 overflow-y-auto px-3 py-2 space-y-2">
                    @forelse($carrito as $pid => $i)
                        @php
                            $expandido = $itemExpandido === $pid;
                            $subItem = (float) $i['precio'] * (float) $i['cantidad'];
                            $subItemConDesc = $subItem * (1 - ((float) ($i['descuento_pct'] ?? 0) / 100));
                            $idx = $loop->iteration;
                        @endphp
                        <div wire:key="item-{{ $pid }}"
                            class="rounded-xl border {{ $expandido ? 'border-orange-300 ring-2 ring-orange-100' : 'border-gray-100 dark:border-gray-700' }} bg-white dark:bg-gray-800 transition">
                            <div class="flex items-center gap-2 p-2">
                                <button type="button" wire:click="toggleItem({{ $pid }})"
                                    class="h-7 w-7 grid place-items-center text-gray-400 hover:text-gray-700">
                                    <i class="fas {{ $expandido ? 'fa-chevron-down' : 'fa-chevron-right' }} text-xs"></i>
                                </button>
                                <span class="text-xs font-semibold text-gray-500 w-5">{{ $idx }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $i['nombre'] }}</p>
                                    @if(($i['descuento_pct'] ?? 0) > 0)
                                        <p class="text-[10px] text-orange-500">{{ rtrim(rtrim(number_format((float)$i['descuento_pct'],2,'.',''), '0'), '.') }}% desc.</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    @if(($i['descuento_pct'] ?? 0) > 0)
                                        <p class="text-xs text-gray-400 line-through">$ {{ number_format($subItem, 0, ',', '.') }}</p>
                                    @endif
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-50">$ {{ number_format($subItemConDesc, 0, ',', '.') }}</p>
                                </div>
                                <button type="button" wire:click="quitar({{ $pid }})"
                                    class="h-6 w-6 grid place-items-center rounded-full text-gray-400 hover:text-red-500 hover:bg-red-50">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>

                            @if($expandido)
                                <div class="px-3 pb-3 pt-1 border-t border-orange-100 grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[11px] text-gray-500">Cantidad</label>
                                        <input type="number" min="0" step="1" value="{{ (int) $i['cantidad'] }}"
                                            wire:change="setCantidadItem({{ $pid }}, $event.target.value)"
                                            class="w-full h-9 mt-1 px-2 text-center rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="text-[11px] text-gray-500">Descuento (%)</label>
                                        <input type="number" min="0" max="100" step="0.01" value="{{ (float) ($i['descuento_pct'] ?? 0) }}"
                                            wire:change="setDescuentoItem({{ $pid }}, $event.target.value)"
                                            class="w-full h-9 mt-1 px-2 text-center rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white">
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-12 text-gray-400">
                            <i class="fas fa-shopping-bag text-3xl mb-2"></i>
                            <p class="text-sm">Sin productos en la orden.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Observaciones --}}
                @if(!empty($carrito))
                    <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-700">
                        <textarea wire:model.lazy="observaciones" rows="1" placeholder="Nota / observación"
                            class="w-full p-2 rounded-md border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-orange-300"></textarea>
                    </div>
                @endif

                {{-- Totales --}}
                <div class="px-3 py-3 border-t border-gray-100 dark:border-gray-700 space-y-1.5 bg-gray-50 dark:bg-gray-900/30">
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300">
                        <span>Subtotal</span>
                        <span>$ {{ number_format($this->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300">
                        <span>Impuestos</span>
                        <span>$ {{ number_format($this->impuestosTotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 dark:text-gray-50 pt-1 border-t border-gray-200 dark:border-gray-700">
                        <span>Total a pagar</span>
                        <span>$ {{ number_format($this->total, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="grid grid-cols-2 gap-2 p-3 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="holdOrder" wire:loading.attr="disabled"
                        title="Guarda la orden como borrador y limpia la pantalla para atender otra venta"
                        class="h-11 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-semibold text-sm shadow disabled:opacity-50">
                        <i class="fas fa-pause mr-1"></i> Dejar pendiente
                    </button>
                    <button type="button" wire:click="proceder" wire:loading.attr="disabled"
                        title="Guarda la orden y abre el modal de pagos"
                        class="h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow disabled:opacity-50">
                        <i class="fas fa-cash-register mr-1"></i> Cobrar
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    {{-- Modal de pagos existente --}}
    <livewire:facturas.pagos-factura />
</div>
