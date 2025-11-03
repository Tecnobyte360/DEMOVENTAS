<div class="p-6 space-y-6 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">

  {{-- Filtros --}}
  <section class="grid grid-cols-1 md:grid-cols-5 gap-4">
    <div>
      <label class="text-xs text-gray-500">Producto</label>
      <select wire:model="producto_id" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700">
        <option value="">— Selecciona —</option>
        @foreach($productos as $p)
          <option value="{{ $p->id }}">{{ $p->nombre }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-xs text-gray-500">Bodega</label>
      <select wire:model="bodega_id" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700">
        <option value="">(Todas)</option>
        @foreach($bodegas as $b)
          <option value="{{ $b->id }}">{{ $b->nombre }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-xs text-gray-500">Desde</label>
      <input type="date" wire:model="desde" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700" />
    </div>
    <div>
      <label class="text-xs text-gray-500">Hasta</label>
      <input type="date" wire:model="hasta" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700" />
    </div>
    <div>
      <label class="text-xs text-gray-500">Buscar doc/ref</label>
      <input type="text" wire:model.debounce.400ms="buscarDoc" placeholder="Código, nombre doc, ref..." class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700" />
    </div>

    <div class="md:col-span-5 flex items-end gap-3">
      <div>
        <label class="text-xs text-gray-500">Fuente</label>
        <select wire:model="fuenteDatos" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700">
          <option value="ambas">Kardex + Costos</option>
          <option value="kardex">Solo Kardex</option>
          <option value="costos">Solo Costos</option>
        </select>
      </div>
      <div>
        <label class="text-xs text-gray-500">Por página</label>
        <select wire:model="perPage" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700">
          <option>10</option><option>25</option><option>50</option><option>100</option>
        </select>
      </div>
      <div class="ml-auto text-sm">
        <div class="font-semibold text-gray-800 dark:text-gray-100">Saldo inicial:</div>
        <div class="text-gray-600 dark:text-gray-300">
          Cant: {{ number_format($saldoInicialCant, 6) }} — Val: {{ number_format($saldoInicialVal, 2) }}
        </div>
      </div>
    </div>
  </section>

  {{-- Grupos (acordeón) --}}
  @if($filas->total() === 0)
    <div class="p-6 text-center text-gray-500 dark:text-gray-400 border rounded-xl">
      No hay movimientos para los filtros seleccionados.
    </div>
  @else
    <div class="space-y-3">
      @foreach($grupos as $g)
        @php
          $isOpen = $openGroups[$g['uid']] ?? false;
        @endphp

        <article class="border rounded-xl overflow-hidden dark:border-gray-700">
          {{-- Cabecera resumida --}}
          <header class="flex items-center gap-4 px-4 py-3 bg-gray-50 dark:bg-gray-800/50">
            <button wire:click="toggleGrupo('{{ $g['uid'] }}')" class="shrink-0 p-2 rounded-lg border dark:border-gray-700 hover:bg-white dark:hover:bg-gray-700">
              {{ $isOpen ? '−' : '+' }}
            </button>
            <div class="flex-1">
              <div class="font-medium text-gray-900 dark:text-gray-100">{{ $g['doc'] }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ $g['fecha'] }} · {{ $g['bodega'] }}
              </div>
            </div>
            <div class="text-right text-sm">
              <div class="text-green-600 dark:text-green-400">Entradas: {{ number_format($g['entrada_total'], 6) }}</div>
              <div class="text-red-600 dark:text-red-400">Salidas: {{ number_format($g['salida_total'], 6) }}</div>
            </div>
            <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>
            <div class="text-right text-sm">
              <div class="font-semibold text-gray-900 dark:text-gray-100">
                Saldo: {{ number_format($g['saldo_cant'], 6) }}
              </div>
              <div class="text-gray-600 dark:text-gray-300">
                Val: {{ number_format($g['saldo_val'], 2) }}
                @if(!is_null($g['saldo_cpu'])) · CPU: {{ number_format($g['saldo_cpu'], 6) }} @endif
              </div>
            </div>
          </header>

          {{-- Detalle expandible --}}
          @if($isOpen)
          <div class="px-4 py-3 overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead class="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                <tr>
                  <th class="py-2">Fecha</th>
                  <th class="py-2">Tipo</th>
                  <th class="py-2">Entrada</th>
                  <th class="py-2">Salida</th>
                  <th class="py-2">Costo unit.</th>
                  <th class="py-2">Saldo cant.</th>
                  <th class="py-2">Saldo val.</th>
                  <th class="py-2">Fuente</th>
                </tr>
              </thead>
              <tbody class="divide-y dark:divide-gray-700">
                @foreach($g['rows'] as $row)
                  <tr>
                    <td class="py-2">{{ $row['fecha'] }}</td>
                    <td class="py-2">{{ $row['tipo'] }}</td>
                    <td class="py-2 text-green-700 dark:text-green-400">
                      {{ $row['entrada'] ? number_format($row['entrada'], 6) : '—' }}
                    </td>
                    <td class="py-2 text-red-700 dark:text-red-400">
                      {{ $row['salida'] ? number_format($row['salida'], 6) : '—' }}
                    </td>
                    <td class="py-2">{{ number_format($row['costo_unit'], 6) }}</td>
                    <td class="py-2">{{ number_format($row['saldo_cant'], 6) }}</td>
                    <td class="py-2">{{ number_format($row['saldo_val'], 2) }}</td>
                    <td class="py-2">
                      <span class="px-2 py-1 rounded-full text-xs
                        {{ $row['fuente']==='kardex' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
                        {{ strtoupper($row['fuente']) }}
                      </span>
                    </td>
                  </tr>

                  {{-- Bloque de costo histórico (solo fuente costos) --}}
                  @if($row['fuente']==='costos' && $row['costo_historico'])
                  <tr>
                    <td colspan="8" class="py-2">
                      <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/40 p-3 rounded-lg">
                        <div><span class="font-medium">Método:</span> {{ $row['costo_historico']['metodo_costeo'] ?? '—' }}</div>
                        <div><span class="font-medium">CPU ant.:</span> {{ number_format($row['costo_historico']['costo_prom_anterior'] ?? 0, 6) }}</div>
                        <div><span class="font-medium">CPU nuevo:</span> {{ number_format($row['costo_historico']['costo_prom_nuevo'] ?? 0, 6) }}</div>
                        <div><span class="font-medium">Último ant.:</span> {{ number_format($row['costo_historico']['ultimo_costo_anterior'] ?? 0, 6) }}</div>
                        <div><span class="font-medium">Último nuevo:</span> {{ number_format($row['costo_historico']['ultimo_costo_nuevo'] ?? 0, 6) }}</div>
                        <div><span class="font-medium">Valor mov.:</span> {{ number_format($row['costo_historico']['valor_mov'] ?? 0, 2) }}</div>
                        <div class="md:col-span-6"><span class="font-medium">Evento:</span> {{ $row['costo_historico']['tipo_evento'] ?? '—' }}</div>
                      </div>
                    </td>
                  </tr>
                  @endif
                @endforeach
              </tbody>
            </table>
          </div>
          @endif
        </article>
      @endforeach
    </div>

    {{-- Paginación + saldos finales --}}
    <div class="flex items-center justify-between pt-4">
      <div class="text-sm text-gray-600 dark:text-gray-300">
        <span class="font-semibold">Saldo final:</span>
        Cant: {{ number_format($saldoFinalCant, 6) }} — Val: {{ number_format($saldoFinalVal, 2) }}
      </div>
      <div>
        {{ $filas->links() }}
      </div>
    </div>
  @endif
</div>
