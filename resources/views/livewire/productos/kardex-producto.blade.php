<div class="p-6 space-y-6 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">

  {{-- ENCABEZADO --}}
  <header class="flex flex-col md:flex-row md:items-end gap-3 justify-between">
    <div>
      <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kárdex de producto</h2>
      <p class="text-sm text-gray-600 dark:text-gray-300">
        Movimientos por producto y bodega dentro del rango seleccionado. Valoración por <span class="font-medium">costo promedio móvil</span>.
      </p>
    </div>

    {{-- Selector de fuente --}}
    <div class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-xl p-2.5">
      <label class="text-xs font-semibold text-violet-800 dark:text-violet-200 block mb-1">Fuente de movimientos</label>
      <div class="flex flex-wrap gap-3 text-sm">
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input type="radio" wire:model.live="fuenteDatos" value="kardex" class="w-4 h-4 text-violet-600 focus:ring-violet-500">
          <span class="text-gray-800 dark:text-gray-200">Solo Kardex <span class="text-xs text-gray-500">(kardex_movimientos)</span></span>
        </label>
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input type="radio" wire:model.live="fuenteDatos" value="costos" class="w-4 h-4 text-violet-600 focus:ring-violet-500">
          <span class="text-gray-800 dark:text-gray-200">Solo Costos <span class="text-xs text-gray-500">(producto_costo_movimientos)</span></span>
        </label>
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input type="radio" wire:model.live="fuenteDatos" value="ambas" class="w-4 h-4 text-violet-600 focus:ring-violet-500">
          <span class="text-gray-800 dark:text-gray-200">Ambas tablas <span class="text-xs text-gray-500">(combinadas)</span></span>
        </label>
      </div>
    </div>
  </header>

  {{-- FILTROS --}}
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

  {{-- RESUMEN DE SALDOS --}}
  @if($producto_id)
    @php $cpuIni = $saldoInicialCant>0 ? $saldoInicialVal/max($saldoInicialCant,1e-9) : null; @endphp
    <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Saldo inicial (cantidad)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white tabular-nums">{{ number_format($saldoInicialCant, 2) }}</p>
      </div>
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Saldo inicial (valor)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white tabular-nums">${{ number_format($saldoInicialVal, 2) }}</p>
      </div>
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Saldo final (cantidad)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white tabular-nums">
          {{ $saldoFinalCant !== null ? number_format($saldoFinalCant, 2) : '—' }}
        </p>
      </div>
      <div class="rounded-xl p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Saldo final (valor)</p>
        <p class="text-2xl font-semibold text-gray-900 dark:text-white tabular-nums">
          {{ $saldoFinalVal !== null ? '$'.number_format($saldoFinalVal, 2) : '—' }}
        </p>
      </div>
    </section>
  @endif

  {{-- TABLA DE MOVIMIENTOS --}}
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

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
      <table class="min-w-[1280px] w-full text-sm bg-white dark:bg-gray-800">
        <colgroup>
          <col class="w-28"><!-- Fuente -->
          <col class="w-40"><!-- Fecha -->
          <col class="w-56"><!-- Bodega -->
          <col><!-- Doc/Ref -->
          <col class="w-28"><!-- Tipo -->
          <col class="w-28"><!-- Entrada -->
          <col class="w-28"><!-- Salida -->
          <col class="w-32"><!-- Costo unit. -->
          <col class="w-32"><!-- Saldo cant. -->
          <col class="w-36"><!-- Saldo valor -->
          <col class="w-28"><!-- CPU saldo -->
          @if(in_array($fuenteDatos, ['costos', 'ambas']))
            <col class="w-32"><col class="w-32"><col class="w-32"><col class="w-32"><col class="w-32"><col class="w-40">
          @endif
        </colgroup>

        <thead class="bg-violet-600 text-white sticky top-0 z-10">
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
              <td colspan="{{ in_array($fuenteDatos, ['costos', 'ambas']) ? 17 : 11 }}" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400 italic">
                Selecciona un producto para ver su kárdex.
              </td>
            </tr>
          @else
            {{-- Fila de SALDO INICIAL (solo 1ra página) --}}
            @if($filas->currentPage() === 1)
              <tr class="bg-gray-50 dark:bg-gray-700/40">
                <td class="px-3 py-2">
                  <span class="text-xs px-2 py-1 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-100 font-medium">INICIAL</span>
                </td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">—</td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-300">
                  {{ $bodega_id ? ($bodegas->firstWhere('id',$bodega_id)->nombre ?? '—') : 'Todas' }}
                </td>
                <td class="px-3 py-2 font-medium">Saldo inicial</td>
                <td class="px-3 py-2 text-center">—</td>
                <td class="px-3 py-2 text-right">—</td>
                <td class="px-3 py-2 text-right">—</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ $cpuIni !== null ? number_format($cpuIni, 2) : '—' }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($saldoInicialCant, 2) }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($saldoInicialVal, 2) }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ $cpuIni !== null ? number_format($cpuIni, 2) : '—' }}</td>
                @if(in_array($fuenteDatos, ['costos', 'ambas']))
                  <td colspan="6" class="px-3 py-2 text-center text-gray-400 dark:text-gray-500">—</td>
                @endif
              </tr>
            @endif

            {{-- FILAS DE MOVIMIENTO --}}
            @forelse($filas as $r)
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 border-l-4
                         {{ $r['fuente'] === 'costos' ? 'border-l-blue-500' : 'border-l-green-500' }}">

                {{-- Fuente --}}
                <td class="px-3 py-2">
                  @if($r['fuente'] === 'kardex')
                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 font-medium">KARDEX</span>
                  @else
                    <span class="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-medium">COSTOS</span>
                  @endif
                </td>

                <td class="px-3 py-2 whitespace-nowrap">{{ $r['fecha'] ?? '—' }}</td>
                <td class="px-3 py-2">{{ $r['bodega'] ?? '—' }}</td>
                <td class="px-3 py-2">{{ $r['doc'] ?? '—' }}</td>

                {{-- Tipo (badge) --}}
                <td class="px-3 py-2 text-center">
                  <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                    @class([
                      'bg-green-100 text-green-700' => $r['tipo']==='ENTRADA',
                      'bg-red-100 text-red-700'     => $r['tipo']==='SALIDA',
                      'bg-amber-100 text-amber-700' => $r['tipo']!=='ENTRADA' && $r['tipo']!=='SALIDA',
                    ])">
                    {{ $r['tipo'] ?? '—' }}
                  </span>
                </td>

                {{-- Números (monoespaciado) --}}
                <td class="px-3 py-2 text-right tabular-nums">{{ $r['entrada'] && $r['entrada']>0 ? number_format($r['entrada'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ $r['salida']  && $r['salida'] >0 ? number_format($r['salida'],  2) : '—' }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ $r['costo_unit'] !== null && $r['costo_unit'] > 0 ? number_format($r['costo_unit'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ $r['saldo_cant'] !== null ? number_format(max($r['saldo_cant'],0), 2) : '—' }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ $r['saldo_val']  !== null ? number_format($r['saldo_val'], 2) : '—' }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ $r['saldo_cpu']  !== null && $r['saldo_cpu']>0 ? number_format($r['saldo_cpu'],  2) : '—' }}</td>

                {{-- Bloque de COSTOS (si aplica) --}}
                @if(in_array($fuenteDatos, ['costos', 'ambas']))
                  @if(!empty($r['costo_historico']))
                    <td class="px-3 py-2 text-center bg-blue-50 dark:bg-gray-800">
                      <span class="text-xs px-2 py-1 rounded-full bg-blue-200 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                        {{ $r['costo_historico']['metodo_costeo'] ?? '—' }}
                      </span>
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums bg-blue-50 dark:bg-gray-800">
                      {{ isset($r['costo_historico']['costo_prom_anterior']) ? number_format((float)$r['costo_historico']['costo_prom_anterior'], 2) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums bg-blue-50 dark:bg-gray-800 font-semibold">
                      {{ isset($r['costo_historico']['costo_prom_nuevo']) ? number_format((float)$r['costo_historico']['costo_prom_nuevo'], 2) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums bg-blue-50 dark:bg-gray-800">
                      {{ isset($r['costo_historico']['ultimo_costo_anterior']) ? number_format((float)$r['costo_historico']['ultimo_costo_anterior'], 2) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums bg-blue-50 dark:bg-gray-800 font-semibold">
                      {{ isset($r['costo_historico']['ultimo_costo_nuevo']) ? number_format((float)$r['costo_historico']['ultimo_costo_nuevo'], 2) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center bg-blue-50 dark:bg-gray-800">
                      <span class="text-xs text-gray-700 dark:text-gray-300">{{ $r['costo_historico']['tipo_evento'] ?? '—' }}</span>
                    </td>
                  @else
                    <td colspan="6" class="px-3 py-2 text-center text-gray-400 italic bg-blue-50/40 dark:bg-gray-800">—</td>
                  @endif
                @endif
              </tr>
            @empty
              <tr>
                <td colspan="{{ in_array($fuenteDatos, ['costos', 'ambas']) ? 17 : 11 }}" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400 italic">
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

  {{-- LEYENDA --}}
  @if($producto_id)
    <section class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
      <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-200 mb-2">📎 Leyenda rápida</h4>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 text-xs font-medium">KARDEX</span>
          <span class="text-gray-700 dark:text-gray-300">Movimientos de <code class="font-mono">kardex_movimientos</code> (borde verde)</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 text-xs font-medium">COSTOS</span>
          <span class="text-gray-700 dark:text-gray-300">Cambios de costo en <code class="font-mono">producto_costo_movimientos</code> (borde azul)</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-xs font-semibold">SALIDA</span>
          <span class="text-gray-700 dark:text-gray-300">Disminuye stock. El CPU <span class="font-medium">no</span> cambia.</span>
        </div>
      </div>
    </section>
  @endif
</div>
