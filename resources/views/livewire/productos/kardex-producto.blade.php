<div class="p-6 space-y-6 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">
  {{-- Filtros --}}
  <section class="grid grid-cols-1 md:grid-cols-5 gap-4">
    <div>
      <label class="text-xs text-gray-500">Producto</label>
      <select wire:model.live="producto_id" class="w-full mt-1 rounded-xl border-gray-300 dark:bg-gray-800">
        <option value="">— Selecciona —</option>
        @foreach($productos as $p)
          <option value="{{ $p->id }}">{{ $p->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-xs text-gray-500">Bodega</label>
      <select wire:model.live="bodega_id" class="w-full mt-1 rounded-xl border-gray-300 dark:bg-gray-800">
        <option value="">Todas</option>
        @foreach($bodegas as $b)
          <option value="{{ $b->id }}">{{ $b->nombre }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-xs text-gray-500">Desde</label>
      <input type="date" wire:model.live="desde" class="w-full mt-1 rounded-xl border-gray-300 dark:bg-gray-800" />
    </div>

    <div>
      <label class="text-xs text-gray-500">Hasta</label>
      <input type="date" wire:model.live="hasta" class="w-full mt-1 rounded-xl border-gray-300 dark:bg-gray-800" />
    </div>

    <div>
      <label class="text-xs text-gray-500">Buscar doc/ref</label>
      <input type="text" placeholder="FAC, ENMER, #id, ref..."
             wire:model.live.debounce.400ms="buscarDoc"
             class="w-full mt-1 rounded-xl border-gray-300 dark:bg-gray-800" />
    </div>
  </section>

  {{-- Tabla simple SOLO de producto_costo_movimientos --}}
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800/60">
        <tr class="text-gray-600 dark:text-gray-300">
          <th class="px-3 py-2 text-left">Fecha</th>
          <th class="px-3 py-2 text-left">Bodega</th>
          <th class="px-3 py-2 text-left">Documento</th>
          <th class="px-3 py-2 text-right">Entrada</th>
          <th class="px-3 py-2 text-right">Salida</th>
          <th class="px-3 py-2 text-right">Costo mov</th>
          <th class="px-3 py-2 text-right">Costo prom</th>
          <th class="px-3 py-2 text-right">Último costo</th>
          <th class="px-3 py-2 text-left">Método</th>
          <th class="px-3 py-2 text-left">Evento</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-900 dark:text-gray-100">
        @forelse($items as $r)
          <tr>
            <td class="px-3 py-2">{{ $r['fecha'] }}</td>
            <td class="px-3 py-2">{{ $r['bodega'] }}</td>
            <td class="px-3 py-2">{{ $r['doc'] }}</td>
            <td class="px-3 py-2 text-right">{{ $r['entrada'] ? number_format($r['entrada'], 2, ',', '.') : '' }}</td>
            <td class="px-3 py-2 text-right">{{ $r['salida'] ? number_format($r['salida'], 2, ',', '.') : '' }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($r['costo_unit_mov'], 2, ',', '.') }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($r['costo_prom_nuevo'], 2, ',', '.') }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($r['ultimo_costo_nuevo'], 2, ',', '.') }}</td>
            <td class="px-3 py-2">{{ $r['metodo_costeo'] }}</td>
            <td class="px-3 py-2">{{ $r['tipo_evento'] }}</td>
          </tr>
        @empty
          <tr><td colspan="10" class="px-3 py-6 text-center text-gray-500">Sin movimientos</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginación + perPage --}}
  <div class="flex items-center justify-between">
    <div class="text-xs text-gray-500">
      <label>Filas por página:</label>
      <select wire:model.live="perPage" class="ml-2 rounded-lg border-gray-300 dark:bg-gray-800">
        <option>10</option>
        <option selected>25</option>
        <option>50</option>
        <option>100</option>
      </select>
    </div>
    <div>
      {{ $filas->onEachSide(1)->links() }}
    </div>
  </div>
</div>