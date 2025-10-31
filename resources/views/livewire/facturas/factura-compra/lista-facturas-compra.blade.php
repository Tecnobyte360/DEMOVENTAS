<div class="p-6 md:p-8">
  <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow">
    <header class="p-4 md:p-6 border-b border-gray-100 dark:border-gray-800">
      <div class="flex flex-wrap items-end gap-3 justify-between">
        <h2 class="text-lg md:text-xl font-semibold text-gray-900 dark:text-gray-100">
          Facturas de compra
        </h2>

        {{-- Filtros rápidos --}}
        <div class="flex flex-wrap items-end gap-2">
          <input type="text" wire:model.live.debounce.400ms="search"
                 placeholder="Buscar (número, proveedor, notas)…"
                 class="h-10 w-64 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white" />

          <select wire:model.live="proveedor_id"
                  class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
            <option value="">Todos los proveedores</option>
            @foreach($proveedores as $p)
              <option value="{{ $p->id }}">{{ $p->razon_social }} @if($p->nit) ({{ $p->nit }}) @endif</option>
            @endforeach
          </select>

          <select wire:model.live="serie_id"
                  class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
            <option value="">Todas las series</option>
            @foreach($series as $s)
              <option value="{{ $s->id }}">{{ $s->nombre }} @if($s->prefijo) ({{ $s->prefijo }}) @endif</option>
            @endforeach
          </select>

          <select wire:model.live="estado"
                  class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
            <option value="">Todos los estados</option>
            <option value="borrador">Borrador</option>
            <option value="emitida">Emitida</option>
            <option value="cerrado">Cerrada</option>
            <option value="anulada">Anulada</option>
          </select>

          <input type="date" wire:model.live="desde"
                 class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white" />
          <input type="date" wire:model.live="hasta"
                 class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white" />

          <select wire:model.live="perPage"
                  class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
            <option value="10">10 / pág</option>
            <option value="25">25 / pág</option>
            <option value="50">50 / pág</option>
          </select>
        </div>
      </div>
    </header>

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
          <tr>
            <th class="px-4 py-3 text-left">
              <button wire:click="sortBy('f.fecha')" class="font-semibold">Fecha</button>
            </th>
            <th class="px-4 py-3 text-left">Proveedor</th>
            <th class="px-4 py-3 text-left">Serie</th>
            <th class="px-4 py-3 text-left">
              <button wire:click="sortBy('f.numero')" class="font-semibold">Número</button>
            </th>
            <th class="px-4 py-3 text-left">Estado</th>
            <th class="px-4 py-3 text-right">
              <button wire:click="sortBy('f.total')" class="font-semibold">Total</button>
            </th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
          @forelse($items as $f)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
              <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($f->fecha)->format('Y-m-d') }}</td>
              <td class="px-4 py-3">
                {{ $f->socioNegocio?->razon_social }}
                @if($f->socioNegocio?->nit) <span class="text-xs text-gray-500">({{ $f->socioNegocio->nit }})</span> @endif
              </td>
              <td class="px-4 py-3">{{ $f->serie?->nombre }}</td>
              <td class="px-4 py-3">
                @if($f->numero)
                  {{ $f->prefijo ? $f->prefijo.'-' : '' }}{{ str_pad($f->numero, 6, '0', STR_PAD_LEFT) }}
                @else
                  <span class="text-gray-500">—</span>
                @endif
              </td>
              <td class="px-4 py-3">
                @php
                  $chip = match($f->estado) {
                    'borrador' => 'bg-slate-100 text-slate-700',
                    'emitida'  => 'bg-indigo-100 text-indigo-700',
                    'cerrado'  => 'bg-teal-100 text-teal-700',
                    'anulada'  => 'bg-rose-100 text-rose-700',
                    default    => 'bg-gray-100 text-gray-700',
                  };
                @endphp
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $chip }}">{{ ucfirst($f->estado) }}</span>
              </td>
              <td class="px-4 py-3 text-right font-semibold">
                $ {{ number_format((float)$f->total, 2) }}
              </td>
              <td class="px-4 py-3 text-right">
                <div class="inline-flex gap-2">
                  {{-- Ver/editar: dispara evento para abrir en el formulario si lo tienes en el mismo layout --}}
                  <button wire:click="$dispatch('abrir-factura', { id: {{ $f->id }} })"
                          class="h-9 px-3 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs">
                    Abrir
                  </button>
                  {{-- Si manejas rutas, puedes agregar un link --}}
                  {{-- <a href="{{ route('compras.facturas.edit', $f->id) }}" class="h-9 px-3 rounded-lg bg-indigo-600 text-white text-xs grid place-items-center">Editar</a> --}}
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                No hay resultados con los filtros actuales.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-4 md:p-6 border-t border-gray-100 dark:border-gray-800">
      {{ $items->onEachSide(1)->links() }}
    </div>
  </section>
</div>
