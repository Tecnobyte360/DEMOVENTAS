@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>[x-cloak]{display:none!important}</style>
    <style>
      /* Resaltado del grupo abierto */
      .group-highlight {
        box-shadow: 0 0 0 2px rgba(124,58,237,.45) inset;
        transition: box-shadow .25s ease;
      }
      .row-pulse {
        animation: rowPulse 1.5s ease 1;
      }
      @keyframes rowPulse {
        0%   { box-shadow: 0 0 0 0 rgba(124,58,237,.5) inset; }
        60%  { box-shadow: 0 0 0 6px rgba(124,58,237,.2) inset; }
        100% { box-shadow: 0 0 0 0 rgba(124,58,237,0) inset; }
      }
    </style>
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Listener compatible con v2/v3 gracias al polyfill del componente
      window.addEventListener('kardex-scroll-to', (e) => {
        const uid = e.detail?.uid;
        const row = document.querySelector(`[data-doc-uid="${uid}"]`);
        if (row) {
          row.scrollIntoView({ behavior: 'smooth', block: 'center' });
          row.classList.add('row-pulse');
          setTimeout(() => row.classList.remove('row-pulse'), 1600);
        }
      });
    </script>
  @endpush
@endonce

<div class="p-6 space-y-6 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">

  <header class="flex flex-col md:flex-row md:items-end gap-4 justify-between">
    <div>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kardex de Producto</h2>
      <p class="text-sm text-gray-600 dark:text-gray-300">Consulta de movimientos por producto, bodega y rango de fechas, con costo promedio móvil.</p>
    </div>
  </header>

  {{-- Selector de fuente de datos --}}
  <section class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-xl p-4">
    <label class="text-sm font-medium text-gray-700 dark:text-gray-200 mb-2 block">Fuente de movimientos</label>
    <div class="flex flex-wrap gap-3">
      <label class="flex items-center cursor-pointer">
        <input type="radio" wire:model.live="fuenteDatos" value="kardex" class="w-4 h-4 text-violet-600 focus:ring-violet-500">
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">
          Solo Kardex <span class="text-xs text-gray-500">(tabla kardex_movimientos)</span>
        </span>
      </label>
      <label class="flex items-center cursor-pointer">
        <input type="radio" wire:model.live="fuenteDatos" value="costos" class="w-4 h-4 text-violet-600 focus:ring-violet-500">
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">
          Solo Costos <span class="text-xs text-gray-500">(tabla producto_costo_movimientos)</span>
        </span>
      </label>
      <label class="flex items-center cursor-pointer">
        <input type="radio" wire:model.live="fuenteDatos" value="ambas" class="w-4 h-4 text-violet-600 focus:ring-violet-500">
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-200">
          Ambas tablas <span class="text-xs text-gray-500">(combinadas)</span>
        </span>
      </label>
    </div>
  </section>

  {{-- Filtros --}}
  <section class="grid grid-cols-1 md:grid-cols-5 gap-4">
    <div>
      <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Producto</label>
      <select wire:model.live="producto_id"
              class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
        <option value="">— Selecciona —</option>
        @foreach($productos as $p)
          <option value="{{ $p->id }}">{{ $p->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Bodega</label>
      <select wire:model.live="bodega_id"
              class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
        <option value="">— Todas —</option>
        @foreach($bodegas as $b)
          <option value="{{ $b->id }}">{{ $b->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Desde</label>
      <input type="date" wire:model.live="desde"
             class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
    </div>

    <div>
      <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Hasta</label>
      <input type="date" wire:model.live="hasta"
             class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
    </div>

    <div>
      <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Buscar doc/ref</label>
      <input type="text" placeholder="OC #123, REF, etc" wire:model.debounce.500ms="buscarDoc"
             class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600">
    </div>
  </section>

  {{-- Resumen saldos --}}
  @if($producto_id)
    <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-300">Saldo inicial (cant)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($saldoInicialCant, 2) }}</p>
      </div>
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-300">Saldo inicial (valor)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format($saldoInicialVal, 2) }}</p>
      </div>
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-300">Saldo final (cant)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ $saldoFinalCant > 0 ? number_format($saldoFinalCant, 2) : '—' }}
        </p>
      </div>
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-300">Saldo final (valor)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ $saldoFinalVal > 0 ? '$'.number_format($saldoFinalVal, 2) : '—' }}
        </p>
      </div>
    </section>
  @endif

  {{-- Tabla --}}
  <section class="space-y-3">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Movimientos</h3>
      <div class="flex items-center gap-2">
        {{-- Botones expandir / contraer --}}
        @if($producto_id && isset($grupos) && count($grupos))
          <button type="button"
                  class="px-3 py-1.5 rounded-lg border text-sm bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700"
                  wire:click="expandirTodo(@js($grupos->pluck('uid')->values()))">
            Expandir todo
          </button>
          <button type="button"
                  class="px-3 py-1.5 rounded-lg border text-sm bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700"
                  wire:click="contraerTodo">
            Contraer todo
          </button>
        @endif

        <select wire:model.live="perPage" class="rounded-xl border bg-white dark:bg-gray-800 dark:text-white">
          @foreach([10,25,50,100] as $n)
            <option value="{{ $n }}">{{ $n }}/página</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-[1400px] w-full text-sm bg-white dark:bg-gray-800 rounded-xl overflow-hidden">
        <thead class="bg-violet-600 text-white">
          <tr>
            <th class="px-3 py-2 text-left">Fuente</th>
            <th class="px-3 py-2 text-left">Fecha</th>
            <th class="px-3 py-2 text-left">Bodega</th>
            <th class="px-3 py-2 text-left">Doc/Ref</th>
            <th class="px-3 py-2 text-center">Tipo</th>
            <th class="px-3 py-2 text-right">Entrada</th>
            <th class="px-3 py-2 text-right">Salida</th>
            <th class="px-3 py-2 text-right">Costo unit.</th>
            <th class="px-3 py-2 text-right">Saldo cant.</th>
            <th class="px-3 py-2 text-right">Saldo valor</th>
            <th class="px-3 py-2 text-right">CPU saldo</th>
            @if(in_array($fuenteDatos, ['costos', 'ambas']))
              <th class="px-3 py-2 text-center bg-violet-700">Método</th>
              <th class="px-3 py-2 text-right bg-violet-700">CPU Ant.</th>
              <th class="px-3 py-2 text-right bg-violet-700">CPU Nuevo</th>
              <th class="px-3 py-2 text-right bg-violet-700">Último Ant.</th>
              <th class="px-3 py-2 text-right bg-violet-700">Último Nuevo</th>
              <th class="px-3 py-2 text-center bg-violet-700">Evento</th>
            @endif
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @if(!$producto_id)
            <tr>
              <td colspan="{{ in_array($fuenteDatos, ['costos','ambas']) ? 17 : 11 }}"
                  class="px-3 py-4 text-center text-gray-500 dark:text-gray-400 italic">
                Selecciona un producto para ver su Kardex.
              </td>
            </tr>
          @else
            {{-- Fila de saldo inicial (solo en primera página) --}}
            @if($filas->currentPage() === 1)
              <tr class="bg-gray-50 dark:bg-gray-700/50">
                <td class="px-3 py-2">
                  <span class="text-xs px-2 py-1 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200">
                    INICIAL
                  </span>
                </td>
                <td class="px-3 py-2 italic text-gray-600 dark:text-gray-200">—</td>
                <td class="px-3 py-2 italic text-gray-600 dark:text-gray-200">
                  {{ $bodega_id ? ($bodegas->firstWhere('id',$bodega_id)->nombre ?? '—') : 'Todas' }}
                </td>
                <td class="px-3 py-2 font-medium">Saldo inicial</td>
                <td class="px-3 py-2 text-center">—</td>
                <td class="px-3 py-2 text-right">—</td>
                <td class="px-3 py-2 text-right">—</td>
                <td class="px-3 py-2 text-right">
                  @php $cpuIni = $saldoInicialCant>0 ? $saldoInicialVal/max($saldoInicialCant,1e-9) : null; @endphp
                  {{ $cpuIni !== null ? number_format($cpuIni, 2) : '—' }}
                </td>
                <td class="px-3 py-2 text-right">{{ number_format($saldoInicialCant, 2) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($saldoInicialVal, 2) }}</td>
                <td class="px-3 py-2 text-right">{{ $cpuIni !== null ? number_format($cpuIni, 2) : '—' }}</td>
                @if(in_array($fuenteDatos, ['costos','ambas']))
                  <td colspan="6" class="px-3 py-2 text-center text-gray-400 italic bg-gray-100 dark:bg-gray-800">—</td>
                @endif
              </tr>
            @endif

            {{-- Acordeón por documento --}}
            @forelse($grupos as $g)
              {{-- Cabecera del grupo (RESUMEN) --}}
              @php $isOpen = (bool)($openGroups[$g['uid']] ?? false); @endphp
              <tr wire:click="toggleGrupo('{{ $g['uid'] }}')"
                  data-doc-uid="{{ $g['uid'] }}"
                  class="cursor-pointer bg-gray-50/70 dark:bg-gray-800/40 hover:bg-gray-100 dark:hover:bg-gray-700/60
                         {{ $isOpen ? 'group-highlight' : '' }}">
                <td class="px-3 py-2">
                  <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-200">
                    DOC
                    <svg class="w-3 h-3 transition-transform {{ $isOpen ? 'rotate-90' : '' }}"
                         viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M6.293 7.293a1 1 0 011.414 0L12 11.586l-4.293 4.293a1 1 0 01-1.414-1.414L9.586 12 6.293 8.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                  </span>
                </td>
                <td class="px-3 py-2 font-medium">{{ $g['fecha'] }}</td>
                <td class="px-3 py-2">{{ $g['bodega'] }}</td>
                <td class="px-3 py-2 font-semibold">{{ $g['doc'] }}</td>
                <td class="px-3 py-2 text-center">RESUMEN</td>
                <td class="px-3 py-2 text-right">{{ $g['entrada_total'] > 0 ? number_format($g['entrada_total'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $g['salida_total']  > 0 ? number_format($g['salida_total'], 2)  : '—' }}</td>
                <td class="px-3 py-2 text-right">—</td>
                {{-- Saldos al cierre del documento --}}
                <td class="px-3 py-2 text-right">{{ ($g['saldo_cant'] ?? 0) > 0 ? number_format($g['saldo_cant'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ isset($g['saldo_val']) && abs($g['saldo_val'])>0.01 ? number_format($g['saldo_val'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">
                  {{ isset($g['saldo_cpu']) && $g['saldo_cpu']>0 ? number_format($g['saldo_cpu'], 2) : '—' }}
                </td>
                @if(in_array($fuenteDatos, ['costos','ambas']))
                  <td class="px-3 py-2 text-center bg-violet-700 text-white">—</td>
                  <td class="px-3 py-2 text-right bg-violet-700 text-white">—</td>
                  <td class="px-3 py-2 text-right bg-violet-700 text-white">—</td>
                  <td class="px-3 py-2 text-right bg-violet-700 text-white">—</td>
                  <td class="px-3 py-2 text-right bg-violet-700 text-white">—</td>
                  <td class="px-3 py-2 text-center bg-violet-700 text-white">—</td>
                @endif
              </tr>

              {{-- Detalle del grupo (solo si está abierto) --}}
              @if($isOpen)
                @foreach($g['rows'] as $r)
                  <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50
                    {{ ($r['fuente'] ?? 'kardex') === 'costos' ? 'border-l-4 border-l-blue-500' : 'border-l-4 border-l-green-500' }}">
                    <td class="px-3 py-2">
                      @if(($r['fuente'] ?? 'kardex') === 'kardex')
                        <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 font-medium">KARDEX</span>
                      @else
                        <span class="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-medium">COSTOS</span>
                      @endif
                    </td>
                    <td class="px-3 py-2">{{ $r['fecha'] }}</td>
                    <td class="px-3 py-2">{{ $r['bodega'] ?? '—' }}</td>
                    <td class="px-3 py-2">{{ $r['doc'] }}</td>
                    <td class="px-3 py-2 text-center">
                      <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                        @class([
                          'bg-green-100 text-green-700' => ($r['tipo'] ?? '')==='ENTRADA',
                          'bg-red-100 text-red-700'     => ($r['tipo'] ?? '')==='SALIDA',
                          'bg-amber-100 text-amber-700' => ($r['tipo'] ?? '')!=='ENTRADA' && ($r['tipo'] ?? '')!=='SALIDA',
                        ])">
                        {{ $r['tipo'] ?? '—' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-right">{{ isset($r['entrada']) && $r['entrada']>0 ? number_format($r['entrada'],2) : '—' }}</td>
                    <td class="px-3 py-2 text-right">{{ isset($r['salida'])  && $r['salida'] >0 ? number_format($r['salida'], 2) : '—' }}</td>
                    <td class="px-3 py-2 text-right">{{ isset($r['costo_unit']) && $r['costo_unit']>0 ? number_format($r['costo_unit'],2) : '—' }}</td>
                    <td class="px-3 py-2 text-right">{{ isset($r['saldo_cant']) && $r['saldo_cant']>0 ? number_format($r['saldo_cant'],2) : '—' }}</td>
                    <td class="px-3 py-2 text-right">{{ isset($r['saldo_val'])  && abs($r['saldo_val'])>0.01 ? number_format($r['saldo_val'],2) : '—' }}</td>
                    <td class="px-3 py-2 text-right">{{ isset($r['saldo_cpu'])  && $r['saldo_cpu']>0 ? number_format($r['saldo_cpu'],2) : '—' }}</td>

                    @if(in_array($fuenteDatos, ['costos','ambas']))
                      @if(!empty($r['costo_historico']))
                        <td class="px-3 py-2 text-center bg-blue-50 dark:bg-gray-800">
                          <span class="text-xs px-2 py-1 rounded-full bg-blue-200 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                            {{ $r['costo_historico']['metodo_costeo'] ?? '—' }}
                          </span>
                        </td>
                        <td class="px-3 py-2 text-right bg-blue-50 dark:bg-gray-800">
                          {{ isset($r['costo_historico']['costo_prom_anterior']) && (float)$r['costo_historico']['costo_prom_anterior']>0 ? number_format((float)$r['costo_historico']['costo_prom_anterior'],2) : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right bg-blue-50 dark:bg-gray-800 font-semibold">
                          {{ isset($r['costo_historico']['costo_prom_nuevo']) && (float)$r['costo_historico']['costo_prom_nuevo']>0 ? number_format((float)$r['costo_historico']['costo_prom_nuevo'],2) : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right bg-blue-50 dark:bg-gray-800">
                          {{ isset($r['costo_historico']['ultimo_costo_anterior']) && (float)$r['costo_historico']['ultimo_costo_anterior']>0 ? number_format((float)$r['costo_historico']['ultimo_costo_anterior'],2) : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right bg-blue-50 dark:bg-gray-800 font-semibold">
                          {{ isset($r['costo_historico']['ultimo_costo_nuevo']) && (float)$r['costo_historico']['ultimo_costo_nuevo']>0 ? number_format((float)$r['costo_historico']['ultimo_costo_nuevo'],2) : '—' }}
                        </td>
                        <td class="px-3 py-2 text-center bg-blue-50 dark:bg-gray-800">
                          <span class="text-xs text-gray-600 dark:text-gray-300">{{ $r['costo_historico']['tipo_evento'] ?? '—' }}</span>
                        </td>
                      @else
                        <td colspan="6" class="px-3 py-2 text-center text-gray-400 italic bg-blue-50/40 dark:bg-gray-800">—</td>
                      @endif
                    @endif
                  </tr>
                @endforeach
              @endif
            @empty
              <tr>
                <td colspan="{{ in_array($fuenteDatos, ['costos','ambas']) ? 17 : 11 }}"
                    class="px-3 py-4 text-center text-gray-500 dark:text-gray-400 italic">
                  Sin movimientos en el rango.
                </td>
              </tr>
            @endforelse
          @endif
        </tbody>
      </table>
    </div>

    <div>
      {{ $filas->links() }}
    </div>
  </section>

  {{-- Leyenda --}}
  @if($producto_id)
    <section class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
      <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-200 mb-2">📊 Leyenda</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 text-xs font-medium">KARDEX</span>
          <span class="text-gray-700 dark:text-gray-300">Movimientos desde tabla kardex_movimientos (borde verde)</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 text-xs font-medium">COSTOS</span>
          <span class="text-gray-700 dark:text-gray-300">Movimientos desde tabla producto_costo_movimientos (borde azul)</span>
        </div>
      </div>
    </section>
  @endif
</div>
