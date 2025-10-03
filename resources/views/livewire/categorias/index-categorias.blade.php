<div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-md">
    <!-- Navegación de pestañas -->
    <div class="mb-6">
        <nav class="flex space-x-4 border-b-2 border-gray-200 dark:border-gray-700">
            <!-- Pestaña Categorías -->
            <button
                wire:click="$set('tab', 'categorias')"
                class="py-2 px-4 text-sm font-medium {{ $tab === 'categorias' ? 'border-b-2 border-violet-600' : 'text-gray-600 dark:text-gray-400' }} focus:outline-none"
            >
                Categorías
            </button>
            <!-- Pestaña Subcategorías -->
            <button
                wire:click="$set('tab', 'subcategorias')"
                class="py-2 px-4 text-sm font-medium {{ $tab === 'subcategorias' ? 'border-b-2 border-violet-600' : 'text-gray-600 dark:text-gray-400' }} focus:outline-none"
            >
                Subcategorías
            </button>
            <!-- Pestaña Productos -->
            <button
                wire:click="$set('tab', 'productos')"
                class="py-2 px-4 text-sm font-medium {{ $tab === 'productos' ? 'border-b-2 border-violet-600' : 'text-gray-600 dark:text-gray-400' }} focus:outline-none"
            >
                Productos
            </button>
            <button
                wire:click="$set('tab', 'precios')"
                class="py-2 px-4 text-sm font-medium {{ $tab === 'precios' ? 'border-b-2 border-violet-600' : 'text-gray-600 dark:text-gray-400' }} focus:outline-none"
            >
                Lista de Precios
            </button>

        </nav>
    </div>

    <!-- Contenido según pestaña activa -->
    @if ($tab === 'categorias')
    <div class="space-y-4">
        <livewire:categoria.categorias />
    </div>
        @elseif ($tab === 'subcategorias')
            <div class="space-y-4">
                <livewire:sub-categorias.sub-categorias />
            </div>
        @elseif ($tab === 'productos')
            <div class="space-y-4">
                <livewire:productos.productos />
            </div>
        @elseif ($tab === 'precios')
            <div class="space-y-4">
                <livewire:productos.precios-producto />
            </div>
        @endif

    
</div>
