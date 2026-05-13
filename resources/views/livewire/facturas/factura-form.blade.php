@once
    @push('styles')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.css">
    @endpush
@endonce

@once
    @push('scripts')
        <script>
            window.deferLoadingAlpine = (alpineInit) => {
                document.addEventListener('livewire:init', alpineInit)
            }
        </script>
        <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>
    @endpush
@endonce

<div x-data="{
    goPicker() {
        const el = document.querySelector('[data-first-product]');
        if (el) {
            el.focus();
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}" x-on:keydown.window.ctrl.k.prevent="goPicker()"
    x-on:keydown.window.meta.k.prevent="goPicker()" class="p-6 md:p-8">
    {{-- ================= PREVISUALIZADOR DE IMÁGENES ================= --}}
    <div x-data="{ open: false, src: null, title: '' }"
        x-on:preview-image.window="
            src   = $event.detail.src;
            title = $event.detail.title || '';
            open  = true;
        "
        x-id="['imgviewer']">
        <div x-show="open" x-transition.opacity x-cloak class="fixed inset-0 z-[200] flex items-center justify-center"
            @keydown.escape.window="open = false">
            <div class="absolute inset-0 bg-black/70" @click="open = false"></div>

            <div
                class="relative z-10 max-w-4xl w-[90vw] max-h-[90vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                        x-text="title || 'Imagen de producto'"></h3>
                    <button type="button"
                        class="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700"
                        @click="open = false" aria-label="Cerrar">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <div class="p-3 flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                    <img :src="src" :alt="title || 'Imagen de producto'"
                        class="max-h-[80vh] w-auto object-contain rounded-xl">
                </div>
            </div>
        </div>
    </div>

    {{-- ================= CARD PRINCIPAL ================= --}}
    <section
        class="mt-6 md:mt-8 rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">

        {{-- ===== PASO 1: Datos de cabecera ===== --}}
        <section class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-800" aria-label="Datos de la factura">
            <header class="mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span
                        class="inline-grid place-items-center w-9 md:w-10 h-9 md:h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">
                        1
                    </span>
                    Factura de ventas
                </h2>
            </header>

            {{-- ===== CARGAR DESDE COTIZACIÓN ===== --}}
            <section class="mb-6">
                <div
                    class="rounded-2xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/70 dark:bg-indigo-900/20 p-4 md:p-5">
                    <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                        <div class="flex-1">
                            <label
                                class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                                Cargar desde cotización
                            </label>

                            <div wire:ignore>
                                <select id="cotizacion-select" data-cotizacion-select
                                    class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-indigo-200 dark:border-indigo-700 bg-white dark:bg-gray-800 dark:text-white text-base focus:outline-none focus:ring-4 focus:ring-indigo-300/60 @error('cotizacion_id') border-red-500 focus:ring-red-300 @enderror">
                                    <option value="">Buscar cotización...</option>
                                    @foreach ($cotizaciones as $cot)
                                        <option value="{{ $cot->id }}" @selected((int) ($cotizacion_id ?? 0) === (int) $cot->id)>
                                            #{{ $cot->id }}
                                            — {{ \Carbon\Carbon::parse($cot->fecha)->format('Y-m-d') }}
                                            — {{ $cot->socioNegocio->razon_social ?? 'Cliente' }}
                                            @if (!empty($cot->estado))
                                                — {{ ucfirst($cot->estado) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @error('cotizacion_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror

                            @if ($cotizacion_id)
                                @php
                                    $cotSel = $cotizaciones->firstWhere('id', (int) $cotizacion_id);
                                @endphp

                                @if ($cotSel)
                                    <div
                                        class="mt-3 flex flex-wrap items-center gap-2 text-xs md:text-sm text-indigo-900 dark:text-indigo-200">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/80 dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700">
                                            <i class="fa-solid fa-file-lines"></i>
                                            Cotización #{{ $cotSel->id }}
                                        </span>

                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/80 dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700">
                                            <i class="fa-solid fa-user"></i>
                                            {{ $cotSel->socioNegocio->razon_social ?? 'Cliente' }}
                                        </span>

                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/80 dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700">
                                            <i class="fa-solid fa-calendar-days"></i>
                                            {{ \Carbon\Carbon::parse($cotSel->fecha)->format('Y-m-d') }}
                                        </span>

                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-white/80 dark:bg-gray-800 border border-indigo-200 dark:border-indigo-700">
                                            <i class="fa-solid fa-circle-dollar-to-slot"></i>
                                            ${{ number_format((float) ($cotSel->total ?? 0), 2) }}
                                        </span>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2">
                            <button type="button" wire:click="cargarCotizacionSeleccionada"
                                wire:loading.attr="disabled"
                                wire:target="cargarCotizacionSeleccionada,cargarDesdeCotizacion"
                                class="h-12 md:h-14 px-5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed">
                                <i class="fa-solid fa-file-import mr-2"></i>
                                <span wire:loading.remove
                                    wire:target="cargarCotizacionSeleccionada,cargarDesdeCotizacion">Cargar
                                    cotización</span>
                                <span wire:loading
                                    wire:target="cargarCotizacionSeleccionada,cargarDesdeCotizacion">Cargando…</span>
                            </button>

                            @if ($cotizacion_id)
                                <button type="button" wire:click="setCotizacion(null)"
                                    class="h-12 md:h-14 px-5 rounded-2xl bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 shadow">
                                    <i class="fa-solid fa-xmark mr-2"></i>
                                    Limpiar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <section>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                        Cliente <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="socio_negocio_id"
                        class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-base focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('socio_negocio_id') border-red-500 focus:ring-red-300 @enderror">
                        <option value="">— Seleccione —</option>
                        @foreach ($clientes as $c)
                            <option value="{{ $c->id }}">{{ $c->razon_social }} ({{ $c->nit }})</option>
                        @endforeach
                    </select>
                    @error('socio_negocio_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </section>

                <section>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                        Serie
                    </label>
                    <div class="flex items-center gap-3">
                        <select wire:model.live="serie_id"
                            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-base focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                            <option value="">— Seleccione —</option>
                            @foreach ($series as $s)
                                <option value="{{ $s->id }}">
                                    {{ $s->nombre }} ({{ $s->prefijo }}: {{ $s->proximo }} →
                                    {{ $s->hasta }})
                                </option>
                            @endforeach
                        </select>

                        @if ($serieDefault)
                            <span
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200 text-xs">
                                <i class="fa fa-star"></i> {{ $serieDefault->nombre }}
                            </span>
                        @endif
                    </div>
                </section>

                <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                            Fecha <span class="text-red-500">*</span>
                        </label>
                        <input type="date" wire:model.live="fecha"
                            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('fecha') border-red-500 focus:ring-red-300 @enderror">
                        @error('fecha')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                            Vencimiento
                        </label>
                        <input type="date" wire:model.live="vencimiento"
                            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                    </div>
                </section>
            </div>

            <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <section>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                        Condición de pago
                    </label>
                    <select wire:model.live="condicion_pago_id"
                        class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                        @foreach ($condicionesPago as $cond)
                            <option value="{{ $cond->id }}">
                                {{ $cond->nombre }}
                                @if ($cond->tipo === 'credito')
                                    ({{ $cond->plazo_dias }} días)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </section>

                <section>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                        Plazo (días)
                    </label>
                    <input type="number" min="1" wire:model.live.debounce.250ms="plazo_dias"
                        class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 disabled:opacity-50"
                        @if ($tipo_pago !== 'credito') disabled @endif>
                </section>

                <section>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                        Cuenta contable para cobrar
                    </label>

                    @php
                        $selectedId = (int) ($cuenta_cobro_id ?? 0);
                        $tieneSel =
                            $selectedId &&
                            ($cuentasCXC->contains('id', $selectedId) || $cuentasCaja->contains('id', $selectedId));
                        $opActual =
                            !$tieneSel && $selectedId
                                ? \App\Models\CuentasContables\PlanCuentas::find($selectedId)
                                : null;
                    @endphp

                    <select wire:model.live.number="cuenta_cobro_id"
                        class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                        @if ($opActual)
                            <option value="{{ $opActual->id }}">
                                {{ $opActual->codigo }} — {{ $opActual->nombre }} (actual)
                            </option>
                        @endif

                        @if ($cuentasCXC->count())
                            <optgroup label="Cuentas por cobrar (CxC)">
                                @foreach ($cuentasCXC as $c)
                                    <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nombre }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif

                        @if ($cuentasCaja->count())
                            <optgroup label="Caja y Bancos">
                                @foreach ($cuentasCaja as $c)
                                    <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nombre }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>

                    @error('cuenta_cobro_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </section>
            </div>

            <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <section class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                            Términos
                        </label>
                        <input type="text" placeholder="Condiciones, notas de crédito…"
                            wire:model.live.debounce.400ms="terminos_pago"
                            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                    </div>
                    <div>
                        <label
                            class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
                            Notas
                        </label>
                        <input type="text" placeholder="Observaciones visibles en el documento"
                            wire:model.live.debounce.400ms="notas"
                            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                    </div>
                </section>
            </div>
        </section>

        {{-- ===== PASO 2: Líneas ===== --}}
        <section class="p-6 md:p-8" aria-label="Líneas de la factura">
            <header class="mb-6">
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span
                        class="inline-grid place-items-center w-9 h-9 md:w-10 md:h-10 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
                        2
                    </span>
                    Agrega productos
                </h2>
            </header>

            <section class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto shadow"
                aria-label="Tabla de líneas">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left">Imagen</th>
                            <th class="px-4 py-3 text-left">Producto</th>
                            <th class="px-4 py-3 text-left">Bodega</th>
                            <th class="px-4 py-3 text-right">Cant.</th>
                            <th class="px-4 py-3 text-right">Precio</th>
                            <th class="px-4 py-3 text-left">Impuesto</th>
                            <th class="px-4 py-3 text-right">Imp. $</th>
                            <th class="px-4 py-3 text-right">Total línea</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($lineas as $i => $l)
                            @php
                                $ivaMonto = \App\Helpers\Money::ivaImporte($l);
                                $totalLin = \App\Helpers\Money::totalLinea($l);

                                $productoIdActual = (int) ($lineas[$i]['producto_id'] ?? 0);
                                $productoNombreActual = $productosSeleccionados[$productoIdActual] ?? null;

                                $pid = $productoIdActual;
                                $bid = (int) ($lineas[$i]['bodega_id'] ?? 0);

                                $prodSel = $pid
                                    ? \App\Models\Productos\Producto::with(['cuentaIngreso', 'impuesto'])
                                        ->select('id', 'nombre', 'cuenta_ingreso_id', 'impuesto_id', 'es_inventariable')
                                        ->find($pid)
                                    : null;

                                $esServicio = $prodSel ? !($prodSel->es_inventariable ?? true) : false;

                                $imgUrl = null;
                            @endphp

                            <tr wire:key="linea-{{ $i }}"
                                class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                {{-- Imagen --}}
                                <td class="px-4 py-3 w-[72px]">
                                    <div
                                        class="h-12 w-12 rounded-xl bg-gray-100 dark:bg-gray-800 grid place-items-center overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700">
                                        @if ($imgUrl)
                                            <button type="button" class="group relative h-full w-full"
                                                @click.stop="$dispatch('preview-image', { src: '{{ $imgUrl }}', title: '{{ e($prodSel->nombre ?? 'Producto') }}' })"
                                                aria-label="Ver imagen de {{ $prodSel->nombre ?? 'producto' }}"
                                                title="Ver imagen">
                                                <img src="{{ $imgUrl }}"
                                                    class="h-full w-full object-cover transition group-hover:scale-105"
                                                    alt="img-{{ $prodSel->nombre ?? 'producto' }}">
                                                <span
                                                    class="pointer-events-none absolute inset-0 ring-2 ring-indigo-400/0 group-hover:ring-indigo-400/60 transition"></span>
                                                <span
                                                    class="pointer-events-none absolute bottom-1 right-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded-md opacity-0 group-hover:opacity-100 transition">
                                                    Ver
                                                </span>
                                            </button>
                                        @else
                                            <div class="h-full w-full grid place-items-center">
                                                <svg viewBox="0 0 24 24" class="h-6 w-6 text-gray-400">
                                                    <path fill="currentColor"
                                                        d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14l4-4h12l2 2Zm-5-9a2 2 0 1 1-4.001-.001A2 2 0 0 1 16 10Z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- Producto --}}
                                <td class="px-4 py-3 min-w-[260px]">
                                    <div wire:ignore>
                                        <select id="producto-select-{{ $i }}" data-producto-select
                                            data-linea="{{ $i }}"
                                            @if ($i === 0) data-first-product @endif
                                            class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
                                            <option value="">— Seleccione —</option>

                                            @if ($productoIdActual && $productoNombreActual)
                                                <option value="{{ $productoIdActual }}" selected>
                                                    {{ $productoNombreActual }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>

                                    @error('lineas.' . $i . '.producto_id')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </td>

                                {{-- Bodega --}}
                                <td class="px-4 py-3 min-w-[180px]">
                                    @if ($esServicio)
                                        <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 border border-dashed border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-200">
                                            <i class="fas fa-concierge-bell"></i>
                                            <span class="text-xs font-semibold">Servicio</span>
                                        </div>
                                    @else
                                        <select wire:model.live="lineas.{{ $i }}.bodega_id"
                                            class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                                            <option value="">— Seleccione —</option>
                                            @foreach ($bodegas as $b)
                                                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                                            @endforeach
                                        </select>

                                        <div class="mt-2" wire:key="stock-linea-{{ $i }}">
                                            <div wire:loading.flex
                                                wire:target="lineas.{{ $i }}.producto_id,lineas.{{ $i }}.bodega_id"
                                                class="items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-dashed border-gray-300 dark:border-gray-600">
                                                <i class="fas fa-spinner fa-spin"></i>
                                                <span class="text-[11px] italic">Consultando…</span>
                                            </div>

                                            @if ($pid && $bid)
                                                @php $stock = $this->getStockDeLinea($i); @endphp
                                                <div wire:loading.remove
                                                    wire:target="lineas.{{ $i }}.producto_id,lineas.{{ $i }}.bodega_id"
                                                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg {{ $stock > 0 ? 'bg-green-50 border border-green-300 text-green-700' : 'bg-red-50 border border-red-300 text-red-700' }}">
                                                    <i class="fas {{ $stock > 0 ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                                                    <span class="text-[11px] font-semibold">Stock: {{ number_format($stock, 0) }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        @error('lineas.' . $i . '.bodega_id')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    @endif
                                </td>

                                {{-- Cantidad --}}
                                <td class="px-4 py-3 text-right">
                                    <input type="number" step="0.001" min="0.001"
    wire:model.blur="lineas.{{ $i }}.cantidad"
    class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                                </td>

                                {{-- Precio --}}
                                <td class="px-4 py-3 text-right">
                                    {{-- wire:key incluye el precio_unitario para que Alpine se reinicie cuando el server cambie el valor --}}
                                    <div wire:key="precio-{{ $i }}-{{ (int)(($l['producto_id'] ?? 0)) }}-{{ (int)round((float)($l['precio_unitario'] ?? 0)) }}"
                                         x-data="moneyInput({
                                            initial: @js((float)($l['precio_unitario'] ?? 0)),
                                            onChange: (v) => $wire.set('lineas.{{ $i }}.precio_unitario', v)
                                         })"
                                         x-on:linea-precio-actualizado.window="
                                            if ($event.detail.index === {{ $i }}) {
                                                if (this._t) { clearTimeout(this._t); this._t = null; }
                                                raw = Number($event.detail.precio) || 0;
                                                display = fmt(raw);
                                            }
                                         ">
                                        <input type="text" inputmode="decimal"
                                            :value="display"
                                            @input="onInput($event)"
                                            @blur="onBlur()"
                                            @focus="$event.target.select()"
                                            @disabled(!$puedeModificarPrecio)
                                            @readonly(!$puedeModificarPrecio)
                                            title="{{ $puedeModificarPrecio ? '' : 'No tienes permiso para modificar el precio' }}"
                                            class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 {{ !$puedeModificarPrecio ? 'opacity-60 cursor-not-allowed bg-gray-100 dark:bg-gray-700' : '' }}">
                                    </div>
                                </td>

                                {{-- Impuesto --}}
                                <td class="px-4 py-3 min-w-[240px]">
                                    <select wire:model.live="lineas.{{ $i }}.impuesto_id"
                                        wire:change="setImpuesto({{ $i }}, $event.target.value)"
                                        class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                                        <option value="">— Sin impuesto / Exento —</option>

                                        @if ($prodSel && $prodSel->impuesto && $prodSel->impuesto->activo)
                                            <option value="{{ $prodSel->impuesto->id }}">
                                                (Sugerido)
                                                {{ $prodSel->impuesto->codigo }} — {{ $prodSel->impuesto->nombre }}
                                                @if (!is_null($prodSel->impuesto->porcentaje))
                                                    ({{ number_format($prodSel->impuesto->porcentaje, 2) }}%)
                                                @else
                                                    (monto fijo)
                                                @endif
                                            </option>
                                        @endif

                                        @foreach ($impuestosVentas as $imp)
                                            <option value="{{ $imp->id }}">
                                                {{ $imp->codigo }} — {{ $imp->nombre }}
                                                @if (!is_null($imp->porcentaje))
                                                    ({{ number_format($imp->porcentaje, 2) }}%)
                                                @else
                                                    (monto fijo)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                {{-- Impuesto $ --}}
                                <td class="px-4 py-3 text-right">
                                    ${{ number_format($ivaMonto, 2) }}
                                </td>

                                {{-- Total línea --}}
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                    ${{ number_format($totalLin, 2) }}
                                </td>

                                {{-- Acciones --}}
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" wire:click="addLinea"
                                            class="h-10 px-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white"
                                            wire:loading.attr="disabled"
                                            wire:target="addLinea,removeLinea,guardar,emitir">
                                            + Línea
                                        </button>
                                        <button type="button" wire:click="removeLinea({{ $i }})"
                                            class="h-10 px-3 rounded-xl bg-red-500 hover:bg-red-600 text-white"
                                            wire:loading.attr="disabled"
                                            wire:target="addLinea,removeLinea,guardar,emitir">
                                            Quitar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay líneas. Usa
                                    <button type="button" class="text-violet-600 hover:underline"
                                        wire:click="addLinea">
                                        + Línea
                                    </button>
                                    o el selector rápido de arriba.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot class="bg-gray-50 dark:bg-gray-800/40">
                        <tr>
                            <td colspan="6"></td>
                            <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Subtotal:
                            </td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                                ${{ number_format($this->subtotal, 2) }}
                            </td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="6"></td>
                            <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Impuestos:
                            </td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                                ${{ number_format($this->impuestosTotal, 2) }}
                            </td>
                            <td></td>
                        </tr>
                        <tr class="bg-gray-100 dark:bg-gray-800/60">
                            <td colspan="6"></td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">Total:</td>
                            <td class="px-4 py-2 text-right text-lg font-extrabold text-gray-900 dark:text-white">
                                ${{ number_format($this->total, 2) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </section>
        </section>

        @php
            $esContado = $tipo_pago === 'contado';
            $totalVista = round((float) ($factura?->total ?? ($this->total ?? 0)), 2);
            $pagadoVista = round((float) ($factura?->pagos?->sum('monto') ?? ($factura?->pagado ?? 0)), 2);
            $saldoVista = max(round($totalVista - $pagadoVista, 2), 0);
            $tieneFactura = (bool) $factura?->id;

            $tieneNumero = !empty($factura?->numero);
            $estadoFactura = (string) ($factura?->estado ?? ($estado ?? 'borrador'));

            $yaEmitida = $tieneNumero || in_array($estadoFactura, ['emitida', 'cerrado'], true);

            $pagadaSinNumero = $estadoFactura === 'pagada' && !$tieneNumero;

            $bloqueaEmitir = !$tieneFactura || $yaEmitida || ($esContado && $saldoVista > 0.01 && !$pagadaSinNumero);
        @endphp
        <footer
            class="sticky bottom-0 inset-x-0 bg-white/85 dark:bg-gray-900/85 backdrop-blur border-t border-gray-200 dark:border-gray-800"
            aria-label="Acciones de factura">
            <div class="px-4 md:px-8 py-4">
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-center">
                    <div
                        class="xl:col-span-3 flex flex-wrap items-center gap-2 text-sm md:text-base text-gray-700 dark:text-gray-300">
                        <span class="font-semibold">Total:</span>
                        <span class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white">
                            $ {{ number_format($totalVista, 2) }}
                        </span>

                        @if ($esContado)
                            <span
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] {{ $bloqueaEmitir ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">
                                <i class="fa-solid {{ $bloqueaEmitir ? 'fa-lock' : 'fa-unlock' }}"></i>
                                Contado
                            </span>
                        @endif

                        @if ($esContado && $saldoVista > 0.01)
                            <span class="text-[13px] ml-1 text-amber-700">
                                Saldo: <strong>${{ number_format($saldoVista, 2) }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="xl:col-span-6">
                        <div x-data="{
                            estado: @entangle('estado'),
                            tipoPago: @entangle('tipo_pago'),
                            get contado() { return this.tipoPago === 'contado' },
                            bloqueaEmitir: @js((bool) ($bloqueaEmitir ?? false)),
                            get currentStep() {
                                if (this.estado === 'anulada') return 4
                                if (this.estado === 'cerrado') return 4
                                if (this.estado === 'emitida') return 3
                                return 1
                            },
                            stepStatus(n) {
                                if (n < this.currentStep) return 'complete'
                                if (n === this.currentStep) return 'current'
                                return 'upcoming'
                            },
                            get progressPct() {
                                return [0, 33, 66, 100][this.currentStep - 1] || 0
                            }
                        }" class="w-full">
                            <div class="relative mx-auto max-w-3xl">
                                <div
                                    class="absolute left-0 right-0 top-5 h-1 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-1 bg-indigo-600 dark:bg-indigo-400 transition-all duration-500"
                                        :style="`width: ${progressPct}%`"></div>
                                </div>

                                <ol class="relative z-10 grid grid-cols-4 gap-3 md:gap-6">
                                    <li class="flex flex-col items-center text-center">
                                        <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                                            :class="{
                                                'bg-indigo-600 text-white border-indigo-600 shadow': stepStatus(
                                                    1) !== 'upcoming',
                                                'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(
                                                    1) === 'upcoming'
                                            }">
                                            <i class="fa-solid fa-pen text-sm md:text-base"></i>
                                        </div>
                                        <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Borrador</div>
                                        <div class="hidden md:block text-xs text-slate-500">Factura Borrador</div>
                                    </li>

                                    <li class="flex flex-col items-center text-center">
                                        <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                                            :class="{
                                                'bg-emerald-600 text-white border-emerald-600 shadow': stepStatus(
                                                    2) !== 'upcoming',
                                                'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(
                                                    2) === 'upcoming'
                                            }">
                                            <i class="fa-solid fa-cash-register text-sm md:text-base"></i>
                                        </div>
                                        <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Pagos</div>
                                        <div class="hidden md:block text-xs text-slate-500"
                                            x-text="contado ? 'Contado' : 'Crédito'"></div>
                                    </li>

                                    <li class="flex flex-col items-center text-center">
                                        <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                                            :class="{
                                                'bg-violet-600 text-white border-violet-600 shadow': stepStatus(
                                                    3) !== 'upcoming',
                                                'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(
                                                    3) === 'upcoming'
                                            }">
                                            <i class="fa-solid fa-stamp text-sm md:text-base"></i>
                                        </div>
                                        <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Emitir</div>
                                        <div class="hidden md:block text-xs text-rose-600" x-show="bloqueaEmitir">
                                            Requiere pago total</div>
                                    </li>

                                    <li class="flex flex-col items-center text-center">
                                        <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                                            :class="{
                                                'bg-rose-600 text-white border-rose-600 shadow': estado === 'anulada',
                                                'bg-teal-600 text-white border-teal-600 shadow': estado === 'cerrado',
                                                'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600':
                                                    !['anulada', 'cerrado'].includes(estado)
                                            }">
                                            <i :class="estado === 'anulada' ? 'fa-solid fa-ban' : (estado === 'cerrado' ?
                                                'fa-solid fa-check' : 'fa-regular fa-circle')"
                                                class="text-sm md:text-base"></i>
                                        </div>
                                        <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium"
                                            x-text="estado === 'anulada' ? 'Anulada' : (estado === 'cerrado' ? 'Cerrada' : 'Fin')">
                                        </div>
                                        <div class="hidden md:block text-xs text-slate-500">—</div>
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="xl:col-span-3">
                        <div x-data="{
                            estado: @entangle('estado'),
                            tipoPago: @entangle('tipo_pago'),
                            get contado() { return this.tipoPago === 'contado' },
                            tieneFactura: @js($tieneFactura),
                            saldo: @js($saldoVista),
                            bloqueaEmitir: @js((bool) ($bloqueaEmitir ?? false)),
                            get next() {
                                if (['anulada', 'cerrado', 'emitida'].includes(this.estado)) return null
                                if (this.contado) {
                                    if (!this.tieneFactura || this.saldo > 0.01) return 'pagos'
                                    return 'emitir'
                                }
                                return 'emitir'
                            },
                            labelNext() {
                                return this.next === 'pagos' ? 'Ir a Pagos' : this.next === 'emitir' ? 'Emitir documento' : ''
                            },
                            isNext(btn) { return this.next === btn }
                        }" class="flex flex-col items-end gap-2">
                            <template x-if="labelNext()">
                                <div
                                    class="flex items-center gap-2 text-xs md:text-sm text-slate-600 dark:text-slate-300">
                                    <i class="fa-solid fa-arrow-turn-down-right hidden sm:inline"></i>
                                    <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800">
                                        Siguiente: <strong x-text="labelNext()"></strong>
                                    </span>
                                </div>
                            </template>

                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button"
                                    class="h-11 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed transition ring-offset-2"
                                    :class="isNext('pagos') ? 'ring-4 ring-emerald-300 animate-pulse' : ''"
                                    wire:click="abrirPagos" wire:loading.attr="disabled"
                                    wire:target="abrirPagos,guardar,emitir" x-data="{ e: @entangle('estado') }"
                                    :disabled="['anulada', 'cerrado'].includes(e)">
                                    <i class="fa-solid fa-cash-register mr-2"></i>
                                    <span>Pagos</span>
                                </button>

                                <button type="button"
                                    class="h-11 px-4 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed transition ring-offset-2"
                                    :class="isNext('guardar') ? 'ring-4 ring-slate-300 animate-pulse' : ''"
                                    wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar,emitir"
                                    x-data="{ e: @entangle('estado') }" :disabled="['anulada', 'cerrado'].includes(e)">
                                    <i class="fa-solid fa-floppy-disk mr-2"></i>
                                    <span wire:loading.remove wire:target="guardar">Factura Borrador</span>
                                    <span wire:loading wire:target="guardar">Guardando…</span>
                                </button>

                                @if ($habilitarActualizar)
                                    <button type="button" wire:click="actualizar" wire:loading.attr="disabled"
                                        class="h-11 px-4 rounded-2xl bg-amber-600 hover:bg-amber-700 text-white shadow">
                                        <i class="fa-solid fa-rotate mr-2"></i>
                                        Actualizar cambios
                                    </button>
                                @endif

                                <button type="button" x-data="{
                                    e: @entangle('estado'),
                                    tipo: @entangle('tipo_pago'),
                                    bloquea: @js((bool) ($bloqueaEmitir ?? false)),
                                    numero: @js((string) ($factura?->numero ?? '')),
                                    get contado() { return this.tipo === 'contado' },
                                    get pagadaSinNumero() { return this.e === 'pagada' && !this.numero }
                                }" wire:click="emitir"
                                    wire:loading.attr="disabled" wire:target="emitir,guardar"
                                    :disabled="['anulada', 'cerrado', 'emitida'].includes(e) || (contado && bloquea && !
                                        pagadaSinNumero)"
                                    :class="[
                                        'h-11 px-4 rounded-2xl transition ring-offset-2 shadow disabled:opacity-50 disabled:cursor-not-allowed',
                                        (contado && bloquea && !pagadaSinNumero) ?
                                        'bg-gray-400 text-gray-100 cursor-not-allowed' :
                                        'bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white'
                                    ]"
                                    :title="(contado && bloquea && !pagadaSinNumero) ?
                                    'Factura de contado: requiere pago total antes de emitir' :
                                    'Emitir documento'">

                                    <i class="fa-solid fa-stamp mr-2"></i>

                                    <span wire:loading.remove wire:target="emitir">
                                        Emitir
                                    </span>

                                    <span wire:loading wire:target="emitir">
                                        Emitiendo…
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </section>
    @if ($showPagos && $factura?->id)
        <livewire:facturas.pagos-factura :facturaId="$factura->id" :key="'pagos-factura-' . $factura->id" />
    @endif
