<div class="space-y-4">
  {{-- Filtros --}}
  <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60 backdrop-blur p-3 md:p-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="flex items-center gap-2">
        <input type="text" wire:model.live.debounce.500ms="search"
               class="px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm"
               placeholder="Buscar #, prefijo, proveedor, NIT, estado..." />

        <select wire:model.live="estado"
                class="px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm">
          <option value="">Todas</option>
          <option value="borrador">Borrador</option>
          <option value="aprobada">Aprobada</option>
          <option value="anulada">Anulada</option>
        </select>

        <input type="date" wire:model.live="desde"
               class="px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm" />
        <input type="date" wire:model.live="hasta"
               class="px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm" />
      </div>

      <div class="flex items-center gap-2">
        <label class="text-sm text-gray-600 dark:text-gray-300">Por página</label>
        <select wire:model.live="perPage"
                class="px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm">
          <option>10</option><option>25</option><option>50</option><option>100</option>
        </select>
      </div>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60 backdrop-blur">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 dark:bg-gray-800/60">
        <tr class="text-left">
          <th class="px-4 py-3">#
            <button wire:click="sortBy('numero')" class="text-xs underline">ordenar</button>
          </th>
          <th class="px-4 py-3">Fecha
            <button wire:click="sortBy('fecha')" class="text-xs underline">ordenar</button>
          </th>
          <th class="px-4 py-3">Proveedor</th>
          <th class="px-4 py-3">Estado
            <button wire:click="sortBy('estado')" class="text-xs underline">ordenar</button>
          </th>
          <th class="px-4 py-3">Total
            <button wire:click="sortBy('total')" class="text-xs underline">ordenar</button>
          </th>
          <th class="px-4 py-3">Serie</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($items as $f)
          <tr>
            <td class="px-4 py-2">{{ $f->prefijo }}{{ $f->numero }}</td>
            <td class="px-4 py-2">{{ \Illuminate\Support\Carbon::parse($f->fecha)->format('Y-m-d') }}</td>
            <td class="px-4 py-2">{{ optional($f->socioNegocio)->razon_social ?? '—' }}</td>
            <td class="px-4 py-2">
              <span class="px-2 py-1 rounded text-xs
                @class([
                  'bg-yellow-100 text-yellow-700' => $f->estado === 'borrador',
                  'bg-green-100 text-green-700'   => $f->estado === 'aprobada',
                  'bg-red-100 text-red-700'       => $f->estado === 'anulada',
                ])">
                {{ ucfirst($f->estado) }}
              </span>
            </td>
            <td class="px-4 py-2 text-right">{{ number_format($f->total, 0, ',', '.') }}</td>
            <td class="px-4 py-2">{{ optional($f->serie)->nombre ?? '—' }}</td>
            <td class="px-4 py-2 text-right">
              {{-- acciones: ver/editar/imprimir --}}
              <a href="{{ route('facturas.compra.show', $f->id) }}" class="underline">Ver</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">No hay facturas con los filtros aplicados.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginación --}}
  <div>
    {{ $items->onEachSide(1)->links() }}
  </div>
</div>
