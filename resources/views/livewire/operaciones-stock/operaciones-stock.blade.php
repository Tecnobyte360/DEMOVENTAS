<div class="p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-2xl space-y-8">

    {{-- Navegación de pestañas --}}
    <div>
        <nav class="flex space-x-6 border-b-2 border-gray-200 dark:border-gray-700">
          
            
        </nav>
    </div>

    {{-- Contenido dinámico según la pestaña activa --}}
    <div class="mt-6">
        @if ($tab === 'entradas')
            <livewire:inventario.entradas-mercancia />
        @elseif ($tab === 'listado')
            <livewire:inventario.lista-entradas-mercancia />
        @endif
    </div>

</div>
