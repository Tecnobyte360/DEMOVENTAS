<form wire:submit.prevent="crearEntrada" class="space-y-8 md:space-y-10">

    {{-- PASOS / AYUDAS --}}
    <nav class="rounded-2xl md:rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/60 dark:bg-gray-900/60 backdrop-blur px-4 py-3 md:px-6 md:py-4">
        <ol class="flex flex-wrap items-center gap-3 md:gap-4 text-xs md:text-sm text-gray-600 dark:text-gray-300">
            <li class="flex items-center gap-2">
                <span class="h-6 w-6 md:h-7 md:w-7 grid place-items-center rounded-full bg-violet-600 text-white text-[10px] md:text-xs font-bold">1</span>
                <span class="font-semibold">Datos</span>
            </li>
            <span class="text-gray-400 hidden sm:inline">—</span>
            <li class="flex items-center gap-2">
                <span class="h-6 w-6 md:h-7 md:w-7 grid place-items-center rounded-full bg-violet-600 text-white text-[10px] md:text-xs font-bold">2</span>
                <span class="font-semibold">Productos</span>
            </li>
            <span class="text-gray-400 hidden sm:inline">—</span>
            <li class="flex items-center gap-2">
                <span class="h-6 w-6 md:h-7 md:w-7 grid place-items-center rounded-full bg-violet-600 text-white text-[10px] md:text-xs font-bold">3</span>
                <span class="font-semibold">Revisar</span>
            </li>
        </ol>
    </nav>

    <section class="grid grid-cols-1 xl:grid-cols-12 gap-6 md:gap-8">
        {{-- COLUMNA IZQUIERDA --}}
        <div class="xl:col-span-8 space-y-6 md:space-y-8">
            <section class="relative rounded-2xl md:rounded-3xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-white via-gray-50 to-gray-100 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900"></div>

                {{-- Header sticky --}}
                <header class="sticky top-0 z-10 backdrop-blur bg-white/80 dark:bg-gray-900/70 border-b border-gray-200 dark:border-gray-800 px-4 py-3 md:px-6 md:py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2 md:gap-3">
                        <span class="inline-flex h-8 w-8 md:h-10 md:w-10 items-center justify-center rounded-xl md:rounded-2xl bg-violet-600/10 text-violet-700 dark:text-violet-300">
                            <i class="fas fa-dolly-flatbed text-sm md:text-base"></i>
                        </span>
                        <div>
                            <h2 class="text-base md:text-xl font-extrabold text-gray-800 dark:text-white tracking-tight">Nueva Entrada</h2>
                            <p class="text-[11px] md:text-xs text-gray-500 dark:text-gray-400">Inventario / Entradas</p>
                        </div>
                    </div>

                    {{-- Acciones desktop --}}
                    <div class="hidden md:flex items-center gap-3">
                        <button type="button" wire:click="cancelarEntrada"
                                class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <i class="fas fa-times mr-2"></i>Cancelar
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="px-5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-semibold shadow-md transition">
                            <i class="fas fa-save mr-2"></i>
                            <span wire:loading.remove wire:target="crearEntrada">Guardar</span>
                            <span wire:loading wire:target="crearEntrada" class="animate-pulse">Guardando…</span>
                        </button>
                    </div>
                </header>

                <div class="relative px-4 py-5 md:px-6 md:py-6 space-y-6 md:space-y-8">
                    {{-- Mensajes --}}
                    @if (session()->has('message'))
                        <div class="px-3 py-2 md:px-4 md:py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center gap-2 shadow-sm text-sm">
                            <i class="fas fa-check-circle"></i> {{ session('message') }}
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="px-3 py-2 md:px-4 md:py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 flex items-center gap-2 shadow-sm text-sm">
                            <i class="fas fa-times-circle"></i> {{ session('error') }}
                        </div>
                    @endif

                    {{-- Datos Generales --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                    <div wire:ignore x-data="{ fp:null }" x-init="
                        fp = flatpickr($refs.fechaContabilizacion, {
                            dateFormat: 'Y-m-d',        // formato para Livewire
                            altInput: true,             // input visible amigable
                            altFormat: 'd-m-Y',         // formato visible
                            defaultDate: @js($fecha_contabilizacion), // si hay valor previo, lo carga
                            onChange: (_sel, iso) => $wire.set('fecha_contabilizacion', iso)
                        });

                        // Cuando Livewire re-renderiza, volvemos a setear la fecha visible
                        Livewire.hook('message.processed', () => {
                            const val = @js($fecha_contabilizacion);
                            if (val) {
                            fp.setDate(val, true);    // true = triggerChange (mantiene sincronía)
                            } else {
                            fp.clear();               // si el prop está null, limpia el input
                            }
                        });
                        ">
                        <label class="block text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Fecha *
                        </label>
                        <input x-ref="fechaContabilizacion" type="text" placeholder="dd-mm-aaaa"
                                class="w-full px-3 py-2 md:px-4 md:py-2.5 rounded-xl border
                                @error('fecha_contabilizacion') border-red-500
                                @else border-gray-300 dark:border-gray-700 @enderror
                                dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500" />
                        @error('fecha_contabilizacion')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        </div>


                        <div>
                            <label class="block text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Socio *</label>
                            <div wire:ignore x-data x-init="
                                const s = new TomSelect($refs.socioSelect,{
                                  placeholder:'Seleccione…', allowEmptyOption:true, create:false,
                                  onChange: v => @this.set('socio_negocio_id', v),
                                });
                                Livewire.hook('message.processed', () => s.refreshOptions(false));
                            ">
                                <select x-ref="socioSelect"
                                        class="w-full px-3 py-2 md:px-4 md:py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500">
                                    <option value="">Seleccione</option>
                                    @foreach ($socios as $socio)
                                        <option value="{{ $socio->id }}">{{ $socio->razon_social }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('socio_negocio_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-end">
                            <div class="w-full h-[42px] md:h-[44px] flex items-center justify-between px-3 md:px-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                <span class="text-[11px] md:text-xs text-gray-500">Estado</span>
                                <span class="inline-flex items-center gap-2 text-[11px] px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    <i class="fas fa-check"></i> Preparando
                                </span>
                            </div>
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <label class="block text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observaciones</label>
                            <textarea rows="3" wire:model.lazy="observaciones"
                                      class="w-full px-3 py-2 md:px-4 md:py-3 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500"
                                      placeholder="Notas internas…"></textarea>
                        </div>
                    </div>

                    {{-- ======= DETALLE: MÓVIL (cards) ======= --}}
                    <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
                <table class="min-w-full table-fixed text-sm">
                    {{-- Forzamos proporciones para que no se achique Producto/Bodega --}}
                    <colgroup>
                    <col style="width:28%">  {{-- Producto --}}
                    <col style="width:24%">  {{-- Descripción --}}
                    <col style="width:12%">  {{-- Cantidad --}}
                    <col style="width:18%">  {{-- Bodega --}}
                    <col style="width:14%">  {{-- Costo --}}
                    <col style="width:4%">   {{-- Acción --}}
                    </colgroup>

                    <thead class="sticky top-0 z-10 bg-gray-100/90 dark:bg-gray-800/90 backdrop-blur border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-200">
                    <tr>
                        <th class="p-3 text-left font-semibold"><i class="fas fa-box"></i> Producto</th>
                        <th class="p-3 text-left font-semibold"><i class="fas fa-info-circle"></i> Descripción</th>
                        <th class="p-3 text-center font-semibold"><i class="fas fa-sort-numeric-up"></i> Cantidad</th>
                        <th class="p-3 text-center font-semibold"><i class="fas fa-warehouse"></i> Bodega</th>
                        <th class="p-3 text-center font-semibold"><i class="fas fa-dollar-sign"></i> Costo</th>
                        <th class="p-3 text-center font-semibold"><i class="fas fa-tools"></i></th>
                    </tr>
                    </thead>

                    <tbody class="[&>tr:nth-child(even)]:bg-gray-50/60 dark:[&>tr:nth-child(even)]:bg-gray-900/40 text-gray-800 dark:text-gray-200">
                    @foreach ($entradas as $index => $entrada)
                        <tr class="border-t dark:border-gray-700 hover:bg-violet-50 dark:hover:bg-gray-700 transition">
                        {{-- Producto (mínimo cómodo) --}}
                        <td class="p-3 align-top">
                            <input
                            id="fila-{{ $index }}-producto"
                            list="productos_list_{{ $index }}"
                            wire:model.lazy="entradas.{{ $index }}.producto_nombre"
                            wire:change="actualizarProductoDesdeNombre({{ $index }})"
                            @keydown="alPresionar($event, {{ $index }}, 'producto')"
                            placeholder="Buscar producto…"
                            class="w-full min-w-[220px] h-10 px-3 rounded-xl border
                                    @error('entradas.' . $index . '.producto_id') border-red-500
                                    @elseif(!empty($entrada['producto_id'])) border-green-500
                                    @else border-gray-300 @enderror
                                    dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600" />
                            <datalist id="productos_list_{{ $index }}">
                            @foreach ($productos as $p)
                                <option value="{{ $p->nombre }}">{{ $p->nombre }}</option>
                            @endforeach
                            </datalist>
                            @error("entradas.$index.producto_id")
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                            @enderror
                        </td>

                        {{-- Descripción --}}
                        <td class="p-3 align-top">
                            <input type="text" disabled
                            wire:model="entradas.{{ $index }}.descripcion"
                            class="w-full min-w-[200px] h-10 bg-gray-100 dark:bg-gray-700 px-3 rounded-xl text-sm text-gray-700 dark:text-white" />
                        </td>

                        {{-- Cantidad (ancho fijo para que no empuje) --}}
                        <td class="p-3 text-center align-top">
                            <div class="inline-flex items-center rounded-xl border border-gray-300 dark:border-gray-700 overflow-hidden">
                            <button type="button" class="px-3 h-10 select-none" @click="$wire.decrementCantidad({{ $index }})">−</button>
                            <input id="fila-{{ $index }}-cantidad" type="number" min="1"
                                wire:model="entradas.{{ $index }}.cantidad"
                                @keydown="alPresionar($event, {{ $index }}, 'cantidad')"
                                class="w-[76px] text-center h-10 border-0 dark:bg-gray-800 dark:text-white focus:ring-0" />
                            <button type="button" class="px-3 h-10 select-none" @click="$wire.incrementCantidad({{ $index }})">+</button>
                            </div>
                            @error("entradas.$index.cantidad")
                            <span class="text-red-600 text-xs block mt-1">{{ $message }}</span>
                            @enderror
                        </td>

                        {{-- Bodega (mínimo ancho para que respire) --}}
                        <td class="p-3 text-center align-top">
                            <select id="fila-{{ $index }}-bodega"
                            wire:model="entradas.{{ $index }}.bodega_id"
                            @change="$wire.recordarUltimos({{ $index }})"
                            @keydown="alPresionar($event, {{ $index }}, 'bodega')"
                            class="w-full min-w-[180px] h-10 px-3 rounded-xl border
                                    @error('entradas.' . $index . '.bodega_id') border-red-500
                                    @elseif(!empty($entrada['bodega_id'])) border-green-500
                                    @else border-gray-300 @enderror
                                    dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
                            <option value="">Seleccione</option>
                            @foreach ($bodegas as $b)
                                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                            @endforeach
                            </select>
                            @error("entradas.$index.bodega_id")
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                            @enderror
                        </td>

                        {{-- Costo (ancho fijo) --}}
                        <td class="p-3 text-center align-top">
                            <input id="fila-{{ $index }}-precio" type="number" step="0.01" min="0"
                            wire:model="entradas.{{ $index }}.precio_unitario"
                            @keydown="alPresionar($event, {{ $index }}, 'precio')"
                            @change="$wire.recordarUltimos({{ $index }})"
                            class="w-[120px] h-10 text-center px-3 rounded-xl border
                                    @error('entradas.' . $index . '.precio_unitario') border-red-500
                                    @elseif(!empty($entrada['precio_unitario'])) border-green-500
                                    @else border-gray-300 @enderror
                                    dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600" />
                            @error("entradas.$index.precio_unitario")
                            <span class="text-red-600 text-xs">{{ $message }}</span>
                            @enderror
                        </td>

                        {{-- Acción --}}
                        <td class="p-3 text-center align-top">
                            <button wire:click="eliminarFila({{ $index }})" type="button"
                            class="text-red-500 hover:text-red-700 transition transform hover:scale-110"
                            title="Eliminar fila">
                            <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                        </tr>
                    @endforeach
                    </tbody>

                
                </table>
                </div>

                 

                {{-- BARRA ACCIONES MÓVIL --}}
                <div class="md:hidden sticky bottom-0 z-10 border-t border-gray-200 dark:border-gray-800 bg-white/95 dark:bg-gray-900/90 backdrop-blur px-4 py-3 flex items-center gap-2">
                    <button type="button" wire:click="agregarFila"
                            class="flex-1 px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200">
                        <i class="fas fa-plus mr-1"></i> Ítem
                    </button>
                    <button type="button" wire:click="cancelarEntrada"
                            class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200">
                        Cancelar
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                            class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-semibold">
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </section>

            {{-- HISTORIAL --}}
            <section class="rounded-2xl md:rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/70 overflow-hidden">
    <header class="px-4 py-3 md:px-6 md:py-4 border-b border-gray-200 dark:border-gray-800">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-8 w-8 md:h-10 md:w-10 items-center justify-center rounded-xl md:rounded-2xl bg-indigo-600/10 text-indigo-600">
                    <i class="fas fa-clipboard-list"></i>
                </span>
                <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-white">Entradas Registradas</h3>
            </div>

          <div wire:ignore x-data="{fpDesde:null, fpHasta:null}"
     x-init="
       fpDesde = flatpickr($refs.desde, {
         dateFormat: 'Y-m-d',
         altInput: true,
         altFormat: 'd-m-Y',
         defaultDate: @js($filtro_desde),
         onChange: (_sel, iso) => $wire.set('filtro_desde', iso)
       });
       fpHasta = flatpickr($refs.hasta, {
         dateFormat: 'Y-m-d',
         altInput: true,
         altFormat: 'd-m-Y',
         defaultDate: @js($filtro_hasta),
         onChange: (_sel, iso) => $wire.set('filtro_hasta', iso)
       });

       // Cuando Livewire re-renderiza, re-sincroniza las fechas visibles
       Livewire.hook('message.processed', () => {
         fpDesde && fpDesde.setDate(@js($filtro_desde), true);
         fpHasta && fpHasta.setDate(@js($filtro_hasta), true);
       });
     ">
  <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
    <div>
      <label class="block text-xs text-gray-500 mb-1">Desde</label>
      <input x-ref="desde" type="text"
             placeholder="dd-mm-aaaa"
             class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
    </div>
    <div>
      <label class="block text-xs text-gray-500 mb-1">Hasta</label>
      <input x-ref="hasta" type="text"
             placeholder="dd-mm-aaaa"
             class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
    </div>
    <div class="flex items-end gap-2">
      <button type="button" wire:click="aplicarFiltros"
              class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl shadow">
        Buscar
      </button>
      <button type="button" wire:click="limpiarFiltros"
              class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
        Limpiar
      </button>
    </div>
  </div>
</div>




        </div>

        {{-- Chips de filtro activos (opcional) --}}
        @if($filtro_desde || $filtro_hasta)
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-full border border-gray-200 dark:border-gray-700 bg-white/70 dark:bg-gray-900/70">
                    <i class="far fa-calendar"></i>
                    Rango:
                    <b>{{ $filtro_desde ?: '—' }}</b> — <b>{{ $filtro_hasta ?: '—' }}</b>
                </span>
            </div>
        @endif
    </header>

    <div class="p-4 md:p-6">
        {{-- Cards móvil --}}
        <div class="md:hidden space-y-3">
            @forelse ($entradasMercancia as $e)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 p-3 bg-white dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold">{{ $e->socioNegocio->razon_social ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $e->fecha_contabilizacion }}</div>
                        </div>
                        <button type="button"
                                wire:click="toggleDetalleFila({{ $e->id }})"
                                class="text-xs px-2 py-1 rounded-full border border-gray-300 dark:border-gray-700">
                            {{ $filaAbiertaId === $e->id ? 'Ocultar' : 'Ver detalle' }}
                        </button>
                    </div>

                    @if ($filaAbiertaId === $e->id)
                        <div class="mt-3 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800 text-[11px] text-gray-600 dark:text-gray-300">Detalle #{{ $e->id }}</div>
                            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                                @php $det = $detallesPorEntrada[$e->id] ?? collect(); @endphp
                                @forelse ($det as $d)
                                    <div class="px-3 py-2 text-sm flex justify-between">
                                        <div class="pr-2">
                                            <div class="font-medium">{{ $d->producto->nombre ?? '-' }}</div>
                                            <div class="text-[11px] text-gray-500">{{ $d->bodega->nombre ?? '-' }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div>x {{ $d->cantidad }}</div>
                                            <div class="text-[11px]">${{ number_format($d->precio_unitario, 2, ',', '.') }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="px-3 py-2 text-center text-gray-500">Sin ítems</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-dashed p-4 text-center text-gray-500">Sin entradas</div>
            @endforelse

            {{-- Paginación móvil --}}
            <div class="mt-3">
                {{ $entradasMercancia->onEachSide(1)->links() }}
            </div>
        </div>

        {{-- Tabla desktop --}}
        <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="p-4 text-left"><i class="fas fa-calendar-alt"></i> Fecha</th>
                        <th class="p-4 text-left"><i class="fas fa-user-tie"></i> Socio</th>
                        <th class="p-4 text-center"><i class="fas fa-eye"></i> Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($entradasMercancia as $entrada)
                        <tr class="hover:bg-indigo-50 dark:hover:bg-gray-800 transition">
                            <td class="p-4">{{ $entrada->fecha_contabilizacion }}</td>
                            <td class="p-4">{{ $entrada->socioNegocio->razon_social ?? '-' }}</td>
                            <td class="p-4 text-center">
                                <button type="button"
                                        wire:click="toggleDetalleFila({{ $entrada->id }})"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                    <i class="fas {{ $filaAbiertaId === $entrada->id ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
                                    {{ $filaAbiertaId === $entrada->id ? 'Ocultar' : 'Ver detalle' }}
                                </button>
                            </td>
                        </tr>

                        @if ($filaAbiertaId === $entrada->id)
                            <tr>
                                <td colspan="3" class="p-0 bg-gray-50/60 dark:bg-gray-900/40">
                                    <div class="px-6 py-4">
                                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                            <div class="px-4 py-2 bg-gray-100 dark:bg-gray-800 text-xs font-semibold text-gray-600 dark:text-gray-300">
                                                Detalle de la entrada #{{ $entrada->id }}
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-sm">
                                                    <thead class="bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300">
                                                        <tr>
                                                            <th class="p-3 text-left">Producto</th>
                                                            <th class="p-3 text-left">Descripción</th>
                                                            <th class="p-3 text-center">Cantidad</th>
                                                            <th class="p-3 text-center">Bodega</th>
                                                            <th class="p-3 text-center">Costo</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                        @php $det = $detallesPorEntrada[$entrada->id] ?? collect(); @endphp
                                                        @forelse ($det as $d)
                                                            <tr class="bg-white dark:bg-gray-900">
                                                                <td class="p-3">{{ $d->producto->nombre ?? '-' }}</td>
                                                                <td class="p-3">{{ $d->producto->descripcion ?? '-' }}</td>
                                                                <td class="p-3 text-center">{{ $d->cantidad }}</td>
                                                                <td class="p-3 text-center">{{ $d->bodega->nombre ?? '-' }}</td>
                                                                <td class="p-3 text-center">${{ number_format($d->precio_unitario, 2, ',', '.') }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="5" class="p-4 text-center text-gray-500">Sin ítems</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación desktop --}}
        <div class="hidden md:block mt-4">
            {{ $entradasMercancia->onEachSide(1)->links() }}
        </div>
    </div>
</section>

        </div>

        {{-- COLUMNA DERECHA (Resumen) --}}
        @php
            $totalItems = collect($entradas)->sum('cantidad');
            $subtotal   = collect($entradas)->sum(fn($e) => (float)($e['cantidad'] ?? 0) * (float)($e['precio_unitario'] ?? 0));
            $iva        = 0.00;
            $total      = $subtotal + $iva;
        @endphp

        <aside class="xl:col-span-4 space-y-6">
            <div class="xl:sticky xl:top-6 space-y-6">
                <div class="rounded-2xl md:rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur p-4 md:p-6 shadow-2xl">
                    <div class="flex items-center justify-between mb-3 md:mb-4">
                        <h4 class="text-sm md:text-base font-bold text-gray-800 dark:text-white">Resumen</h4>
                        <span class="text-[10px] md:text-xs px-2 py-1 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">Tiempo real</span>
                    </div>

                    <dl class="space-y-2 md:space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-600 dark:text-gray-300">Fecha</dt><dd class="font-semibold">{{ $fecha_contabilizacion ?: '—' }}</dd></div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600 dark:text-gray-300">Socio</dt>
                            <dd class="font-semibold">
                                @php
                                    $socioTxt = '—';
                                    if (!empty($socio_negocio_id ?? null)) {
                                        $sel = $socios->firstWhere('id', (int)$socio_negocio_id);
                                        $socioTxt = $sel->razon_social ?? '—';
                                    }
                                @endphp
                                {{ $socioTxt }}
                            </dd>
                        </div>
                        <div class="flex justify-between"><dt class="text-gray-600 dark:text-gray-300">Ítems</dt><dd class="font-semibold">{{ number_format($totalItems ?? 0) }}</dd></div>
                        <div class="border-t border-dashed border-gray-200 dark:border-gray-800 my-2"></div>
                        <div class="flex justify-between"><dt class="text-gray-600 dark:text-gray-300">Subtotal</dt><dd class="font-semibold">${{ number_format($subtotal ?? 0, 2, ',', '.') }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-600 dark:text-gray-300">Impuestos</dt><dd class="font-semibold">${{ number_format($iva ?? 0, 2, ',', '.') }}</dd></div>
                        <div class="flex justify-between text-base md:text-lg"><dt class="font-extrabold">Total</dt><dd class="text-violet-700 dark:text-violet-300 font-extrabold">${{ number_format($total ?? 0, 2, ',', '.') }}</dd></div>
                    </dl>

                    <div class="hidden md:grid mt-5 grid-cols-2 gap-3">
                        <button type="button" wire:click="agregarFila"
                                class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <i class="fas fa-plus mr-1"></i> Agregar ítem
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                                class="px-3 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-semibold shadow-md transition">
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>
        </aside>
    </section>

    {{-- FAB agregar producto (visible en móvil también) --}}
    <button type="button" wire:click="agregarFila"
            class="md:hidden fixed bottom-20 right-5 h-12 w-12 rounded-full shadow-2xl bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 grid place-items-center hover:scale-105 transition"
            title="Agregar Producto">
        <i class="fas fa-plus"></i>
    </button>
</form>
