{{-- =============== MOVIMIENTOS (RESUMEN + DETALLE) =============== --}}
<section class="space-y-3">
  {{-- Toolbar --}}
  <div class="flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Movimientos</h3>
    <div class="flex items-center gap-2">
      <label class="text-xs text-gray-500 dark:text-gray-400">Items:</label>
      <select wire:model.live="perPage" class="rounded-xl border bg-white dark:bg-gray-800 dark:text-white">
        @foreach([10,25,50,100] as $n)
          <option value="{{ $n }}">{{ $n }}/página</option>
        @endforeach
      </select>
    </div>
  </div>

  {{-- Acordeones controlados por Alpine, con persistencia --}}
  <div
    x-data="kdxAccordions('kdx-open')"
    x-init="init()"
    class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
  >
    {{-- Encabezado (solo columnas resumen) --}}
    <table class="min-w-[900px] w-full text-sm">
      <thead class="bg-violet-600 text-white">
        <tr>
          <th class="px-3 py-2 text-left">Fuente</th>
          <th class="px-3 py-2 text-left">Fecha</th>
          <th class="px-3 py-2 text-left">Bodega</th>
          <th class="px-3 py-2 text-left">Doc/Ref</th>
          <th class="px-3 py-2 text-center">Tipo</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        {{-- Si no hay producto seleccionado --}}
        @unless($producto_id)
          <tr>
            <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400 italic">
              Selecciona un producto para ver su Kardex.
            </td>
          </tr>
        @else
          {{-- Saldo inicial (solo página 1) --}}
          @if($filas->currentPage() === 1)
            <tr class="bg-gray-50 dark:bg-gray-700/50">
              <td class="px-3 py-2">
                <span class="text-xs px-2 py-1 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200">
                  INICIAL
                </span>
              </td>
              <td class="px-3 py-2 italic text-gray-600 dark:text-gray-200">—</td>
              <td class="px-3 py-2 italic text-gray-600 dark:text-gray-200">
                {{ $bodega_id ? ($bodegas->firstWhere('id', $bodega_id)->nombre ?? '—') : 'Todas' }}
              </td>
              <td class="px-3 py-2 font-medium">Saldo inicial</td>
              <td class="px-3 py-2 text-center">—</td>
            </tr>
          @endif

          {{-- === GRUPOS POR DOCUMENTO: RESUMEN + DETALLE === --}}
          @forelse($grupos as $g)
            @php
              // id único por grupo para aria-controls (seguro para HTML)
              $rowId = 'kdx-' . $g['uid'];
            @endphp

            {{-- FILA RESUMEN --}}
            <tr
              wire:key="kdx-row-{{ $g['uid'] }}"
              class="hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer border-l-4 border-l-indigo-500"
              :aria-expanded="isOpen('{{ $g['uid'] }}')"
              @click="toggle('{{ $g['uid'] }}')"
              @keydown.enter.prevent="toggle('{{ $g['uid'] }}')"
              @keydown.space.prevent="toggle('{{ $g['uid'] }}')"
              tabindex="0"
              role="button"
              aria-controls="{{ $rowId }}"
            >
              <td class="px-3 py-2">
                <span class="text-xs px-2 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-200 font-medium">DOC</span>
              </td>
              <td class="px-3 py-2">{{ $g['fecha'] }}</td>
              <td class="px-3 py-2">{{ $g['bodega'] ?: '—' }}</td>
              <td class="px-3 py-2">
                <div class="flex items-center gap-2">
                  <svg class="w-4 h-4 transition-transform"
                       :class="isOpen('{{ $g['uid'] }}') ? 'rotate-90' : 'rotate-0'"
                       viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7 5a1 1 0 011.707-.707l4 4a1 1 0 010 1.414l-4 4A1 1 0 017 12.586L10.586 9 7 5.414A1 1 0 017 5z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-violet-700 dark:text-violet-300 underline-offset-2 hover:underline">
                    {{ $g['doc'] }}
                  </span>
                </div>
              </td>
              <td class="px-3 py-2 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">RESUMEN</span>
              </td>
            </tr>

            {{-- FILA DETALLE (ACORDEÓN) --}}
            <tr id="{{ $rowId }}" x-show="isOpen('{{ $g['uid'] }}')" x-collapse x-cloak>
              <td colspan="5" class="px-0 pb-3">
                <div class="mx-3 mt-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                  <div class="overflow-x-auto">
                    <table class="min-w-[1200px] w-full text-xs">
                      <thead class="sticky top-0 z-10 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
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
                          @if(in_array($fuenteDatos, ['costos','ambas']))
                            <th class="px-3 py-2 text-center">Método</th>
                            <th class="px-3 py-2 text-right">CPU Ant.</th>
                            <th class="px-3 py-2 text-right">CPU Nuevo</th>
                            <th class="px-3 py-2 text-right">Último Ant.</th>
                            <th class="px-3 py-2 text-right">Último Nuevo</th>
                            <th class="px-3 py-2 text-center">Evento</th>
                          @endif
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($g['rows'] as $r)
                          <tr class="{{ $r['fuente']==='costos' ? 'border-l-4 border-l-blue-500' : 'border-l-4 border-l-green-500' }}">
                            <td class="px-3 py-2">
                              @if($r['fuente']==='kardex')
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 font-medium">KARDEX</span>
                              @else
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-medium">COSTOS</span>
                              @endif
                            </td>
                            <td class="px-3 py-2">{{ $r['fecha'] }}</td>
                            <td class="px-3 py-2">{{ $r['bodega'] ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $r['doc'] }}</td>
                            <td class="px-3 py-2 text-center">
                              <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold
                                @class([
                                  'bg-green-100 text-green-700' => $r['tipo']==='ENTRADA',
                                  'bg-red-100 text-red-700'     => $r['tipo']==='SALIDA',
                                  'bg-amber-100 text-amber-700' => $r['tipo']!=='ENTRADA' && $r['tipo']!=='SALIDA',
                                ])">
                                {{ $r['tipo'] }}
                              </span>
                            </td>
                            <td class="px-3 py-2 text-right">{{ $r['entrada'] ? number_format($r['entrada'],2) : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $r['salida']  ? number_format($r['salida'],2)  : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $r['costo_unit'] ? number_format($r['costo_unit'],2) : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $r['saldo_cant'] ? number_format($r['saldo_cant'],2) : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ abs($r['saldo_val'])>0.01 ? number_format($r['saldo_val'],2) : '—' }}</td>
                            <td class="px-3 py-2 text-right">{{ $r['saldo_cpu'] ? number_format($r['saldo_cpu'],2) : '—' }}</td>

                            @if(in_array($fuenteDatos, ['costos','ambas']))
                              @if($r['costo_historico'])
                                <td class="px-3 py-2 text-center">
                                  <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-200 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    {{ $r['costo_historico']['metodo_costeo'] ?? '—' }}
                                  </span>
                                </td>
                                <td class="px-3 py-2 text-right">
                                  {{ ($r['costo_historico']['costo_prom_anterior']??null) ? number_format((float)$r['costo_historico']['costo_prom_anterior'],2) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right font-semibold">
                                  {{ ($r['costo_historico']['costo_prom_nuevo']??null) ? number_format((float)$r['costo_historico']['costo_prom_nuevo'],2) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                  {{ ($r['costo_historico']['ultimo_costo_anterior']??null) ? number_format((float)$r['costo_historico']['ultimo_costo_anterior'],2) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-right font-semibold">
                                  {{ ($r['costo_historico']['ultimo_costo_nuevo']??null) ? number_format((float)$r['costo_historico']['ultimo_costo_nuevo'],2) : '—' }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                  <span class="text-[10px] text-gray-600 dark:text-gray-300">
                                    {{ $r['costo_historico']['tipo_evento'] ?? '—' }}
                                  </span>
                                </td>
                              @else
                                <td colspan="6" class="px-3 py-2 text-center text-gray-400 italic">—</td>
                              @endif
                            @endif
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>

                  {{-- Totales del documento (opcional) --}}
                  <div class="flex items-center justify-end gap-6 px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                    <div><span class="font-medium">Total entrada:</span> {{ number_format($g['entrada_total'],2) }}</div>
                    <div><span class="font-medium">Total salida:</span>  {{ number_format($g['salida_total'],2) }}</div>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400 italic">Sin movimientos en el rango.</td>
            </tr>
          @endforelse
        @endunless
      </tbody>
    </table>
  </div>

  {{-- Paginación --}}
  <div>
    {{ $filas->links() }}
  </div>
</section>

{{-- ===== Alpine helpers ===== --}}
<script>
  document.documentElement.classList.add('js'); // ayuda para x-cloak en CSS si lo usas global
  function kdxAccordions(storageKey) {
    return {
      openMap: {},
      init() {
        const raw = localStorage.getItem(storageKey);
        this.openMap = raw ? JSON.parse(raw) : {};
      },
      isOpen(uid) { return !!this.openMap[uid]; },
      toggle(uid) {
        this.openMap[uid] = !this.openMap[uid];
        localStorage.setItem(storageKey, JSON.stringify(this.openMap));
      },
    }
  }
</script>
