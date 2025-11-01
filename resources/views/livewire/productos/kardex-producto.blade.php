<div class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6"
     x-data="{ open: null }">
  <header class="flex justify-between mb-4">
    <div>
      <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <i class="fa-solid fa-layer-group text-violet-600"></i>
        Movimientos de Kardex
      </h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">Agrupado por documento</p>
    </div>
  </header>

  <div class="overflow-x-auto">
    <table class="w-full text-sm border-collapse">
      <thead class="bg-violet-600 text-white">
        <tr>
          <th class="px-3 py-2 w-8"></th>
          <th class="px-3 py-2 text-left">Documento</th>
          <th class="px-3 py-2 text-left">Fecha</th>
          <th class="px-3 py-2 text-left">Bodega</th>
          <th class="px-3 py-2 text-right">Entrada total</th>
          <th class="px-3 py-2 text-right">Salida total</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        @forelse($grupos as $g)
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
              @click="open === '{{ $g['uid'] }}' ? open = null : open = '{{ $g['uid'] }}'">
            <td class="px-3 py-2 text-center">
              <i class="fa-solid"
                 :class="open === '{{ $g['uid'] }}' ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
            </td>
            <td class="px-3 py-2 font-medium text-violet-700 dark:text-violet-300">{{ $g['doc'] }}</td>
            <td class="px-3 py-2">{{ $g['fecha'] }}</td>
            <td class="px-3 py-2">{{ $g['bodega'] }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($g['entrada_total'], 2) }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($g['salida_total'], 2) }}</td>
          </tr>

          {{-- Detalle expandible --}}
          <tr x-show="open === '{{ $g['uid'] }}'" x-cloak>
            <td colspan="6" class="bg-gray-50 dark:bg-gray-800 p-4">
              <table class="w-full text-xs">
                <thead class="text-gray-600 dark:text-gray-300 border-b border-gray-300 dark:border-gray-700">
                  <tr>
                    <th class="text-left py-1">Tipo</th>
                    <th class="text-right py-1">Entrada</th>
                    <th class="text-right py-1">Salida</th>
                    <th class="text-right py-1">Costo unit.</th>
                    <th class="text-right py-1">Saldo cant.</th>
                    <th class="text-right py-1">Saldo val.</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($g['rows'] as $r)
                    <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700/40">
                      <td class="py-1">{{ $r['tipo'] }}</td>
                      <td class="py-1 text-right">{{ $r['entrada'] ? number_format($r['entrada'],2) : '—' }}</td>
                      <td class="py-1 text-right">{{ $r['salida'] ? number_format($r['salida'],2) : '—' }}</td>
                      <td class="py-1 text-right">{{ $r['costo_unit'] ? number_format($r['costo_unit'],2) : '—' }}</td>
                      <td class="py-1 text-right">{{ $r['saldo_cant'] ? number_format($r['saldo_cant'],2) : '—' }}</td>
                      <td class="py-1 text-right">{{ $r['saldo_val'] ? number_format($r['saldo_val'],2) : '—' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400 italic">
              Sin movimientos disponibles.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $filas->links() }}
  </div>
</div>
