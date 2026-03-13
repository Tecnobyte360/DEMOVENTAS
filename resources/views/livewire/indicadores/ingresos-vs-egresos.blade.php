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

            const labels   = @json($labels);
            const ingresos = @json($ingresos);
            const egresos  = @json($egresos);
            const neto     = @json($neto);

            el.__chart = new Chart(el.getContext('2d'), {
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Ingresos',
                            data: ingresos,
                            backgroundColor: 'rgba(16,185,129,.65)',
                            borderColor: 'rgba(16,185,129,1)',
                            borderWidth: 1,
                            borderRadius: 10,
                            borderSkipped: false,
                            maxBarThickness: 24,
                            categoryPercentage: 0.7,
                            barPercentage: 0.9,
                            minBarLength: 6,
                            order: 1
                        },
                        {
                            type: 'bar',
                            label: 'Egresos',
                            data: egresos,
                            backgroundColor: 'rgba(244,63,94,.65)',
                            borderColor: 'rgba(244,63,94,1)',
                            borderWidth: 1,
                            borderRadius: 10,
                            borderSkipped: false,
                            maxBarThickness: 24,
                            categoryPercentage: 0.7,
                            barPercentage: 0.9,
                            minBarLength: 6,
                            order: 1
                        },
                        {
                            type: 'line',
                            label: 'Neto',
                            data: neto,
                            borderColor: 'rgba(59,130,246,1)',
                            backgroundColor: 'rgba(59,130,246,.15)',
                            borderWidth: 3,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: 'rgba(59,130,246,1)',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            fill: false,
                            order: 0
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
                                color: document.documentElement.classList.contains('dark')
                                    ? '#CBD5E1'
                                    : '#475569'
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
                                color: document.documentElement.classList.contains('dark')
                                    ? '#94A3B8'
                                    : '#64748B'
                            }
                        },
                        y: {
                            stacked: false,
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(148,163,184,.18)'
                            },
                            ticks: {
                                color: document.documentElement.classList.contains('dark')
                                    ? '#94A3B8'
                                    : '#64748B',
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