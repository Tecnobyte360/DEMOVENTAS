@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

<div class="p-10 bg-gradient-to-br from-violet-200 via-white to-purple-100 dark:from-gray-900 dark:via-gray-800 dark:to-black rounded-3xl shadow-2xl space-y-12">

  {{-- HEADER --}}
  <section class="backdrop-blur bg-white/80 dark:bg-gray-900/80 rounded-3xl shadow-2xl border border-gray-200/60 dark:border-gray-800 p-6 md:p-8">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
      <div>
        <h3 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">Tipos de Documentos</h3>
        <p class="text-sm md:text-base text-gray-600 dark:text-gray-300">Catálogo maestro de tipos de documentos.</p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
          <i class="fa fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <input type="text" wire:model.live="search" placeholder="Buscar..."
            class="pl-10 pr-4 h-12 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
        </div>

        <button wire:click="create"
          class="px-5 h-12 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-semibold shadow-lg hover:from-indigo-700 hover:to-violet-700">
          <i class="fa fa-plus mr-1"></i> Nuevo tipo
        </button>
      </div>
    </div>
  </section>

  {{-- TABLA --}}
  <section>
    <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xl">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 dark:bg-gray-800/60 text-gray-700 dark:text-gray-300 uppercase text-xs">
          <tr>
            <th class="px-3 py-3 text-left">Código</th>
            <th class="px-3 py-3 text-left">Nombre</th>
            <th class="px-3 py-3 text-left">Módulo</th>
            <th class="px-3 py-3 text-left">Config</th>
            <th class="px-3 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($items as $row)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
              <td class="px-3 py-2 font-semibold text-indigo-600 dark:text-indigo-300">{{ $row->codigo }}</td>
              <td class="px-3 py-2">{{ $row->nombre }}</td>
              <td class="px-3 py-2">{{ $row->modulo ?: '—' }}</td>
              <td class="px-3 py-2 text-xs text-gray-500 truncate max-w-[250px]">{{ json_encode($row->config) }}</td>
              <td class="px-3 py-2 text-right">
                <button wire:click="edit({{ $row->id }})"
                  class="px-3 py-1 rounded-md border hover:bg-gray-50 dark:hover:bg-gray-700">
                  <i class="fa fa-pen"></i>
                </button>
                <button onclick="if(confirm('¿Eliminar este tipo?')) Livewire.dispatch('call',{fn:'delete',args:[{{ $row->id }}]})"
                  class="px-3 py-1 rounded-md border text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30">
                  <i class="fa fa-trash"></i>
                </button>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">Sin registros…</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="pt-3">{{ $items->links() }}</div>
  </section>

  {{-- MODAL --}}
  <section>
    <div x-data="{ open: @entangle('showModal') }" x-cloak>
      <div class="fixed inset-0 bg-black/40 z-40" x-show="open" x-transition.opacity></div>
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-show="open" x-transition>
        <div class="w-full max-w-2xl bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6 border dark:border-gray-700 space-y-4 max-h-[90vh] overflow-y-auto">
          <div class="flex justify-between items-center border-b pb-3">
            <h2 class="font-bold text-gray-800 dark:text-white">
              <i class="fa fa-file-lines text-indigo-600 mr-2"></i> Tipo de documento
            </h2>
            <button class="text-gray-400 hover:text-red-500" @click="open=false">
              <i class="fa fa-times-circle"></i>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <label class="block text-xs font-medium mb-1">Código</label>
              <input type="text" wire:model.defer="codigo" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('codigo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-xs font-medium mb-1">Nombre</label>
              <input type="text" wire:model.defer="nombre" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-medium mb-1">Módulo</label>
              <input type="text" wire:model.defer="modulo" placeholder="ventas, compras, inventario…" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white">
              @error('modulo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
              <label class="block text-xs font-medium mb-1">Config (JSON)</label>
              <textarea wire:model.defer="config_json" rows="4" class="w-full px-3 py-2 rounded-xl border dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono text-xs"></textarea>
              @error('config_json') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-3 border-t">
            <button class="px-4 py-2 rounded-xl border hover:bg-gray-50 dark:hover:bg-gray-800" @click="open=false">Cancelar</button>
            <button wire:click="save" class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700">Guardar</button>
          </div>
        </div>
      </div>
    </div>
  </section>

</div>
