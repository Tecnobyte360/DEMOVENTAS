<div class="max-w-md mx-auto p-6 bg-white dark:bg-gray-800 shadow-md rounded-lg">
 
    <form wire:submit.prevent="store">
        <div class="mb-4">
            <label for="name" class="block text-gray-700 dark:text-gray-300">Nombre del Rol:</label>
            <input type="text" wire:model="name" 
                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500
                          bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-200">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="flex items-center space-x-2">
           <button type="submit" class="px-4 py-2 rounded-lg bg-gradient-to-r from-violet-500 to-indigo-600 dark:from-indigo-700 dark:to-indigo-900 text-white">
    <i class="fas fa-save"></i> Guardar
</button>

            <button type="button" wire:click="cancel" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">

                <i class="fas fa-times"></i> Cancelar
            </button>
        </div>

        @if (session()->has('message'))
            <div class="mt-4 text-green-600 dark:text-green-400">
                {{ session('message') }}
            </div>
        @endif
    </form>
</div>
