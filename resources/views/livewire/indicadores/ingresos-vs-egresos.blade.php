@php
$cid = $this->getId();
$chartId = 'ingresos-egresos-chart-' . $cid;
@endphp

<div wire:ignore class="h-[380px]">
    <canvas id="{{ $chartId }}"></canvas>
</div>

<script>
(function () {

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
                        minBarLength: 6
                    },
                    {
                        type: 'bar',
                        label: 'Egresos',
                        data: egresos,
                        backgroundColor: 'rgba(244,63,94,.65)',
                        borderColor: 'rgba(244,63,94,1)',
                        borderWidth: 1,
                        borderRadius: 10,
                        minBarLength: 6
                    },
                    {
                        type: 'line',
                        label: 'Neto',
                        data: neto,
                        borderColor: 'rgba(59,130,246,1)',
                        borderWidth: 3,
                        tension: .35,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

    }

    document.addEventListener('DOMContentLoaded', initChart);
    document.addEventListener('livewire:navigated', initChart);

})();
</script>