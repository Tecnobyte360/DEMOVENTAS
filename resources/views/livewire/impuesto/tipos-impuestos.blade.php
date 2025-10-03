<div class="space-y-4" x-data>
  {{-- Filtros / acciones --}}
  <div class="flex flex-col sm:flex-row sm:items-center gap-3">
    <div class="flex-1 flex items-center gap-2">
      <input type="text" wire:model.debounce.400ms="q" placeholder="Buscar código o nombre…"
             class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" wire:model="soloActivos" class="rounded">
        <span>Activos</span>
      </label>
    </div>

    <button type="button" wire:click="openCreate"
            class="px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm">
      <i class="fa-solid fa-plus mr-1.5"></i> Nuevo tipo
    </button>
  </div>

  {{-- Tabla --}}
  <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
        <tr>
          <th class="px-3 py-2 text-left">Código</th>
          <th class="px-3 py-2 text-left">Nombre</th>
          <th class="px-3 py-2 text-center">¿Retención?</th>
          <th class="px-3 py-2 text-center">Activo</th>
          <th class="px-3 py-2 text-right">Orden</th>
          <th class="px-3 py-2 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
        @forelse($items as $t)
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
            <td class="px-3 py-2 font-mono">{{ $t->codigo }}</td>
            <td class="px-3 py-2">{{ $t->nombre }}</td>
            <td class="px-3 py-2 text-center">
              @if($t->es_retencion)
                <span class="inline-flex items-center gap-1 text-amber-700 dark:text-amber-400">
                  <i class="fa-solid fa-circle-check"></i> Sí
                </span>
              @else
                <span class="text-gray-400">No</span>
              @endif
            </td>
            <td class="px-3 py-2 text-center">
              <button class="text-sm"
                      wire:click="toggleActivo({{ $t->id }})"
                      title="Activar/Desactivar">
                @if($t->activo)
                  <i class="fa-solid fa-toggle-on text-emerald-600 text-lg"></i>
                @else
                  <i class="fa-solid fa-toggle-off text-gray-400 text-lg"></i>
                @endif
              </button>
            </td>
            <td class="px-3 py-2 text-right">{{ $t->orden }}</td>
            <td class="px-3 py-2 text-right">
              <div class="inline-flex items-center gap-2">
                <button wire:click="openEdit({{ $t->id }})"
                        class="px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-xs">
                  Editar
                </button>
                <button wire:click="delete({{ $t->id }})"
                        class="px-3 py-1.5 rounded-md bg-red-600 hover:bg-red-700 text-white text-xs">
                  Eliminar
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
              No hay tipos registrados.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Modal crear/editar --}}
  @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>

      <div class="relative z-50 w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-xl">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
            {{ $editingId ? 'Editar tipo' : 'Nuevo tipo' }}
          </h3>
          <button class="text-gray-500 hover:text-gray-700" wire:click="$set('showModal', false)">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <form wire:submit.prevent="save" class="p-5 grid grid-cols-1 gap-4 text-sm">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs mb-1">Código</label>
              <input type="text" wire:model.defer="codigo"
                     class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
              @error('codigo') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
            </div>
            <div>
              <label class="block text-xs mb-1">Orden</label>
              <input type="number" wire:model.defer="orden" min="1"
                     class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
              @error('orden') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
            </div>
          </div>

          <div>
            <label class="block text-xs mb-1">Nombre</label>
            <input type="text" wire:model.defer="nombre"
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
            @error('nombre') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
          </div>

          <div class="grid grid-cols-2 gap-3">
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" wire:model="es_retencion" class="rounded">
              <span>¿Es retención?</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" wire:model="activo" class="rounded">
              <span>Activo</span>
            </label>
          </div>

          <div class="flex items-center justify-end gap-2">
            <button type="button" wire:click="$set('showModal', false)"
                    class="px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600">
              Cancelar
            </button>
            <button type="submit"
                    class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">
              {{ $editingId ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif
</div>
