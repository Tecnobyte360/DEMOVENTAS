<div class="p-6">
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 flex items-center gap-2">
            <i class="fas fa-undo-alt text-purple-600"></i> Devoluciones de Mercancía
        </h2>

        {{-- FILTROS --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 text-sm">
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-1">Desde</label>
                <input type="date" wire:model.defer="filtroDesde" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-1">Hasta</label>
                <input type="date" wire:model.defer="filtroHasta" class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-1">Producto</label>
                <input type="text" wire:model.defer="filtroProducto" placeholder="Buscar producto..." class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
                <label class="block text-gray-700 dark:text-gray-300 mb-1">Bodega</label>
                <input type="text" wire:model.defer="filtroBodega" placeholder="Buscar bodega..." class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
            </div>
            <div class="flex items-end">
                <button wire:click="filtrar" class="w-full py-2 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-semibold shadow">
                    <i class="fas fa-search mr-1"></i> Filtrar
                </button>
            </div>
        </div>

        {{-- TABLA --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-gray-800 dark:text-gray-300">
                <thead class="bg-gray-100 dark:bg-gray-800 text-left uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2">#</th>
                        <th class="px-4 py-2">Ruta</th>
                        <th class="px-4 py-2">Usuario</th>
                        <th class="px-4 py-2">Fecha</th>
                        <th class="px-4 py-2">Observación</th>
                        <th class="px-4 py-2">Detalles</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($devoluciones as $devolucion)
                        <tr>
                            <td class="px-4 py-2 font-semibold">{{ $devolucion->id }}</td>
                            <td class="px-4 py-2">{{ $devolucion->ruta->ruta ?? '-' }}</td>
                            <td class="px-4 py-2">{{ strtoupper($devolucion->usuario->name ?? '-') }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($devolucion->fecha)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 italic">{{ $devolucion->observaciones }}</td>
                            <td class="px-4 py-2 space-y-1">
                                @foreach($devolucion->detalles as $detalle)
                                    <span class="inline-block bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full text-xs">
                                        {{ $detalle->cantidad }} x {{ $detalle->producto->nombre }} <span class="text-violet-600">({{ $detalle->bodega->nombre }})</span>
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center italic text-gray-500 dark:text-gray-400">
                                No hay devoluciones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
