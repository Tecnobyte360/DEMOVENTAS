@php
  $cid     = $this->getId();
  $chartId = 'ventas-mes-chart-' . $cid;
  $wrapId  = 'ventas-mes-wrap-' . $cid;

  $year = now()->year;
@endphp

<style>
  .legend-chip {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .375rem .75rem;
    border-radius: 9999px;
    border: 1px solid rgba(226,232,240,.7);
    transition: .15s ease;
    cursor: pointer;
    user-select: none;
    font-size: 12px;
    line-height: 1;
    background: rgba(248,250,252,.6);
  }
  .dark .legend-chip { border-color: rgba(51,65,85,.6); background: rgba(15,23,42,.25); }
  .legend-chip:hover { transform: translateY(-1px); }
  .legend-chip.off { opacity: .45; text-decoration: line-through; }
  .legend-dot { width: 8px; height: 8px; border-radius: 9999px; display: inline-block; }
</style>

<div
  id="{{ $wrapId }}"
  class="relative flex flex-col col-span-full sm:col-span-6 xl:col-span-8
         bg-white dark:bg-slate-900
         rounded-3xl
         border border-slate-200/70 dark:border-gray-700/60
         shadow-[0_40px_120px_-50px_rgba(15,23,42,.22)]
         overflow-hidden"
