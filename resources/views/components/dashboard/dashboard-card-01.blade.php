
      {{-- @livewire('indicadores.indicadores', [
            'titulo' => 'Ventas Totales',
            'valor' => $dataFeed->sumDataSet(1, 1),
            'colorFondo' => 'bg-green-100 dark:bg-green-900',
            'colorTexto' => 'text-green-800 dark:text-green-300',
            'icono' => 'fas fa-dollar-sign'
        ])
    </div>
  --}}

  <div
    class="relative flex flex-col col-span-full sm:col-span-6 xl:col-span-4
           bg-white
           rounded-2xl
           border border-slate-200/70
           shadow-[0_30px_80px_-30px_rgba(15,23,42,.18)]
           overflow-hidden">

    <!-- Brand top line -->
    <div class="h-[3px] bg-gradient-to-r from-[#132742] via-sky-400 to-transparent"></div>

    <!-- Header -->
    <header class="px-6 py-4 border-b border-slate-200/70">
        <div class="flex items-center justify-between">
            <h2 class="font-bold tracking-tight text-[#132742]">Top Countries</h2>

            <!-- Chip opcional (puedes borrar si no lo usas) -->
            <span class="text-[11px] font-semibold uppercase tracking-wider
                         text-slate-500 bg-slate-100 px-2 py-1 rounded-full">
                Analytics
            </span>
        </div>
    </header>

    <!-- Body -->
    <div class="grow flex flex-col justify-center">
        <div class="px-4 sm:px-5 pt-4">
            <canvas id="dashboard-card-06" width="389" height="260" class="!w-full"></canvas>
        </div>

        <!-- Legend -->
        <div id="dashboard-card-06-legend" class="px-6 pt-2 pb-6">
            <ul class="flex flex-wrap justify-center gap-2"></ul>
        </div>
    </div>

    <!-- subtle bottom fade -->
    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-10
                bg-gradient-to-t from-white to-transparent"></div>
</div>
