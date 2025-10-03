<div>
    {{-- Éxito --}}
    @if (session()->has('message'))
        <div class="p-3 mb-4 text-white bg-green-500 rounded flex justify-between items-center">
            {{ session('message') }}
            <button wire:click="$set('excelFile', null)" class="font-bold">X</button>
        </div>

    {{-- Error general --}}
    @elseif (session()->has('error'))
        <div class="p-3 mb-4 text-white bg-red-500 rounded">
            {{ session('error') }}
        </div>

    {{-- Errores de importación por fila --}}
    @elseif (session()->has('errores_importacion'))
        <div class="p-3 mb-4 text-white bg-yellow-500 rounded">
            <p class="font-bold mb-2">Se encontraron errores durante la importación:</p>
            <ul class="list-disc ml-5 text-sm">
                @foreach (session('errores_importacion') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario de carga --}}
    <form wire:submit.prevent="preview" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                Selecciona el archivo Excel
            </label>
            <input 
                type="file" 
                wire:model="excelFile" 
                accept=".xlsx,.xls" 
                class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm"
            />
            @error('excelFile') 
                <span class="text-red-500 text-sm">{{ $message }}</span> 
            @enderror
        </div>

        <div class="flex justify-end space-x-2">
          
           
        </div>
    </form>

    {{-- Tabla de vista previa --}}
    @if ($previewData)
       <div class="mt-6">
    <h3 class="text-lg font-bold mb-4 text-gray-800">Vista previa de los datos:</h3>

    <div class="overflow-auto max-h-96 rounded-xl shadow-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm text-left text-gray-700">
            <thead class="bg-gray-100 sticky top-0 z-10">
                <tr>
                    <th class="px-4 py-2">Razón Social</th>
                    <th class="px-4 py-2">NIT</th>
                    <th class="px-4 py-2">Tel. Fijo</th>
                    <th class="px-4 py-2">Tel. Móvil</th>
                    <th class="px-4 py-2">Dirección</th>
                    <th class="px-4 py-2">Correo</th>
                    <th class="px-4 py-2">Barrio</th>
                    <th class="px-4 py-2">Saldo</th>
                    <th class="px-4 py-2">Tipo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($previewData as $index => $row)
                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                        @foreach ($row as $cell)
                            <td class="px-4 py-2 whitespace-nowrap">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        <button 
            wire:click="import" 
            class="px-6 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition duration-200 shadow"
        >
            Confirmar e Importar
        </button>
    </div>
</div>

    @endif
</div>
