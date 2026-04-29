@assets
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
@endassets

@php
    $codigo = strtoupper($serieSeleccionada?->tipo?->codigo ?? '');
    $esCompra = in_array($codigo, ['FACTURACOMPRA', 'NOTA_CREDITO_COMPRA', 'NOTACREDITOCOMPRA'], true);

    if ($codigo === '') {
        $esCompra = false;
    }
@endphp

<div x-data="{ openFilters: false }"
    class="p-4 md:p-6 bg-gradient-to-br from-white via-white to-gray-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950 rounded-2xl shadow-xl space-y-6 md:space-y-8">

    <!-- ENCABEZADO -->
    <header
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4 border-b border-gray-200/70 dark:border-gray-800 pb-4">
        <div class="flex items-center gap-3">
            <span
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl
                {{ $esCompra ? 'bg-amber-600/10 text-amber-600' : 'bg-indigo-600/10 text-indigo-600' }}">
                <i class="fas {{ $esCompra ? 'fa-truck' : 'fa-chart-line' }}"></i>
            </span>

            <div>
                <h1 class="text-xl md:text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">
                    Informe de {{ $esCompra ? 'Compras' : 'Ventas' }}
                </h1>
                <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">
                    El tipo de informe se define automáticamente según la serie seleccionada.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                class="md:hidden inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white/70 dark:bg-gray-800/70"
                @click="openFilters=!openFilters">
                <i class="fas fa-sliders-h"></i> Filtros
            </button>
        </div>
    </header>

    <!-- KPIs -->
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Documentos</div>
            <div class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">
                {{ number_format($totalFacturas ?? 0) }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Contado</div>
            <div class="text-lg md:text-xl font-bold text-emerald-600 dark:text-emerald-400">
                ${{ number_format($totalContado ?? 0, 0, ',', '.') }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Crédito</div>
            <div class="text-lg md:text-xl font-bold text-indigo-600 dark:text-indigo-400">
                ${{ number_format($totalCredito ?? 0, 0, ',', '.') }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Facturado</div>
            <div class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">
                ${{ number_format($totalFacturado ?? 0, 0, ',', '.') }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Pagado</div>
            <div class="text-lg md:text-xl font-bold text-emerald-600 dark:text-emerald-400">
                ${{ number_format($totalPagado ?? 0, 0, ',', '.') }}
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Saldo pendiente por pagar</div>
            <div
                class="text-lg md:text-xl font-bold {{ ($totalSaldo ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                ${{ number_format($totalSaldo ?? 0, 0, ',', '.') }}
            </div>
        </div>
    </section>

    @if (!$esCompra && !$esCotizacion && ($puedeVerCostos ?? false))
        <!-- KPIs RENTABILIDAD -->
        <section class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">Venta (base sin IVA)</div>
                <div class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">
                    ${{ number_format($totalBaseVenta ?? 0, 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">Costo</div>
                <div class="text-lg md:text-xl font-bold text-amber-600 dark:text-amber-400">
                    ${{ number_format($totalCostoVenta ?? 0, 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">Utilidad</div>
                <div class="text-lg md:text-xl font-bold {{ ($totalGanancia ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    ${{ number_format($totalGanancia ?? 0, 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">Margen</div>
                <div class="text-lg md:text-xl font-bold {{ ($margenPromedio ?? 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                    {{ number_format($margenPromedio ?? 0, 2, ',', '.') }}%
                </div>
            </div>
        </section>
    @endif

    <!-- FILTROS -->
    <section
        class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-4 md:p-5 space-y-4"
        :class="{ 'block': openFilters, 'hidden md:block': !openFilters }">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-8 gap-3 md:gap-4">

            <!-- SERIE -->
            <div class="flex flex-col">
                <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Serie (Ventas / Compras)
                </label>

                <select wire:model.live="serieId"
                    class="rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="">— FACTURAS DE VENTA (por defecto) —</option>

                    <optgroup label="VENTAS">
                        @foreach ($seriesVentas ?? collect() as $s)
                            <option value="{{ $s->id }}">
                                {{ $s->nombre }} · {{ $s->prefijo }} ({{ $s->tipo->codigo ?? '' }})
                            </option>
                        @endforeach
                    </optgroup>

                    <optgroup label="COMPRAS">
                        @foreach ($seriesCompras ?? collect() as $s)
                            <option value="{{ $s->id }}">
                                {{ $s->nombre }} · {{ $s->prefijo }} ({{ $s->tipo->codigo ?? '' }})
                            </option>
                        @endforeach
                    </optgroup>
                </select>

                @if ($serieSeleccionada)
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Seleccionada: <b>{{ $serieSeleccionada->nombre }}</b>
                    </div>
                @endif
            </div>

            <!-- TIPO DOCUMENTO -->
            <div class="flex flex-col">
                <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo
                    documento</label>
                <select wire:model="tipoDocumentoFiltro"
                    class="rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="todos">Todos</option>
                    <option value="FACTURA">Facturas</option>
                    <option value="COTIZACION">Cotizaciones</option>
                    <option value="NOTA_CREDITO">Notas crédito</option>
                </select>
            </div>

            <!-- ESTADO -->
            <div class="flex flex-col">
                <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                <select wire:model="estadoFiltro"
                    class="rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="todos">Todos</option>
                    <option value="borrador">Borrador</option>
                    <option value="emitida">Emitida</option>
                    <option value="parcialmente_pagada">Parcial</option>
                    <option value="pagada">Pagada</option>
                    <option value="anulada">Anulada</option>
                    <option value="vencida">Vencida</option>
                </select>
            </div>

            <!-- CLIENTE / PROVEEDOR -->
            <div class="flex flex-col">
                <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $esCompra ? 'Proveedor' : 'Cliente' }}
                </label>

                <div class="relative">
                    <input type="text" list="terceros_list" wire:model.debounce.400ms="filtroCliente"
                        placeholder="Buscar por nombre..."
                        class="w-full rounded-xl text-sm pl-9 pr-8 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">

                    <datalist id="terceros_list">
                        @foreach ($terceros ?? [] as $t)
                            <option value="{{ $t }}"></option>
                        @endforeach
                    </datalist>

                    
                    <button type="button" wire:click="$set('filtroCliente','')"
                        class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>

            <!-- ASESOR (multi-select con checkboxes) -->
            <div class="flex flex-col" x-data="{ open: false }" @click.outside="open = false">
                <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Asesores
                </label>
                <div class="relative">
                    <button type="button" @click="open = !open"
                        class="w-full flex items-center justify-between rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 text-left">
                        <span class="truncate">
                            @if (empty($asesoresFiltro))
                                Todos
                            @else
                                {{ count($asesoresFiltro) }}
                                {{ count($asesoresFiltro) === 1 ? 'asesor seleccionado' : 'asesores seleccionados' }}
                            @endif
                        </span>
                        <i class="fa-solid fa-chevron-down text-xs ml-2" :class="{ 'rotate-180': open }"></i>
                    </button>

                    <div x-show="open" x-cloak x-transition
                        class="absolute z-30 mt-1 w-full max-h-64 overflow-y-auto rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">

                        <div class="sticky top-0 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-3 py-2 flex items-center justify-between">
                            <span class="text-[11px] uppercase font-semibold text-gray-500 dark:text-gray-400">
                                Seleccionar asesores
                            </span>
                            @if (!empty($asesoresFiltro))
                                <button type="button"
                                    wire:click="$set('asesoresFiltro', [])"
                                    class="text-[11px] text-rose-600 hover:underline">
                                    Limpiar
                                </button>
                            @endif
                        </div>

                        @forelse ($asesores ?? collect() as $asesor)
                            <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox"
                                    value="{{ $asesor->id }}"
                                    wire:model.live="asesoresFiltro"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="truncate">{{ $asesor->name }}</span>
                            </label>
                        @empty
                            <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                                No hay asesores disponibles
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- TIPO PAGO -->
            <div class="flex flex-col">
                <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Pago</label>
                <select wire:model="tipoPagoFiltro"
                    class="rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="todos">Todos</option>
                    <option value="contado">Contado</option>
                    <option value="credito">Crédito</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>

            <!-- FECHAS -->
            <div class="grid grid-cols-2 gap-3" x-data="{ fpDesde: null, fpHasta: null }" x-init="fpDesde = flatpickr($refs.desde, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                defaultDate: @js($fechaInicio),
                onChange: (_sel, iso) => $wire.set('fechaInicio', iso)
            });
            
            fpHasta = flatpickr($refs.hasta, {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                defaultDate: @js($fechaFin),
                onChange: (_sel, iso) => $wire.set('fechaFin', iso)
            });
            
            Livewire.hook('message.processed', () => {
                fpDesde && fpDesde.setDate(@js($fechaInicio), true);
                fpHasta && fpHasta.setDate(@js($fechaFin), true);
            });">
                <div class="flex flex-col" wire:ignore>
                    <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Desde</label>
                    <input x-ref="desde" type="text" placeholder="dd/mm/aaaa"
                        class="w-full rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex flex-col" wire:ignore>
                    <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasta</label>
                    <input x-ref="hasta" type="text" placeholder="dd/mm/aaaa"
                        class="w-full rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- BOTONES -->
        <div class="flex flex-col sm:flex-row justify-end gap-2 pt-1">
            <button type="button" wire:click="cargarVentas"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow">
                <i class="fas fa-search"></i> Buscar
            </button>

            <button type="button" wire:click="limpiarFiltros"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
                <i class="fas fa-sync-alt"></i> Limpiar
            </button>

            <label class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-amber-50 cursor-pointer text-sm">
                <input type="checkbox" wire:model.live="verTopProductos" class="rounded text-amber-500 focus:ring-amber-400">
                <i class="fas fa-trophy text-amber-500"></i>
                Productos más vendidos
            </label>
        </div>
    </section>

    <!-- TOP PRODUCTOS POR MES -->
    @if(!empty($topProductosPorMes))
        <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-10 w-10 rounded-xl bg-amber-100 text-amber-600 grid place-items-center">
                    <i class="fas fa-trophy"></i>
                </div>
                <div>
                    <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100">Productos más vendidos por mes</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Top 10 por mes según el rango de fechas seleccionado.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach($topProductosPorMes as $mes => $items)
                    @php
                        $mesNombre = \Illuminate\Support\Carbon::createFromFormat('Y-m', $mes)->locale('es')->isoFormat('MMMM YYYY');
                        $totalMes = collect($items)->sum('total');
                    @endphp
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white px-4 py-2 flex items-center justify-between">
                            <span class="font-semibold capitalize">{{ $mesNombre }}</span>
                            <span class="text-sm font-bold">$ {{ number_format($totalMes, 0, ',', '.') }}</span>
                        </div>
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left w-8">#</th>
                                    <th class="px-3 py-2 text-left">Producto</th>
                                    <th class="px-3 py-2 text-right">Cant.</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($items as $idx => $it)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                        <td class="px-3 py-2 font-bold text-amber-600">{{ $idx + 1 }}</td>
                                        <td class="px-3 py-2 text-gray-800 dark:text-gray-100">{{ $it['producto'] }}</td>
                                        <td class="px-3 py-2 text-right text-gray-700 dark:text-gray-200">{{ rtrim(rtrim(number_format($it['cantidad'], 2, '.', ''), '0'), '.') }}</td>
                                        <td class="px-3 py-2 text-right font-semibold text-emerald-600">$ {{ number_format($it['total'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <!-- TABLA -->
    <section
        class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 overflow-hidden">
        <div wire:loading.flex class="p-4 md:p-6 gap-3 md:gap-4 flex flex-col">
            @for ($i = 0; $i < 4; $i++)
                <div class="animate-pulse h-16 rounded-xl bg-gray-100 dark:bg-gray-800"></div>
            @endfor
        </div>

        <div wire:loading.remove>
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100/80 dark:bg-gray-800/80 text-gray-800 dark:text-gray-200">
                        <tr class="uppercase text-xs tracking-wider">
                            <th class="px-4 py-3 text-left">Número</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Tipo Doc.</th>
                            <th class="px-4 py-3 text-left">{{ $esCompra ? 'Proveedor' : 'Cliente' }}</th>
                            <th class="px-4 py-3 text-left">Asesor</th>
                            <th class="px-4 py-3 text-left">Serie</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Pagado</th>
                            <th class="px-4 py-3 text-right">Saldo pendiente</th>
                            @if (!$esCompra && !$esCotizacion && ($puedeVerCostos ?? false))
                                <th class="px-4 py-3 text-right">Costo</th>
                                <th class="px-4 py-3 text-right">Utilidad</th>
                                <th class="px-4 py-3 text-right">Margen %</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($facturas as $factura)
                            @php
                                $total = (float) $factura->total;
                                $pagado = (float) $factura->pagado;
                                $saldo = (float) $factura->saldo;
                                $estado = $factura->estado;

                                $terceroNombre = $esCompra
                                    ? data_get($factura, 'socioNegocio.razon_social') ?? 'SIN PROVEEDOR'
                                    : data_get($factura, 'cliente.razon_social') ?? 'SIN CLIENTE';

                                $tipoDocumento = data_get($factura, 'serie.tipo.codigo');

                                if (!$tipoDocumento) {
                                    $tipoDocumento = 'COTIZACION';
                                }

                                $tipoDocumento = strtoupper($tipoDocumento);
                                $asesor = data_get($factura, 'creadoPor.name', '—');
                            @endphp

                            <tr class="hover:bg-indigo-50/50 dark:hover:bg-gray-800 transition">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                    {{ $factura->numero_formateado ?? '#' . $factura->id }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ filled($factura->fecha) ? \Illuminate\Support\Carbon::parse($factura->fecha)->format('d/m/Y') : '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                        {{ str_replace('_', ' ', $tipoDocumento) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    {{ $terceroNombre }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $asesor }}
                                </td>

                                <td class="px-4 py-3 text-xs">
                                    {{ $factura->serie->nombre ?? '—' }}
                                    @if (!empty($factura->serie->prefijo))
                                        ({{ $factura->serie->prefijo }})
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                                        @class([
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' =>
                                                $estado === 'pagada',
                                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' =>
                                                $estado === 'parcialmente_pagada',
                                            'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300' =>
                                                $estado === 'anulada',
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' => in_array(
                                                $estado,
                                                ['emitida', 'borrador']),
                                        ])">
                                        <i class="fas fa-circle"></i>
                                        {{ ucfirst(str_replace('_', ' ', $estado)) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    ${{ number_format($total, 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400">
                                    ${{ number_format($pagado, 0, ',', '.') }}
                                </td>

                                <td
                                    class="px-4 py-3 text-right {{ $saldo > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                    ${{ number_format($saldo, 0, ',', '.') }}
                                </td>

                                @if (!$esCompra && !$esCotizacion && ($puedeVerCostos ?? false))
                                    @php
                                        $r = $rentabilidad[$factura->id] ?? ['costo' => 0, 'ganancia' => 0, 'margen' => 0];
                                    @endphp
                                    <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">
                                        ${{ number_format($r['costo'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right {{ $r['ganancia'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        ${{ number_format($r['ganancia'], 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold {{ $r['margen'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ number_format($r['margen'], 2, ',', '.') }}%
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ (!$esCompra && !$esCotizacion && ($puedeVerCostos ?? false)) ? 13 : 10 }}" class="px-6 py-10 text-center text-gray-500">
                                    <i class="fas fa-inbox text-2xl mb-2"></i>
                                    <div>No hay resultados con los filtros actuales.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4 px-4 pb-4">
                    {{ $facturas->links() }}
                </div>
            </div>
        </div>
    </section>
</div>

@assets
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endassets
