<div class="p-7 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-8">

  {{-- Header + búsqueda --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white">Unidades de medida</h2>

    <div class="flex items-center gap-2">
      <input type="text" placeholder="Buscar por código, nombre, símbolo..."
             wire:model.debounce.500ms="search"
             class="w-72 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none">
      <button wire:click="$refresh"
              class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl shadow text-sm">
        <i class="fas fa-search mr-1"></i> Buscar
      </button>
    </div>
  </div>

  {{-- Formulario --}}
  <div class="p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="grid grid-cols-1 md:grid-cols-5 gap-5">

      <div class="md:col-span-1">
        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Código *</label>
        <input type="text" placeholder="KG, LT, UND..."
               class="w-full px-4 py-2 rounded-xl border uppercase
                      @error('codigo') border-red-500 @else border-gray-300 @enderror
                      dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none"
               wire:model.lazy="codigo">
        @error('codigo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Nombre *</label>
        <input type="text" placeholder="Kilogramo, Litro, Unidad..."
               class="w-full px-4 py-2 rounded-xl border
                      @error('nombre') border-red-500 @else border-gray-300 @enderror
                      dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none"
               wire:model.lazy="nombre">
        @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Símbolo</label>
        <input type="text" placeholder="kg, L, u..."
               class="w-full px-4 py-2 rounded-xl border
                      @error('simbolo') border-red-500 @else border-gray-300 @enderror
                      dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none"
               wire:model.lazy="simbolo">
        @error('simbolo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div>
        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Tipo</label>
        <select wire:model="tipo"
                class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none">
          @foreach($tipos as $t)
            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
          @endforeach
        </select>
        @error('tipo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
      </div>

      <div class="flex items-end justify-between gap-3 md:col-span-5">
        <label class="flex items-center gap-3">
          <span class="text-sm text-gray-700 dark:text-gray-300">Activo</span>
          <div class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" class="sr-only peer" wire:model="activo">
            <div class="w-11 h-6 bg-gray-300 rounded-full peer-checked:bg-violet-600 transition"></div>
            <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition peer-checked:translate-x-5"></div>
          </div>
        </label>

        <div class="flex items-center gap-3">
          @if($isEdit)
            <button type="button" wire:click="$set('isEdit', false); $set('unidad_id', null); $set('codigo',''); $set('nombre',''); $set('simbolo',''); $set('tipo','otro'); $set('activo', true)"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-xl">
              Cancelar
            </button>
          @endif
          <button type="submit"
                  class="px-5 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl shadow">
            <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Actualizar' : 'Guardar' }}
          </button>
        </div>
      </div>
    </form>
  </div>

  {{-- Tabla --}}
  <div class="overflow-x-auto">
    <table class="min-w-[900px] w-full bg-white dark:bg-gray-800 rounded-2xl shadow overflow-hidden text-sm">
      <thead class="bg-violet-600 text-white">
        <tr>
          <th class="p-3 text-left">#ID</th>
          <th class="p-3 text-left">Código</th>
          <th class="p-3 text-left">Nombre</th>
          <th class="p-3 text-left">Símbolo</th>
          <th class="p-3 text-left">Tipo</th>
          <th class="p-3 text-center">Estado</th>
          <th class="p-3 text-center">Productos</th>
          <th class="p-3 text-center">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
        @forelse($unidades as $u)
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <td class="p-3">{{ $u->id }}</td>
            <td class="p-3 font-semibold">{{ $u->codigo }}</td>
            <td class="p-3">{{ $u->nombre }}</td>
            <td class="p-3">{{ $u->simbolo ?? '—' }}</td>
            <td class="p-3 capitalize">{{ $u->tipo ?? '—' }}</td>
            <td class="p-3 text-center">
              <span class="inline-flex items-center px-2 py-1 text-xs font-semibold rounded-full {{ $u->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                {{ $u->activo ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="p-3 text-center">
              {{ $u->productos()->count() }}
            </td>
            <td class="p-3 text-center space-x-2">
              <button wire:click="edit({{ $u->id }})" class="text-blue-600 hover:text-blue-800" title="Editar">
                <i class="fas fa-edit"></i>
              </button>
              <button wire:click="toggleActivo({{ $u->id }})" class="text-violet-600 hover:text-violet-800" title="Activar/Inactivar">
                <i class="fas fa-toggle-on"></i>
              </button>
              <button wire:click="destroy({{ $u->id }})" class="text-red-600 hover:text-red-800" title="Eliminar" onclick="return confirm('¿Eliminar definitivamente esta unidad?')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="p-4 text-center text-gray-500 dark:text-gray-400 italic">No hay unidades registradas.</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="mt-4">
      {{ $unidades->links() }}
    </div>
  </div>
</div>