</div>

<script>
    document.addEventListener('livewire:init', () => {
        const productoInstances = {};
        let cotizacionInstance = null;

        const buildLineasPayload = (lineas) => {
            return (lineas || []).map((l) => ({
                ...l,
                producto_id: l?.producto_id ?? null,
                producto_nombre: l?.producto_nombre ?? ''
            }));
        };

        const ensureProductoTomSelect = (el) => {
            if (!el) return null;

            const linea = parseInt(el.dataset.linea || '0', 10);
            const key = `producto-select-${linea}`;

            if (productoInstances[key]) {
                return productoInstances[key];
            }

            const ts = new TomSelect(el, {
                valueField: 'id',
                labelField: 'text',
                searchField: ['text'],
                placeholder: 'Escribe para buscar un producto...',
                allowEmptyOption: true,
                create: false,
                preload: false,
                closeAfterSelect: true,
                loadThrottle: 250,
                maxOptions: 100,

                render: {
                    option: function(item, escape) {
                        return `<div>${escape(item.text)}</div>`;
                    },
                    item: function(item, escape) {
                        return `<div>${escape(item.text)}</div>`;
                    },
                    no_results: function(data, escape) {
                        return `<div class="px-3 py-2 text-sm text-gray-500">No se encontraron productos para "${escape(data.input)}"</div>`;
                    },
                    loading: function() {
                        return `<div class="px-3 py-2 text-sm text-gray-500">Buscando productos...</div>`;
                    }
                },

                load: function(query, callback) {
                    @this.call('buscarProductos', query)
                        .then((results) => callback(results || []))
                        .catch(() => callback());
                },

                onChange(value) {
                    const pid = value ? parseInt(value, 10) : null;
                    @this.call('setProducto', linea, pid);
                }
            });

            productoInstances[key] = ts;
            return ts;
        };

        const destroyProductoTomSelect = (el) => {
            if (!el) return;

            const linea = parseInt(el.dataset.linea || '0', 10);
            const key = `producto-select-${linea}`;

            if (productoInstances[key]) {
                productoInstances[key].destroy();
                delete productoInstances[key];
            }
        };

        const ensureCotizacionTomSelect = (el) => {
            if (!el) return null;
            if (cotizacionInstance) return cotizacionInstance;

            cotizacionInstance = new TomSelect(el, {
                placeholder: 'Buscar cotización...',
                allowEmptyOption: true,
                create: false,
                maxOptions: 300,
                hideSelected: false,
                closeAfterSelect: true,
                searchField: ['text'],
                onChange(value) {
                    const cotizacionId = value ? parseInt(value, 10) : null;
                    @this.call('setCotizacion', cotizacionId);
                }
            });

            return cotizacionInstance;
        };

        const initProductoSelects = () => {
            document.querySelectorAll('select[data-producto-select]').forEach((el) => {
                ensureProductoTomSelect(el);
            });
        };

        const initCotizacionSelect = () => {
            const el = document.querySelector('select[data-cotizacion-select]');
            if (el) ensureCotizacionTomSelect(el);
        };

        const initAll = () => {
            initProductoSelects();
            initCotizacionSelect();
        };

        initAll();

        Livewire.hook('morph.removing', ({
            el
        }) => {
            if (el.matches && el.matches('select[data-producto-select]')) {
                destroyProductoTomSelect(el);
            }

            el.querySelectorAll?.('select[data-producto-select]').forEach((select) => {
                destroyProductoTomSelect(select);
            });
        });

        Livewire.hook('morph.updated', () => {
            initAll();
        });

        Livewire.on('sync-productos-tomselect', (payload) => {
            const lineas = buildLineasPayload(payload?.lineas || []);

            lineas.forEach((l, i) => {
                const el = document.querySelector(
                    `select[data-producto-select][data-linea="${i}"]`);
                if (!el) return;

                const ts = ensureProductoTomSelect(el);
                if (!ts) return;

                const productoId = l?.producto_id ? String(l.producto_id) : '';
                const productoNombre = l?.producto_nombre || '';

                if (productoId && productoNombre) {
                    if (!ts.options[productoId]) {
                        ts.addOption({
                            id: productoId,
                            text: productoNombre
                        });
                    }
                    ts.setValue(productoId, true);
                } else if (!productoId) {
                    ts.clear(true);
                }
            });
        });

        Livewire.on('sync-cotizacion-tomselect', (payload) => {
            const cotizacionId = payload?.cotizacionId ? String(payload.cotizacionId) : '';
            const el = document.querySelector('select[data-cotizacion-select]');
            if (!el) return;

            const ts = ensureCotizacionTomSelect(el);
            if (!ts) return;

            ts.setValue(cotizacionId, true);
        });

        // ✅ esto ayuda a que, después de renderizar el componente PagosFactura,
        // se abra correctamente el modal
        Livewire.on('preparar-modal-pago', (payload) => {
            const facturaId = payload?.facturaId ?? null;

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    Livewire.dispatch('abrir-modal-pago', {
                        facturaId: facturaId
                    });
                });
            });
        });
    });
</script>
