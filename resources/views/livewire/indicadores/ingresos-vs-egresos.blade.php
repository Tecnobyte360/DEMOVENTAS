@php
    $cid = $this->getId();
    $chartId = 'ingresos-egresos-chart-' . $cid;

    $fmt = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
@endphp

<div
    class="relative flex flex-col h-full
            bg-white dark:bg-slate-900
            rounded-3xl
            border border-slate-200/70 dark:border-slate-700/60
            shadow-[0_40px_120px_-50px_rgba(15,23,42,.18)]
            overflow-hidden">

    {{-- glows --}}
    <div class="pointer-events-none absolute -top-28 -right-28 h-64 w-64 rounded-full bg-sky-400/25 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-28 -left-28 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>

    <div class="h-[3px] bg-gradient-to-r from-[#132742] via-sky-400 to-transparent"></div>

    {{-- Header --}}
    <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span
                class="inline-flex h-10 w-10 items-center justify-center rounded-2xl
                   bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 ring-1 ring-emerald-500/10">
                <i class="fa-solid fa-scale-balanced"></i>
            </span>

            <div class="leading-tight">
                <h2 class="font-semibold text-slate-800 dark:text-slate-100">Ingresos vs Egresos</h2>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Año {{ now()->year }} · Ingresos = Factura - Nota crédito · Egresos = Gastos + Compras - NC compra
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

    {{-- Body (crece) --}}
    <div class="flex-1 min-h-0 px-5 pt-4 pb-5 flex flex-col">

        {{-- KPIs --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div
                class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-sky-500"></span> Ingresos año
                </div>
                <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
                    {{ $fmt($totalIngresos) }}
                </div>
            </div>

            <div
                class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-rose-500"></span> Egresos año
                </div>
                <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
                    {{ $fmt($totalEgresos) }}
                </div>
            </div>

            <div
                class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Neto año
                </div>
                <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
                    {{ $fmt($totalNeto) }}
                </div>
            </div>
        </div>

        {{-- Toggles (si los quieres mantener, OK) --}}
        <div class="mt-4 flex flex-wrap items-center gap-2" x-data="{ ingresos: true, egresos: true, neto: true }" x-init="$nextTick(() => window.__IVE_applyToggles_{{ $cid }}?.(ingresos, egresos, neto))">
            <button type="button"
                class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 dark:border-slate-700/60 px-3 py-1 text-xs
                     bg-white/60 dark:bg-slate-900/40 backdrop-blur hover:bg-slate-50 dark:hover:bg-slate-800/60"
                @click="ingresos = !ingresos; window.__IVE_applyToggles_{{ $cid }}?.(ingresos, egresos, neto)">
                <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                <span class="text-slate-600 dark:text-slate-300">Ingresos</span>
                <span class="font-bold text-slate-800 dark:text-white" x-text="ingresos ? 'ON' : 'OFF'"></span>
            </button>

            <button type="button"
                class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 dark:border-slate-700/60 px-3 py-1 text-xs
                     bg-white/60 dark:bg-slate-900/40 backdrop-blur hover:bg-slate-50 dark:hover:bg-slate-800/60"
                @click="egresos = !egresos; window.__IVE_applyToggles_{{ $cid }}?.(ingresos, egresos, neto)">
                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                <span class="text-slate-600 dark:text-slate-300">Egresos</span>
                <span class="font-bold text-slate-800 dark:text-white" x-text="egresos ? 'ON' : 'OFF'"></span>
            </button>

            <button type="button"
                class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 dark:border-slate-700/60 px-3 py-1 text-xs
                     bg-white/60 dark:bg-slate-900/40 backdrop-blur hover:bg-slate-50 dark:hover:bg-slate-800/60"
                @click="neto = !neto; window.__IVE_applyToggles_{{ $cid }}?.(ingresos, egresos, neto)">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <span class="text-slate-600 dark:text-slate-300">Neto</span>
                <span class="font-bold text-slate-800 dark:text-white" x-text="neto ? 'ON' : 'OFF'"></span>
            </button>

            <span class="ml-auto text-[11px] text-slate-500 dark:text-slate-400">
                Tip: apaga “Neto” si quieres comparar solo barras
            </span>
        </div>

        {{-- Chart box (crece y centra la torta) --}}
        <div class="mt-4 flex-1 min-h-0">
            <div
                class="h-full rounded-3xl border border-slate-200/70 dark:border-slate-700/60
              bg-white/60 dark:bg-slate-900/40 backdrop-blur
              shadow-[0_20px_60px_-40px_rgba(15,23,42,.18)]
              p-4">

                <div class="h-[380px]" wire:ignore>
                    <canvas id="{{ $chartId }}"></canvas>
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
                    const neto = @json($neto);

                    el.__chart = new Chart(el.getContext('2d'), {
                        data: {
                            labels,
                            datasets: [{
                                    type: 'bar',
                                    label: 'Ingresos',
                                    data: ingresos,
                                    backgroundColor: 'rgba(14, 165, 233, .75)',
                                    borderRadius: 10,
                                    borderSkipped: false,
                                    maxBarThickness: 28,
                                },
                                {
                                    type: 'bar',
                                    label: 'Egresos',
                                    data: egresos,
                                    backgroundColor: 'rgba(244, 63, 94, .75)',
                                    borderRadius: 10,
                                    borderSkipped: false,
                                    maxBarThickness: 28,
                                },
                                {
                                    type: 'line',
                                    label: 'Neto',
                                    data: neto,
                                    borderColor: 'rgba(16, 185, 129, 1)',
                                    backgroundColor: 'rgba(16, 185, 129, .15)',
                                    borderWidth: 3,
                                    tension: .35,
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                    fill: false,
                                    yAxisID: 'y',
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
                                        color: document.documentElement.classList.contains('dark') ? '#CBD5E1' :
                                            '#475569'
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(ctx) {
                                            return `${ctx.dataset.label}: $${Number(ctx.raw || 0).toLocaleString('es-CO')}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: document.documentElement.classList.contains('dark') ? '#94A3B8' :
                                            '#64748B'
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(148,163,184,.18)'
                                    },
                                    ticks: {
                                        color: document.documentElement.classList.contains('dark') ? '#94A3B8' :
                                            '#64748B',
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

    <script>
        (function() {
            const chartId = @json($chartId);

            const totalIngresos = Number(@json($totalIngresos) || 0);
            const totalEgresos = Number(@json($totalEgresos) || 0);
            const totalNeto = Number(@json($totalNeto) || 0);

            const totalNC = Number(@json($totalNotasCredito ?? 0) || 0);

            function money(v) {
                const n = Number(v || 0);
                const abs = Math.abs(n).toLocaleString('es-CO');
                return (n < 0 ? '-$' : '$') + abs;
            }

            // Center text plugin
            const CenterTextPlugin = {
                id: 'centerText',
                afterDraw(chart, args, pluginOptions) {
                    const {
                        ctx,
                        chartArea
                    } = chart;
                    if (!chartArea) return;

                    const x = (chartArea.left + chartArea.right) / 2;
                    const y = (chartArea.top + chartArea.bottom) / 2;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    ctx.font = '600 12px Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                    ctx.fillStyle = pluginOptions?.titleColor || 'rgba(100,116,139,1)';
                    ctx.fillText('Neto', x, y - 12);

                    ctx.font = '900 20px Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                    ctx.fillStyle = pluginOptions?.valueColor || 'rgba(15,23,42,1)';
                    ctx.fillText(money(totalNeto), x, y + 14);

                    ctx.restore();
                }
            };

            function initChart() {
                const el = document.getElementById(chartId);
                if (!el || typeof Chart === 'undefined') return;

                if (el.__chart) {
                    el.__chart.destroy();
                    el.__chart = null;
                }

                const labels = totalNC > 0 ?
                    ['Ingresos', 'Egresos', 'Notas crédito'] :
                    ['Ingresos', 'Egresos'];

                const values = totalNC > 0 ?
                    [totalIngresos, totalEgresos, totalNC] :
                    [totalIngresos, totalEgresos];

                // ✅ Colores MÁS vivos + mejor contraste
                const colors = totalNC > 0 ?
                    ['rgba(14, 165, 233, .92)', 'rgba(244, 63, 94, .88)', 'rgba(245, 158, 11, .88)'] :
                    ['rgba(14, 165, 233, .92)', 'rgba(244, 63, 94, .88)'];

                el.__chart = new Chart(el.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            backgroundColor: colors,
                            // ✅ borde para que resalte y se vea "premium"
                            borderColor: document.documentElement.classList.contains('dark') ?
                                'rgba(15,23,42,.55)' :
                                'rgba(255,255,255,.95)',
                            borderWidth: 6,
                            hoverOffset: 10,
                            spacing: 2,
                        }]
                    },
                    plugins: [CenterTextPlugin],
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        rotation: -90,
                        plugins: {
                            legend: {
                                display: false
                            }, // usamos chips
                            tooltip: {
                                padding: 12,
                                backgroundColor: 'rgba(15,23,42,.92)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(148,163,184,.25)',
                                borderWidth: 1,
                                cornerRadius: 12,
                                callbacks: {
                                    label: (ctx) => {
                                        const val = Number(ctx.raw || 0);
                                        const sum = ctx.dataset.data.reduce((a, b) => a + Number(b || 0),
                                            0) || 1;
                                        const pct = (val / sum) * 100;
                                        return `${ctx.label}: ${money(val)} (${pct.toFixed(1)}%)`;
                                    }
                                }
                            },
                            centerText: {
                                titleColor: 'rgba(100,116,139,1)',
                                valueColor: document.documentElement.classList.contains('dark') ?
                                    'rgba(255,255,255,1)' :
                                    'rgba(15,23,42,1)',
                            }
                        }
                    }
                });

                // toggles (si quieres ocultar por dataset)
                window.__IVE_applyToggles_{{ $cid }} = function(showIng, showEgr, showNeto) {
                    if (!el.__chart) return;

                    // Nota: en doughnut, toggles escondiendo por "labels" no es directo
                    // Lo más simple: reconstruir datos según toggles.
                    const baseLabels = totalNC > 0 ? ['Ingresos', 'Egresos', 'Notas crédito'] : ['Ingresos',
                        'Egresos'
                    ];
                    const baseValues = totalNC > 0 ? [totalIngresos, totalEgresos, totalNC] : [totalIngresos,
                        totalEgresos
                    ];
                    const baseColors = totalNC > 0 ?
                        ['rgba(14, 165, 233, .92)', 'rgba(244, 63, 94, .88)', 'rgba(245, 158, 11, .88)'] :
                        ['rgba(14, 165, 233, .92)', 'rgba(244, 63, 94, .88)'];

                    const newLabels = [];
                    const newValues = [];
                    const newColors = [];

                    baseLabels.forEach((l, i) => {
                        if (l === 'Ingresos' && !showIng) return;
                        if (l === 'Egresos' && !showEgr) return;
                        // "Neto" toggle lo dejamos solo para el texto central
                        newLabels.push(l);
                        newValues.push(baseValues[i]);
                        newColors.push(baseColors[i]);
                    });

                    el.__chart.data.labels = newLabels;
                    el.__chart.data.datasets[0].data = newValues;
                    el.__chart.data.datasets[0].backgroundColor = newColors;

                    // Centro: si neto OFF, mostramos "—"
                    CenterTextPlugin.afterDraw = function(chart) {
                        const {
                            ctx,
                            chartArea
                        } = chart;
                        if (!chartArea) return;
                        const x = (chartArea.left + chartArea.right) / 2;
                        const y = (chartArea.top + chartArea.bottom) / 2;

                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';

                        ctx.font = '600 12px Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                        ctx.fillStyle = 'rgba(100,116,139,1)';
                        ctx.fillText('Neto', x, y - 12);

                        ctx.font = '900 20px Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
                        ctx.fillStyle = document.documentElement.classList.contains('dark') ? '#fff' :
                            'rgba(15,23,42,1)';
                        ctx.fillText(showNeto ? money(totalNeto) : '—', x, y + 14);

                        ctx.restore();
                    };

                    el.__chart.update();
                };
            }

            document.addEventListener('DOMContentLoaded', initChart);
            document.addEventListener('livewire:load', function() {
                initChart();
                Livewire.hook('message.processed', initChart);
            });
        })();
    </script>
</div>
