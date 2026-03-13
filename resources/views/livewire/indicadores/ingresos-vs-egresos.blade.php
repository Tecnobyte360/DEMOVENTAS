@php
    $cid = $this->getId();
    $chartId = 'ingresos-egresos-chart-' . $cid;
    $fmt = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
@endphp

<div class="relative flex flex-col h-full
            bg-white dark:bg-slate-900
            rounded-3xl
            border border-slate-200/70 dark:border-slate-700/60
            shadow-[0_40px_120px_-50px_rgba(15,23,42,.18)]
            overflow-hidden">

    {{-- Efectos visuales --}}
    <div class="pointer-events-none absolute -top-28 -right-28 h-64 w-64 rounded-full bg-emerald-400/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-28 -left-28 h-64 w-64 rounded-full bg-rose-400/20 blur-3xl"></div>

    <div class="h-[3px] bg-gradient-to-r from-[#132742] via-emerald-400 to-transparent"></div>

    {{-- Header --}}
    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl
                   bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 ring-1 ring-emerald-500/10">
                <i class="fa-solid fa-scale-balanced"></i>
            </span>

            <div class="leading-tight">
                <h2 class="font-semibold text-slate-800 dark:text-slate-100">Ingresos vs Egresos</h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Año {{ $year ?? now()->year }} · Comparativo mensual
                </p>
            </div>
        </div>

        <div class="text-right">
            <div class="text-[11px] text-slate-500 dark:text-slate-400">Neto año</div>
            <div class="mt-1 text-2xl font-extrabold text-slate-800 dark:text-white tabular-nums">
                {{ $fmt($totalNeto) }}
            </div>
        </div>
    </header>

    {{-- Contenido --}}
    <div class="flex-1 min-h-0 px-5 pt-4 pb-5 flex flex-col">

        {{-- KPI cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Ingresos año
                </div>
                <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
                    {{ $fmt($totalIngresos) }}
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-rose-500"></span> Egresos año
                </div>
                <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
                    {{ $fmt($totalEgresos) }}
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span> Neto año
                </div>
                <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
                    {{ $fmt($totalNeto) }}
                </div>
            </div>
        </div>

        {{-- Leyenda --}}
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 dark:border-slate-700/60 px-3 py-1 text-xs bg-white/70 dark:bg-slate-900/30">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span class="text-slate-600 dark:text-slate-300">Ingresos</span>
                </span>

                <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 dark:border-slate-700/60 px-3 py-1 text-xs bg-white/70 dark:bg-slate-900/30">
                    <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                    <span class="text-slate-600 dark:text-slate-300">Egresos</span>
                </span>
            </div>

            <span class="text-[11px] text-slate-500 dark:text-slate-400">
                Solo meses con movimiento
            </span>
        </div>

        {{-- Chart box --}}
        <div class="mt-4 flex-1 min-h-0">
            <div class="h-full rounded-3xl border border-slate-200/70 dark:border-slate-700/60
                  bg-white/60 dark:bg-slate-900/40 backdrop-blur
                  shadow-[0_20px_60px_-40px_rgba(15,23,42,.18)]
                  p-4">

                <div class="h-[380px]" wire:ignore>
                    <canvas id="{{ $chartId }}"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const chartId = @json($chartId);

            function initChart() {
                const el = document.getElementById(chartId);
                if (!el || typeof Chart === 'undefined') return;

                if (el.__chart) {
                    el.__chart.destroy();
                    el.__chart = null;
                }

                const labels = @json($labels);
                const ingresos = @json($ingresos);
                const egresos = @json($egresos);

                el.__chart = new Chart(el.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Ingresos',
                                data: ingresos,
                                backgroundColor: 'rgba(16,185,129,.70)',
                                borderColor: 'rgba(16,185,129,1)',
                                borderWidth: 1,
                                borderRadius: 10,
                                borderSkipped: false,
                                maxBarThickness: 38,
                                categoryPercentage: 0.58,
                                barPercentage: 0.88,
                                minBarLength: 8
                            },
                            {
                                label: 'Egresos',
                                data: egresos,
                                backgroundColor: 'rgba(244,63,94,.70)',
                                borderColor: 'rgba(244,63,94,1)',
                                borderWidth: 1,
                                borderRadius: 10,
                                borderSkipped: false,
                                maxBarThickness: 38,
                                categoryPercentage: 0.58,
                                barPercentage: 0.88,
                                minBarLength: 8
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    color: document.documentElement.classList.contains('dark') ? '#CBD5E1' : '#475569'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(15,23,42,.92)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(148,163,184,.25)',
                                borderWidth: 1,
                                cornerRadius: 12,
                                callbacks: {
                                    label: function(ctx) {
                                        return `${ctx.dataset.label}: $${Number(ctx.raw || 0).toLocaleString('es-CO')}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                stacked: false,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: document.documentElement.classList.contains('dark') ? '#94A3B8' : '#64748B'
                                }
                            },
                            y: {
                                stacked: false,
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(148,163,184,.18)'
                                },
                                ticks: {
                                    color: document.documentElement.classList.contains('dark') ? '#94A3B8' : '#64748B',
                                    callback: function(value) {
                                        return '$' + Number(value).toLocaleString('es-CO');
                                    }
                                }
                            }
                        }
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', initChart);
            document.addEventListener('livewire:navigated', initChart);

            document.addEventListener('livewire:load', function() {
                initChart();

                if (window.Livewire && Livewire.hook) {
                    Livewire.hook('message.processed', () => {
                        initChart();
                    });
                }
            });
        })();
    </script>
</div>