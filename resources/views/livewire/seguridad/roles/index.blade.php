<div x-data="{
    isVisibleCreatePermissionModal: $wire.entangle('isVisibleCreatePermissionModal').live,
    isVisibleEditPermissionModal: $wire.entangle('isVisibleEditPermissionModal').live,
}">
    <div class="mb-4 mt-5 px-4">
        <!-- Breadcrumb -->
        <nav class="flex flex-col sm:flex-row gap-2 sm:gap-0" aria-label="Breadcrumb">
            <ol class="flex flex-col sm:flex-row items-center gap-1">
                <li class="flex items-center">
                    <div class="flex items-center text-sm font-medium text-gray-700 hover:text-red-500">
                        <div class="w-5 text-center">
                            <i class="fas fa-box"></i>
                        </div>
                        <span class="ml-2">Dashboard</span>
                    </div>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <div class="flex items-center text-sm font-medium text-gray-500">
                        <div class="w-5 text-center">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <span class="ml-2">Permisos</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-semibold">Gestión de Permisos</h1>
            <button 
                @click="openModal($event)"
                wire:click="OpenCreatePermission"
                wire:loading.attr="disabled"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors inline-flex items-center justify-center"
            >
                <i class="fas fa-plus mr-2"></i>
                <span>Nuevo Permiso</span>
                
                <svg 
                    wire:loading
                    wire:target="OpenCreatePermission" 
                    class="animate-spin h-5 w-5 text-white ml-2" 
                    xmlns="http://www.w3.org/2000/svg" 
                    fill="none" 
                    viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </button>
        </div>

        <!-- Search -->
        <div class="mt-4">
            <div class="relative">
                <input wire:model.live="search" type="text" 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                    placeholder="Buscar permisos...">
                <span class="absolute left-3 top-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
            </div>
        </div>
        <!-- Permissions Table -->
        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($permissions as $permission)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $permission->id }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $permission->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $permission->description }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    @forelse($permission->roles as $role)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="px-2 py-1 bg-black text-white rounded-full text-xs font-medium">
                                           No posee rol
                                        </span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 flex space-x-2">
                                <button @click="openModal($event)"
                                        wire:click="OpenEditPermission({{ $permission->id }})" 
                                        class="text-blue-600 hover:text-blue-900 transition-colors">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="openModal($event)"
                                    	wire:click="OpenDeletePermissionDichotomic({{ $permission->id }})"
                                        wire:confirm="¿Estás seguro de borrar {{$permission->name}}?"
                                        class="text-red-600 hover:text-red-900 transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr class="hover:bg-gray-50">
                            <th colspan="5" class="text-center px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">No hay permisos registrados.</th>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $permissions->links() }}
        </div>
    </div>

    <div x-show="isVisibleCreatePermissionModal">
        <div class="fixed top-0 left-0 w-screen h-screen bg-gray-900/50 backdrop-blur-lg z-[49]"></div>
    </div>
    <div x-show="isVisibleCreatePermissionModal" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-90"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-90" id="isVisibleCreatePermissionModal"
    class="transform-gpu fixed overflow-x-hidden overflow-y-auto inset-0 z-50 items-center justify-center top-4 md:inset-0 h-modal sm:h-full">
        <div class="relative w-full h-full">
            <div class="absolute inset-0 flex justify-center items-start mt-24 pointer-events-none">
                <button class="cursor-none inline-flex items-center justify-center p-3 text-sm font-medium text-center border border-amber-800 text-white bg-gray-300 rounded hover:bg-blue-800 rounded-lg focus:ring-4 focus:ring-primary-300 sm:w-auto">
                    <div class="flex justify-center items-center">
                        <i class="fa-solid fa-magnifying-glass text-black font-bold text-4xl"></i>
                    </div>
                </button>
            </div>
            @if($isVisibleCreatePermissionModal)
            <div class="fixed inset-0 overflow-y-auto z-50" >
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Modal panel -->
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 uppercase">
                                        ● Crear Nuevo Permiso
                                        <i class="fa-solid fa-key text-lg"></i>
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <!-- Nombre -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Nombre del Permiso
                                            </label>
                                            <input type="text" wire:model="name"
                                            oninput="this.value = this.value.toLowerCase();"
                                            @keydown.enter="handleEnter" @keydown.enter="openModal($event)" wire:keydown.enter="store"
                                            class="w-full px-3 py-2 border @if($errors->has("name")) bg-red-100 border-red-500 @else border-gray-300 @endif rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                        </div>

                                        <!-- Descripción -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Descripción
                                            </label>
                                            <textarea wire:model="description"
                                            @keydown.enter="handleEnter" @keydown.enter="openModal($event)" wire:keydown.enter="store"
                                            class="w-full px-3 py-2 border @if($errors->has("description")) bg-red-100 border-red-500 @else border-gray-300 @endif rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                                            rows="3"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button 
                                wire:click="store"
                                @click="openModal($event)"
                                wire:loading.attr="disabled"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors items-center"
                            >
                                <span>Guardar</span>

                                <svg 
                                    wire:loading
                                    wire:target="store"
                                    class="animate-spin h-5 w-5 text-white ml-2" 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    fill="none" 
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>

                            <button wire:click="CloseModalClick('isVisibleCreatePermissionModal')"
                                    x-on:click="isVisibleCreatePermissionModal = false"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>


    <div x-show="isVisibleEditPermissionModal">
        <div class="fixed top-0 left-0 w-screen h-screen bg-gray-900/50 backdrop-blur-lg z-[49]"></div>
    </div>
    <div x-show="isVisibleEditPermissionModal" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-90"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-90" id="isVisibleEditPermissionModal"
    class="fixed inset-0 z-50 items-center justify-center overflow-x-hidden overflow-y-auto transform-gpu top-4 md:inset-0 h-modal sm:h-full">
        <div class="relative w-full h-full">
            <div class="absolute inset-0 flex items-start justify-center mt-24 pointer-events-none">
                <button class="inline-flex items-center justify-center p-3 text-sm font-medium text-center text-white bg-gray-300 border rounded rounded-lg cursor-none border-amber-800 hover:bg-blue-800 focus:ring-4 focus:ring-primary-300 sm:w-auto">
                    <div class="flex items-center justify-center">
                        <i class="text-4xl font-bold text-black fa-solid fa-magnifying-glass"></i>
                    </div>
                </button>
            </div>
            @if($isVisibleEditPermissionModal)
            <div class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">

                    <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                    <h3 class="text-lg font-medium leading-6 text-gray-900 uppercase">
                                        ● Editar Permiso
                                        <i class="text-lg fa-solid fa-key"></i>
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <!-- Nombre -->
                                        <div>
                                            <label class="block mb-1 text-sm font-medium text-gray-700">
                                                Nombre del Permiso
                                            </label>
                                            <input type="text" wire:model="name"
                                            oninput="this.value = this.value.toLowerCase();"
                                            @keydown.enter="handleEnter" @keydown.enter="openModal($event)" wire:keydown.enter="update"
                                            class="w-full px-3 py-2 border @if($errors->has("name")) bg-red-100 border-red-500 @else border-gray-300 @endif rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors">
                                       
                                        </div>

                                        <!-- Descripción -->
                                        <div>
                                            <label class="block mb-1 text-sm font-medium text-gray-700">
                                                Descripción
                                            </label>
                                            <textarea wire:model="description"
                                            @keydown.enter="handleEnter" @keydown.enter="openModal($event)" wire:keydown.enter="update"
                                            class="w-full px-3 py-2 border @if($errors->has("description")) bg-red-100 border-red-500 @else border-gray-300 @endif rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors"
                                            rows="3"></textarea>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button 
                                @click="openModal($event)"
                                wire:click="update"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center w-full px-4 py-2 text-base font-medium text-white transition-colors bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                <span>Guardar</span>

                                <svg 
                                    wire:loading
                                    wire:target="update"
                                    class="w-5 h-5 ml-2 text-white animate-spin" 
                                    xmlns="http://www.w3.org/2000/svg" 
                                    fill="none" 
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </button>
                            <button wire:click="$set('isVisibleEditPermissionModal', false)"
                                    x-on:click="isVisibleEditPermissionModal = false"
                                    class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>


</div>