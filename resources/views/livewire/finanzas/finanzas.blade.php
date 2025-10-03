<div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-md space-y-6">

    <!-- Navegación de pestañas -->
    <nav class="flex space-x-6 border-b border-gray-200 dark:border-gray-700 pb-2">
        <button
            wire:click="$set('tab', 'creditos')"
            class="py-2 px-4 text-sm font-semibold transition-all duration-200 
                   {{ $tab === 'creditos' ? 'border-b-2 border-violet-600 text-violet-600 dark:text-violet-400' : 'text-gray-600 dark:text-gray-400 hover:text-violet-500' }}">
            Pagos de contado y crédito
        </button>

        <button
            wire:click="$set('tab', 'pedidos_mes')"
            class="py-2 px-4 text-sm font-semibold transition-all duration-200 
                   {{ $tab === 'pedidos_mes' ? 'border-b-2 border-violet-600 text-violet-600 dark:text-violet-400' : 'text-gray-600 dark:text-gray-400 hover:text-violet-500' }}">
            Liquidación conductores
        </button>
       <button
  wire:click="$set('tab', 'reportes')"
  class="py-2 px-4 text-sm font-semibold transition-all duration-200 
    {{ $tab === 'reportes' ? 'border-b-2 border-violet-600 text-violet-600 dark:text-violet-400' : 'text-gray-600 dark:text-gray-400 hover:text-violet-500' }}">
  Reportes
</button>

    </nav>
<!-- Contenido de pestañas -->
@if ($tab === 'creditos')
  <div class="space-y-4">
    @livewire('finanzas.creditos')
  </div>

@elseif ($tab === 'pedidos_mes')
  <div class="space-y-4">
    @livewire('finanzas.liquidacion')
  </div>

@elseif ($tab === 'reportes')
  <div class="space-y-4">
    @livewire('finanzas.reportes')
  </div>
@endif


</div>
