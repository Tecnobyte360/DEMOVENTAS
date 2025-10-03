<div
  class="relative p-6 md:p-10 rounded-3xl shadow-2xl overflow-hidden
         bg-gradient-to-br from-violet-50 via-white to-indigo-50
         dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">

  {{-- Overlay de carga --}}
  <div wire:loading
       class="absolute inset-0 z-50 flex flex-col items-center justify-center
              bg-white/60 dark:bg-black/40 backdrop-blur-sm">
    <svg class="animate-spin h-8 w-8 text-violet-600 mb-3" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
      <path class="opacity-75" fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
    </svg>
    <div class="text-sm font-medium text-gray-700 dark:text-gray-200">Actualizando…</div>
  </div>

  {{-- Fondos decorativos --}}
  <div aria-hidden="true" class="pointer-events-none absolute -top-24 -right-24 w-72 h-72 rounded-full
              bg-violet-400/25 blur-3xl"></div>
  <div aria-hidden="true" class="pointer-events-none absolute -bottom-20 -left-20 w-96 h-96 rounded-full
              bg-indigo-400/20 blur-3xl"></div>

  {{-- Encabezado --}}
  <div class="relative z-10 mb-6 md:mb-8">
    <div class="flex items-center gap-3">
      <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl
                   bg-gradient-to-tr from-violet-600/15 to-indigo-600/15
                   text-violet-700 dark:text-violet-300 ring-1 ring-violet-500/20">
        <i class="fas fa-chart-line text-xl"></i>
      </span>
      <div>
        <h2 class="text-2xl md:text-3xl font-black tracking-tight
                   text-gray-800 dark:text-white">Reportes y Analítica</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">
          Top clientes, artículos más vendidos, compras por cliente y entradas de mercancía.
        </p>
      </div>
    </div>
  </div>

  {{-- Filtros (sticky) --}}
  <div class="relative z-10 mb-8 sticky top-4">
    <div
      class="bg-white/70 dark:bg-white/10 backdrop-blur-md rounded-2xl
             border border-violet-200/60 dark:border-violet-500/20
             shadow-lg shadow-violet-200/40 dark:shadow-black/30">
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 p-5">
        <div>
          <label class="text-xs text-gray-500">Desde</label>
          <input type="date" wire:model.live="desde"
                 class="w-full mt-1 rounded-xl border-gray-300 focus:ring-violet-500 focus:border-violet-500
                        dark:bg-gray-800 dark:border-gray-700"/>
        </div>
        <div>
          <label class="text-xs text-gray-500">Hasta</label>
          <input type="date" wire:model.live="hasta"
                 class="w-full mt-1 rounded-xl border-gray-300 focus:ring-violet-500 focus:border-violet-500
                        dark:bg-gray-800 dark:border-gray-700"/>
        </div>

        {{-- Selector de cliente por nombre --}}
        <div class="lg:col-span-2">
          <label class="text-xs text-gray-500">Cliente / Socio de Negocio</label>
          <div class="mt-1 flex gap-2">
            <select wire:model="clienteId"
                    class="flex-1 rounded-xl border-gray-300 focus:ring-violet-500 focus:border-violet-500
                           dark:bg-gray-800 dark:border-gray-700">
              <option value="">— Todos —</option>
              @foreach($clientesOptions ?? [] as $op)
                <option value="{{ $op['id'] }}">{{ $op['nombre'] }}</option>
              @endforeach
            </select>
            @if($clienteId)
              <button wire:click="limpiarCliente"
                      class="px-3 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700
                             text-gray-700 dark:text-gray-200">
                Limpiar
              </button>
            @endif
          </div>
        </div>

        <div class="flex items-end">
          <button wire:click="generar"
                  class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl
                         bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700
                         text-white font-semibold shadow-lg hover:shadow-xl transition">
            <i class="fas fa-sync mr-2"></i> Actualizar
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- KPIs --}}
  @php
    $mejorCliente = $topClientes[0]['cliente'] ?? '—';
    $mejorClienteTotal = isset($topClientes[0]['total']) ? (float)$topClientes[0]['total'] : 0;
    $ventasPeriodo = array_sum(array_map(fn($x)=> (float)$x['total'], $topClientes));
    $numClientes = max(count($topClientes), 1);
    $ticketPromedio = $ventasPeriodo / $numClientes;
    $topProductoNombre = $topProductos[0]['producto'] ?? '—';
    $topProductoCant = $topProductos[0]['cantidad'] ?? 0;
  @endphp

  <div class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    @php
      $kpiClasses = 'p-6 rounded-2xl bg-white/80 dark:bg-white/10 border
                     shadow group ring-1 ring-transparent
                     hover:ring-violet-400/30 transition';
    @endphp

    <div class="{{ $kpiClasses }} border-violet-200/60 dark:border-violet-500/20">
      <div class="text-[11px] uppercase tracking-wider text-gray-500">Mejor cliente</div>
      <div class="mt-1 text-xl font-bold text-gray-800 dark:text-white truncate" title="{{ $mejorCliente }}">
        {{ $mejorCliente }}
      </div>
      <div class="text-sm text-gray-600 dark:text-gray-300">Total $ {{ number_format($mejorClienteTotal, 0) }}</div>
      <div class="mt-3 inline-flex items-center gap-1 text-[11px] text-violet-700/80 dark:text-violet-300/90">
        <i class="fas fa-crown"></i> Top del periodo
      </div>
    </div>

    <div class="{{ $kpiClasses }} border-indigo-200/60 dark:border-indigo-500/20">
      <div class="text-[11px] uppercase tracking-wider text-gray-500">Ventas del periodo</div>
      <div class="mt-1 text-2xl font-extrabold text-gray-800 dark:text-white">$ {{ number_format($ventasPeriodo, 0) }}</div>
      <div class="text-[11px] text-gray-500">Suma Top (10)</div>
    </div>

    <div class="{{ $kpiClasses }} border-fuchsia-200/60 dark:border-fuchsia-500/20">
      <div class="text-[11px] uppercase tracking-wider text-gray-500">Ticket promedio</div>
      <div class="mt-1 text-2xl font-extrabold text-gray-800 dark:text-white">$ {{ number_format($ticketPromedio, 0) }}</div>
      <div class="text-[11px] text-gray-500">Entre Top (10)</div>
    </div>

    <div class="{{ $kpiClasses }} border-emerald-200/60 dark:border-emerald-500/20">
      <div class="text-[11px] uppercase tracking-wider text-gray-500">Producto estrella</div>
      <div class="mt-1 text-xl font-bold text-gray-800 dark:text-white truncate" title="{{ $topProductoNombre }}">
        {{ $topProductoNombre }}
      </div>
      <div class="text-sm text-gray-600 dark:text-gray-300">Cantidad: {{ number_format($topProductoCant, 2) }}</div>
    </div>
  </div>

  {{-- Gráficos --}}
  <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    {{-- Top Clientes --}}
    <div
      class="bg-white/80 dark:bg-white/10 border border-gray-200 dark:border-gray-800
             rounded-2xl shadow ring-1 ring-violet-400/10">
      <div class="px-6 py-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
          <i class="fas fa-users mr-2 text-violet-600"></i>Top clientes
        </h3>
        <span class="text-xs px-2 py-1 rounded bg-violet-50 dark:bg-violet-500/10
                     text-violet-700 dark:text-violet-200 ring-1 ring-violet-400/20">
          Ventas por cliente
        </span>
      </div>
      <div class="px-4 pb-6">
        @if(count($topClientes))
          <canvas id="chartTopClientes" height="280"></canvas>
        @else
          <div class="p-6 text-center text-gray-500">Sin datos para el periodo</div>
        @endif
      </div>
    </div>

    {{-- Top Productos --}}
    <div
      class="bg-white/80 dark:bg-white/10 border border-gray-200 dark:border-gray-800
             rounded-2xl shadow ring-1 ring-indigo-400/10">
      <div class="px-6 py-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
          <i class="fas fa-boxes mr-2 text-indigo-600"></i>Artículos más vendidos
        </h3>
        <span class="text-xs px-2 py-1 rounded bg-indigo-50 dark:bg-indigo-500/10
                     text-indigo-700 dark:text-indigo-200 ring-1 ring-indigo-400/20">
          Cantidad vendida
        </span>
      </div>
      <div class="px-4 pb-6">
        @if(count($topProductos))
          <canvas id="chartTopProductos" height="280"></canvas>
        @else
          <div class="p-6 text-center text-gray-500">Sin datos para el periodo</div>
        @endif
      </div>
    </div>
  </div>

  {{-- Qué compraron los clientes --}}
  <div
    class="relative z-10 bg-white/80 dark:bg-white/10 border border-gray-200 dark:border-gray-800
           rounded-2xl shadow ring-1 ring-gray-400/10">
    <div class="px-6 py-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
        <i class="fas fa-receipt mr-2 text-emerald-600"></i>Qué compraron los clientes (Top)
      </h3>
      <span class="text-xs px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-500/10
                   text-emerald-700 dark:text-emerald-200 ring-1 ring-emerald-400/20">
        Desglose por producto
      </span>
    </div>

    <div class="divide-y divide-gray-100/70 dark:divide-gray-800/70">
      @forelse($comprasPorCliente as $c)
        <div x-data="{ open: false }" class="px-4 md:px-6 py-4">
          <button @click="open=!open"
                  class="w-full flex items-center justify-between gap-4 text-left">
            <div>
              <div class="font-semibold text-gray-900 dark:text-white">{{ $c['cliente'] }}</div>
              <div class="text-xs text-gray-500">Total $ {{ number_format($c['total'],0) }}</div>
            </div>
            <div class="shrink-0 text-gray-400" :class="{'rotate-180': open}">
              <i class="fas fa-chevron-down transition-transform"></i>
            </div>
          </button>

          <div x-show="open" x-transition class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="bg-gray-50/70 dark:bg-gray-800/50">
                <tr>
                  <th class="px-3 py-2 text-left">Producto</th>
                  <th class="px-3 py-2 text-right">Cantidad</th>
                  <th class="px-3 py-2 text-right">Ingreso</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($c['items'] as $i)
                  <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                    <td class="px-3 py-2">{{ $i['producto'] }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format($i['cantidad'],2) }}</td>
                    <td class="px-3 py-2 text-right">$ {{ number_format($i['ingreso'],0) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @empty
        <div class="px-6 py-10 text-center text-gray-500">No hay compras en el periodo.</div>
      @endforelse
    </div>
  </div>

  {{-- Entradas de mercancía del socio --}}
  <div
    class="relative z-10 mt-8 bg-white/80 dark:bg-white/10 border border-gray-200 dark:border-gray-800
           rounded-2xl shadow ring-1 ring-gray-400/10">
    <div class="px-6 py-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
        <i class="fas fa-dolly-flatbed mr-2 text-amber-600"></i>Entradas de mercancía del socio
        @if($clienteId)
          <span class="text-xs text-gray-500"> (filtrado por cliente)</span>
        @endif
      </h3>
      <div class="text-xs px-3 py-1 rounded bg-amber-50 dark:bg-amber-500/10
                  text-amber-700 dark:text-amber-200 ring-1 ring-amber-400/20">
        Total periodo: $ {{ number_format($entradasSocioTotal ?? 0, 0) }}
      </div>
    </div>

    @if(count($entradasSocio ?? []))
      <div class="divide-y divide-gray-100/70 dark:divide-gray-800/70">
        @foreach($entradasSocio as $e)
          <div x-data="{ open: false }" class="px-4 md:px-6 py-4">
            <button @click="open=!open"
                    class="w-full flex items-center justify-between gap-4 text-left">
              <div class="min-w-0">
                <div class="font-semibold text-gray-900 dark:text-white truncate">
                  EM-{{ $e['id'] }} • {{ $e['cliente'] }}
                </div>
                <div class="text-xs text-gray-500">
                  Fecha: {{ $e['fecha'] }} • Lista: {{ $e['lista_precio'] ?? '—' }} •
                  Total $ {{ number_format($e['total'], 0) }}
                </div>
                @if(!empty($e['observaciones']))
                  <div class="text-[11px] mt-1 text-gray-500 italic truncate">“{{ $e['observaciones'] }}”</div>
                @endif
              </div>
              <div class="shrink-0 text-gray-400" :class="{'rotate-180': open}">
                <i class="fas fa-chevron-down transition-transform"></i>
              </div>
            </button>

            <div x-show="open" x-transition class="mt-4 overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-50/70 dark:bg-gray-800/50">
                  <tr>
                    <th class="px-3 py-2 text-left">Producto</th>
                    <th class="px-3 py-2 text-left">Bodega</th>
                    <th class="px-3 py-2 text-right">Cantidad</th>
                    <th class="px-3 py-2 text-right">Precio unit.</th>
                    <th class="px-3 py-2 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  @foreach($e['items'] as $i)
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                      <td class="px-3 py-2">{{ $i['producto'] }}</td>
                      <td class="px-3 py-2">{{ $i['bodega'] }}</td>
                      <td class="px-3 py-2 text-right">{{ number_format($i['cantidad'], 2) }}</td>
                      <td class="px-3 py-2 text-right">$ {{ number_format($i['precio_unitario'], 0) }}</td>
                      <td class="px-3 py-2 text-right">$ {{ number_format($i['subtotal'], 0) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="px-6 py-8 text-center text-gray-500">
        No hay entradas de mercancía en el periodo.
      </div>
    @endif
  </div>

</div>

{{-- Scripts de charts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.addEventListener('livewire:load', () => {
    const makeGradient = (ctx, area, from, to) => {
      const g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
      g.addColorStop(0, from);
      g.addColorStop(1, to);
      return g;
    };

    const renderCharts = () => {
      const topClientes  = @json($topClientes ?? []);
      const topProductos = @json($topProductos ?? []);

      // -------- Top Clientes --------
      const c1 = document.getElementById('chartTopClientes');
      if (c1) {
        if (c1.__chart) c1.__chart.destroy();
        if (topClientes.length) {
          const ctx = c1.getContext('2d');
          c1.__chart = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: topClientes.map(x => x.cliente),
              datasets: [{
                label: 'Ventas ($)',
                data: topClientes.map(x => +x.total),
                borderWidth: 1,
                borderRadius: 8,
                backgroundColor: (context) => {
                  const {chart} = context;
                  const {ctx, chartArea} = chart;
                  if (!chartArea) return '#7c3aed';
                  return makeGradient(ctx, chartArea, 'rgba(124,58,237,0.65)', 'rgba(99,102,241,0.25)');
                }
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, callback: v => {
                  const label = this?.data?.labels?.[v] || '';
                  return label.length > 14 ? label.slice(0, 14) + '…' : label;
                }}},
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' },
                     ticks: { callback: v => '$ ' + new Intl.NumberFormat().format(v) } }
              },
              plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => '$ ' + new Intl.NumberFormat().format(ctx.parsed.y) } }
              }
            },
            plugins: [{ id: 'fixHeight', beforeInit: (chart) => { chart.canvas.parentNode.style.height = '280px'; } }]
          });
        }
      }

      // -------- Top Productos --------
      const c2 = document.getElementById('chartTopProductos');
      if (c2) {
        if (c2.__chart) c2.__chart.destroy();
        if (topProductos.length) {
          const ctx = c2.getContext('2d');
          c2.__chart = new Chart(ctx, {
            type: 'bar',
            data: {
              labels: topProductos.map(x => x.producto),
              datasets: [{
                label: 'Cantidad',
                data: topProductos.map(x => +x.cantidad),
                borderWidth: 1,
                borderRadius: 8,
                backgroundColor: (context) => {
                  const {chart} = context;
                  const {ctx, chartArea} = chart;
                  if (!chartArea) return '#6366f1';
                  return makeGradient(ctx, chartArea, 'rgba(99,102,241,0.65)', 'rgba(56,189,248,0.25)');
                }
              }]
            },
            options: {
              responsive: true, maintainAspectRatio: false,
              scales: {
                x: { grid: { display: false },
                     ticks: { maxRotation: 0, autoSkip: true, callback: (v, i, ticks) => {
                       const label = c2.__chart?.data?.labels?.[v] || '';
                       return label.length > 14 ? label.slice(0, 14) + '…' : label;
                     }}},
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.06)' } }
              },
              plugins: { legend: { display: false } }
            },
            plugins: [{ id: 'fixHeight2', beforeInit: (chart) => { chart.canvas.parentNode.style.height = '280px'; } }]
          });
        }
      }
    };

    renderCharts();
    if (window.Livewire) {
      Livewire.hook('message.processed', () => { renderCharts(); });
    }
  });
</script>
