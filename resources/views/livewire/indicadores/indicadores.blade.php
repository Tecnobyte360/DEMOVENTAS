
<div class="flex flex-col col-span-full sm:col-span-6 bg-white dark:bg-gray-800 shadow-lg rounded-2xl">
    <!-- Encabezado -->
  
    <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <i class="fas fa-bolt text-indigo-500"></i> Ventas del Día (Real Time)
        </h2>

        <!-- Tooltip Info -->
        <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
            {{-- Tooltips desactivados --}}
        </div>
    </header>
  @if(auth()->user()->hasRole('administrador'))
    <!-- Valores destacados -->
    <div class="px-5 py-4 space-y-4">
        <div class="flex items-baseline gap-2">
            <div class="text-4xl font-extrabold text-gray-800 dark:text-white tabular-nums">
                ${{ number_format($totalFacturado ?? 0, 0, ',', '.') }}
            </div>
            <div class="text-sm font-medium bg-green-500/20 text-green-700 dark:text-green-300 px-2 py-0.5 rounded-full">
                +{{ number_format($totalPagado / max($totalFacturado, 1) * 100, 0) }}%
            </div>
        </div>

        <!-- Indicadores -->
        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <i class="fas fa-hand-holding-usd text-green-500"></i>
                Pagado:
                <span class="ml-auto text-green-700 dark:text-green-300 font-bold">
                    ${{ number_format($totalPagado ?? 0, 0, ',', '.') }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-red-500"></i>
                Pendiente:
                <span class="ml-auto text-red-700 dark:text-red-300 font-bold">
                    ${{ number_format($totalPendiente ?? 0, 0, ',', '.') }}
                </span>
            </div>

            <div class="flex items-center gap-2 col-span-2">
                <i class="fas fa-list text-indigo-500"></i>
                Total Pedidos:
                <span class="ml-auto text-indigo-700 dark:text-indigo-300 font-bold">
                    {{ number_format($totalPedidos ?? 0, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
    @endif

</div>
