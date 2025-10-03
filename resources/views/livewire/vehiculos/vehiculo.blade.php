<div class="p-7 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-10">
   <div class="p-7 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-10">
    <form wire:submit.prevent="guardarVehiculo" class="space-y-10">
        <section class="space-y-6 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
                {{ $isEdit ? 'Editar Vehículo' : 'Registrar Nuevo Vehículo' }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Placa -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Placa *</label>
                    <input wire:model.lazy="placa" type="text" placeholder="ABC123"
                        class="w-full px-4 py-2 rounded-xl border @error('placa') border-red-500 @else border-gray-300 @enderror
                            dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none" />
                    @error('placa') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Modelo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Modelo *</label>
                    <input wire:model.lazy="modelo" type="text" placeholder="Ej: NHR, Hilux, F-150"
                        class="w-full px-4 py-2 rounded-xl border @error('modelo') border-red-500 @else border-gray-300 @enderror
                            dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none" />
                    @error('modelo') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Marca -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Marca</label>
                    <input wire:model.lazy="marca" type="text" placeholder="Toyota, Chevrolet..."
                        class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none" />
                </div>

                <!-- Estado -->
               <div>
                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-2">Estado *</label>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Inactivo</span>

                        <label class="relative inline-flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                wire:model.lazy="estado_activo" 
                                class="sr-only peer"
                            >
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-violet-500 
                                        dark:bg-gray-700 rounded-full peer dark:peer-focus:ring-violet-600 
                                        peer-checked:bg-violet-600 transition-all"></div>
                            <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform 
                                        peer-checked:translate-x-5"></div>
                        </label>

                        <span class="text-sm text-gray-700 dark:text-gray-300">Activo</span>
                    </div>
                </div>


            </div>

            <div class="flex justify-end pt-4">
                <button type="submit"
                    class="flex items-center gap-2 px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white text-base font-bold rounded-2xl shadow-md hover:shadow-lg transition-all">
                    <i class="fas fa-save"></i> {{ $isEdit ? 'Actualizar' : 'Guardar' }}
                </button>
            </div>
        </section>
    </form>

    <!-- Tabla de vehículos -->
   <section class="space-y-6 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
    <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Vehículos Registrados</h3>

   <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700 shadow-md">
    <table class="min-w-full text-sm bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-xl overflow-hidden text-gray-800 dark:text-gray-200">
        <thead class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white uppercase text-xs tracking-wider">
                <tr>
                    <th class="p-3 text-left font-semibold">ID</th>
                    <th class="p-3 text-left font-semibold">Placa</th>
                    <th class="p-3 text-left font-semibold">Modelo</th>
                    <th class="p-3 text-left font-semibold">Marca</th>
                    <th class="p-3 text-center font-semibold">Estado</th>
                    <th class="p-3 text-center font-semibold">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($vehiculos as $veh)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="p-3">{{ $veh->id }}</td>
                        <td class="p-3">{{ $veh->placa }}</td>
                        <td class="p-3">{{ $veh->modelo }}</td>
                        <td class="p-3">{{ $veh->marca }}</td>
                        <td class="p-3 text-center">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:click="toggleEstado({{ $veh->id }})" {{ $veh->estado === 'activo' ? 'checked' : '' }}
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:bg-green-500 relative transition-all duration-300">
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-all duration-300 peer-checked:translate-x-full"></div>
                                </div>
                            </label>
                        </td>
                        <td class="p-3 text-center space-x-2">
                            <button wire:click="editar({{ $veh->id }})" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500 dark:text-gray-400 italic">No hay vehículos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
</div>

</div>

