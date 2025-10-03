
<div class="p-6 space-y-6">
    <header class="flex justify-between items-center">
        <h2 class="text-xl font-bold">Actividades Económicas (CIIU)</h2>
        <button wire:click="create"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            Nueva
        </button>
    </header>

    <input type="text" wire:model.debounce.500ms="search"
           placeholder="Buscar por código o descripción"
           class="w-full px-3 py-2 border rounded-lg">

    @if (session()->has('message'))
        <div class="p-3 bg-green-100 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    <table class="w-full border rounded-lg">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left">Código</th>
                <th class="p-2 text-left">Descripción</th>
                <th class="p-2 text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($actividades as $a)
                <tr class="border-t">
                    <td class="p-2">{{ $a->codigo }}</td>
                    <td class="p-2">{{ $a->descripcion }}</td>
                    <td class="p-2 text-center space-x-2">
                        <button wire:click="edit({{ $a->id }})"
                            class="px-3 py-1 bg-blue-500 text-white rounded-lg">Editar</button>
                        <button wire:click="delete({{ $a->id }})"
                            class="px-3 py-1 bg-red-500 text-white rounded-lg">Eliminar</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>
        {{ $actividades->links() }}
    </div>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
            <div class="bg-white p-6 rounded-xl w-full max-w-lg">
                <h3 class="text-lg font-bold mb-4">
                    {{ $actividadId ? 'Editar Actividad' : 'Nueva Actividad' }}
                </h3>
                <form wire:submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Código</label>
                        <input type="text" wire:model="codigo"
                               class="w-full px-3 py-2 border rounded-lg">
                        @error('codigo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Descripción</label>
                        <input type="text" wire:model="descripcion"
                               class="w-full px-3 py-2 border rounded-lg">
                        @error('descripcion') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="px-4 py-2 bg-gray-500 text-white rounded-lg">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>