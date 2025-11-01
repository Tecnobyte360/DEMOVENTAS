<tbody class="divide-y divide-gray-100 dark:divide-gray-700">
  @if(!$producto_id)
    <tr>
      <td colspan="{{ in_array($fuenteDatos, ['costos', 'ambas']) ? 17 : 11 }}" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400 italic">
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
        @if(in_array($fuenteDatos, ['costos', 'ambas']))
          <td colspan="6" class="px-3 py-2 text-center text-gray-400 italic bg-gray-100 dark:bg-gray-800">—</td>
        @endif
      </tr>
    @endif

    {{-- ======= ACORDEONES POR DOCUMENTO ======= --}}
    @forelse($grupos as $g)
      <tbody x-data="{ open: false }" class="group border-l-4 border-transparent">
        {{-- Cabecera del grupo (click para abrir/cerrar) --}}
        <tr @click="open = !open"
            class="cursor-pointer bg-gray-50/70 dark:bg-gray-800/40 hover:bg-gray-100 dark:hover:bg-gray-700/60">
          <td class="px-3 py-2">
            {{-- Indicador DOC --}}
            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-200">
              DOC
              {{-- Chevron --}}
              <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6.293 7.293a1 1 0 011.414 0L12 11.586l-4.293 4.293a1 1 0 01-1.414-1.414L9.586 12 6.293 8.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
              </svg>
            </span>
          </td>
          <td class="px-3 py-2">{{ $g['fecha'] }}</td>
          <td class="px-3 py-2">{{ $g['bodega'] }}</td>
          <td class="px-3 py-2 font-medium">{{ $g['doc'] }}</td>
          <td class="px-3 py-2 text-center">—</td>
          <td class="px-3 py-2 text-right">
            {{ $g['entrada_total'] > 0 ? number_format($g['entrada_total'], 2) : '—' }}
          </td>
          <td class="px-3 py-2 text-right">
            {{ $g['salida_total'] > 0 ? number_format($g['salida_total'], 2) : '—' }}
          </td>
          {{-- El resto de columnas (alineadas) se dejan en blanco en la cabecera --}}
          <td class="px-3 py-2 text-right">—</td>
          <td class="px-3 py-2 text-right">—</td>
          <td class="px-3 py-2 text-right">—</td>
          <td class="px-3 py-2 text-right">—</td>
          @if(in_array($fuenteDatos, ['costos', 'ambas']))
            <td class="px-3 py-2 text-center bg-violet-700 text-white">—</td>
            <td class="px-3 py-2 text-right bg-violet-700 text-white">—</td>
            <td class="px-3 py-2 text-right bg-violet-700 text-white">—</td>
            <td class="px-3 py-2 text-right bg-violet-700 text-white">—</td>
            <td class="px-3 py-2 text-right bg-violet-700 text-white">—</td>
            <td class="px-3 py-2 text-center bg-violet-700 text-white">—</td>
          @endif
        </tr>

        {{-- Detalle del grupo (filas reales), aparece al abrir --}}
        @foreach($g['rows'] as $r)
          <tr x-show="open" x-cloak
              class="transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50
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

            @if(in_array($fuenteDatos, ['costos', 'ambas']))
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
      </tbody>
    @empty
      <tr>
        <td colspan="{{ in_array($fuenteDatos, ['costos', 'ambas']) ? 17 : 11 }}" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400 italic">
          Sin movimientos en el rango.
        </td>
      </tr>
    @endforelse
  @endif
</tbody>
