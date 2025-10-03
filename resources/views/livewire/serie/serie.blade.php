@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

<div class="p-10 bg-gradient-to-br from-violet-200 via-white to-purple-100 dark:from-gray-900 dark:via-gray-800 dark:to-black rounded-3xl shadow-2xl space-y-12">

 

  {{-- ============= FILTROS / ACCIONES ============= --}}
 <section class="backdrop-blur bg-white/80 dark:bg-gray-900/80 rounded-3xl shadow-2xl border border-gray-200/60 dark:border-gray-800 p-6 md:p-8">
  <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
    {{-- Título --}}
    <div>
      <h3 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Series</h3>
      <p class="text-sm md:text-base text-gray-600 dark:text-gray-300">Prefijos, rangos y numeración por documento.</p>
    </div>

    {{-- Filtros --}}
    <div class="w-full lg:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
      {{-- Buscador grande con icono --}}
      <div class="relative flex-1 min-w-[240px]">
        <i class="fa fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input
          type="text"
          wire:model.live="search"
          placeholder="Buscar (nombre, prefijo, resolución)…"
          class="w-full h-12 md:h-14 pl-11 pr-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 text-base md:text-lg
                 bg-white dark:bg-gray-800 dark:text-white shadow-sm focus:outline-none focus:ring-4 focus:ring-indigo-300/50"
        />
      </div>

      {{-- Per Page grande --}}
      <div class="relative">
        <i class="fa fa-list-ol absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
        <select
          wire:model.live="perPage"
          class="appearance-none w-full h-12 md:h-14 pl-11 pr-12 rounded-2xl border-2 border-gray-200 dark:border-gray-700
                 text-base md:text-lg bg-white dark:bg-gray-800 dark:text-white shadow-sm focus:outline-none focus:ring-4 focus:ring-indigo-300/50"
        >
          <option value="10">10 por página</option>
          <option value="20">20 por página</option>
          <option value="50">50 por página</option>
        </select>
        <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">
          <i class="fa fa-chevron-down"></i>
        </span>
      </div>

      {{-- Botón prominente --}}
      <button
        wire:click="create"
        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600
               px-5 md:px-7 h-12 md:h-14 text-base md:text-lg text-white font-semibold shadow-lg hover:from-indigo-700 hover:to-violet-700
               focus:outline-none focus:ring-4 focus:ring-indigo-300/50"
      >
        <i class="fa fa-plus"></i>
        Nueva serie
      </button>
    </div>
  </div>
