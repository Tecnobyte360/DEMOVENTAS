<form wire:submit.prevent="save">
    <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
        <input type="text" id="nombre" wire:model="nombre" class="mt-1 block w-full p-2 border rounded" />
        @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
    </div>

    <div>
        <label for="ubicacion" class="block text-sm font-medium text-gray-700">Ubicación</label>
        <input type="text" id="ubicacion" wire:model="ubicacion" class="mt-1 block w-full p-2 border rounded" />
        @error('ubicacion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
    </div>

    <div class="mt-2">
        <label for="activo" class="inline-flex items-center">
            <input type="checkbox" id="activo" wire:model="activo" class="form-checkbox h-5 w-5 text-indigo-600" />
            <span class="ml-2">Activo</span>
        </label>
    </div>

    <div class="mt-4 flex justify-end">
        <button type="submit" class="px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600">
            Guardar Cambios
        </button>
    </div>
</form>
