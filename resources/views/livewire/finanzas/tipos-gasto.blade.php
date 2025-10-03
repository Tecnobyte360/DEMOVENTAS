<div class="space-y-10 p-10 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="fas fa-layer-group text-violet-600"></i> Tipos de Gasto
    </h2>

    @if (session('message'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded-xl shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="{{ $modoEdicion ? 'actualizar' : 'guardar' }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Nombre *</label>
            <input type="text" wire:model="nombre"
                class="w-full px-4 py-2 rounded-xl border @error('nombre') border-red-500 @else border-gray-300 @enderror dark:bg-gray-800 dark:text-white focus:ring-violet-500">
            @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Descripción</label>
            <input type="text" wire:model="descripcion"
                class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:bg-gray-800 dark:text-white focus:ring-violet-500">
        </div>

        <div class="col-span-full flex justify-end gap-2 mt-2">
            @if ($modoEdicion)
                <button type="button" wire:click="cancelar"
                    class="px-4 py-2 bg-gray-500 text-white rounded-xl hover:bg-gray-600">Cancelar</button>
            @endif

            <button type="submit"
                class="px-4 py-2 bg-violet-600 text-white rounded-xl hover:bg-violet-700">
                {{ $modoEdicion ? 'Actualizar' : 'Guardar' }}
            </button>
        </div>
    </form>

    <table class="w-full mt-8 text-sm rounded-xl overflow-hidden">
        <thead class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white uppercase text-xs">
            <tr>
                <th class="p-3 text-left">Nombre</th>
                <th class="p-3 text-left">Descripción</th>
                <th class="p-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800">
            @forelse ($tipos as $tipo)
                <tr class="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="p-3">{{ $tipo->nombre }}</td>
                    <td class="p-3">{{ $tipo->descripcion }}</td>
                    <td class="p-3 text-right">
                        <button wire:click="editar({{ $tipo->id }})" class="text-blue-600 hover:text-blue-800 mr-3">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button wire:click="eliminar({{ $tipo->id }})" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="p-4 text-center text-gray-500 italic">No hay tipos de gasto registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
