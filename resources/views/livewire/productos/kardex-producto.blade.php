{{-- resources/views/livewire/productos/kardex-producto.blade.php --}}
@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>[x-cloak]{display:none!important}</style>
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Evita conflicto con Livewire: carga Alpine después de livewire:init
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<section class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 mb-8">
  {{-- HEADER --}}
  <div class="flex items-center justify-between mb-5">
    <div>
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <i class="fa-solid fa-layer-group text-violet-600"></i>
        Kardex de Producto
      </h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Consulta y detalla los movimientos de inventario por documento, con costo promedio móvil.
      </p>
    </div>

    <div class="flex items-center gap-2">
      <button wire:click="recargar"
              class="px-4 py-2 rounded-xl text-sm font-semibold bg-violet-600 hover:bg-violet-700 text-white shadow-sm transition">
        <i class="fa-solid fa-rotate me-1"></i> Recargar
      </button>
      <button wire:click="exportarExcel"
              class="px-4 py-2 rounded-xl text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm transition">
        <i class="fa-solid fa-file-excel me-1"></i> Exportar
      </button>
    </div>
  </div>

  {{-- FILTROS --}}
  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
    <div class="col-span-1 md:col-span-2">
      <input wire:model.live="buscarDoc" type="text" placeholder="Buscar doc/ref..."
             class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm
                    focus:ring-violet-500 focus:border-violet-500" />
    </div>

    <div>
      <select wire:model.live="bodega_id"
              class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm
                     focus:ring-violet-500 focus:border-violet-500">
        <option value="">Todas las bodegas</option>
        @foreach($bodegas as $b)
          <option value="{{ $b->id }}">{{ $b->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <input wire:model.live="desde" type="date"
             class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm
                    focus:ring-violet-500 focus:border-violet-500" />
    </div>

    <div class="flex gap-3">
      <input wire:model.live="hasta" type="date"
             class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm
                    focus:ring-violet-500 focus:border-violet-500" />
      <select wire:model.live="perPage"
              class="w-28 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 text-sm
                     focus:ring-violet-500 focus:border-violet-500">
        @foreach([10,25,50,100] as $n)
          <option value="{{ $n }}">{{ $n }}/página</option>
        @endforeach
      </select>
    </div>
  </div>

  {{-- RESUMEN DE SALDOS (opcional mostrar cuando hay producto) --}}
  @if($producto_id)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
    </div>
  @endif

  {{-- TABLA CON ACORDEÓN POR DOCUMENTO --}}
  <div class="overflow-x-auto" x-data="{ open: null }">
    <table class="min-w-[1100px] w-full text-sm bg-white dark:bg-gray-800 rounded-xl overflow-hidden">
      <thead class="bg-violet-600 text-white">
        <tr>
          <th class="px-3 py-2 text-left w-10"></th>
          <th class="px-3 py-2 text-left">Fuente</th>
          <th class="px-3 py-2 text-left">Fecha</th>
          <th class="px-3 py-2 text-left">Bodega</th>
          <th class="px-3 py-2 text-left">Doc/Ref</th>
          <th class="px-3 py-2 text-center">Tipo</th>
          <th class="px-3 py-2 text-right">Entrada</th>
          <th class="px-3 py-2 text-right">Salida</th>
          {{-- columnas “detalladas” se muestran en el panel expandido --}}
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        @if(!$producto_id)
          <tr>
            <td colspan="8" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400 italic">
              Selecciona un producto para ver su Kardex.
            </td>
          </tr>
        @else
          {{-- Fila de saldo inicial (solo primera página) --}}
          @if($filas->currentPage() === 1)
            <tr class="bg-gray-50 dark:bg-gray-700/50">
              <td class="px-3 py-2"></td>
              <td class="px-3 py-2">
                <span class="text-xs px-2 py-1 rounded-full bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200">INICIAL</span>
              </td>
              <td class="px-3 py-2">—</td>
              <td class="px-3 py-2">{{ $bodega_id ? ($bodegas->firstWhere('id', $bodega_id)->nombre ?? '—') : 'Todas' }}</td>
              <td class="px-3 py-2 font-medium">Saldo inicial</td>
              <td class="px-3 py-2 text-center">—</td>
              <td class="px-3 py-2 text-right">—</td>
              <td class="px-3 py-2 text-right">—</td>
            </tr>
          @endif

          @forelse($filas as $r)
            @php $rowKey = $r['row_id'] ?? md5(json_encode([$r['doc'] ?? '', $r['fecha'] ?? '', $r['tipo'] ?? ''])); @endphp

            {{-- FILA RESUMEN (clic para expandir) --}}
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer
                       {{ $r['fuente'] === 'costos' ? 'border-l-4 border-l-blue-500' : 'border-l-4 border-l-green-500' }}"
                @click="open === '{{ $rowKey }}' ? open = null : open = '{{ $rowKey }}'">
              <td class="px-3 py-2 text-center">
                <i class="fa-solid"
                   :class="open === '{{ $rowKey }}' ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
              </td>

              <td class="px-3 py-2">
                @if($r['fuente'] === 'kardex')
                  <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 font-medium">KARDEX</span>
                @else
                  <span class="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 font-medium">DOC</span>
                @endif
              </td>

              <td class="px-3 py-2">{{ $r['fecha'] }}</td>
              <td class="px-3 py-2">{{ $r['bodega'] ?? '—' }}</td>
              <td class="px-3 py-2">
                @if(!empty($r['doc_url']))
                  <a href="{{ $r['doc_url'] }}" target="_blank" class="text-violet-700 dark:text-violet-300 hover:underline">
                    {{ $r['doc'] }}
                  </a>
                @else
                  {{ $r['doc'] }}
                @endif
              </td>
              <td class="px-3 py-2 text-center">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                  @class([
                    'bg-green-100 text-green-700' => $r['tipo']==='ENTRADA',
                    'bg-red-100 text-red-700'     => $r['tipo']==='SALIDA',
                    'bg-amber-100 text-amber-700' => $r['tipo']!=='ENTRADA' && $r['tipo']!=='SALIDA',
                  ])">
                  {{ $r['tipo'] ?? 'RESUMEN' }}
                </span>
              </td>
              <td class="px-3 py-2 text-right">{{ $r['entrada'] ? number_format($r['entrada'],2) : '—' }}</td>
              <td class="px-3 py-2 text-right">{{ $r['salida']  ? number_format($r['salida'],2)  : '—' }}</td>
            </tr>

            {{-- PANEL DETALLE (colspan total) --}}
            <tr x-show="open === '{{ $rowKey }}'" x-cloak>
              <td colspan="8" class="px-3 pb-4 pt-0">
                <div class="mt-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-4">
                  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
                    <div>
                      <p class="text-gray-500 dark:text-gray-400">Costo unit.</p>
                      <p class="font-semibold text-gray-900 dark:text-white">
                        {{ $r['costo_unit'] ? number_format($r['costo_unit'],2) : '—' }}
                      </p>
                    </div>
                    <div>
                      <p class="text-gray-500 dark:text-gray-400">Saldo cant.</p>
                      <p class="font-semibold text-gray-900 dark:text-white">
                        {{ $r['saldo_cant'] ? number_format($r['saldo_cant'],2) : '—' }}
                      </p>
                    </div>
                    <div>
                      <p class="text-gray-500 dark:text-gray-400">Saldo valor</p>
                      <p class="font-semibold text-gray-900 dark:text-white">
                        {{ abs($r['saldo_val'] ?? 0) > 0.01 ? number_format($r['saldo_val'],2) : '—' }}
                      </p>
                    </div>
                    <div>
                      <p class="text-gray-500 dark:text-gray-400">CPU saldo</p>
                      <p class="font-semibold text-gray-900 dark:text-white">
                        {{ $r['saldo_cpu'] ? number_format($r['saldo_cpu'],2) : '—' }}
                      </p>
                    </div>

                    @if(in_array($fuenteDatos, ['costos','ambas']) && !empty($r['costo_historico']))
                      <div>
                        <p class="text-gray-500 dark:text-gray-400">Método</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                          {{ $r['costo_historico']['metodo_costeo'] ?? '—' }}
                        </p>
                      </div>
                      <div>
                        <p class="text-gray-500 dark:text-gray-400">CPU ant. → nuevo</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                          {{ ($r['costo_historico']['costo_prom_anterior'] ?? null) ? number_format((float)$r['costo_historico']['costo_prom_anterior'],2) : '—' }}
                          →
                          {{ ($r['costo_historico']['costo_prom_nuevo'] ?? null) ? number_format((float)$r['costo_historico']['costo_prom_nuevo'],2) : '—' }}
                        </p>
                      </div>
                      <div>
                        <p class="text-gray-500 dark:text-gray-400">Último ant. → nuevo</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                          {{ ($r['costo_historico']['ultimo_costo_anterior'] ?? null) ? number_format((float)$r['costo_historico']['ultimo_costo_anterior'],2) : '—' }}
                          →
                          {{ ($r['costo_historico']['ultimo_costo_nuevo'] ?? null) ? number_format((float)$r['costo_historico']['ultimo_costo_nuevo'],2) : '—' }}
                        </p>
                      </div>
                      <div class="md:col-span-2 lg:col-span-2">
                        <p class="text-gray-500 dark:text-gray-400">Evento</p>
                        <p class="font-semibold text-gray-900 dark:text-white">
                          {{ $r['costo_historico']['tipo_evento'] ?? '—' }}
                        </p>
                      </div>
                    @endif
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400 italic">
                Sin movimientos en el rango.
              </td>
            </tr>
          @endforelse
        @endif
      </tbody>
    </table>
  </div>

  {{-- PAGINACIÓN --}}
  <div class="mt-4">
    {{ $filas->links() }}
  </div>

  {{-- LEYENDA --}}
  @if($producto_id)
    <div class="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
      <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-200 mb-2">📊 Leyenda</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-gray-600 dark:text-gray-300">
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 text-xs font-medium">KARDEX</span>
          Movimientos desde <code>kardex_movimientos</code> (borde verde).
        </div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-200 text-xs font-medium">DOC</span>
          Movimientos documentales / costos (borde azul).
        </div>
      </div>
    </div>
  @endif
</section>
