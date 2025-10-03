<div class="p-7 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-10">
    <form class="space-y-10">

    
       @if($erroresFormulario)
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">¡Corrige los errores!</strong>
                <span class="block sm:inline">Completa todos los campos y asigna al menos un producto a la ruta antes de continuar.</span>
            </div>
        @endif

    <section class="space-y-6 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
        <h3 class="text-2xl font-bold text-gray-800 dark:text-white">
        {{ $isEdit ? 'Editar Ruta' : 'Registrar Ruta de Vehículo' }}
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Vehículo --}}
            <div class="relative">
                <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Vehículo *</label>
                <select 
                    wire:model.lazy="vehiculo_id"
                    class="w-full px-4 py-2 rounded-xl border 
                        @error('vehiculo_id') border-red-500 
                        @elseif(!$errors->has('vehiculo_id') && !empty($vehiculo_id)) border-green-500 
                        @else border-gray-300 @enderror
                        dark:border-gray-700 dark:bg-gray-800 dark:text-white 
                        focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10">
                    <option value="">-- Selecciona Vehículo --</option>
                    @foreach($vehiculos as $vehiculo)
                        <option value="{{ $vehiculo->id }}">{{ $vehiculo->placa }} - {{ $vehiculo->modelo }}</option>
                    @endforeach
                </select>
                @if(!$errors->has('vehiculo_id') && !empty($vehiculo_id))
                    <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                @endif
                @error('vehiculo_id') 
                    <span class="text-red-600 text-xs">{{ $message }}</span> 
                @enderror
            </div>

             <div class="relative">
                <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Ruta *</label>

                <input 
                    wire:model.lazy="ruta"
                    type="text" 
                    placeholder="Nombre de la ruta"
                    class="w-full px-4 py-2 rounded-xl border transition-all duration-200
                        @error('ruta') border-red-500 
                        @elseif(!empty($ruta)) border-green-500 
                        @else border-gray-300 @enderror 
                        dark:border-gray-700 dark:bg-gray-800 dark:text-white 
                        focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10" />

     
                    @if(!$errors->has('ruta') && !empty($ruta))
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                            <i class="fas fa-check-circle text-green-500 animate-scale-in"></i>
                        </div>
                    @endif

        
                @error('ruta') 
                    <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> 
                @enderror
        </div>


   
        <div class="relative">

            <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Fecha de salida *</label>
            <input 
                 wire:model.lazy="fecha_salida" 
                type="date"
                class="w-full px-4 py-2 rounded-xl border transition-all duration-200
                    @error('fecha_salida') border-red-500 
                    @elseif(!empty($fecha_salida)) border-green-500 
                    @else border-gray-300 @enderror 
                    dark:border-gray-700 dark:bg-gray-800 dark:text-white 
                    focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10"/>
            @if(!$errors->has('fecha_salida') && !empty($fecha_salida))
                <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                    <i class="fas fa-check-circle text-green-500 animate-scale-in"></i>
                </div>
            @endif
            @error('fecha_salida') 
                <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> 
            @enderror
        </div>


     
        <div class="relative">
                <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Conductores *</label>

                <div class="space-y-1 max-h-48 overflow-y-auto pr-2 border rounded-xl px-3 py-2 transition-all duration-200
                    @error('conductor_ids') border-red-500 
                    @elseif(count($conductor_ids)) border-green-500 
                    @else border-gray-300 @enderror
                    dark:border-gray-700 dark:bg-gray-800">

                    @foreach($conductores as $conductor)
                        <label class="inline-flex items-center space-x-2">
                            <input 
                                type="checkbox" 
                                 wire:model.lazy="conductor_ids" 
                                value="{{ $conductor->id }}"
                                class="rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                            >
                            <span class="text-gray-700 dark:text-gray-300 text-sm">{{ $conductor->name }}</span>
                        </label>
                    @endforeach
                </div>

             
                @if(!$errors->has('conductor_ids') && count($conductor_ids))
                    <div class="absolute right-3 bottom-3 flex items-center pointer-events-none">
                        <i class="fas fa-check-circle text-green-500 animate-scale-in"></i>
                    </div>
                @endif
                @error('conductor_ids') 
                    <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> 
                @enderror
        </div>
    </div>


    <section class="space-y-6">
        <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-300">Asignar Inventario</h4>

        
            <div class="mt-2">
                @if (!is_null($stockDisponible))
                    <div class="flex items-center gap-2 px-4 py-2 rounded-xl 
                                {{ $stockDisponible > 0 ? 'bg-green-50 border border-green-300 text-green-700' : 'bg-red-50 border border-red-300 text-red-700' }}">
                        <i class="fas {{ $stockDisponible > 0 ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                        <span class="text-sm font-semibold">
                            Stock disponible: {{ $stockDisponible }}
                        </span>
                    </div>
                @else
                    <div class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-500 border border-dashed border-gray-300 dark:border-gray-600">
                        <i class="fas fa-info-circle"></i>
                        <span class="text-sm italic">Seleccione producto y bodega para consultar stock</span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
                    <div class="relative" wire:ignore x-data x-init="
                tom = new TomSelect($refs.bodegaSelect, {
                    placeholder: 'Selecciona una bodega...',
                    allowEmptyOption: true,
                    create: false,
                    maxOptions: 500,
                    onChange: value => @this.set('bodega_id', value),
                });
                Livewire.hook('message.processed', () => tom.refreshOptions(false));
            ">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bodega *</label>

                <select x-ref="bodegaSelect"
                    class="w-full px-4 py-2 rounded-xl border transition-all duration-200
                        @error('bodega_id') border-red-500 
                        @elseif(!empty($bodega_id)) border-green-500 
                        @else border-gray-300 @enderror
                        dark:border-gray-700 dark:bg-gray-800 dark:text-white 
                        focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10"
                >
                    <option value="">Selecciona una bodega...</option>
                    @foreach($bodegas as $bodega)
                        <option value="{{ $bodega->id }}" 
                            {{ $bodega_id == $bodega->id ? 'selected' : '' }}>
                            {{ $bodega->nombre }}
                        </option>
                    @endforeach
                </select>


                @if(!$errors->has('bodega_id') && !empty($bodega_id))
                    <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                @endif

                @error('bodega_id') 
                    <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> 
                @enderror
            </div>


               <div class="relative" wire:ignore x-data x-init="
    tom = new TomSelect($refs.selectProducto, {
        placeholder: 'Buscar producto...',
        allowEmptyOption: true,
        create: false,
        onChange: value => @this.set('producto_id', value),
    });
    Livewire.hook('message.processed', () => tom.refreshOptions(false));
