<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

    {{-- Total Clientes --}}
    <div class="bg-indigo-100 dark:bg-indigo-900 p-5 rounded-xl shadow flex items-center gap-4">
        <div class="p-3 bg-white dark:bg-gray-900 rounded-full shadow">
            <i class="fas fa-users text-indigo-600 dark:text-indigo-300 text-2xl"></i>
        </div>
        <div>
            <h3 class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Clientes Registrados</h3>
            <p class="text-xl font-bold text-indigo-600 dark:text-indigo-300">{{ $totalClientes }}</p>
        </div>
    </div>

    {{-- Clientes nuevos hoy --}}
    <div class="bg-green-100 dark:bg-green-900 p-5 rounded-xl shadow flex items-center gap-4">
        <div class="p-3 bg-white dark:bg-gray-900 rounded-full shadow">
            <i class="fas fa-user-plus text-green-600 dark:text-green-300 text-2xl"></i>
        </div>
        <div>
            <h3 class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Nuevos Hoy</h3>
            <p class="text-xl font-bold text-green-600 dark:text-green-300">{{ $clientesNuevosHoy }}</p>
        </div>
    </div>

    {{-- Clientes con deuda --}}
    <div class="bg-red-100 dark:bg-red-900 p-5 rounded-xl shadow flex items-center gap-4">
        <div class="p-3 bg-white dark:bg-gray-900 rounded-full shadow">
            <i class="fas fa-user-clock text-red-600 dark:text-red-300 text-2xl"></i>
        </div>
        <div>
            <h3 class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-400">Con Deuda</h3>
            <p class="text-xl font-bold text-red-600 dark:text-red-300">{{ $clientesConDeuda }}</p>
        </div>
    </div>

</div>
