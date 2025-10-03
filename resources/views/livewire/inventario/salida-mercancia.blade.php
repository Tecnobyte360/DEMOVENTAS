<form wire:submit.prevent="guardarSalida" class="space-y-10">
    <section class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        {{-- COLUMNA IZQUIERDA --}}
        <div class="xl:col-span-8 space-y-8">
            {{-- CARD PRINCIPAL --}}
            <section class="relative rounded-3xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-white via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900"></div>

                {{-- Header sticky --}}
                <header class="sticky top-0 z-10 backdrop-blur bg-white/70 dark:bg-gray-900/70 border-b border-gray-200 dark:border-gray-800 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-600/10 text-violet-700 dark:text-violet-300">
                            <i class="fas fa-truck-loading"></i>
                        </span>
                        <div>
                            <h2 class="text-xl font-extrabold text-gray-800 dark:text-white tracking-tight">Nueva Salida de Mercancía</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Ventas / Salidas</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="reset" wire:click="$refresh"
                                class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-semibold shadow-md transition">
                            <i class="fas fa-save mr-2"></i>
                            <span wire:loading.remove>Guardar salida</span>
                            <span wire:loading class="animate-pulse">Guardando…</span>
                        </button>
                    </div>
                </header>

                <div class="relative px-6 py-6 space-y-8">
                    {{-- Mensajes --}}
                    @if (session()->has('message'))
                        <div class="px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center gap-2 shadow-sm">
                            <i class="fas fa-check-circle"></i> {{ session('message') }}
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-center gap-2 shadow-sm">
                            <i class="fas fa-times-circle"></i> {{ session('error') }}
                        </div>
                    @endif

                    {{-- Datos Generales --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha *</label>
                            <input type="date" wire:model="fecha"
                                   class="w-full px-4 py-2.5 rounded-xl border @error('fecha') border-red-500 @else border-gray-300 dark:border-gray-700 @enderror dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500">
                            @error('fecha') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ruta *</label>
                            <select wire:model="ruta_id"
                                    class="w-full px-4 py-2.5 rounded-xl border @error('ruta_id') border-red-500 @else border-gray-300 dark:border-gray-700 @enderror dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500">
                                <option value="">Seleccione</option>
                                @foreach ($rutas as $ruta)
                                    <option value="{{ $ruta->id }}">{{ $ruta->ruta }}</option>
                                @endforeach
                            </select>
                            @error('ruta_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cliente *</label>
                            <select wire:model="socio_negocio_id"
                                    class="w-full px-4 py-2.5 rounded-xl border @error('socio_negocio_id') border-red-500 @else border-gray-300 dark:border-gray-700 @enderror dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500">
                                <option value="">Seleccione</option>
                                @foreach ($socios as $socio)
                                    <option value="{{ $socio->id }}">{{ $socio->razon_social }}</option>
                                @endforeach
                            </select>
                            @error('socio_negocio_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observaciones</label>
                            <textarea rows="3" wire:model="observaciones"
                                      class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500"
                                      placeholder="Agrega observaciones internas si es necesario…"></textarea>
                        </div>
                    </div>

                    {{-- Carga de ítems --}}
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 bg-white/70 dark:bg-gray-900/70">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-layer-group text-amber-500"></i>
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-200">Productos</h4>
                            @if (!is_null($stockDisponible))
                                <span class="ml-auto text-[11px] px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                    <i class="fas fa-boxes"></i> Stock disp.: <b>{{ $stockDisponible }}</b>
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-300 mb-1">Bodega *</label>
                                <select wire:model="bodega_id"
                                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500">
                                    <option value="">Seleccione bodega</option>
                                    @foreach ($bodegas as $bodega)
                                        <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('bodega_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-300 mb-1">Producto *</label>
                                <select wire:model="producto_id"
                                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500">
                                    <option value="">Seleccione producto</option>
                                    @foreach ($productos as $producto)
                                        <option value="{{ $producto->id }}">{{ $producto->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('producto_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-600 dark:text-gray-300 mb-1">Cantidad *</label>
                                    <input type="number" min="1" wire:model="cantidad" placeholder="Cantidad"
                                           class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500">
                                    @error('cantidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <button type="button" wire:click="agregarItem"
                                        class="h-[42px] px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow-md transition">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Detalle: móvil tarjetas / desktop tabla --}}
                    <div class="space-y-3">
                        {{-- Móvil --}}
                        <div class="md:hidden space-y-3">
                            @forelse($items as $index => $item)
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-900">
                                    <div class="flex justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-bold text-gray-800 dark:text-white">{{ $item['producto_nombre'] }}</div>
                                            <div class="text-[11px] text-gray-500">{{ $item['bodega_nombre'] }}</div>
                                        </div>
                                        <button type="button" wire:click="quitarItem({{ $index }})"
                                                class="text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                    <div class="mt-2 text-sm"><span class="text-gray-600 dark:text-gray-300">Cant:</span> <b>{{ $item['cantidad'] }}</b></div>
                                </div>
                            @empty
                                <div class="rounded-xl border border-dashed p-4 text-center text-gray-500 dark:text-gray-400">Sin ítems agregados.</div>
                            @endforelse
                        </div>

                        {{-- Desktop --}}
                        <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
                                    <tr>
                                        <th class="p-3 text-left"><i class="fas fa-box"></i> Producto</th>
                                        <th class="p-3 text-left"><i class="fas fa-warehouse"></i> Bodega</th>
                                        <th class="p-3 text-center"><i class="fas fa-sort-numeric-up"></i> Cantidad</th>
                                        <th class="p-3 text-center"><i class="fas fa-tools"></i> Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse ($items as $index => $item)
                                        <tr class="hover:bg-violet-50 dark:hover:bg-gray-800 transition">
                                            <td class="p-3">{{ $item['producto_nombre'] }}</td>
                                            <td class="p-3">{{ $item['bodega_nombre'] }}</td>
                                            <td class="p-3 text-center">{{ $item['cantidad'] }}</td>
                                            <td class="p-3 text-center">
                                                <button type="button" wire:click="quitarItem({{ $index }})"
                                                        class="text-red-500 hover:text-red-700 transform hover:scale-110 transition">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="p-6 text-center text-gray-500">Sin ítems agregados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </section>

            {{-- HISTORIAL --}}
           <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/70 overflow-hidden">
    <header class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600">
                    <i class="fas fa-clipboard-list"></i>
                </span>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Salidas Registradas</h3>
            </div>

            {{-- Filtros por fecha --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 w-full md:w-auto">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Desde</label>
                    <input type="date" wire:model.defer="filtro_desde"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Hasta</label>
                    <input type="date" wire:model.defer="filtro_hasta"
                           class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
                </div>
                <div class="flex items-end gap-2">
                    <button type="button" wire:click="buscarSalidas"
                            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm">
                        Buscar
                    </button>
                    <button type="button" wire:click="limpiarFiltros"
                            class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        {{-- Chips opcionales mostrando el rango activo --}}
        @if($filtro_desde || $filtro_hasta)
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-900/70">
                    <i class="far fa-calendar"></i>
                    Rango: <b>{{ $filtro_desde ?: '—' }}</b> — <b>{{ $filtro_hasta ?: '—' }}</b>
                </span>
            </div>
        @endif
    </header>

    <div class="p-6">
        {{-- Cards móvil --}}
        <div class="md:hidden space-y-3">
            @forelse ($salidas as $s)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 p-3 bg-white dark:bg-gray-900">
                    <div class="text-sm font-semibold">{{ $s->socioNegocio->razon_social ?? '-' }}</div>
                    <div class="text-xs text-gray-500">{{ $s->fecha }} • {{ $s->ruta->ruta ?? '-' }}</div>
                    <div class="mt-2 text-xs text-gray-600 dark:text-gray-300">{{ $s->observaciones ?: '—' }}</div>
                    <div class="mt-2 border-t border-dashed pt-2">
                        <ul class="text-sm space-y-1">
                            @foreach ($s->detalles as $d)
                                <li>• {{ $d->producto->nombre ?? 'Producto' }} ({{ $d->cantidad }} — {{ $d->bodega->nombre }})</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed p-4 text-center text-gray-500">No hay salidas registradas aún.</div>
            @endforelse
        </div>

        {{-- Tabla desktop --}}
        <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="p-4 text-left"><i class="fas fa-calendar-alt"></i> Fecha</th>
                        <th class="p-4 text-left"><i class="fas fa-road"></i> Ruta</th>
                        <th class="p-4 text-left"><i class="fas fa-user-tie"></i> Cliente</th>
                        <th class="p-4 text-left"><i class="fas fa-comment-dots"></i> Observaciones</th>
                        <th class="p-4 text-left"><i class="fas fa-boxes"></i> Productos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($salidas as $salida)
                        <tr class="hover:bg-indigo-50 dark:hover:bg-gray-800 transition">
                            <td class="p-4">{{ $salida->fecha }}</td>
                            <td class="p-4">{{ $salida->ruta->ruta ?? '-' }}</td>
                            <td class="p-4">{{ $salida->socioNegocio->razon_social ?? '-' }}</td>
                            <td class="p-4">{{ $salida->observaciones }}</td>
                            <td class="p-4">
                                <ul class="list-disc ml-5 space-y-1">
                                    @foreach ($salida->detalles as $detalle)
                                        <li>{{ $detalle->producto->nombre ?? 'Producto' }} ({{ $detalle->cantidad }} — {{ $detalle->bodega->nombre }})</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500 dark:text-gray-400 italic">
                                <i class="fas fa-info-circle"></i> No hay salidas registradas aún.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

        </div>

        {{-- COLUMNA DERECHA (Resumen) --}}
        @php
            $totalItems = collect($items ?? [])->sum('cantidad');
        @endphp
        <aside class="xl:col-span-4 space-y-6">
            <div class="xl:sticky xl:top-6 space-y-6">
                <div class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-base font-bold text-gray-800 dark:text-white">Resumen de la salida</h4>
                        <span class="text-xs px-2 py-1 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">Tiempo real</span>
                    </div>

                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-300">Fecha</dt>
                            <dd class="font-semibold">{{ $fecha ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-300">Ruta</dt>
                            <dd class="font-semibold">
                                @php
                                    $rutaTxt = '—';
                                    if (!empty($ruta_id ?? null)) {
                                        $sel = $rutas->firstWhere('id', (int)$ruta_id);
                                        $rutaTxt = $sel->ruta ?? '—';
                                    }
                                @endphp
                                {{ $rutaTxt }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-300">Cliente</dt>
                            <dd class="font-semibold">
                                @php
                                    $cliTxt = '—';
                                    if (!empty($socio_negocio_id ?? null)) {
                                        $sel = $socios->firstWhere('id', (int)$socio_negocio_id);
                                        $cliTxt = $sel->razon_social ?? '—';
                                    }
                                @endphp
                                {{ $cliTxt }}
                            </dd>
                        </div>
                        <div class="border-t border-dashed border-gray-200 dark:border-gray-800 my-2"></div>
                        <div class="flex justify-between text-lg">
                            <dt class="font-extrabold">Ítems totales</dt>
                            <dd class="text-violet-700 dark:text-violet-300 font-extrabold">{{ number_format($totalItems ?? 0) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button type="button" wire:click="agregarItem"
                                class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <i class="fas fa-plus mr-1"></i> Agregar ítem
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="px-3 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-semibold shadow-md transition">
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                    </div>
                </div>

                <div class="rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 p-4 text-xs text-gray-600 dark:text-gray-300 bg-white/60 dark:bg-gray-900/60">
                    <p class="flex items-center gap-2"><i class="far fa-lightbulb text-amber-400"></i> Selecciona bodega y producto, ingresa la cantidad y pulsa <b>+</b>.</p>
                </div>
            </div>
        </aside>
    </section>
</form>