">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Producto *</label>
    
    <select 
        x-ref="selectProducto"
        class="w-full px-4 py-2 rounded-xl border transition-all duration-200
            @error('producto_id') border-red-500 
            @elseif(!empty($producto_id)) border-green-500 
            @else border-gray-300 @enderror
            dark:border-gray-700 dark:bg-gray-800 dark:text-white 
            focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10"
    >
        <option value="">-- Selecciona producto --</option>
        @foreach($productos as $producto)
            <option value="{{ $producto->id }}">{{ $producto->nombre }}</option>
        @endforeach
    </select>

    @if(!$errors->has('producto_id') && !empty($producto_id))
        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
            <i class="fas fa-check-circle text-green-500"></i>
        </div>
    @endif

    @error('producto_id') 
        <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> 
    @enderror
</div>


            <div class="relative flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cantidad *</label>
                        <input 
                            type="number" 
                            wire:model.lazy="cantidad_asignada"
                            placeholder="Ej. 10"
                            class="w-full px-4 py-2 rounded-xl border transition-all duration-200
                                @error('cantidad_asignada') border-red-500 
                                @elseif(!empty($cantidad_asignada)) border-green-500 
                                @else border-gray-300 @enderror
                                dark:border-gray-700 dark:bg-gray-800 dark:text-white 
                                focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10"
                            :disabled="$stockDisponible === 0"
                        />

                        @if(!$errors->has('cantidad_asignada') && !empty($cantidad_asignada))
                            <div class="absolute inset-y-0 right-12 flex items-center pointer-events-none">
                                <i class="fas fa-check-circle text-green-500"></i>
                            </div>
                        @endif

                        @error('cantidad_asignada') 
                            <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> 
                        @enderror
                    </div>
                    <button type="button"
                        wire:click="agregarAsignacion"
                        class="w-11 h-11 bg-green-600 hover:bg-green-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300 disabled:opacity-50"
                        title="Agregar a la Ruta"
                        @if($stockDisponible === 0) disabled @endif
                    >
                        <i class="fas fa-plus text-md"></i>
                    </button>
                </div>
            </div>

           @if(count($asignaciones))
                <div class="mt-10 space-y-6">
                    <h4 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
                        <i class="fas fa-truck text-violet-700 dark:text-violet-300 animate-pulse"></i>
                        Carrito Logístico de Carga
                    </h4>

                    <div class="relative bg-gradient-to-br from-white via-gray-50 to-gray-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 
                                rounded-3xl border-2 border-dashed border-violet-300 dark:border-violet-600 
                                shadow-2xl p-6 overflow-hidden">

                        <div class="absolute -top-6 left-6 z-10">
                            <div class="bg-violet-700 text-white p-4 rounded-full shadow-lg border-4 border-white dark:border-gray-900">
                                <i class="fas fa-truck text-2xl animate-bounce"></i>
                            </div>
                        </div>

                        <div class="absolute bottom-0 left-0 w-full h-3 bg-violet-600 rounded-b-xl shadow-inner opacity-60 z-0"></div>

                        <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($asignaciones as $index => $item)
                                <div 
                                    x-data="{ visible: true }" 
                                    x-show="visible" 
                                    x-transition.opacity.duration.300ms 
                                    wire:key="asignacion-{{ $index }}"
                                    class="relative bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 
                                        rounded-2xl p-5 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1"
                                >
                                    <div class="flex items-center gap-4">
                                        <div class="bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300 p-3 rounded-full shadow-inner">
                                            <i class="fas fa-box-open text-xl"></i>
                                        </div>
                                        <div>
                                            <p class="text-base font-bold text-gray-800 dark:text-white">{{ $item['producto_nombre'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-300">Bodega: {{ $item['bodega_nombre'] }}</p>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-dolly-flatbed mr-1 text-gray-500 dark:text-gray-400"></i>
                                        Cantidad asignada:
                                        <span class="font-semibold text-violet-700 dark:text-violet-300">
                                            {{ $item['cantidad'] }} uds
                                        </span>
                                    </div>

                                    <button 
                                        type="button"
                                        @click.stop="visible = false; $wire.eliminarAsignacion({{ $index }})"
                                        class="absolute top-2 right-2 text-red-500 hover:text-red-700 transition"
                                        title="Quitar del carrito"
                                    >
                                        <i class="fas fa-times-circle text-lg"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif




        
            <div class="flex justify-end gap-4 pt-4">
            @if ($isEdit)
                <button type="button" wire:click="actualizarRuta"
                    class="flex items-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white text-base font-bold rounded-2xl shadow-md hover:shadow-lg transition-all"
                    wire:loading.attr="disabled">
                    <i class="fas fa-sync-alt"></i>
                    <span wire:loading.remove>Actualizar Ruta</span>
                    <span wire:loading class="animate-pulse">Procesando...</span>
                </button>
                <button type="button" wire:click="resetFormulario"
                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold rounded-2xl transition-all">
                    Cancelar edición
                </button>
            @else
            <button type="button" wire:click="guardarRuta"
                class="flex items-center gap-2 px-6 py-3 bg-violet-600 hover:bg-violet-700 text-white text-base font-bold rounded-2xl shadow-md hover:shadow-lg transition-all"
                    wire:loading.attr="disabled">
                    <i class="fas fa-save"></i>
                    <span wire:loading.remove>Registrar Ruta</span>
                    <span wire:loading class="animate-pulse">Procesando...</span>
            </button>
            @endif
            </div>
        </section>
    </section>


{{-- Encabezado --}}


   <section class="space-y-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">

    {{-- Encabezado bonito --}}
        <header class="relative text-center py-4 px-4 bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-t-2xl shadow-sm">
            <div class="flex flex-col items-center space-y-1">
                <div class="bg-gray-300 dark:bg-gray-600 text-white p-2 rounded-full shadow">
                    <i class="fas fa-route text-xl animate-pulse"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white tracking-wide">Historial de Rutas</h2>
                <p class="text-xs text-gray-500 dark:text-gray-300">Consulta de rutas registradas e inventario asignado</p>
            </div>
            <div class="absolute top-0 left-0 w-1 h-full bg-white rounded-l-2xl opacity-10"></div>
            <div class="absolute bottom-0 right-0 w-1 h-full bg-white rounded-r-2xl opacity-10"></div>
        </header>


    {{-- Tabla de rutas --}}
   <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700 shadow-md p-6">
    <table class="min-w-full bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl text-sm text-gray-800 dark:text-gray-200">
    <thead class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white uppercase text-xs tracking-wider">
        <tr>
            <th class="p-3 text-left font-semibold">ID</th>
            <th class="p-3 text-left font-semibold">Vehículo</th>
            <th class="p-3 text-left font-semibold">Ruta</th>
            <th class="p-3 text-left font-semibold">Conductores</th>
            <th class="p-3 text-center font-semibold">Fecha Salida</th>
            <th class="p-3 text-center font-semibold">Acciones</th>
        </tr>
    </thead>
    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
        @forelse($rutas as $ruta)
            {{-- Fila principal --}}
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <td class="p-3">{{ $ruta->id }}</td>
                <td class="p-3">{{ $ruta->vehiculo->placa ?? '-' }}</td>
                <td class="p-3">{{ $ruta->ruta }}</td>
                <td class="p-3">
                    @if($ruta->conductores && $ruta->conductores->count())
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($ruta->conductores as $conductor)
                                <li>{{ $conductor->name }}</li>
                            @endforeach
                        </ul>
                    @else
                        <span class="italic text-gray-400">Sin conductores</span>
                    @endif
                </td>
                <td class="p-3 text-center">{{ $ruta->fecha_salida }}</td>
                <td class="p-3 text-center space-x-2">
                    <button type="button" wire:click="edit({{ $ruta->id }})" class="text-blue-600 hover:text-blue-800" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" wire:click="confirmarEliminacion({{ $ruta->id }})" class="text-red-600 hover:text-red-800" title="Eliminar Ruta">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                    <button type="button" wire:click="verInventario({{ $ruta->id }})" class="text-gray-600 hover:text-indigo-700" title="Ver inventario asignado">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>

            {{-- Inventario asignado --}}
            @if($rutaVistaId === $ruta->id)
                <tr>
                    <td colspan="6">
                        <div class="mt-4 space-y-4 px-6 pb-6">
                            <h4 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <i class="fas fa-truck-loading text-violet-600 dark:text-violet-300"></i>
                                Inventario asignado a esta ruta
                            </h4>

                            <div class="relative bg-gradient-to-br from-white via-gray-50 to-gray-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 
                                        rounded-3xl border-2 border-dashed border-violet-300 dark:border-violet-600 
                                        shadow-2xl p-6 overflow-hidden">

                                <div class="absolute -top-6 left-6 z-10">
                                    <div class="bg-violet-700 text-white p-4 rounded-full shadow-lg border-4 border-white dark:border-gray-900">
                                        <i class="fas fa-truck text-2xl animate-bounce"></i>
                                    </div>
                                </div>

                                <div class="absolute bottom-0 left-0 w-full h-3 bg-violet-600 rounded-b-xl shadow-inner opacity-60 z-0"></div>

                                <div class="relative z-10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                    @forelse($inventarioVista as $item)
                                        <div class="relative bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 
                                                    rounded-2xl p-5 shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
                                            <div class="flex items-center gap-4">
                                                <div class="bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300 
                                                            p-3 rounded-full shadow-inner">
                                                    <i class="fas fa-box text-xl"></i>
                                                </div>
                                                <div>
                                                    <p class="text-base font-bold text-gray-800 dark:text-white">{{ $item['producto'] }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-300">Bodega: {{ $item['bodega'] }}</p>
                                                </div>
                                            </div>

                                            <div class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                                                <i class="fas fa-dolly-flatbed mr-1 text-gray-500 dark:text-gray-400"></i>
                                                Cantidad asignada:
                                                <span class="font-semibold text-violet-700 dark:text-violet-300">
                                                    {{ $item['cantidad_asignada'] }} uds
                                                </span>
                                            </div>

                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Restante:
                                                <span class="font-semibold text-orange-400 dark:text-orange-300">
                                                    {{ $item['cantidad_restante'] }} uds
                                                </span>
                                            </div>
                                      <div class="text-xs text-gray-500 dark:text-gray-400">
                                               
                                                <span class="text-xs text-orange-600 italic">
                                                    Devuelto: {{ $item['cantidad_devuelta'] }}
                                                </span>
                                          </div>

                                        </div>
                                    @empty
                                        <p class="text-center text-gray-400 italic py-4 col-span-full">Sin inventario asignado</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="6" class="p-4 text-center text-gray-500 dark:text-gray-400 italic">
                    No hay rutas registradas.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

    </div>
</section>
<div 
    x-data 
    x-show="$wire.mostrarModalConfirmacion" 
    style="display: none;" 
    class="fixed inset-0 z-50 flex items-center justify-center bg-white/20 backdrop-blur-sm"
>
    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-md w-full p-8 space-y-6">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white">
            ¿Eliminar ruta?
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">
            ¿Estás seguro que deseas eliminar la ruta? <br>
            <span class="font-semibold text-red-600">Esto cargará nuevamente el inventario en la bodega.</span>
        </p>

        <div class="flex justify-end gap-4 pt-4">
            <button 
                wire:click="$set('mostrarModalConfirmacion', false)"
                class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-xl"
            >
                Cancelar
            </button>

            <button 
                wire:click="eliminarRutaConfirmada"
                wire:loading.attr="disabled"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl"
            >
                <span wire:loading.remove>Eliminar</span>
                <span wire:loading class="animate-pulse">Procesando...</span>
            </button>
        </div>
    </div>
</div>
