
<div class="p-8 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-10">
    {{-- Encabezado --}}
    <header class="text-center space-y-2">
        <h2 class="text-3xl font-extrabold text-gray-800 dark:text-white">Administrar Categorías</h2>

    </header>

    {{-- Formulario --}}
    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
            <div>
                <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">Nombre de la categoría</label>
                <input
                    wire:model="nombre"
                    type="text"
                  
                    class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none"
                />
            </div>

            <div>
                <label class="text-sm font-semibold text-gray-600 dark:text-gray-300 block mb-1">Descripción</label>
                <input
                    wire:model="descripcion"
                    type="text"
                  
                    class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none"
                />
            </div>

            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <label class="flex items-center space-x-2">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Estado</span>
                    <div class="relative">
                    <input type="checkbox" wire:model="activo" class="sr-only peer" />
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-violet-500 rounded-full peer peer-checked:bg-violet-600 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 peer-checked:left-5 w-5 h-5 bg-white rounded-full transition-transform"></div>
                    </div>
                </label>
              <button
    type="submit"
    class="mt-2 md:mt-0 inline-flex items-center gap-2 px-6 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-xl shadow transition-all duration-200"
>
    <!-- Icono de Font Awesome -->
    <i class="fas fa-save"></i>
    {{ $isEdit ? 'Actualizar' : 'Guardar' }}
</button>

            </div>
        </div>
    </form>

    {{-- Tabla de categorías --}}
    <section class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <thead class="bg-violet-600 text-white">
                <tr>
                    <th class="p-4 font-semibold">#ID</th>
                    <th class="p-4 font-semibold">Nombre</th>
                    <th class="p-4 font-semibold">Descripción</th>
                    <th class="p-4 text-center font-semibold">Estado</th>
                    <th class="p-4 text-center font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($categorias as $cat)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="p-4">{{ $cat->id }}</td>
                        <td class="p-4">{{ $cat->nombre }}</td>
                        <td class="p-4 text-gray-600 dark:text-gray-400">{{ $cat->descripcion }}</td>
                        <td class="p-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                {{ $cat->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $cat->activo ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="p-4 text-center space-x-2">
                            <button
                                wire:click="edit({{ $cat->id }})"
                                class="text-blue-600 hover:text-blue-800"
                                title="Editar"
                            >
                                 <i class="fas fa-edit mr-1"></i> 
                            </button>
                            {{-- <button
                                wire:click="delete({{ $cat->id }})"
                                class="text-red-600 hover:text-red-800"
                                title="Eliminar"
                            >
                                 <i class="fas fa-trash-alt mr-1"></i>
                            </button> --}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4 text-center text-gray-500 dark:text-gray-400 italic">
                            No hay categorías registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
