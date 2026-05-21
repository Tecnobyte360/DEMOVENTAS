<div class="space-y-8">

    {{-- ===== FORMULARIO NUEVA SALIDA ===== --}}
    <form wire:submit.prevent="guardar">
        <section class="relative rounded-3xl border border-gray-200 dark:border-gray-800 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-white via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900 pointer-events-none"></div>

            {{-- Header --}}
            <header class="relative sticky top-0 z-10 backdrop-blur bg-white/70 dark:bg-gray-900/70 border-b border-gray-200 dark:border-gray-800 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-600/10 text-rose-600">
                        <i class="fas fa-minus-circle"></i>
                    </span>
                    <div>
                        <h2 class="text-xl font-extrabold text-gray-800 dark:text-white tracking-tight">Nueva Salida Manual</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Inventario &rsaquo; Salidas manuales</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" wire:click="$refresh"
                            class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition text-sm">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold shadow-md transition text-sm">
                        <i class="fas fa-save mr-1"></i>
                        <span wire:loading.remove>Registrar salida</span>
                        <span wire:loading class="animate-pulse">Guardando&hellip;</span>
                    </button>
                </div>
            </header>

            <div class="relative px-6 py-6 space-y-6">

                {{-- Datos generales --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha <span class="text-rose-500">*</span></label>
                        <input type="date" wire:model="fecha"
                               class="w-full px-4 py-2.5 rounded-xl border @error('fecha') border-rose-500 @else border-gray-300 dark:border-gray-700 @enderror dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500">
                        @error('fecha') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motivo <span class="text-rose-500">*</span></label>
                        <select wire:model="motivo"
                                class="w-full px-4 py-2.5 rounded-xl border @error('motivo') border-rose-500 @else border-gray-300 dark:border-gray-700 @enderror dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500">
                            <option value="">Seleccione motivo&hellip;</option>
                            @foreach($motivos as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('motivo') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Referencia / No. documento</label>
                        <input type="text" wire:model.defer="referencia" placeholder="Ej: REF-001"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observaciones</label>
                        <input type="text" wire:model.defer="observaciones" placeholder="Nota general&hellip;"
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500">
                    </div>
                </div>

                {{-- Agregar producto --}}
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-900/60 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-boxes text-amber-500"></i>
                        <h4 class="text-sm font-bold text-gray-700 dark:text-gray-200">Agregar producto</h4>
                        @if(!is_null($stockDisponible))
                            <span class="ml-auto text-xs px-2 py-1 rounded-full {{ $stockDisponible > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                <i class="fas fa-cubes mr-1"></i>Stock: <b>{{ number_format($stockDisponible, 3) }}</b>
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Bodega <span class="text-rose-500">*</span></label>
                            <select wire:model="bodega_id"
                                    class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500 text-sm">
                                <option value="">Seleccione&hellip;</option>
                                @foreach($bodegas as $b)
                                    <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                                @endforeach
                            </select>
                            @error('bodega_id') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Producto <span class="text-rose-500">*</span></label>
                            <select wire:model="producto_id"
                                    class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500 text-sm">
                                <option value="">Seleccione&hellip;</option>
                                @foreach($productos as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                @endforeach
                            </select>
                            @error('producto_id') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Cantidad <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.001" min="0.001" wire:model="cantidad" placeholder="0"
                                   class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500 text-sm">
                            @error('cantidad') <p class="text-rose-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex gap-2 items-end">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Nota del item</label>
                                <input type="text" wire:model.defer="obs_item" placeholder="Opcional&hellip;"
                                       class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500 text-sm">
                            </div>
                            <button type="button" wire:click="agregarItem"
                                    class="h-[42px] px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Tabla de items --}}
                @if(count($items))
                <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full text-sm">
                        <thead class="bg-rose-50 dark:bg-rose-900/20 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
                            <tr>
                                <th class="p-3 text-left">Producto</th>
                                <th class="p-3 text-left">Bodega</th>
                                <th class="p-3 text-center">Cantidad</th>
                                <th class="p-3 text-left">Nota</th>
                                <th class="p-3 text-center">Accion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($items as $i => $item)
                                <tr class="hover:bg-rose-50/50 dark:hover:bg-gray-800 transition">
                                    <td class="p-3 font-medium">{{ $item['producto_nombre'] }}</td>
                                    <td class="p-3 text-gray-600 dark:text-gray-300">{{ $item['bodega_nombre'] }}</td>
                                    <td class="p-3 text-center font-bold">{{ number_format($item['cantidad'], 3) }}</td>
                                    <td class="p-3 text-gray-500 text-xs">{{ $item['obs_item'] ?: '—' }}</td>
                                    <td class="p-3 text-center">
                                        <button type="button" wire:click="quitarItem({{ $i }})"
                                                class="text-red-500 hover:text-red-700 transition">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-900">
                                <td colspan="2" class="p-3 text-right text-xs font-semibold text-gray-500 uppercase">Total items:</td>
                                <td class="p-3 text-center font-extrabold text-rose-700">{{ count($items) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                    <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                        <i class="fas fa-inbox text-2xl mb-2 block"></i>
                        Sin productos agregados. Selecciona bodega, producto y cantidad.
                    </div>
                @endif

            </div>
        </section>
    </form>

    {{-- ===== HISTORIAL ===== --}}
    <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/70 overflow-hidden">
        <header class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-600/10 text-rose-600">
                        <i class="fas fa-history"></i>
                    </span>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">Historial de Salidas Manuales</h3>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Desde</label>
                        <input type="date" wire:model.defer="filtro_desde"
                               class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Hasta</label>
                        <input type="date" wire:model.defer="filtro_hasta"
                               class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Motivo</label>
                        <select wire:model.defer="filtro_motivo"
                                class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-rose-500 text-sm">
                            <option value="">Todos</option>
                            @foreach($motivos as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="button" wire:click="buscar"
                                class="flex-1 px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-sm shadow-sm">
                            <i class="fas fa-search mr-1"></i>Buscar
                        </button>
                        <button type="button" wire:click="limpiar"
                                class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="p-3 text-left">Fecha</th>
                        <th class="p-3 text-left">Motivo</th>
                        <th class="p-3 text-left">Referencia</th>
                        <th class="p-3 text-left">Productos</th>
                        <th class="p-3 text-left">Registrado por</th>
                        <th class="p-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($salidas as $s)
                        @php
                            $motivoLabel = match($s['motivo']) {
                                'ajuste'          => 'Ajuste',
                                'merma'           => 'Merma',
                                'consumo_interno' => 'Consumo',
                                'dano'            => 'Danio',
                                'transferencia'   => 'Transferencia',
                                default           => 'Otro',
                            };
                            $motivoColor = match($s['motivo']) {
                                'ajuste'          => 'bg-blue-100 text-blue-700',
                                'merma'           => 'bg-orange-100 text-orange-700',
                                'consumo_interno' => 'bg-green-100 text-green-700',
                                'dano'            => 'bg-red-100 text-red-700',
                                'transferencia'   => 'bg-purple-100 text-purple-700',
                                default           => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr class="hover:bg-rose-50/40 dark:hover:bg-gray-800 transition">
                            <td class="p-3 font-medium">{{ \Carbon\Carbon::parse($s['fecha'])->format('d/m/Y') }}</td>
                            <td class="p-3">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold {{ $motivoColor }}">
                                    {{ $motivoLabel }}
                                </span>
                            </td>
                            <td class="p-3 text-gray-500 text-xs">{{ $s['referencia'] ?: '—' }}</td>
                            <td class="p-3">
                                <ul class="space-y-0.5 text-xs">
                                    @foreach($s['detalles'] as $d)
                                        <li class="text-gray-700 dark:text-gray-300">
                                            <span class="font-medium">{{ $d['producto']['nombre'] ?? '—' }}</span>
                                            <span class="text-gray-500"> &times; {{ number_format($d['cantidad'], 3) }}</span>
                                            <span class="text-gray-400">({{ $d['bodega']['nombre'] ?? '—' }})</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="p-3 text-xs text-gray-500">{{ $s['user']['name'] ?? '—' }}</td>
                            <td class="p-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <button type="button" wire:click="verDetalle({{ $s['id'] }})"
                                            class="text-indigo-500 hover:text-indigo-700 transition" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" wire:click="pedirEliminar({{ $s['id'] }})"
                                            class="text-red-400 hover:text-red-600 transition" title="Eliminar y restaurar stock">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">
                                <i class="fas fa-info-circle mr-1"></i> No hay salidas manuales registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ===== MODAL DETALLE ===== --}}
    @if($mostrarDetalle && $salidaSeleccionada)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-rose-50 dark:bg-rose-900/20">
                <h3 class="font-extrabold text-gray-800 dark:text-white">Detalle de salida #{{ $salidaSeleccionada->id }}</h3>
                <button wire:click="cerrarDetalle" class="text-gray-500 hover:text-gray-800 dark:hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><span class="text-gray-500">Fecha:</span> <strong>{{ $salidaSeleccionada->fecha->format('d/m/Y') }}</strong></div>
                    <div><span class="text-gray-500">Motivo:</span> <strong>{{ \App\Models\Inventario\SalidaManual::motivoLabel($salidaSeleccionada->motivo) }}</strong></div>
                    <div><span class="text-gray-500">Referencia:</span> {{ $salidaSeleccionada->referencia ?: '—' }}</div>
                    <div><span class="text-gray-500">Registrado por:</span> {{ $salidaSeleccionada->user->name ?? '—' }}</div>
                    @if($salidaSeleccionada->observaciones)
                    <div class="col-span-2"><span class="text-gray-500">Observaciones:</span> {{ $salidaSeleccionada->observaciones }}</div>
                    @endif
                </div>
                <table class="w-full text-sm border rounded-xl overflow-hidden">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-xs uppercase">
                        <tr>
                            <th class="p-2 text-left">Producto</th>
                            <th class="p-2 text-left">Bodega</th>
                            <th class="p-2 text-center">Cantidad</th>
                            <th class="p-2 text-left">Nota</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($salidaSeleccionada->detalles as $d)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="p-2">{{ $d->producto->nombre ?? '—' }}</td>
                            <td class="p-2 text-gray-500">{{ $d->bodega->nombre ?? '—' }}</td>
                            <td class="p-2 text-center font-bold">{{ number_format($d->cantidad, 3) }}</td>
                            <td class="p-2 text-xs text-gray-400">{{ $d->observacion ?: '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end">
                <button wire:click="cerrarDetalle"
                        class="px-5 py-2.5 rounded-xl bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-semibold">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== MODAL CONFIRMAR ELIMINAR ===== --}}
    @if($confirmarEliminar)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6 w-full max-w-sm mx-4">
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </span>
                <div>
                    <h4 class="font-extrabold text-gray-800 dark:text-white">Eliminar salida</h4>
                    <p class="text-xs text-gray-500">El stock sera restaurado automaticamente.</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300 mb-5">
                Esta accion eliminara el registro y devolvera el stock a las bodegas correspondientes.
                <strong>No se puede deshacer.</strong>
            </p>
            <div class="flex gap-3">
                <button wire:click="cancelarEliminar"
                        class="flex-1 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
                    Cancelar
                </button>
                <button wire:click="eliminar"
                        class="flex-1 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold text-sm shadow-md">
                    <i class="fas fa-trash-alt mr-1"></i> Si, eliminar
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