>
  <div class="pointer-events-none absolute -top-24 -right-24 h-56 w-56 rounded-full bg-sky-400/20 blur-3xl"></div>
  <div class="pointer-events-none absolute -bottom-28 -left-28 h-56 w-56 rounded-full bg-indigo-500/20 blur-3xl"></div>

  <div class="h-[3px] bg-gradient-to-r from-[#132742] via-sky-400 to-transparent"></div>

  <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl
                   bg-indigo-500/10 text-indigo-600 dark:text-indigo-300 ring-1 ring-indigo-500/10">
        <i class="fa-solid fa-chart-column"></i>
      </span>

      <div class="leading-tight">
        <h2 class="font-semibold text-gray-800 dark:text-gray-100">Ventas por Mes</h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Contado vs Crédito · (Notas crédito aparte) · Año {{ $year }}
        </p>
      </div>
    </div>

    <div class="text-right">
      <div class="text-[11px] text-slate-500 dark:text-slate-400">Neto año</div>
      <div class="mt-1 text-2xl font-extrabold text-slate-800 dark:text-white tabular-nums">
        ${{ number_format($totalAnioNeto ?? 0, 0, ',', '.') }}
      </div>
    </div>
  </header>

  {{-- KPI cards --}}
  <div class="px-5 pt-4">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
          <span class="legend-dot" style="background: rgba(16,185,129,1)"></span> Contado año
        </div>
        <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
          ${{ number_format($totalAnioContado ?? 0, 0, ',', '.') }}
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
          <span class="legend-dot" style="background: rgba(99,102,241,1)"></span> Crédito año
        </div>
        <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
          ${{ number_format($totalAnioCredito ?? 0, 0, ',', '.') }}
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
          <span class="legend-dot" style="background: rgba(244,63,94,1)"></span> Notas crédito año
        </div>
        <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
          ${{ number_format($totalAnioNotasCredito ?? 0, 0, ',', '.') }}
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 bg-slate-50/60 dark:bg-slate-800/40 p-3">
        <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
          <span class="legend-dot" style="background: rgba(56,189,248,1)"></span> Neto año
        </div>
        <div class="mt-1 font-extrabold text-slate-800 dark:text-white tabular-nums">
          ${{ number_format($totalAnioNeto ?? 0, 0, ',', '.') }}
        </div>
      </div>
    </div>
  </div>

  {{-- Chart container --}}
  <div class="px-5 pt-4 pb-5">
    <div class="rounded-3xl border border-slate-200/70 dark:border-slate-700/60
                bg-white/60 dark:bg-slate-900/40 backdrop-blur
                shadow-[0_20px_60px_-40px_rgba(15,23,42,.25)]
                p-4">

      {{-- ✅ Chips clickeables --}}
      <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div class="flex flex-wrap items-center gap-2">
          <button type="button" class="legend-chip" data-ds="contado">
            <span class="legend-dot" style="background: rgba(16,185,129,1)"></span> Contado
          </button>

          <button type="button" class="legend-chip" data-ds="credito">
            <span class="legend-dot" style="background: rgba(99,102,241,1)"></span> Crédito
          </button>

          <button type="button" class="legend-chip" data-ds="notas">
            <span class="legend-dot" style="background: rgba(244,63,94,1)"></span> Notas crédito
          </button>

          <button type="button" class="legend-chip" data-ds="neto">
            <span class="legend-dot" style="background: rgba(56,189,248,1)"></span> Neto
          </button>
        </div>

        <div class="text-[11px] text-slate-500 dark:text-slate-400">
          Año {{ $year }}
        </div>
      </div>

      <div class="h-[320px]" wire:ignore>
        <canvas id="{{ $chartId }}"></canvas>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const wrapId  = @json($wrapId);
      const chartId = @json($chartId);

      function initChart() {
        const wrap = document.getElementById(wrapId);
        const el   = document.getElementById(chartId);
        if (!wrap || !el || typeof Chart === 'undefined') return;

        // evita duplicar listeners en re-render
        if (wrap.__initedLegend && el.__chart) {
          return;
        }

        // destruir si existía
        if (el.__chart) {
          el.__chart.destroy();
          el.__chart = null;
        }

        const labels  = @json($labels);
        const contado = @json($dataContado);
        const credito = @json($dataCredito);
        const notas   = @json($dataNotasCredito); // abs()
        const neto    = @json($dataNeto);

        const chart = new Chart(el.getContext('2d'), {
          data: {
            labels,
            datasets: [
              {
                id: 'contado',
                type: 'bar',
                label: 'Contado',
                data: contado,
                stack: 'ventas',
                borderWidth: 0,
                borderRadius: 14,
                borderSkipped: false,
                maxBarThickness: 30,
                backgroundColor: 'rgba(16,185,129,.55)',
              },
              {
                id: 'credito',
                type: 'bar',
                label: 'Crédito',
                data: credito,
                stack: 'ventas',
                borderWidth: 0,
                borderRadius: 14,
                borderSkipped: false,
                maxBarThickness: 30,
                backgroundColor: 'rgba(99,102,241,.55)',
              },
              {
                id: 'neto',
                type: 'line',
                label: 'Neto',
                data: neto,
                tension: .35,
                pointRadius: 0,
                borderWidth: 2,
                borderColor: 'rgba(56,189,248,1)',
              },
              {
                id: 'notas',
                type: 'line',
                label: 'Notas crédito',
                data: notas,
                tension: .35,
                pointRadius: 0,
                borderWidth: 2,
                borderColor: 'rgba(244,63,94,1)',
              },
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                padding: 12,
                callbacks: {
                  label: (ctx) => `${ctx.dataset.label}: $${Number(ctx.raw || 0).toLocaleString('es-CO')}`
                }
              }
            },
            scales: {
              x: { stacked: true, grid: { display: false } },
              y: {
                stacked: true,
                beginAtZero: true,
                grid: { color: 'rgba(148,163,184,.18)' },
                ticks: { callback: (v) => '$' + Number(v).toLocaleString('es-CO') }
              }
            }
          }
        });

        el.__chart = chart;

        // ✅ chips toggles
        const chips = wrap.querySelectorAll('.legend-chip');
        chips.forEach(btn => {
          btn.addEventListener('click', () => {
            const id = btn.dataset.ds;
            const ds = chart.data.datasets.find(d => d.id === id);
            if (!ds) return;

            ds.hidden = !ds.hidden;
            btn.classList.toggle('off', ds.hidden);
            chart.update();
          });
        });

        wrap.__initedLegend = true;
      }

      document.addEventListener('DOMContentLoaded', initChart);

      document.addEventListener('livewire:load', function () {
        initChart();
        Livewire.hook('message.processed', function () {
          // re-init (por si Livewire recrea el canvas)
          const wrap = document.getElementById(wrapId);
          const el   = document.getElementById(chartId);
          if (wrap) wrap.__initedLegend = false;
          if (el && el.__chart) { el.__chart.destroy(); el.__chart = null; }
          initChart();
        });
      });
    })();
  </script>
</div>
