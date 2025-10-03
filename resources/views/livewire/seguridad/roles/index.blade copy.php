<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<div x-data="{ showCreateModal: false, showEditModal: false }" 
     x-init="console.log('Alpine.js cargado')"
     wire:init="loadRoles"
     class="p-4 bg-white dark:bg-gray-900 shadow-md rounded-lg transition-all duration-300">

    <div class="flex items-center justify-between mb-4 border-b pb-3">
        <h2 class="text-lg sm:text-2xl font-semibold text-gray-700 dark:text-gray-200 flex items-center space-x-2">
            <i class="fas fa-users-cog text-indigo-500"></i>
            <span>Gestión de Roles</span>
        </h2>
        <button @click="showCreateModal = true"  
           class="px-3 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition">
            <i class="fas fa-plus"></i> Nuevo Rol
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-sm sm:text-base">
            <thead>
                <tr class="bg-gradient-to-r from-violet-500 to-indigo-600 dark:from-indigo-700 dark:to-indigo-900 text-white">
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">Nombre</th>
                    <th class="px-4 py-2 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                @foreach ($roles as $rol)
                    <tr wire:key="role-{{ $rol->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-300 text-center">{{ $rol->id }}</td>
                        <td class="px-4 py-2 text-gray-800 dark:text-gray-300">{{ $rol->name }}</td>
                        <td class="px-4 py-2 flex justify-center space-x-2">

                            <!-- Botón Editar -->
                  <button wire:click="editRole({{ $rol->id }})"
        @click="showEditModal = true"
        class="text-blue-500 hover:text-blue-700 transition p-2 rounded-lg"
        title="Editar">
    <i class="fas fa-edit"></i>
</button>




                            <!-- Botón Inactivar -->
                            <button wire:click="toggleRoleStatus({{ $rol->id }})" 
                                class="text-yellow-500 hover:text-yellow-700 transition p-2 rounded-lg"
                                title="Inactivar">
                                <i class="fas fa-ban"></i> 
                            </button>

                            <!-- Botón Eliminar con confirmación -->
                            <button onclick="confirm('¿Estás seguro de eliminar este rol?') || event.stopImmediatePropagation()" 
                                wire:click="deleteRole({{ $rol->id }})"
                                class="text-red-500 hover:text-red-700 transition p-2 rounded-lg"
                                title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Paginación -->
        <div class="mt-4">
            {{ $roles->links() }}
        </div>
    </div>

    <!-- Modal Crear -->
    <div 
        class="fixed inset-0 flex items-center justify-center bg-opacity-50 backdrop-blur-sm z-50"
        x-show="showCreateModal" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        @keydown.escape.window="showCreateModal = false"
        @close-modal.window="showCreateModal = false" 
        wire:ignore.self
    >
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Crear Nuevo Rol</h3>
                <button @click="showCreateModal = false" class="text-gray-600 dark:text-gray-400 hover:text-red-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @livewire('seguridad.roles.create')
        </div>
    </div>

    <!-- Modal Editar -->
    <div 
        class="fixed inset-0 flex items-center justify-center bg-opacity-50 backdrop-blur-sm z-50"
        x-show="showEditModal" 
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-90"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-90"
        @keydown.escape.window="showEditModal = false"
        @close-modal.window="showEditModal = false" 
        wire:ignore.self
    >
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-96">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Editar Rol</h3>
                <button @click="showEditModal = false" class="text-gray-600 dark:text-gray-400 hover:text-red-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
          @livewire('seguridad.roles.edit', ['roleId' => $selectedRoleId])

        </div>
    </div>
</div>
