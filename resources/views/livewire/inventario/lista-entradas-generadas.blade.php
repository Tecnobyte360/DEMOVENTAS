<section class="p-6 md:p-8 border-t border-gray-100 dark:border-gray-800 space-y-4">
  <header class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
    <div>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Entradas registradas</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400">Filtra por fecha y gestiona emisión o reversión.</p>
    </div>

    <div class="flex flex-wrap items-end gap-3">
      <div>
        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Desde</label>
        <input type="date" wire:model.live="filtro_desde"
               class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Hasta</label>
        <input type="date" wire:model.live="filtro_hasta"
               class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
      </div>
      <div class="flex items-center gap-2">
        <button type="button" wire:click="buscar"
                class="h-10 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white">Buscar</button>
        <button type="button" wire:click="limpiarFiltros"
                class="h-10 px-4 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">Limpiar</button>
      </div>
    </div>
  </header>

  <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
        <tr>
          <th class="px-4 py-3 text-left">Fecha</th>
          <th class="px-4 py-3 text-left">Proveedor</th>
          <th class="px-4 py-3 text-left">Serie/Número</th>
          <th class="px-4 py-3 text-left">Estado</th>
          <th class="px-4 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
        @forelse($entradasMercancia as $e)
          <tr>
            <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($e->fecha_contabilizacion)->format('Y-m-d') }}</td>
            <td class="px-4 py-3">{{ $e->socioNegocio->razon_social ?? '—' }}</td>
            <td class="px-4 py-3">
              @if($e->numero)
                <span class="inline-flex items-center gap-2 px-2 py-1 rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800">
                  {{ $e->prefijo ? $e->prefijo.'-' : '' }}{{ str_pad($e->numero,  (int)($series->firstWhere('id',$e->serie_id)->longitud ?? 6), '0', STR_PAD_LEFT) }}
                </span>
              @else
                <span class="text-gray-500">—</span>
              @endif
            </td>
            <td class="px-4 py-3">
              @if($e->estado === 'emitida')
                <span class="inline-flex items-center px-2 py-1 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800">
                  <i class="fa-solid fa-check mr-1"></i> Emitida
                </span>
              @else
                <span class="inline-flex items-center px-2 py-1 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200 border border-amber-200 dark:border-amber-800">
                  <i class="fa-solid fa-pen-to-square mr-1"></i> Borrador
                </span>
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <div class="inline-flex items-center gap-2">
                <button type="button"
                        wire:click="toggleDetalleFila({{ $e->id }})"
                        class="h-9 px-3 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                  <i class="fa-solid fa-eye mr-1"></i> Detalle
                </button>

                @if($e->estado !== 'emitida')
                  <button type="button"
                          wire:click="emitirEntrada({{ $e->id }})"
                          class="h-9 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Emitir
                  </button>
                @else
                  <button type="button"
                          wire:click="revertirEntrada({{ $e->id }})"
                          class="h-9 px-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Revertir
                  </button>
                @endif
              </div>
            </td>
          </tr>

          {{-- Fila expandible (detalle) --}}
          @if($filaAbiertaId === $e->id)
            <tr class="bg-gray-50/60 dark:bg-gray-800/40">
              <td colspan="5" class="px-4 py-4">
                @php
                  $det = $detallesPorEntrada[$e->id] ?? collect();
                @endphp
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
                  <table class="min-w-full text-xs md:text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                      <tr>
                        <th class="px-3 py-2 text-left">Producto</th>
                        <th class="px-3 py-2 text-left">Bodega</th>
                        <th class="px-3 py-2 text-left">Descripción</th>
                        <th class="px-3 py-2 text-right">Cantidad</th>
                        <th class="px-3 py-2 text-right">Costo</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                      @forelse($det as $d)
                        <tr>
                          <td class="px-3 py-2">{{ $d->producto->nombre ?? '—' }}</td>
                          <td class="px-3 py-2">{{ $d->bodega->nombre ?? '—' }}</td>
                          <td class="px-3 py-2">{{ $d->descripcion ?? ($d->producto->descripcion ?? '') }}</td>
                          <td class="px-3 py-2 text-right">{{ number_format($d->cantidad, 2) }}</td>
                          <td class="px-3 py-2 text-right">{{ number_format($d->precio_unitario, 2) }}</td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="5" class="px-3 py-4 text-center text-gray-500">Sin detalle</td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>
          @endif
        @empty
          <tr>
            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
              No hay entradas registradas.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-4">
    {{ $entradasMercancia->links() }}
  </div>
</section>
