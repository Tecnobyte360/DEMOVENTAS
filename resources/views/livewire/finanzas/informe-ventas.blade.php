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

    <!-- KPIs unificados -->
    @php
        $verRentab = !$esCompra && !$esCotizacion && ($puedeVerCostos ?? false);
        $kpis = [
            ['label' => 'Documentos', 'value' => number_format($totalFacturas ?? 0), 'icon' => 'fa-file-invoice', 'color' => 'gray', 'money' => false],
            ['label' => 'Facturado', 'value' => '$' . number_format($totalFacturado ?? 0, 0, ',', '.'), 'icon' => 'fa-coins', 'color' => 'gray'],
            ['label' => 'Contado', 'value' => '$' . number_format($totalContado ?? 0, 0, ',', '.'), 'icon' => 'fa-money-bill-wave', 'color' => 'emerald'],
            ['label' => 'Crédito', 'value' => '$' . number_format($totalCredito ?? 0, 0, ',', '.'), 'icon' => 'fa-credit-card', 'color' => 'indigo'],
            ['label' => 'Pagado', 'value' => '$' . number_format($totalPagado ?? 0, 0, ',', '.'), 'icon' => 'fa-check-circle', 'color' => 'emerald'],
            ['label' => 'Saldo pendiente', 'value' => '$' . number_format($totalSaldo ?? 0, 0, ',', '.'), 'icon' => 'fa-hourglass-half', 'color' => ($totalSaldo ?? 0) > 0 ? 'rose' : 'emerald'],
        ];
        if ($verRentab) {
            $kpis[] = ['label' => 'Venta base', 'value' => '$' . number_format($totalBaseVenta ?? 0, 0, ',', '.'), 'icon' => 'fa-tag', 'color' => 'gray'];
            $kpis[] = ['label' => 'Costo', 'value' => '$' . number_format($totalCostoVenta ?? 0, 0, ',', '.'), 'icon' => 'fa-warehouse', 'color' => 'amber'];
            $kpis[] = ['label' => 'Utilidad', 'value' => '$' . number_format($totalGanancia ?? 0, 0, ',', '.'), 'icon' => 'fa-chart-line', 'color' => ($totalGanancia ?? 0) >= 0 ? 'emerald' : 'rose'];
            $kpis[] = ['label' => 'Margen', 'value' => number_format($margenPromedio ?? 0, 1, ',', '.') . '%', 'icon' => 'fa-percentage', 'color' => ($margenPromedio ?? 0) >= 0 ? 'emerald' : 'rose'];
        }
        $colorMap = [
            'gray' => ['bg' => 'bg-gray-100 dark:bg-gray-700/40', 'text' => 'text-gray-600 dark:text-gray-300', 'value' => 'text-gray-900 dark:text-white', 'bar' => 'bg-gray-400'],
            'emerald' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-600', 'value' => 'text-emerald-600 dark:text-emerald-400', 'bar' => 'bg-emerald-500'],
            'indigo' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/30', 'text' => 'text-indigo-600', 'value' => 'text-indigo-600 dark:text-indigo-400', 'bar' => 'bg-indigo-500'],
            'rose' => ['bg' => 'bg-rose-100 dark:bg-rose-900/30', 'text' => 'text-rose-600', 'value' => 'text-rose-600 dark:text-rose-400', 'bar' => 'bg-rose-500'],
            'amber' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-600', 'value' => 'text-amber-600 dark:text-amber-400', 'bar' => 'bg-amber-500'],
        ];
    @endphp
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach($kpis as $k)
            @php
                $c = $colorMap[$k['color']] ?? $colorMap['gray'];
                $clickable = ($k['label'] ?? '') === 'Saldo pendiente';
                $activo = $clickable && $estadoFiltro === 'con_saldo';
            @endphp
            <div
                @if($clickable) wire:click="verSaldoPendiente" role="button" tabindex="0" title="Ver facturas con saldo pendiente" @endif
                class="relative rounded-xl bg-white dark:bg-gray-800 shadow-sm border {{ $activo ? 'border-rose-400 ring-2 ring-rose-200' : 'border-gray-100 dark:border-gray-700' }} p-3 overflow-hidden {{ $clickable ? 'cursor-pointer hover:shadow-md hover:-translate-y-0.5 transition' : '' }}">
                <div class="absolute left-0 top-0 bottom-0 w-1 {{ $c['bar'] }}"></div>
                <div class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-lg {{ $c['bg'] }} {{ $c['text'] }} grid place-items-center shrink-0">
                        <i class="fas {{ $k['icon'] }} text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400 truncate">
                            {{ $k['label'] }}
                            @if($clickable)
                                <i class="fas fa-filter text-[9px] ml-1 {{ $activo ? 'text-rose-500' : 'text-gray-300' }}"></i>
                            @endif
                        </p>
                        <p class="text-base font-bold {{ $c['value'] }} truncate">{{ $k['value'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <!-- FILTROS -->
    <section
        class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-4 md:p-5 space-y-4"
        :class="{ 'block': openFilters, 'hidden md:block': !openFilters }">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-4">

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
                    <option value="con_saldo">Con saldo pendiente</option>
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
            <div class="grid grid-cols-2 gap-3 xl:col-span-2" x-data="{ fpDesde: null, fpHasta: null }" x-init="fpDesde = flatpickr($refs.desde, {
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
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2 border-t border-gray-100 dark:border-gray-800">
            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 cursor-pointer text-sm hover:bg-amber-100 dark:hover:bg-amber-900/30 transition">
                <input type="checkbox" wire:model.live="verTopProductos" class="rounded text-amber-500 focus:ring-amber-400">
                <i class="fas fa-trophy text-amber-500"></i>
                <span class="font-medium text-amber-800 dark:text-amber-200">Ver productos más vendidos por mes</span>
            </label>

            <div class="flex items-center gap-2">
                <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
                    <i class="fas fa-sync-alt"></i> Limpiar
                </button>
                <button type="button" wire:click="cargarVentas"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
        </div>
    </section>

    <!-- TOP PRODUCTOS POR MES -->
    @if(!empty($topProductosPorMes))
        @php $cantMeses = count($topProductosPorMes); @endphp
        <section class="rounded-2xl border border-teal-200 dark:border-teal-800/40 bg-gradient-to-br from-teal-50/40 via-white to-white dark:from-teal-900/10 dark:via-gray-900 dark:to-gray-900 p-4 md:p-6">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-teal-100 dark:border-teal-800/30">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white grid place-items-center shadow">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div>
                        <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100">Productos más vendidos por mes</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Top 10 por mes · {{ $cantMeses }} {{ $cantMeses === 1 ? 'mes' : 'meses' }}</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('verTopProductos', false)"
                    class="text-gray-400 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 {{ $cantMeses > 1 ? 'lg:grid-cols-2 xl:grid-cols-3' : '' }} gap-4">
                @foreach($topProductosPorMes as $mes => $items)
                    @php
                        $mesNombre = \Illuminate\Support\Carbon::createFromFormat('Y-m', $mes)->locale('es')->isoFormat('MMMM YYYY');
                        $totalMes = collect($items)->sum('total');
                    @endphp
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gradient-to-r from-teal-500 to-emerald-600 text-white px-4 py-2 flex items-center justify-between">
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
                                        <td class="px-3 py-2 font-bold text-teal-600">{{ $idx + 1 }}</td>
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

                                <td class="px-4 py-3 text-right">
                                    @if($saldo > 0 && !$esCompra && !$esCotizacion)
                                        <button type="button"
                                            wire:click="verDetalleFactura({{ $factura->id }})"
                                            title="Ver detalle de la factura pendiente"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 font-semibold transition">
                                            <i class="fas fa-eye text-xs"></i>
                                            ${{ number_format($saldo, 0, ',', '.') }}
                                        </button>
                                    @else
                                        <span class="{{ $saldo > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            ${{ number_format($saldo, 0, ',', '.') }}
                                        </span>
                                    @endif
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

    {{-- Modal de pagos para registrar abonos desde el informe --}}
    <livewire:facturas.pagos-factura />

    {{-- Modal: Detalle de factura pendiente --}}
    @if($detalleFacturaModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="cerrarDetalleFactura">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-teal-600 to-emerald-600 text-white px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-xl bg-white/20 grid place-items-center">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-wider opacity-90">Factura pendiente</p>
                            <h2 class="text-lg font-bold">{{ $detalleFacturaModal['numero'] ?: '#' . $detalleFacturaModal['id'] }}</h2>
                        </div>
                    </div>
                    <button type="button" wire:click="cerrarDetalleFactura" class="text-white/80 hover:text-white text-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    {{-- Cliente / fechas --}}
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Cliente</p>
                            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $detalleFacturaModal['cliente'] }}</p>
                            @if($detalleFacturaModal['nit'])
                                <p class="text-xs text-gray-500">NIT: {{ $detalleFacturaModal['nit'] }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase">Fecha</p>
                            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $detalleFacturaModal['fecha'] }}</p>
                            @if($detalleFacturaModal['vencimiento'])
                                <p class="text-xs text-gray-500">Vence: {{ $detalleFacturaModal['vencimiento'] }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Detalles --}}
                    <div>
                        <h4 class="text-xs uppercase font-bold text-gray-500 mb-2">Productos</h4>
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 text-xs uppercase">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Producto</th>
                                        <th class="px-3 py-2 text-right">Cant.</th>
                                        <th class="px-3 py-2 text-right">Precio</th>
                                        <th class="px-3 py-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($detalleFacturaModal['detalles'] as $d)
                                        <tr>
                                            <td class="px-3 py-2 text-gray-800 dark:text-gray-100">{{ $d['producto'] }}</td>
                                            <td class="px-3 py-2 text-right">{{ rtrim(rtrim(number_format($d['cantidad'], 2, '.', ''), '0'), '.') }}</td>
                                            <td class="px-3 py-2 text-right">$ {{ number_format($d['precio'], 0, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-right font-semibold">$ {{ number_format($d['total'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Pagos previos --}}
                    @if(!empty($detalleFacturaModal['pagos']))
                        <div>
                            <h4 class="text-xs uppercase font-bold text-gray-500 mb-2">Abonos registrados</h4>
                            <div class="rounded-lg border border-teal-200 bg-teal-50/50 overflow-hidden">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-teal-100/60 text-teal-700 text-xs uppercase">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Fecha</th>
                                            <th class="px-3 py-2 text-left">Método</th>
                                            <th class="px-3 py-2 text-left">Ref.</th>
                                            <th class="px-3 py-2 text-right">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-teal-100">
                                        @foreach($detalleFacturaModal['pagos'] as $p)
                                            <tr>
                                                <td class="px-3 py-2">{{ $p['fecha'] }}</td>
                                                <td class="px-3 py-2">{{ $p['metodo'] ?? '—' }}</td>
                                                <td class="px-3 py-2">{{ $p['ref'] ?? '—' }}</td>
                                                <td class="px-3 py-2 text-right text-teal-700 font-semibold">$ {{ number_format($p['monto'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($detalleFacturaModal['notas'])
                        <div class="bg-amber-50 border-l-4 border-amber-400 p-3 rounded-r-lg text-sm text-amber-800">
                            <span class="font-semibold">Notas:</span> {{ $detalleFacturaModal['notas'] }}
                        </div>
                    @endif

                    {{-- Resumen --}}
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 space-y-1.5">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Total factura</span>
                            <span class="font-semibold">$ {{ number_format($detalleFacturaModal['total'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Pagado</span>
                            <span class="font-semibold text-teal-700">$ {{ number_format($detalleFacturaModal['pagado'], 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between text-base pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="font-bold">Saldo pendiente</span>
                            <span class="font-extrabold text-amber-600 text-lg">$ {{ number_format($detalleFacturaModal['saldo'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-200 dark:border-gray-700 px-5 py-3 flex items-center justify-end gap-2 bg-gray-50 dark:bg-gray-800">
                    <button type="button" wire:click="cerrarDetalleFactura"
                        class="px-4 h-10 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 text-sm">
                        Cerrar
                    </button>
                    <button type="button"
                        wire:click="$dispatchTo('facturas.pagos-factura', 'abrir-modal-pago', { facturaId: {{ $detalleFacturaModal['id'] }} })"
                        class="px-4 h-10 rounded-lg bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white text-sm font-semibold shadow">
                        <i class="fas fa-money-check-alt mr-1"></i> Registrar pago
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@assets
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endassets
