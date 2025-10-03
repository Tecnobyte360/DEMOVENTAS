<div class="space-y-8" x-data>

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600">
                    <i class="fas fa-hand-holding-usd"></i>
                </span>
                Informe de Liquidación de Conductores
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Rango:
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    <i class="far fa-calendar"></i>
                    {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}
                </span>
            </p>
        </div>

        {{-- TOOLBAR --}}
        <div class="flex items-center gap-2">
            <button type="button" wire:click="generarResumenConductores"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-white font-semibold shadow hover:bg-indigo-700 active:scale-[.99] transition">
                <i class="fas fa-rotate"></i>
                Actualizar
            </button>
        </div>
    </div>

    {{-- FILTROS --}}
    <div class="bg-white dark:bg-gray-900/60 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="fechaInicio" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Desde</label>
                <input id="fechaInicio" type="date" wire:model="fechaInicio"
                       class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label for="fechaFin" class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1 uppercase tracking-wide">Hasta</label>
                <input id="fechaFin" type="date" wire:model="fechaFin"
                       class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="generarResumenConductores"
                        class="w-full sm:w-auto inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-white font-semibold shadow hover:bg-indigo-700 transition">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
        </div>
    </div>

    {{-- RESUMEN PLEGABLE --}}
    <div x-data="{open:false}" class="space-y-3">
        <button type="button" @click="open=!open"
                class="inline-flex items-center gap-2 rounded-xl bg-gray-100 dark:bg-gray-800 px-4 py-2 text-gray-800 dark:text-gray-200 font-semibold hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            <i class="fas" :class="open ? 'fa-eye-slash' : 'fa-chart-pie'"></i>
            <span x-show="!open">Mostrar resumen</span>
            <span x-show="open">Ocultar resumen</span>
        </button>

        <div x-show="open" x-transition
             class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
            @php
              $cards = [
                ['icon'=>'fa-receipt','label'=>'Deducciones (Ruta + Devoluciones)','value'=>number_format(collect($resumenConductores)->sum('total_gastos') + collect($resumenConductores)->sum('total_devoluciones'),0,',','.'),'cls'=>'text-red-700 bg-red-50 dark:text-red-300 dark:bg-red-900/30'],
                ['icon'=>'fa-boxes','label'=>'Total Vendido','value'=>number_format(collect($resumenConductores)->sum(fn($f)=>($f['total_facturado']??0)+($f['total_pagos']??0)),0,',','.'),'cls'=>'text-slate-700 bg-slate-50 dark:text-slate-300 dark:bg-slate-900/30'],
                ['icon'=>'fa-fire-extinguisher','label'=>'Gastos Ruta','value'=>number_format(collect($resumenConductores)->sum('total_gastos'),0,',','.'),'cls'=>'text-amber-700 bg-amber-50 dark:text-amber-300 dark:bg-amber-900/30'],
                ['icon'=>'fa-rotate-left','label'=>'Devoluciones','value'=>number_format(collect($resumenConductores)->sum('total_devoluciones'),0,',','.') ,'cls'=>'text-pink-700 bg-pink-50 dark:text-pink-300 dark:bg-pink-900/30'],
                ['icon'=>'fa-sack-dollar','label'=>'Total a Liquidar','value'=>number_format(collect($resumenConductores)->sum('total_liquidar'),0,',','.'),'cls'=>'text-indigo-700 bg-indigo-50 dark:text-indigo-300 dark:bg-indigo-900/30'],
                ['icon'=>'fa-chart-line','label'=>'Neto Acumulado','value'=>number_format($netoAcumulado,0,',','.'),'cls'=>'text-violet-700 bg-violet-50 dark:text-violet-300 dark:bg-violet-900/30'],
                ['icon'=>'fa-building','label'=>'Gastos Administrativos','value'=>number_format($totalGastosAdministrativos,0,',','.'),'cls'=>'text-orange-700 bg-orange-50 dark:text-orange-300 dark:bg-orange-900/30'],
                ['icon'=>'fa-money-bill-trend-up','label'=>'Utilidad Total','value'=>number_format(collect($resumenConductores)->sum('utilidad'),0,',','.'),'cls'=>'text-green-700 bg-green-50 dark:text-green-300 dark:bg-green-900/30'],
              ];
            @endphp

            @foreach($cards as $c)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $c['label'] }}</div>
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl {{ $c['cls'] }}">
                          <i class="fas {{ $c['icon'] }}"></i>
                        </span>
                    </div>
                    <div class="mt-3 text-xl font-bold text-gray-900 dark:text-gray-100">${{ $c['value'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- OVERLAY DE CARGA GLOBAL --}}

            <div wire:loading.delay
                wire:target="generarResumenConductores,abrirCreditos,abrirTransferencias,verPedido"
                class="fixed inset-0 z-50 grid place-items-center bg-white/60 dark:bg-gray-950/60 backdrop-blur-md">

            <div class="relative w-[min(92vw,28rem)] md:w-[32rem] rounded-3xl border border-indigo-200/60 dark:border-indigo-800/60
                        bg-white/70 dark:bg-gray-900/60 shadow-2xl p-6 md:p-8 overflow-hidden">

                {{-- Shimmer sutil --}}
                <div class="pointer-events-none absolute inset-0 rounded-3xl overflow-hidden">
                <div class="animate-shimmer absolute -inset-x-40 -top-1/2 h-[300%]
                            bg-gradient-to-r from-transparent via-white/25 to-transparent dark:via-white/10"></div>
                </div>

                <div class="relative z-10 space-y-5 text-center">
                {{-- Spinner principal --}}
                <div class="mx-auto h-16 w-16 md:h-20 md:w-20 rounded-full border-4 border-indigo-500/60 border-t-transparent animate-spin"></div>

                {{-- Icono simpático --}}
                <div class="text-4xl md:text-5xl text-indigo-600/90 dark:text-indigo-300/90 animate-bounce drop-shadow">
                    <i class="fas fa-sync-alt"></i>
                </div>

                {{-- Título y subtítulo --}}
                <div class="space-y-1">
                    <h2 class="text-lg md:text-xl font-extrabold text-gray-800 dark:text-white tracking-tight">
                    Actualizando liquidación…
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                    Estamos preparando los datos. Un momento, por favor.
                    </p>
                </div>

                {{-- Barra de progreso animada --}}
                <div class="h-2 rounded-full bg-indigo-200/60 dark:bg-indigo-900/60 overflow-hidden">
                    <div class="animate-marquee h-full w-1/2 bg-gradient-to-r from-indigo-500 to-violet-500"></div>
                </div>
                </div>
            </div>
            </div>
<style>
@keyframes shimmer {
  0%   { transform: translateX(-60%); }
  100% { transform: translateX(60%); }
}
.animate-shimmer {
  animation: shimmer 2.2s infinite linear;
}

@keyframes marquee {
  0%   { transform: translateX(-100%); }
  100% { transform: translateX(200%); }
}
.animate-marquee {
  animation: marquee 1.4s infinite linear;
}
</style>



    {{-- TABLA PRINCIPAL --}}
    <div class="relative">
        <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <table class="min-w-full">
                <thead class="bg-gray-100/90 dark:bg-gray-800/80 backdrop-blur sticky top-0 z-10">
                    <tr class="text-[11px] tracking-wide text-gray-600 dark:text-gray-300 uppercase">
                        <th class="px-4 py-3 text-left">🗓 Fecha</th>
                        <th class="px-4 py-3 text-left">Conductor</th>
                        <th class="px-4 py-3 text-right">Facturado</th>
                        <th class="px-4 py-3 text-right">Gastos</th>
                        <th class="px-4 py-3 text-right">Utilidad</th>
                        <th class="px-4 py-3 text-right">Devoluciones</th>
                        <th class="px-4 py-3 text-right">Contado</th>
                        <th class="px-4 py-3 text-right">Créditos</th>
                        <th class="px-4 py-3 text-right">Transferencias</th>
                        <th class="px-4 py-3 text-right">Pagos Anteriores</th>
                        <th class="px-4 py-3 text-right">Debe entregar</th>
                        <th class="px-4 py-3 text-center">Detalle</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                @forelse($resumenConductores as $idx => $fila)
                    <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/30 transition" wire:key="fila-{{ $idx }}">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                            {{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                                    <i class="fas fa-user"></i>
                                </span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $fila['nombre'] }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right font-medium">${{ number_format($fila['total_facturado'] ?? 0,0,',','.') }}</td>
                        <td class="px-4 py-3 text-right text-amber-600">${{ number_format($fila['total_gastos'] ?? 0,0,',','.') }}</td>
                        <td class="px-4 py-3 text-right text-emerald-700">${{ number_format($fila['utilidad'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-pink-600">${{ number_format($fila['total_devoluciones'] ?? 0,0,',','.') }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600">${{ number_format($fila['total_pagos_contado'] ?? 0,0,',','.') }}</td>

                        {{-- Créditos: subfila Livewire --}}
                        <td class="px-4 py-3 text-right">
                            <button type="button"
                                    class="inline-flex items-center gap-1 text-indigo-700 hover:text-indigo-900 underline decoration-dotted"
                                    wire:click="abrirCreditos({{ $idx }})"
                                    wire:key="btn-cred-{{ $idx }}"
                                    title="Ver pedidos a crédito">
                                ${{ number_format($fila['total_pagos_credito'] ?? 0,0,',','.') }}
                                <i class="fas fa-caret-down"></i>
                            </button>
                        </td>

                        {{-- Transferencias: subfila Livewire --}}
                        <td class="px-4 py-3 text-right">
                            <button type="button"
                                    class="inline-flex items-center gap-1 text-cyan-700 hover:text-cyan-900 underline decoration-dotted"
                                    wire:click="abrirTransferencias({{ $idx }})"
                                    wire:key="btn-transf-{{ $idx }}"
                                    title="Ver transferencias">
                                ${{ number_format($fila['total_pagos_transferencia'] ?? 0,0,',','.') }}
                                <i class="fas fa-caret-down"></i>
                            </button>
                        </td>

                        <td class="px-4 py-3 text-right text-indigo-600">
                            <span class="cursor-help" @if(!empty($fila['pagos_credito_anteriores_detalle']))
                                  title="{{ collect($fila['pagos_credito_anteriores_detalle'])->map(fn($p) => 'Pedido #'.$p['pedido_id'].' - '.$p['fecha_pedido'])->implode(' | ') }}"
                                  @endif>
                                ${{ number_format($fila['pagos_credito_anteriores'] ?? 0, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-extrabold text-indigo-700">
                            ${{ number_format(
                                ($fila['total_pagos_contado'] ?? 0)
                              - ($fila['total_gastos'] ?? 0)
                              - ($fila['total_devoluciones'] ?? 0)
                              + ($fila['pagos_credito_anteriores'] ?? 0)
                            , 0, ',', '.') }}
                        </td>

                        {{-- Detalle productos (toggle rápido con Alpine) --}}
                        <td class="px-4 py-3 text-center">
                            <button type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                    @click="document.getElementById('detprod-{{ $idx }}')?.classList.toggle('hidden')"
                                    title="Ver detalle de productos">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </td>
                    </tr>

                    {{-- SUBFILA: CRÉDITOS --}}
                    @if(($filaCreditosAbierta ?? null) === $idx)
                    <tr class="bg-gray-50 dark:bg-gray-800/60" wire:key="creditos-{{ $idx }}">
                        <td colspan="12" class="px-4 pt-3 pb-5">
                            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <div class="px-4 py-2 text-[11px] font-semibold uppercase tracking-wide
                                     bg-gray-600 text-white
                                     dark:bg-gray-900 dark:text-gray-100
                                     border-b border-black/10 dark:border-white/10">
                                    Créditos del {{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }} — {{ $fila['nombre'] }}
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-white dark:bg-gray-900">
                                            <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                <th class="px-4 py-2 text-left">Pedido</th>
                                                <th class="px-4 py-2 text-left">Conductor</th>
                                                <th class="px-4 py-2 text-left">Cliente</th>
                                                <th class="px-4 py-2 text-left">Origen</th>
                                                <th class="px-4 py-2 text-left">Fecha</th>
                                                <th class="px-4 py-2 text-right">Monto</th>
                                                <th class="px-4 py-2 text-left">Observación</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @forelse(($fila['creditos_detalle'] ?? []) as $cd)
                                                <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/30 transition" wire:key="credrow-{{ $idx }}-{{ $cd['pedido_id'] }}">
                                                    <td class="px-4 py-2">
                                                        <button type="button"
                                                                wire:click="verPedido({{ (int)($cd['pedido_id'] ?? 0) }}, {{ $idx }}, 'creditos')"
                                                                wire:loading.attr="disabled"
                                                                class="text-indigo-700 hover:text-indigo-900 underline decoration-dotted font-semibold"
                                                                title="Ver detalle de este pedido">
                                                            #{{ $cd['pedido_id'] ?? '—' }}
                                                        </button>
                                                    </td>
                                                    <td class="px-4 py-2">{{ $cd['conductor'] ?? '—' }}</td>
                                                    <td class="px-4 py-2">{{ $cd['cliente'] ?? '—' }}</td>
                                                    <td class="px-4 py-2">{{ $cd['origen'] ?? '—' }}</td>
                                                    <td class="px-4 py-2">{{ isset($cd['fecha']) ? \Carbon\Carbon::parse($cd['fecha'])->format('d/m/Y H:i') : '—' }}</td>
                                                    <td class="px-4 py-2 text-right font-medium">${{ number_format($cd['monto'] ?? 0, 0, ',', '.') }}</td>
                                                    <td class="px-4 py-2">{{ $cd['observacion'] ?? '' }}</td>
                                                </tr>

                                                {{-- Detalle inline del pedido seleccionado (Créditos) --}}
                                                @if(($pedidoSeleccionado['id'] ?? null) === (int)($cd['pedido_id'] ?? 0))
                                                    <tr wire:loading.class.remove="hidden" wire:target="verPedido" class="hidden">
                                                        <td colspan="7" class="px-4 py-3">
                                                            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                                                <div class="animate-spin h-4 w-4 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
                                                                Cargando detalle…
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <tr class="bg-white dark:bg-gray-900" wire:key="creddet-{{ $cd['pedido_id'] }}">
                                                        <td colspan="7" class="px-4 py-4">
                                                            {{-- DETALLE DEL PEDIDO (inline) --}}
                                                            <div>
                                                                <div class="text-xs text-gray-600 dark:text-gray-300 mb-3 flex flex-wrap items-center gap-2">
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-1">
                                                                        <i class="far fa-calendar"></i> {{ $pedidoSeleccionado['fecha'] ?? '—' }}
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-1">
                                                                        <i class="fas fa-id-badge"></i> {{ $pedidoSeleccionado['conductor'] ?? '—' }}
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-1">
                                                                        <i class="fas fa-user-tie"></i> {{ $pedidoSeleccionado['cliente'] ?? '—' }}
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-1">
                                                                        <i class="fas fa-route"></i> {{ $pedidoSeleccionado['ruta'] ?? '—' }}
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 px-2 py-1">
                                                                        <i class="fas fa-wallet"></i> {{ ucfirst($pedidoSeleccionado['tipo_pago'] ?? '—') }}
                                                                    </span>
                                                                </div>

                                                                <div class="rounded-2xl border border-indigo-200 dark:border-indigo-800 bg-indigo-50/60 dark:bg-indigo-900/20 overflow-hidden">
                                                                    <div class="overflow-x-auto">
                                                                        <table class="min-w-full text-xs">
                                                                            <thead class="bg-indigo-100 dark:bg-indigo-900/60">
                                                                                <tr class="uppercase tracking-wide text-indigo-900 dark:text-indigo-200">
                                                                                    <th class="px-3 py-2 text-left">Producto</th>
                                                                                    <th class="px-3 py-2 text-left">Lista</th>
                                                                                    <th class="px-3 py-2 text-center">Cant.</th>
                                                                                    <th class="px-3 py-2 text-right">Costo</th>
                                                                                    <th class="px-3 py-2 text-right">Precio</th>
                                                                                    <th class="px-3 py-2 text-right">Subtotal</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody class="divide-y divide-indigo-100 dark:divide-indigo-800">
                                                                                @forelse(($pedidoSeleccionado['detalles'] ?? []) as $d)
                                                                                    <tr class="hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition">
                                                                                        <td class="px-3 py-2 text-indigo-950 dark:text-indigo-100">{{ $d['producto'] ?? '—' }}</td>
                                                                                        <td class="px-3 py-2 text-indigo-950 dark:text-indigo-100">{{ $d['lista'] ?? '—' }}</td>
                                                                                        <td class="px-3 py-2 text-center text-indigo-950 dark:text-indigo-100">{{ number_format($d['cantidad'] ?? 0, 0) }}</td>
                                                                                        <td class="px-3 py-2 text-right text-indigo-950 dark:text-indigo-100">${{ number_format($d['precio_base'] ?? 0, 0, ',', '.') }}</td>
                                                                                        <td class="px-3 py-2 text-right text-indigo-950 dark:text-indigo-100">${{ number_format($d['precio_venta'] ?? 0, 0, ',', '.') }}</td>
                                                                                        <td class="px-3 py-2 text-right font-medium text-indigo-950 dark:text-indigo-100">${{ number_format($d['subtotal'] ?? 0, 0, ',', '.') }}</td>
                                                                                    </tr>
                                                                                @empty
                                                                                    <tr><td colspan="6" class="px-3 py-3 text-center italic text-indigo-400 dark:text-indigo-300/70">Sin ítems.</td></tr>
                                                                                @endforelse
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>

                                                                @if(!empty($pedidoSeleccionado['pagos']))
                                                                    <div class="mt-4 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                                                                        <div class="bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                                                            Pagos
                                                                        </div>
                                                                        <div class="overflow-x-auto">
                                                                            <table class="min-w-full text-xs">
                                                                                <thead class="bg-white dark:bg-gray-900">
                                                                                    <tr class="text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                                                                        <th class="px-3 py-2 text-left">Fecha</th>
                                                                                        <th class="px-3 py-2 text-left">Método</th>
                                                                                        <th class="px-3 py-2 text-left">Observación</th>
                                                                                        <th class="px-3 py-2 text-right">Monto</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                                                    @foreach($pedidoSeleccionado['pagos'] as $p)
                                                                                        <tr>
                                                                                            <td class="px-3 py-2">{{ $p['fecha'] ?? '—' }}</td>
                                                                                            <td class="px-3 py-2 capitalize">{{ $p['metodo'] ?? '—' }}</td>
                                                                                            <td class="px-3 py-2">{{ $p['obs'] ?? '' }}</td>
                                                                                            <td class="px-3 py-2 text-right font-medium">${{ number_format($p['monto'] ?? 0, 0, ',', '.') }}</td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="px-4 py-4 text-center italic text-gray-400">Sin registros de crédito.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>

                                        @if(!empty($fila['creditos_detalle']))
                                            <tfoot class="bg-gray-50 dark:bg-gray-900">
                                                <tr class="text-sm font-semibold">
                                                    <td colspan="5" class="px-4 py-3 text-right">Total créditos:</td>
                                                    <td class="px-4 py-3 text-right">
                                                        ${{ number_format(collect($fila['creditos_detalle'])->sum('monto'), 0, ',', '.') }}
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- SUBFILA: TRANSFERENCIAS --}}
                    @if(($filaTransferenciasAbierta ?? null) === $idx)
                    <tr class="bg-gray-50 dark:bg-gray-800/60" wire:key="transf-{{ $idx }}">
                        <td colspan="12" class="px-4 pt-3 pb-5">
                            <div class="rounded-2xl border border-cyan-200 dark:border-cyan-800 overflow-hidden">
                                <div class="px-4 py-2 text-[11px] font-semibold uppercase tracking-wide
                                            bg-cyan-700 text-white dark:bg-cyan-900 dark:text-cyan-100">
                                    Transferencias del {{ \Carbon\Carbon::parse($fila['fecha'])->format('d/m/Y') }} — {{ $fila['nombre'] }}
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-cyan-50 dark:bg-cyan-900/40">
                                            <tr class="text-xs uppercase tracking-wide text-cyan-900 dark:text-cyan-100">
                                                <th class="px-4 py-2 text-left">Pedido</th>
                                                <th class="px-4 py-2 text-left">Conductor</th>
                                                <th class="px-4 py-2 text-left">Cliente</th>
                                                <th class="px-4 py-2 text-left">Origen</th>
                                                <th class="px-4 py-2 text-left">Fecha</th>
                                                <th class="px-4 py-2 text-right">Monto</th>
                                                <th class="px-4 py-2 text-left">Observación</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-cyan-100 dark:divide-cyan-800">
                                            @forelse(($fila['transferencias_detalle'] ?? []) as $td)
                                                <tr class="hover:bg-cyan-50 dark:hover:bg-cyan-900/30 transition" wire:key="trow-{{ $idx }}-{{ $td['pedido_id'] }}">
                                                    <td class="px-4 py-2">
                                                        <button type="button"
                                                                wire:click="verPedido({{ (int)($td['pedido_id'] ?? 0) }}, {{ $idx }}, 'transferencias')"
                                                                wire:loading.attr="disabled"
                                                                class="text-cyan-700 hover:text-cyan-900 underline decoration-dotted font-semibold"
                                                                title="Ver detalle de este pedido">
                                                            #{{ $td['pedido_id'] ?? '—' }}
                                                        </button>
                                                    </td>
                                                    <td class="px-4 py-2">{{ $td['conductor'] ?? '—' }}</td>
                                                    <td class="px-4 py-2">{{ $td['cliente'] ?? '—' }}</td>
                                                    <td class="px-4 py-2">{{ $td['origen'] ?? '—' }}</td>
                                                    <td class="px-4 py-2">{{ isset($td['fecha']) ? \Carbon\Carbon::parse($td['fecha'])->format('d/m/Y H:i') : '—' }}</td>
                                                    <td class="px-4 py-2 text-right font-medium">${{ number_format($td['monto'] ?? 0, 0, ',', '.') }}</td>
                                                    <td class="px-4 py-2">{{ $td['observacion'] ?? '' }}</td>
                                                </tr>

                                                {{-- Detalle inline del pedido seleccionado (Transferencias) --}}
                                                @if(($pedidoSeleccionado['id'] ?? null) === (int)($td['pedido_id'] ?? 0))
                                                    <tr wire:loading.class.remove="hidden" wire:target="verPedido" class="hidden">
                                                        <td colspan="7" class="px-4 py-3">
                                                            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                                                <div class="animate-spin h-4 w-4 border-2 border-cyan-600 border-t-transparent rounded-full"></div>
                                                                Cargando detalle…
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <tr class="bg-white dark:bg-gray-900" wire:key="tdet-{{ $td['pedido_id'] }}">
                                                        <td colspan="7" class="px-4 py-4">
                                                            {{-- DETALLE DEL PEDIDO (inline) --}}
                                                            <div>
                                                                <div class="text-xs text-gray-600 dark:text-gray-300 mb-3 flex flex-wrap items-center gap-2">
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-1">
                                                                        <i class="far fa-calendar"></i> {{ $pedidoSeleccionado['fecha'] ?? '—' }}
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-1">
                                                                        <i class="fas fa-id-badge"></i> {{ $pedidoSeleccionado['conductor'] ?? '—' }}
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-1">
                                                                        <i class="fas fa-user-tie"></i> {{ $pedidoSeleccionado['cliente'] ?? '—' }}
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 dark:bg-gray-800 px-2 py-1">
                                                                        <i class="fas fa-route"></i> {{ $pedidoSeleccionado['ruta'] ?? '—' }}
                                                                    </span>
                                                                    <span class="inline-flex items-center gap-1 rounded-lg bg-cyan-50 text-cyan-700 dark:bg-cyan-900/40 dark:text-cyan-200 px-2 py-1">
                                                                        <i class="fas fa-wallet"></i> {{ ucfirst($pedidoSeleccionado['tipo_pago'] ?? '—') }}
                                                                    </span>
                                                                </div>

                                                                <div class="rounded-2xl border border-cyan-200 dark:border-cyan-800 bg-cyan-50/60 dark:bg-cyan-900/20 overflow-hidden">
                                                                    <div class="overflow-x-auto">
                                                                        <table class="min-w-full text-xs">
                                                                            <thead class="bg-cyan-100 dark:bg-cyan-900/60">
                                                                                <tr class="uppercase tracking-wide text-cyan-900 dark:text-cyan-100">
                                                                                    <th class="px-3 py-2 text-left">Producto</th>
                                                                                    <th class="px-3 py-2 text-left">Lista</th>
                                                                                    <th class="px-3 py-2 text-center">Cant.</th>
                                                                                    <th class="px-3 py-2 text-right">Costo</th>
                                                                                    <th class="px-3 py-2 text-right">Precio</th>
                                                                                    <th class="px-3 py-2 text-right">Subtotal</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody class="divide-y divide-cyan-100 dark:divide-cyan-800">
                                                                                @forelse(($pedidoSeleccionado['detalles'] ?? []) as $d)
                                                                                    <tr class="hover:bg-cyan-50 dark:hover:bg-cyan-900/30 transition">
                                                                                        <td class="px-3 py-2 text-cyan-950 dark:text-cyan-100">{{ $d['producto'] ?? '—' }}</td>
                                                                                        <td class="px-3 py-2 text-cyan-950 dark:text-cyan-100">{{ $d['lista'] ?? '—' }}</td>
                                                                                        <td class="px-3 py-2 text-center text-cyan-950 dark:text-cyan-100">{{ number_format($d['cantidad'] ?? 0, 0) }}</td>
                                                                                        <td class="px-3 py-2 text-right text-cyan-950 dark:text-cyan-100">${{ number_format($d['precio_base'] ?? 0, 0, ',', '.') }}</td>
                                                                                        <td class="px-3 py-2 text-right text-cyan-950 dark:text-cyan-100">${{ number_format($d['precio_venta'] ?? 0, 0, ',', '.') }}</td>
                                                                                        <td class="px-3 py-2 text-right font-medium text-cyan-950 dark:text-cyan-100">${{ number_format($d['subtotal'] ?? 0, 0, ',', '.') }}</td>
                                                                                    </tr>
                                                                                @empty
                                                                                    <tr><td colspan="6" class="px-3 py-3 text-center italic text-cyan-600 dark:text-cyan-300/80">Sin ítems.</td></tr>
                                                                                @endforelse
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>

                                                                @if(!empty($pedidoSeleccionado['pagos']))
                                                                    <div class="mt-4 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                                                                        <div class="bg-gray-50 dark:bg-gray-800 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                                                            Pagos
                                                                        </div>
                                                                        <div class="overflow-x-auto">
                                                                            <table class="min-w-full text-xs">
                                                                                <thead class="bg-white dark:bg-gray-900">
                                                                                    <tr class="text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                                                                        <th class="px-3 py-2 text-left">Fecha</th>
                                                                                        <th class="px-3 py-2 text-left">Método</th>
                                                                                        <th class="px-3 py-2 text-left">Observación</th>
                                                                                        <th class="px-3 py-2 text-right">Monto</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                                                    @foreach($pedidoSeleccionado['pagos'] as $p)
                                                                                        <tr>
                                                                                            <td class="px-3 py-2">{{ $p['fecha'] ?? '—' }}</td>
                                                                                            <td class="px-3 py-2 capitalize">{{ $p['metodo'] ?? '—' }}</td>
                                                                                            <td class="px-3 py-2">{{ $p['obs'] ?? '' }}</td>
                                                                                            <td class="px-3 py-2 text-right font-medium">${{ number_format($p['monto'] ?? 0, 0, ',', '.') }}</td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </tbody>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="px-4 py-4 text-center italic text-cyan-600 dark:text-cyan-300">Sin transferencias.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>

                                        @if(!empty($fila['transferencias_detalle']))
                                            <tfoot class="bg-cyan-50 dark:bg-cyan-900/40">
                                                <tr class="text-sm font-semibold text-cyan-900 dark:text-cyan-100">
                                                    <td colspan="5" class="px-4 py-3 text-right">Total transferencias:</td>
                                                    <td class="px-4 py-3 text-right">
                                                        ${{ number_format(collect($fila['transferencias_detalle'])->sum('monto'), 0, ',', '.') }}
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endif

                    {{-- SUBFILA: Detalle de productos (general de la fila) --}}
                    <tr id="detprod-{{ $idx }}" class="hidden" wire:key="detprod-{{ $idx }}">
                        <td colspan="12" class="px-4 pb-6">
                            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                    Detalle de productos
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-white dark:bg-gray-900">
                                            <tr class="text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                                <th class="px-3 py-2 text-left">Producto</th>
                                                <th class="px-3 py-2 text-center">Cantidad</th>
                                                <th class="px-3 py-2 text-right">Costo</th>
                                                <th class="px-3 py-2 text-right">Precio Base</th>
                                                <th class="px-3 py-2 text-right">Precio Venta</th>
                                                <th class="px-3 py-2 text-left">Lista</th>
                                                <th class="px-3 py-2 text-right">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @foreach(($fila['detalles'] ?? []) as $item)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <td class="px-3 py-2">
                                                        {{ $item['producto'] ?? '—' }}
                                                        @if(($item['tipo'] ?? '') === 'devolución')
                                                            <span class="ml-2 text-[10px] text-red-600">(Devolución)</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2 text-center">{{ $item['cantidad'] ?? 0 }}</td>
                                                    <td class="px-3 py-2 text-right">${{ number_format($item['costo'] ?? 0, 0, ',', '.') }}</td>
                                                    <td class="px-3 py-2 text-right">${{ number_format($item['precio_base'] ?? 0, 0, ',', '.') }}</td>
                                                    <td class="px-3 py-2 text-right">${{ number_format($item['precio_venta'] ?? 0, 0, ',', '.') }}</td>
                                                    <td class="px-3 py-2">{{ $item['lista'] ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-right font-medium">${{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="12" class="px-4 py-10">
                            <div class="text-center text-gray-400 italic">No hay registros para el rango seleccionado.</div>
                        </td>
                    </tr>
                @endforelse

                @if(collect($resumenConductores)->isNotEmpty())
                    <tr class="bg-indigo-50 dark:bg-indigo-900/40 text-indigo-900 dark:text-indigo-100 font-semibold">
                        <td colspan="6" class="px-4 py-4 text-right">🧾 Total General:</td>
                        <td class="px-4 py-4 text-right">
                            ${{ number_format(collect($resumenConductores)->sum('total_pagos_contado'),0,',','.') }}
                        </td>
                        <td class="px-4 py-4 text-right">
                            ${{ number_format(collect($resumenConductores)->sum('total_pagos_credito'),0,',','.') }}
                        </td>
                        <td class="px-4 py-4 text-right">
                            ${{ number_format(collect($resumenConductores)->sum('total_pagos_transferencia'),0,',','.') }}
                        </td>
                        <td class="px-4 py-4 text-right">
                            ${{ number_format(collect($resumenConductores)->sum('pagos_credito_anteriores'),0,',','.') }}
                        </td>
                        <td class="px-4 py-4 text-right text-lg">
                            ${{ number_format(
                                collect($resumenConductores)->sum('total_pagos_contado')
                              + collect($resumenConductores)->sum('pagos_credito_anteriores')
                              - collect($resumenConductores)->sum('total_gastos')
                              - collect($resumenConductores)->sum('total_devoluciones')
                            ,0,',','.') }}
                        </td>
                        <td></td>
                    </tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
