<div class="p-6 space-y-6 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">

  <header class="flex flex-col md:flex-row md:items-end gap-4 justify-between">
    <div>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kardex de Producto</h2>
      <p class="text-sm text-gray-600 dark:text-gray-300">Consulta de movimientos por producto, bodega y rango de fechas, con costo promedio móvil.</p>
    </div>
  </header>

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
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($saldoInicialCant, 6) }}</p>
      </div>
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-300">Saldo inicial (valor)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format($saldoInicialVal, 2) }}</p>
      </div>
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-300">Saldo final (cant)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ $saldoFinalCant > 0 ? number_format($saldoFinalCant, 6) : '—' }}
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
      <table class="min-w-[1200px] w-full text-sm bg-white dark:bg-gray-800 rounded-xl overflow-hidden">
        <thead class="bg-violet-600 text-white">
          <tr>
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
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @if(!$producto_id)
            <tr><td colspan="10" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400 italic">Selecciona un producto para ver su Kardex.</td></tr>
          @else
            {{-- Fila referencial de saldo inicial (solo en primera página) --}}
            @if($filas->currentPage() === 1)
              <tr class="bg-gray-50 dark:bg-gray-700/50">
                <td class="px-3 py-2 italic text-gray-600 dark:text-gray-200">—</td>
                <td class="px-3 py-2 italic text-gray-600 dark:text-gray-200">{{ $bodega_id ? ($bodegas->firstWhere('id',$bodega_id)->nombre ?? '—') : 'Todas' }}</td>
                <td class="px-3 py-2 font-medium">Saldo inicial</td>
                <td class="px-3 py-2 text-center">—</td>
                <td class="px-3 py-2 text-right">—</td>
                <td class="px-3 py-2 text-right">—</td>
                <td class="px-3 py-2 text-right">
                  @php $cpuIni = $saldoInicialCant>0 ? $saldoInicialVal/max($saldoInicialCant,1e-9) : null; @endphp
                  {{ $cpuIni !== null ? number_format($cpuIni, 6) : '—' }}
                </td>
                <td class="px-3 py-2 text-right">{{ number_format($saldoInicialCant, 6) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($saldoInicialVal, 2) }}</td>
                <td class="px-3 py-2 text-right">{{ $cpuIni !== null ? number_format($cpuIni, 6) : '—' }}</td>
              </tr>
            @endif

            @forelse($filas as $r)
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
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
                <td class="px-3 py-2 text-right">{{ $r['entrada'] !== null ? number_format($r['entrada'], 6) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $r['salida']  !== null ? number_format($r['salida'], 6)  : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ $r['costo_unit'] !== null ? number_format($r['costo_unit'], 6) : '—' }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($r['saldo_cant'], 6) }}</td>
                <td class="px-3 py-2 text-right">{{ number_format($r['saldo_val'], 2) }}</td>
                <td class="px-3 py-2 text-right">{{ $r['saldo_cpu'] !== null ? number_format($r['saldo_cpu'], 6) : '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400 italic">Sin movimientos en el rango.</td>
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
</div>
