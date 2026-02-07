@php
  $cid     = $this->getId();
  $chartId = 'ventas-dia-gauge-' . $cid;

  $pct = $totalFacturado > 0 ? round(($totalPagado / $totalFacturado) * 100) : 0;
@endphp

<div
  class="relative flex flex-col h-full
         bg-white dark:bg-slate-900
         rounded-3xl
         border border-slate-200/70 dark:border-slate-700/60
         shadow-[0_40px_120px_-50px_rgba(15,23,42,.18)]
         overflow-hidden"
  wire:poll.30s
>
  <div class="h-[3px] bg-gradient-to-r from-[#132742] via-sky-400 to-transparent"></div>

  {{-- Header --}}
  <header class="px-5 py-4 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-500/10 text-sky-600 dark:text-sky-300 ring-1 ring-sky-500/10">
        <i class="fa-solid fa-bolt"></i>
      </span>
      <div class="leading-tight">
        <h2 class="font-semibold text-slate-800 dark:text-slate-100">Ventas del Día</h2>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">Total de hoy (todo el día)</p>
      </div>
    </div>

    <div class="text-right">
      <div class="text-[11px] text-slate-500 dark:text-slate-400">Estado</div>
      <div class="mt-1 inline-flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-100">
        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
        En vivo
      </div>
    </div>
  </header>

  {{-- Body (crece para igualar altura) --}}
  <div class="flex-1 min-h-0 px-5 pt-4 pb-5 flex flex-col">

    {{-- Top KPI --}}
    <div class="flex items-end justify-between gap-3">
      <div>
        <div class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">
          Total facturado hoy
        </div>

        <div class="mt-1 flex items-center gap-2">
          <div class="text-3xl font-extrabold text-slate-800 dark:text-slate-100 tabular-nums">
            ${{ number_format($totalFacturado, 0, ',', '.') }}
          </div>

          <span class="text-xs font-semibold px-2 py-1 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">
            {{ $pct }}% pagado
          </span>
        </div>
      </div>
    </div>

    {{-- Mini KPIs --}}
    <div class="mt-4 grid grid-cols-3 gap-3">
      <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 p-3 bg-slate-50/60 dark:bg-slate-800/40">
        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
          <i class="fa-solid fa-hand-holding-dollar text-emerald-500"></i> Pagado
        </div>
        <div class="mt-1 font-bold text-slate-800 dark:text-white tabular-nums text-sm">
          ${{ number_format($totalPagado, 0, ',', '.') }}
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 p-3 bg-slate-50/60 dark:bg-slate-800/40">
        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
          <i class="fa-solid fa-triangle-exclamation text-rose-500"></i> Pendiente
        </div>
        <div class="mt-1 font-bold text-slate-800 dark:text-white tabular-nums text-sm">
          ${{ number_format($totalPendiente, 0, ',', '.') }}
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-slate-700/60 p-3 bg-slate-50/60 dark:bg-slate-800/40">
        <div class="flex items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
          <i class="fa-solid fa-file-invoice text-indigo-500"></i> Facturas
        </div>
        <div class="mt-1 font-bold text-slate-800 dark:text-white tabular-nums text-sm">
          {{ number_format($totalPedidos, 0, ',', '.') }}
        </div>
      </div>
    </div>

    {{-- Gauge area (esto es lo que lo hace GRANDE y parejo) --}}
    <div class="mt-4 flex-1 min-h-0">
      <div class="h-full rounded-3xl border border-slate-200/70 dark:border-slate-700/60 bg-white/60 dark:bg-slate-900/40 backdrop-blur
                  shadow-[0_20px_60px_-40px_rgba(15,23,42,.18)]
                  p-4 flex flex-col">

        <div class="flex-1 min-h-0 flex items-center justify-center" wire:ignore>
          <div class="relative w-full max-w-[320px]">
            <div class="h-[320px]">
              <canvas id="{{ $chartId }}"></canvas>
            </div>

            {{-- Texto centrado --}}
            <div class="pointer-events-none absolute inset-0 grid place-items-center">
              <div class="text-center">
                <div class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Pagado</div>
                <div class="text-3xl font-extrabold text-slate-800 dark:text-white tabular-nums">
                  {{ $pct }}%
                </div>
                <div class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                  ${{ number_format($totalPagado, 0, ',', '.') }} / ${{ number_format($totalFacturado, 0, ',', '.') }}
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Chips --}}
        <div class="pt-3 flex items-center justify-center gap-2 text-xs">
          <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 dark:border-slate-700/60 px-3 py-1">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            <span class="text-slate-600 dark:text-slate-300">Pagado</span>
            <b class="text-slate-800 dark:text-white tabular-nums">${{ number_format($totalPagado, 0, ',', '.') }}</b>
          </span>

          <span class="inline-flex items-center gap-2 rounded-full border border-slate-200/70 dark:border-slate-700/60 px-3 py-1">
            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
            <span class="text-slate-600 dark:text-slate-300">Pendiente</span>
            <b class="text-slate-800 dark:text-white tabular-nums">${{ number_format($totalPendiente, 0, ',', '.') }}</b>
          </span>
        </div>

      </div>
    </div>
  </div>

  <script>
    (function () {
      const chartId = @json($chartId);

      function money(v){
        return '$' + Number(v || 0).toLocaleString('es-CO');
      }

      function initChart() {
        const el = document.getElementById(chartId);
        if (!el || typeof Chart === 'undefined') return;

        if (el.__chart) { el.__chart.destroy(); el.__chart = null; }

        const pagado    = Number(@json($totalPagado)) || 0;
        const pendiente = Number(@json($totalPendiente)) || 0;

        el.__chart = new Chart(el.getContext('2d'), {
          type: 'doughnut',
          data: {
            labels: ['Pagado', 'Pendiente'],
            datasets: [{
              data: [pagado, pendiente],
              borderWidth: 0,
              hoverOffset: 8,
              cutout: '74%',
              backgroundColor: [
                'rgba(16, 185, 129, .85)',
                'rgba(244, 63, 94, .75)',
              ],
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false },
              tooltip: {
                padding: 12,
                backgroundColor: 'rgba(15,23,42,.92)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(148,163,184,.25)',
                borderWidth: 1,
                cornerRadius: 12,
                callbacks: {
                  label: (ctx) => `${ctx.label}: ${money(ctx.raw)}`
                }
              }
            }
          }
        });
      }

      document.addEventListener('DOMContentLoaded', initChart);
      document.addEventListener('livewire:load', function () {
        initChart();
        Livewire.hook('message.processed', initChart);
      });
    })();
  </script>
</div>
