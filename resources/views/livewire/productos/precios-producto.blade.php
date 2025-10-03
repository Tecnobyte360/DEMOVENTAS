<div class="p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-6">

    <h2 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
        <i class="fas fa-tags text-violet-500"></i> Listas de Precios por Producto
    </h2>

    {{-- Campo de búsqueda + botón --}}
    <div class="mb-4 flex space-x-2">
        <input
            type="text"
            wire:model.defer="searchTerm"
            placeholder="🔍 Escriba nombre de producto..."
            class="flex-1 px-4 py-2 text-sm rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-violet-500"
        />
        <button
            wire:click="buscar"
            class="bg-violet-600 hover:bg-violet-700 text-white px-4 py-2 rounded-xl text-sm shadow"
        >
            Buscar
        </button>
    </div>

    {{-- Tabla general de productos filtrados --}}
    <table class="min-w-full text-sm text-gray-700 dark:text-gray-300 border rounded-xl overflow-x-auto">
        <thead class="bg-gray-100 dark:bg-gray-800">
            <tr>
                <th class="p-2 text-left">Producto</th>
                <th class="p-2 text-left">Precios Existentes</th>
                <th class="p-2 text-left">Agregar Nueva Lista</th>
            </tr>
        </thead>
       <tbody>
    @forelse($filteredProducts as $producto)
        <tr class="border-t dark:border-gray-700 align-top">
            {{-- Primera columna: nombre del producto --}}
            <td class="p-2">{{ $producto->nombre }}</td>

            {{-- Segunda columna: Precios existentes (con edición inline) --}}
            <td class="p-2">
                @if($producto->precios->isNotEmpty())
                    <table class="w-full text-xs text-gray-700 dark:text-gray-300 border rounded-lg">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="p-1 text-left">Nombre</th>
                                <th class="p-1 text-left">Valor</th>
                                <th class="p-1 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($producto->precios as $precio)
                                @php
                                    // ¿Está en modo edición este precio?
                                    $enEdicion = $editingPrecio[$precio->id] ?? false;
                                @endphp

                                @if($enEdicion)
                                    {{-- Modo edición: inputs + botones Guardar / Cancelar --}}
                                    <tr class="border-t dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                                        {{-- Input: nombre --}}
                                        <td class="p-1">
                                            <input
                                                type="text"
                                                wire:model.defer="editNombres.{{ $precio->id }}"
                                                class="w-full px-1 py-0.5 text-[10px] rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-violet-500"
                                            />
                                            @error("editNombres.{$precio->id}")
                                                <span class="text-red-500 text-[9px]">{{ $message }}</span>
                                            @enderror
                                        </td>

                                        {{-- Input: valor --}}
                                        <td class="p-1">
                                            <input
                                                type="number"
                                                step="0.01"
                                                wire:model.defer="editValores.{{ $precio->id }}"
                                                class="w-full px-1 py-0.5 text-[10px] rounded-xl border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-violet-500"
                                            />
                                            @error("editValores.{$precio->id}")
                                                <span class="text-red-500 text-[9px]">{{ $message }}</span>
                                            @enderror
                                        </td>

                                        {{-- Botones: Guardar / Cancelar --}}
                                        <td class="p-1 text-center space-x-1">
                                            <button
                                                wire:click="actualizarPrecio({{ $precio->id }})"
                                                class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-[10px]"
                                                title="Guardar cambios"
                                            >
                                                Guardar
                                            </button>
                                            <button
                                                wire:click="cancelarEdicion({{ $precio->id }})"
                                                class="bg-gray-500 hover:bg-gray-600 text-white px-2 py-1 rounded text-[10px]"
                                                title="Cancelar"
                                            >
                                                Cancelar
                                            </button>
                                        </td>
                                    </tr>
                                @else
                                    {{-- Modo lectura: mostrar texto + botones Editar / Eliminar --}}
                                    <tr class="border-t dark:border-gray-600">
                                        <td class="p-1">{{ $precio->nombre }}</td>
                                        <td class="p-1">${{ number_format($precio->valor, 2, ',', '.') }}</td>
                                        <td class="p-1 text-center space-x-1">
                                            <button
                                                wire:click="editarPrecio({{ $precio->id }})"
                                                class="text-blue-500 hover:text-blue-700 text-[11px]"
                                                title="Editar este precio"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            {{-- <button
                                                wire:click="eliminarPrecio({{ $precio->id }})"
                                                class="text-red-500 hover:text-red-700 text-[11px]"
                                                title="Eliminar este precio"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button> --}}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic">
                        Ningún precio definido
                    </p>
                @endif
            </td>

            {{-- Tercera columna: Inputs para crear nueva lista (igual que antes) --}}
            <td class="p-2">
                <div class="space-y-2">
                    {{-- Input nombre --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                            Nombre
                        </label>
                        <input
                            type="text"
                            wire:model.defer="nombres.{{ $producto->id }}"
                            class="w-full px-2 py-1 text-xs rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-violet-500"
                        />
                        @error("nombres.{$producto->id}")
                            <span class="text-red-500 text-[10px]">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Input valor --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                            Valor
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            wire:model.defer="valores.{{ $producto->id }}"
                            class="w-full px-2 py-1 text-xs rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-violet-500"
                        />
                        @error("valores.{$producto->id}")
                            <span class="text-red-500 text-[10px]">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Botón Agregar --}}
                    <div>
                        <button
                            wire:click="guardarPrecio({{ $producto->id }})"
                            class="bg-violet-600 hover:bg-violet-700 text-white px-3 py-1 rounded-xl text-xs shadow w-full"
                            title="Crear lista de precios para {{ $producto->nombre }}"
                        >
                            Agregar
                        </button>
                    </div>
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="3" class="p-4 text-center text-gray-500 dark:text-gray-400 italic">
                No se encontraron productos que coincidan con “{{ $search }}”.
            </td>
        </tr>
    @endforelse
</tbody>
    </table>
</div>
