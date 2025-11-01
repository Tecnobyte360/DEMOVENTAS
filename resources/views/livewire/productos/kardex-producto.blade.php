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

            @forelse($filas as $r)
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 
                {{ $r['fuente'] === 'costos' ? 'border-l-4 border-l-blue-500' : 'border-l-4 border-l-green-500' }}">
                
                {{-- Columna de fuente --}}
                <td class="px-3 py-2">
                  @if($r['fuente'] === 'kardex')
                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 font-medium">
                      KARDEX
                    </span>
                  @else
                    <span class="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-medium">
                      COSTOS
                    </span>
                  @endif
                </td>

                <td class="px-3 py-2">{{ $r['fecha'] }}</td>
                <td class="px-3 py-2">{{ $r['bodega'] ?? '—' }}</td>
                <td class="px-3 py-2">{{ $r['doc'] }}</td>
                <td class="px-3 py-2 text-center">
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                    @class([
                      'bg-green-100 text-green-700' => $r['tipo']==='ENTRADA',
                      'bg-red-100 text-red-700'     => $r['tipo']==='SALIDA',
                      'bg-amber-100 text-amber-700' => $r['tipo']!=='ENTRADA' && $r['tipo']!=='SALIDA',
                    ])">
                    {{ $r['tipo'] }}
                  </span>
                </td>
                <td class="px-3 py-2 text-right">{{ $r['entrada'] !== null && $r['entrada'] > 0 ? number_format($r['entrada'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $r['salida'] !== null && $r['salida'] > 0 ? number_format($r['salida'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $r['costo_unit'] !== null && $r['costo_unit'] > 0 ? number_format($r['costo_unit'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $r['saldo_cant'] > 0 ? number_format($r['saldo_cant'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ abs($r['saldo_val']) > 0.01 ? number_format($r['saldo_val'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $r['saldo_cpu'] !== null && $r['saldo_cpu'] > 0 ? number_format($r['saldo_cpu'], 2) : '—' }}</td>
                
                @if(in_array($fuenteDatos, ['costos', 'ambas']))
                  @if($r['costo_historico'])
                    <td class="px-3 py-2 text-center bg-blue-50 dark:bg-gray-800">
                      <span class="text-xs px-2 py-1 rounded-full bg-blue-200 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                        {{ $r['costo_historico']['metodo_costeo'] ?? '—' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-right bg-blue-50 dark:bg-gray-800">
                      {{ $r['costo_historico']['costo_prom_anterior'] !== null && (float)$r['costo_historico']['costo_prom_anterior'] > 0 ? number_format((float)$r['costo_historico']['costo_prom_anterior'], 2) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right bg-blue-50 dark:bg-gray-800 font-semibold">
                      {{ $r['costo_historico']['costo_prom_nuevo'] !== null && (float)$r['costo_historico']['costo_prom_nuevo'] > 0 ? number_format((float)$r['costo_historico']['costo_prom_nuevo'], 2) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right bg-blue-50 dark:bg-gray-800">
                      {{ $r['costo_historico']['ultimo_costo_anterior'] !== null && (float)$r['costo_historico']['ultimo_costo_anterior'] > 0 ? number_format((float)$r['costo_historico']['ultimo_costo_anterior'], 2) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right bg-blue-50 dark:bg-gray-800 font-semibold">
                      {{ $r['costo_historico']['ultimo_costo_nuevo'] !== null && (float)$r['costo_historico']['ultimo_costo_nuevo'] > 0 ? number_format((float)$r['costo_historico']['ultimo_costo_nuevo'], 2) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center bg-blue-50 dark:bg-gray-800">
                      <span class="text-xs text-gray-600 dark:text-gray-300">{{ $r['costo_historico']['tipo_evento'] ?? '—' }}</span>
                    </td>
                  @else
                    <td colspan="6" class="px-3 py-2 text-center text-gray-400 italic bg-gray-50 dark:bg-gray-800">
                      —
                    </td>
                  @endif
                @endif
              </tr>
            @empty
              <tr>
                <td colspan="{{ in_array($fuenteDatos, ['costos', 'ambas']) ? 17 : 11 }}" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400 italic">
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
        @if(in_array($fuenteDatos, ['costos', 'ambas']))
          <div class="md:col-span-2 pt-2 border-t border-gray-200 dark:border-gray-700">
            <p class="text-gray-700 dark:text-gray-300"><strong>Columnas de costos históricos:</strong> Se muestran solo para movimientos de la tabla producto_costo_movimientos, donde se registran los cambios en costos promedio y último costo.</p>
          </div>
        @endif
      </div>
    </section>
  @endif
</div>