</section>


  {{-- ============= TABLA ============= --}}
  <section class="space-y-4">
    <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xl">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 dark:bg-gray-800/60 text-gray-700 dark:text-gray-300 uppercase text-xs">
          <tr>
            <th class="px-3 py-3 text-left">Documento</th>
            <th class="px-3 py-3 text-left">Nombre</th>
            <th class="px-3 py-3 text-left">Prefijo</th>
            <th class="px-3 py-3 text-left">Rango</th>
            <th class="px-3 py-3 text-left">Long.</th>
            <th class="px-3 py-3 text-left">Próximo</th>
            <th class="px-3 py-3 text-left">Default</th>
            <th class="px-3 py-3 text-left">Vigencia</th>
            <th class="px-3 py-3 text-left">Activo</th>
            <th class="px-3 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200/70 dark:divide-gray-800">
          @forelse($items as $row)
            @php
              $long = $row->longitud ?? 6;
              $n    = max((int)$row->proximo, (int)$row->desde);
              $num  = str_pad((string)$n, $long, '0', STR_PAD_LEFT);
              $proximoFmt = ($row->prefijo ? "{$row->prefijo}-" : '').$num;
            @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
              <td class="px-3 py-2">{{ strtoupper($row->documento ?? 'factura') }}</td>
              <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-100">{{ $row->nombre }}</td>
              <td class="px-3 py-2">{{ $row->prefijo ?: '—' }}</td>
              <td class="px-3 py-2">{{ number_format($row->desde) }}–{{ number_format($row->hasta) }}</td>
              <td class="px-3 py-2">{{ $long }}</td>
              <td class="px-3 py-2 font-semibold">{{ $proximoFmt }}</td>
              <td class="px-3 py-2">
                @if($row->es_default ?? false)
                  <span class="px-2 py-0.5 text-[11px] rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">Default</span>
                @else
                  —
                @endif
              </td>
              <td class="px-3 py-2">
                @if($row->vigente_desde || $row->vigente_hasta)
                  {{ optional($row->vigente_desde)->format('Y-m-d') }} → {{ optional($row->vigente_hasta)->format('Y-m-d') }}
                @else
                  —
                @endif
              </td>
              <td class="px-3 py-2">
                <button
                  wire:click="toggleActivo({{ $row->id }})"
                  class="px-2 py-1 rounded-md text-xs {{ $row->activa ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}"
                >
                  {{ $row->activa ? 'Activo' : 'Inactivo' }}
                </button>
              </td>
              <td class="px-3 py-2 text-right">
                <div class="inline-flex gap-2">
                  <button
                    wire:click="edit({{ $row->id }})"
                    class="px-3 py-1.5 rounded-md bg-white dark:bg-gray-800 border hover:bg-gray-50 dark:hover:bg-gray-700"
                  >
                    <i class="fa fa-pen"></i> Editar
                  </button>
                  <button
                    onclick="if(confirm('¿Eliminar la serie?')) Livewire.dispatch('call',{fn:'delete',args:[{{ $row->id }}]})"
                    class="px-3 py-1.5 rounded-md bg-white dark:bg-gray-800 border hover:bg-red-50 text-red-600"
                  >
                    <i class="fa fa-trash"></i> Eliminar
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="10" class="px-3 py-8 text-center text-gray-500">Sin resultados…</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="pt-2">
      {{ $items->links() }}
    </div>
  </section>

  {{-- ============= MODAL CREAR/EDITAR ============= --}}
  <section>
    <div x-data="{ open: @entangle('showModal') }" x-cloak>
      <div class="fixed inset-0 bg-black/40 z-40" x-show="open" x-transition.opacity></div>
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="open" x-transition>
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6 border dark:border-gray-700 space-y-4">
          {{-- HEADER --}}
          <div class="flex justify-between items-center border-b pb-3">
            <h2 class="text-base font-bold text-gray-800 dark:text-white">
              <i class="fas fa-clipboard-list text-indigo-600 mr-2"></i> Serie
            </h2>
            <button class="text-gray-400 hover:text-red-500" @click="open=false">
              <i class="fas fa-times-circle"></i>
            </button>
          </div>

          {{-- FORM --}}
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Documento</label>
              <select wire:model.defer="documento" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="factura">Factura</option>
                <option value="oferta">Oferta</option>
                <option value="pedido">Pedido</option>
                <option value="nota_credito">Nota crédito</option>
                <option value="otro">Otro</option>
              </select>
              @error('documento') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Nombre</label>
              <input type="text" wire:model.defer="nombre" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Prefijo</label>
              <input type="text" wire:model.defer="prefijo" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('prefijo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Desde</label>
              <input type="number" min="1" wire:model.defer="rango_desde" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('rango_desde') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Hasta</label>
              <input type="number" min="1" wire:model.defer="rango_hasta" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('rango_hasta') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Longitud</label>
              <input type="number" min="1" max="12" wire:model.defer="longitud" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('longitud') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-3">
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Resolución (opcional)</label>
              <input type="text" wire:model.defer="resolucion" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('resolucion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Vigencia inicio</label>
              <input type="date" wire:model.defer="fecha_inicio" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('fecha_inicio') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Vigencia fin</label>
              <input type="date" wire:model.defer="fecha_fin" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('fecha_fin') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-6 mt-2 md:col-span-3">
              <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model.defer="es_default" class="h-4 w-4">
                <span class="text-sm">Marcar como Default</span>
              </label>
              <label class="inline-flex items-center gap-2">
                <input type="checkbox" wire:model.defer="activo" class="h-4 w-4">
                <span class="text-sm">Activo</span>
              </label>
            </div>
          </div>

          {{-- FOOTER --}}
          <div class="flex items-center justify-between pt-2">
            <div class="text-sm text-gray-600 dark:text-gray-300">
              Próximo: <span class="font-semibold">{{ $this->previewSiguiente($serie_id) ?: $this->previewSiguiente() }}</span>
            </div>
            <div class="flex gap-2">
              <button class="px-4 py-2 rounded-xl border dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800" @click="open=false">Cancelar</button>
              <button wire:click="save" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">Guardar</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

</div>
