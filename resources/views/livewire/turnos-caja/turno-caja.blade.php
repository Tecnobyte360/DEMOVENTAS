{{-- resources/views/livewire/turnos-caja/turno-caja.blade.php --}}

@once
    @push('styles')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <style>
            [x-cloak] {
                display: none !important
            }

            .chip {
                @apply px-2 py-0.5 rounded-full text-xs font-medium;
            }

            .badge {
                @apply px-2 py-0.5 rounded-full text-xs font-semibold;
            }

            .tab-btn {
                @apply px-3 py-2 rounded-xl text-sm font-medium transition;
            }

            .tab-active {
                @apply bg-indigo-600 text-white shadow;
            }

            .tab-idle {
                @apply bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700;
            }

            .card {
                @apply bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow;
            }

            .title {
                @apply text-gray-900 dark:text-white font-semibold;
            }

            .muted {
                @apply text-gray-500 dark:text-gray-400;
            }

            .thead {
                @apply bg-gray-50 dark:bg-gray-900/40;
            }

            .th {
                @apply text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 py-2;
            }

            .td {
                @apply py-2;
            }
        </style>
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
    @endpush
@endonce

@php
    $fmt = fn($v) => number_format((float) $v, 0, ',', '.');
@endphp

<div class="p-6 md:p-8 space-y-8 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700"
    wire:poll.8s x-data="{ tab: 'resumen' }">

    {{-- ============================ BLOQUE TURNO ACTUAL ============================ --}}
    @if (!$turno || $turno->estado === 'cerrado')
        <div class="max-w-2xl mx-auto text-center space-y-6">
            <div class="flex items-center justify-center gap-3">
                <i class="fa-solid fa-cash-register text-3xl text-indigo-600"></i>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Apertura de Turno de Caja</h2>
            </div>

            <div class="card p-6 text-left">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="text-sm muted mb-1 block">Base inicial</label>
                        <input type="number" step="0.01" wire:model.defer="base_inicial"
                            class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('base_inicial')
                            <div class="text-xs text-rose-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-center gap-3">
                    <button wire:click="abrir"
                        class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow transition">
                        <i class="fa-solid fa-lock-open mr-2"></i> Abrir turno
                    </button>
                </div>

                @if (session()->has('message'))
                    <div class="text-sm text-emerald-600 mt-4 text-center">{{ session('message') }}</div>
                @endif
                @if (session()->has('error'))
                    <div class="text-sm text-rose-600 mt-4 text-center">{{ session('error') }}</div>
                @endif
            </div>

            <p class="muted text-sm">
                Cuando abras el turno, todos los <strong>pagos</strong> que registres en facturas/notas quedarán
                vinculados a este turno.
            </p>
        </div>
    @else
        <section class="card p-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-cash-register text-indigo-600"></i>
                        Turno de Caja #{{ $turno->id }}
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                        <div class="text-sm muted">
                            Inicio:
                            <span class="font-medium text-gray-700 dark:text-gray-200">
                                {{ $turno->fecha_inicio }}
                            </span>
                        </div>

                        <div class="text-sm muted">
                            Abrió:
                            <span class="font-medium text-gray-700 dark:text-gray-200">
                                {{ $turno->abiertoPor?->name ?? ($turno->user?->name ?? '—') }}
                            </span>
                        </div>

                        @if ($turno->fecha_cierre)
                            <div class="text-sm muted">
                                Cierre:
                                <span class="font-medium text-gray-700 dark:text-gray-200">
                                    {{ $turno->fecha_cierre }}
                                </span>
                            </div>
                        @endif

                        @if ($turno->cerradoPor)
                            <div class="text-sm muted">
                                Cerró:
                                <span class="font-medium text-gray-700 dark:text-gray-200">
                                    {{ $turno->cerradoPor?->name ?? '—' }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span
                        class="badge {{ $turno->estado === 'abierto'
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                            : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                        {{ ucfirst($turno->estado) }}
                    </span>

                    <button wire:click="cerrar"
                        class="px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow transition">
                        <i class="fa-solid fa-lock mr-2"></i> Cerrar turno
                    </button>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <button @click="tab='resumen'" :class="tab === 'resumen' ? 'tab-btn tab-active' : 'tab-btn tab-idle'">
                    <i class="fa-solid fa-chart-pie mr-2"></i> Resumen
                </button>

                <button @click="tab='cierre'" :class="tab === 'cierre' ? 'tab-btn tab-active' : 'tab-btn tab-idle'">
                    <i class="fa-solid fa-calculator mr-2"></i> Cierre
                </button>
            </div>
        </section>

        <section x-show="tab==='cierre'" x-cloak class="space-y-4">
            <div class="grid md:grid-cols-3 gap-4">
                <div class="md:col-span-2 card p-4">
                    <h3 class="title mb-3 flex items-center gap-2">
                        <i class="fa-solid fa-scale-balanced text-indigo-500"></i> Resumen de cierre
                    </h3>

                    <div class="grid sm:grid-cols-2 gap-3 text-sm">
                        <div class="flex justify-between">
                            <span class="muted">Base inicial</span>
                            <span class="font-semibold">${{ $fmt($resumen['base_inicial'] ?? 0) }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="muted">Cobrado (todos los medios)</span>
                            <span class="font-semibold">${{ $fmt($resumen['total_ventas'] ?? 0) }}</span>
                        </div>

                        <div class="flex items-center justify-between gap-3 sm:col-span-2">
                            <span class="muted">Retiros</span>

                            @if (($resumen['retiros'] ?? 0) > 0)
                                <button type="button" wire:click="toggleRetiros"
                                    class="font-semibold text-rose-600 hover:text-rose-700 hover:underline transition"
                                    title="Ver detalle de retiros">
                                    -${{ $fmt($resumen['retiros'] ?? 0) }}
                                </button>
                            @else
                                <span class="font-semibold text-rose-600">
                                    -${{ $fmt($resumen['retiros'] ?? 0) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 my-3"></div>

                    @if ($mostrarTablaRetiros)
                        <div class="mt-4">
                            <h4
                                class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                                <i class="fa-solid fa-money-bill-transfer text-rose-500"></i>
                                Detalle de retiros del turno
                            </h4>

                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="thead">
                                        <tr>
                                            <th class="th">#</th>
                                            <th class="th">Fecha</th>
                                            <th class="th">Usuario</th>
                                            <th class="th">Motivo</th>
                                            <th class="th text-right">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @forelse($retirosDetalle as $retiro)
                                            <tr>
                                                <td class="td">{{ $retiro['id'] }}</td>
                                                <td class="td muted">{{ $retiro['fecha'] }}</td>
                                                <td class="td">{{ $retiro['usuario'] }}</td>
                                                <td class="td">{{ $retiro['motivo'] }}</td>
                                                <td class="td text-right font-semibold text-rose-600">
                                                    -${{ $fmt($retiro['monto']) }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="td muted text-center">
                                                    No hay retiros registrados en este turno.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4">
                        <button wire:click="cerrar"
                            class="w-full md:w-auto px-5 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow">
                            <i class="fa-solid fa-lock mr-2"></i> Confirmar cierre
                        </button>
                    </div>
                </div>

                <div class="card p-4">
                    <h3 class="title mb-3">Cobrado por tipo</h3>
                    <div class="space-y-2 text-sm">
                        @foreach ((array) $porTipo as $tipo => $total)
                            <div
                                class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                                <span class="muted capitalize">{{ strtolower($tipo) }}</span>
                                <span class="font-semibold">${{ $fmt($total) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ============================ INFORME HISTÓRICO ============================ --}}
    <section class="mt-8 space-y-4">
        <div class="card p-4">
            <h3 class="title mb-3 flex items-center gap-2">
                <i class="fa-solid fa-file-lines text-indigo-500"></i> Informe de turnos por rango de fechas
            </h3>

            <div class="grid md:grid-cols-4 gap-3 items-end">
                <div>
                    <label class="text-sm muted mb-1 block">Desde</label>
                    <input type="date" wire:model.defer="filtro_desde"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('filtro_desde')
                        <div class="text-xs text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label class="text-sm muted mb-1 block">Hasta</label>
                    <input type="date" wire:model.defer="filtro_hasta"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('filtro_hasta')
                        <div class="text-xs text-rose-600 mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="md:col-span-2 flex gap-2">
                    <button wire:click="actualizarInforme"
                        class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow">
                        <i class="fa-solid fa-magnifying-glass mr-2"></i> Aplicar filtro
                    </button>
                </div>
            </div>
        </div>

        <div class="card p-4 overflow-x-auto">
            <h3 class="title mb-3 flex items-center gap-2">
                <i class="fa-solid fa-table"></i> Detalle de turnos
            </h3>

            <table class="w-full text-sm">
                <thead class="thead">
                    <tr>
                        <th class="th">#</th>
                        <th class="th">Inicio</th>
                        <th class="th">Cierre</th>
                        <th class="th">Abrió</th>
                        <th class="th">Cerró</th>
                        <th class="th text-right">Base inicial</th>
                        <th class="th text-right">Ventas</th>
                        <th class="th text-right">Retiros</th>
                        <th class="th text-right">Neto del efectivo</th>
                        <th class="th">Medios de pago</th>
                        <th class="th">Estado</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($turnosInforme as $t)
                        @php
                            $baseInicial = (float) $t->base_inicial;
                            $ventas = (float) $t->ventas_efectivo;
                            $ingresos = (float) $t->ingresos_efectivo;
                            $retiros = (float) $t->retiros_efectivo;
                            $devoluciones = (float) $t->devoluciones;

                            $neto = $baseInicial + $ventas + $ingresos - $retiros - $devoluciones;

                            $abiertoPor = $t->abiertoPor?->name ?? ($t->user?->name ?? '—');
                            $cerradoPor = $t->cerradoPor?->name ?? '—';
                        @endphp

                        <tr>
                            <td class="td">{{ $t->id }}</td>
                            <td class="td muted">{{ $t->fecha_inicio }}</td>
                            <td class="td muted">{{ $t->fecha_cierre ?? '—' }}</td>
                            <td class="td">{{ $abiertoPor }}</td>
                            <td class="td">{{ $cerradoPor }}</td>
                            <td class="td text-right font-semibold">${{ $fmt($t->base_inicial) }}</td>
                            <td class="td text-right font-semibold">${{ $fmt($t->total_ventas) }}</td>

                            <td class="td text-right">
                                @if ((float) $t->retiros_efectivo > 0)
                                    <button type="button" wire:click="verRetirosTurno({{ $t->id }})"
                                        class="text-rose-600 font-semibold hover:text-rose-700 hover:underline transition"
                                        title="Ver detalle de retiros">
                                        -${{ $fmt($t->retiros_efectivo) }}
                                    </button>
                                @else
                                    <span class="text-rose-600">-${{ $fmt($t->retiros_efectivo) }}</span>
                                @endif
                            </td>

                            <td
                                class="td text-right font-bold {{ $neto < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                                ${{ $fmt($neto) }}
                            </td>

                            <td class="td">
                                <div class="flex gap-2 whitespace-nowrap overflow-x-auto py-1">
                                    @foreach ($mediosActivos as $medio)
                                        @php
                                            $total = $mapMediosPorTurno[$t->id][$medio['id']] ?? 0;
                                        @endphp

                                        <span
                                            class="chip {{ $total > 0
                                                ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200'
                                                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                            {{ strtoupper($medio['nombre']) }}:
                                            ${{ $fmt($total) }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="td">
                                <span
                                    class="chip {{ $t->estado === 'cerrado'
                                        ? 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-100'
                                        : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' }}">
                                    {{ ucfirst($t->estado) }}
                                </span>
                            </td>
                        </tr>

                        @if ($mostrarTablaRetiros && $turnoDetalleRetirosId === $t->id)
                            <tr>
                                <td colspan="11" class="py-3">
                                    <div
                                        class="rounded-2xl border border-rose-200 bg-rose-50/60 dark:bg-rose-900/10 dark:border-rose-800 p-4">
                                        <h4
                                            class="text-sm font-semibold text-rose-700 dark:text-rose-300 mb-3 flex items-center gap-2">
                                            <i class="fa-solid fa-money-bill-transfer"></i>
                                            Detalle de retiros del turno #{{ $t->id }}
                                        </h4>

                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead class="thead">
                                                    <tr>
                                                        <th class="th">#</th>
                                                        <th class="th">Fecha</th>
                                                        <th class="th">Usuario</th>
                                                        <th class="th">Motivo</th>
                                                        <th class="th text-right">Monto</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                    @forelse($retirosDetalle as $retiro)
                                                        <tr>
                                                            <td class="td">{{ $retiro['id'] }}</td>
                                                            <td class="td muted">{{ $retiro['fecha'] }}</td>
                                                            <td class="td">{{ $retiro['usuario'] }}</td>
                                                            <td class="td">{{ $retiro['motivo'] }}</td>
                                                            <td class="td text-right font-semibold text-rose-600">
                                                                -${{ $fmt($retiro['monto']) }}
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="td muted text-center">
                                                                No hay retiros registrados en este turno.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif

                    @empty
                        <tr>
                            <td class="td muted" colspan="11">
                                No hay turnos en el rango seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <p class="muted text-xs mt-3">
                * Los “Medios de pago” se muestran con base en el resumen guardado al cerrar el turno.
            </p>
        </div>
    </section>
</div>
