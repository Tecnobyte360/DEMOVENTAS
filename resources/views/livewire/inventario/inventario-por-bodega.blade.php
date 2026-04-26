@once
  @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  @endpush
@endonce

<div class="p-6 md:p-8 space-y-6">

  {{-- HEADER --}}
  <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 text-white shadow-2xl">
    <div class="px-6 md:px-8 py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center gap-3">
          <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/15 backdrop-blur">
            <i class="fas fa-warehouse text-2xl"></i>
          </span>
          Inventario por Bodega
        </h1>
        <p class="text-sm text-white/80">Stock disponible y valorización (stock × costo promedio) en cada bodega.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
          <i class="fa-solid fa-warehouse"></i> Bodegas: {{ $totales['bodegas'] }}
        </span>
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
          <i class="fa-solid fa-boxes-stacked"></i> Unidades: {{ number_format((float)$totales['unidades'], 2, ',', '.') }}
        </span>
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/30 font-bold text-xs md:text-sm">
          <i class="fa-solid fa-sack-dollar"></i> Valor total: $ {{ number_format((float)$totales['valor'], 0, ',', '.') }}
        </span>
      </div>
    </div>
  </section>

  {{-- TARJETAS POR BODEGA --}}
  <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    @forelse ($resumenPorBodega as $r)
      @php
        $isSelected = $bodegaId == $r->id;
      @endphp
      <button
        type="button"
        wire:click="$set('bodegaId', {{ $isSelected ? 'null' : $r->id }})"
        class="text-left rounded-2xl border-2 p-5 bg-white dark:bg-gray-900 shadow-sm hover:shadow-lg transition
               {{ $isSelected ? 'border-emerald-500 ring-4 ring-emerald-200' : 'border-gray-200 dark:border-gray-800' }}"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-[11px] uppercase tracking-wider text-gray-500">Bodega</div>
            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $r->nombre }}</div>
            <div class="text-xs text-gray-500"><i class="fa fa-location-dot mr-1"></i>{{ $r->ubicacion ?: '—' }}</div>
          </div>
          <span class="text-[10px] px-2 py-0.5 rounded-full {{ $r->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700' }}">
            {{ $r->activo ? 'Activa' : 'Inactiva' }}
          </span>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-3">
          <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3">
            <div class="text-[10px] uppercase text-gray-500">Productos</div>
            <div class="text-base font-bold">{{ number_format((int)$r->productos, 0, ',', '.') }}</div>
          </div>
          <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-3">
            <div class="text-[10px] uppercase text-gray-500">Unidades</div>
            <div class="text-base font-bold">{{ number_format((float)$r->unidades, 2, ',', '.') }}</div>
          </div>
          <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/30 p-3">
            <div class="text-[10px] uppercase text-emerald-700 dark:text-emerald-300">Valor</div>
            <div class="text-base font-bold text-emerald-700 dark:text-emerald-300">
              $ {{ number_format((float)$r->valor, 0, ',', '.') }}
            </div>
          </div>
        </div>
      </button>
    @empty
      <div class="col-span-full rounded-xl border border-dashed p-6 text-center text-gray-500">
        No hay bodegas con inventario.
      </div>
    @endforelse
  </section>

  {{-- FILTROS + DETALLE --}}
  <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/90 dark:bg-gray-900/80 shadow-2xl overflow-hidden">

    <div class="p-6 border-b border-gray-100 dark:border-gray-800 grid grid-cols-1 md:grid-cols-12 gap-4">
      <div class="md:col-span-5">
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Buscar producto</label>
        <input type="text" wire:model.live.debounce.350ms="q"
               placeholder="Nombre, descripción o ID…"
               class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-emerald-300/60" />
      </div>

      <div class="md:col-span-3">
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Bodega</label>
        <select wire:model.live="bodegaId"
                class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-emerald-300/60">
          <option value="">— Todas —</option>
          @foreach ($bodegas as $b)
            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Stock</label>
        <select wire:model.live="stockFiltro"
                class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-emerald-300/60">
          <option value="todos">Todos</option>
          <option value="con">Con stock</option>
          <option value="sin">Sin stock</option>
          <option value="bajo">Bajo mínimo</option>
        </select>
      </div>

      <div class="md:col-span-2 flex items-end gap-2">
        <label class="inline-flex items-center gap-2 cursor-pointer text-sm">
          <input type="checkbox" wire:model.live="soloActivas" class="rounded">
          Solo activas
        </label>
        <button type="button" wire:click="limpiar"
                class="ml-auto h-11 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
          Limpiar
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
          <tr>
            <th class="p-3 text-left">Bodega</th>
            <th class="p-3 text-left">Producto</th>
            <th class="p-3 text-right">Stock</th>
            <th class="p-3 text-right">Mín / Máx</th>
            <th class="p-3 text-right">Costo prom.</th>
            <th class="p-3 text-right">Último costo</th>
            <th class="p-3 text-right">Valor ($)</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          @forelse ($detalle as $d)
            @php
              $bajoMin = $d->stock_minimo > 0 && $d->stock <= $d->stock_minimo;
            @endphp
            <tr class="hover:bg-emerald-50/40 dark:hover:bg-gray-800 transition">
              <td class="p-3">
                <span class="inline-flex items-center gap-1 text-[11px] px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-700">
                  <i class="fa fa-warehouse"></i> {{ $d->bodega }}
                </span>
              </td>
              <td class="p-3">
                <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $d->producto }}</div>
                <div class="text-[11px] text-gray-500">ID #{{ $d->producto_id }}</div>
              </td>
              <td class="p-3 text-right font-bold {{ $bajoMin ? 'text-rose-600' : '' }}">
                {{ number_format((float)$d->stock, 2, ',', '.') }}
                @if ($bajoMin)
                  <i class="fa-solid fa-triangle-exclamation ml-1" title="Bajo mínimo"></i>
                @endif
              </td>
              <td class="p-3 text-right text-xs text-gray-500">
                {{ number_format((float)$d->stock_minimo, 2, ',', '.') }}
                /
                {{ number_format((float)$d->stock_maximo, 2, ',', '.') }}
              </td>
              <td class="p-3 text-right">$ {{ number_format((float)$d->costo_promedio, 2, ',', '.') }}</td>
              <td class="p-3 text-right text-gray-500">$ {{ number_format((float)$d->ultimo_costo, 2, ',', '.') }}</td>
              <td class="p-3 text-right font-bold text-emerald-700 dark:text-emerald-300">
                $ {{ number_format((float)$d->valor, 0, ',', '.') }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-6 text-center text-gray-500">Sin resultados con los filtros actuales.</td>
            </tr>
          @endforelse
        </tbody>
        @if ($detalle->total() > 0)
          <tfoot class="bg-gray-50 dark:bg-gray-800/60">
            <tr>
              <td colspan="2" class="p-3 text-right font-semibold">Página actual:</td>
              <td class="p-3 text-right font-bold">
                {{ number_format((float)$detalle->getCollection()->sum('stock'), 2, ',', '.') }}
              </td>
              <td></td>
              <td></td>
              <td class="p-3 text-right font-semibold">Subtotal:</td>
              <td class="p-3 text-right font-extrabold text-emerald-700 dark:text-emerald-300">
                $ {{ number_format((float)$detalle->getCollection()->sum('valor'), 0, ',', '.') }}
              </td>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>

    <div class="p-4 border-t border-gray-100 dark:border-gray-800">
      {{ $detalle->links() }}
    </div>
  </section>
</div>
