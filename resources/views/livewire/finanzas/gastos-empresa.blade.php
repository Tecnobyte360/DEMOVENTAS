<form wire:submit.prevent="guardarGasto" class="space-y-16">
    <section class="bg-gradient-to-br from-white to-gray-100 dark:from-gray-900 dark:to-gray-800 p-10 rounded-3xl shadow-2xl space-y-10">

        <!-- ENCABEZADO -->
        <div class="flex justify-between items-center border-b pb-6">
            <h2 class="text-3xl font-bold text-gray-800 dark:text-white flex items-center gap-3">
                <i class="fas fa-hand-holding-usd text-violet-600 text-2xl"></i> Registrar Gasto
            </h2>
        </div>

        <!-- MENSAJES -->
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-300 text-green-800 px-5 py-3 rounded-2xl shadow-sm animate-fade-in flex items-center gap-2">
                <i class="fas fa-check-circle"></i> <span>{{ session('message') }}</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-300 text-red-800 px-5 py-3 rounded-2xl shadow-sm animate-fade-in flex items-center gap-2">
                <i class="fas fa-times-circle"></i> <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- FORMULARIO -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Selección de Ruta -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Ruta (opcional)</label>
                <select wire:model="ruta_id"
                    class="w-full px-4 py-2 rounded-xl border @error('ruta_id') border-red-500 @else border-gray-300 @enderror dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500">
                    <option value="">-- Gasto Administrativo (sin ruta) --</option>
                    @foreach ($rutas as $ruta)
                        <option value="{{ $ruta->id }}">{{ $ruta->ruta }}</option>
                    @endforeach
                </select>
                @error('ruta_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Selección de Tipo de Gasto -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Gasto *</label>
                <select wire:model="tipo_gasto_id"
                    class="w-full px-4 py-2 rounded-xl border @error('tipo_gasto_id') border-red-500 @else border-gray-300 @enderror dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500">
                    <option value="">-- Selecciona Tipo de Gasto --</option>
                    @foreach ($tiposGasto as $tipo)
                        <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                    @endforeach
                </select>
                @error('tipo_gasto_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <!-- Monto -->
            <div class="space-y-1">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Monto *</label>
                <input type="number" wire:model="monto" step="0.01"
                    class="w-full px-4 py-2 rounded-xl border @error('monto') border-red-500 @else border-gray-300 @enderror dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500"
                    placeholder="0.00">
                @error('monto') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Observación -->
        <div class="space-y-1">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Observación</label>
            <textarea wire:model="observacion" rows="3"
                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-500"
                placeholder="Detalles del gasto..."></textarea>
        </div>

        <!-- BOTONES -->
        <div class="flex justify-between items-center pt-6">
            <button type="reset" wire:click="$refresh"
                class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-full shadow-md">
                <i class="fas fa-times mr-1"></i> Cancelar
            </button>

            <button type="submit"
                class="px-6 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-full shadow-md"
                wire:loading.attr="disabled">
                <i class="fas fa-save mr-1"></i>
                <span wire:loading.remove>Guardar Gasto</span>
                <span wire:loading>Guardando...</span>
            </button>
        </div>

        <!-- FILTRO -->
        <div class="flex justify-end">
            <select wire:model="filtroTipo" class="px-4 py-2 rounded-xl border border-gray-300 dark:bg-gray-800 dark:text-white mb-4">
                <option value="todos">Todos</option>
                <option value="ruta">Solo con Ruta</option>
                <option value="admin">Solo Administrativos</option>
            </select>
        </div>

        <!-- HISTORIAL -->
        <table class="min-w-full bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-900 text-sm text-gray-700 dark:text-gray-300 rounded-2xl">
            <thead class="bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-white text-xs uppercase tracking-wider">
                <tr>
                    <th class="p-4 text-left"><i class="fas fa-calendar-alt"></i> Fecha</th>
                    <th class="p-4 text-left"><i class="fas fa-road"></i> Ruta / Área</th>
                    <th class="p-4 text-left"><i class="fas fa-dollar-sign"></i> Monto</th>
                    <th class="p-4 text-left"><i class="fas fa-file-alt"></i> Tipo</th>
                    <th class="p-4 text-left"><i class="fas fa-comment-dots"></i> Observación</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gastosFiltrados as $gasto)
                    <tr class="border-t dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-gray-700 transition">
                        <td class="p-4">{{ $gasto->created_at->format('Y-m-d') }}</td>
                        <td class="p-4">{{ $gasto->ruta->ruta ?? 'Administración' }}</td>
                        <td class="p-4">${{ number_format($gasto->monto, 2) }}</td>
                        <td class="p-4">{{ $gasto->tipoGasto->nombre ?? '—' }}</td>
                        <td class="p-4">{{ $gasto->observacion }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-500 dark:text-gray-400 italic">
                            <i class="fas fa-info-circle"></i> No hay gastos registrados con el filtro seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</form>
