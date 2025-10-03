<div class="max-w-md mx-auto p-6 bg-white dark:bg-gray-900 shadow-md rounded-lg">
    <h2 class="text-xl font-bold mb-4 text-gray-700 dark:text-gray-200 flex items-center">
        <i class="fas fa-edit text-yellow-500 mr-2"></i> Editar Rol
    </h2>

    <form wire:submit.prevent="update">
        <div class="mb-4">
            <label for="name" class="block text-gray-700 dark:text-gray-300">Nombre del Rol:</label>
            <input type="text" wire:model="name" 
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center space-x-2">
            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                <i class="fas fa-save"></i> Actualizar
            </button>
            <a href="{{ route('roles.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>

        @if (session()->has('message'))
            <div class="mt-4 text-green-600 dark:text-green-400">
                {{ session('message') }}
            </div>
        @endif
    </form>
</div>
