<div class="p-6">
  <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">

    {{-- ENCABEZADO --}}
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <i class="fas fa-balance-scale text-purple-600"></i>
          Normas de Reparto
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ $editId ? 'Edita la norma seleccionada y sus líneas de detalle.' : 'Crea una nueva norma y define sus líneas de reparto.' }}
        </p>
      </div>
      @if($editId)
        <span class="inline-flex items-center gap-2 text-xs px-3 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
          Modo edición
        </span>
      @endif
    </div>

    {{-- ================= FORMULARIO: CABECERA ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
      {{-- Código --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código</label>
        <input type="text" wire:model.defer="codigo" placeholder="NR-2025-01"
               class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                      shadow-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500
                      @error('codigo') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
        @error('codigo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
      </div>

      {{-- Descripción --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
        <input type="text" wire:model.defer="descripcion" placeholder="Ej. Reparto por centro de operaciones"
               class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                      shadow-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
      </div>

      {{-- Dimensión --}}
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Dimensión</label>
        <select wire:model="dimension"
                class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                       shadow-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
          <option value="CENTRO_DE_OPERACIONES">Centro de Operaciones</option>
          <option value="UNIDAD_DE_NEGOCIO">Unidad de Negocio</option>
          <option value="CENTRO_DE_COSTOS">Centro de Costos</option>
          <option value="OTRO">Otro</option>
        </select>
        @error('dimension') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
      </div>

      {{-- Fechas --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Válido desde</label>
          <input type="date" wire:model="valido_desde"
                 class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                        shadow-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
          @error('valido_desde') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Válido hasta</label>
          <input type="date" wire:model="valido_hasta"
                 class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                        shadow-sm px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
        </div>
      </div>
    </div>

    {{-- ================= SWITCHES ================= --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <label class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 p-3">
        <div>
          <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Activo</span>
          <p class="text-xs text-gray-500 dark:text-gray-400">Habilita la norma para su uso.</p>
        </div>
        <input type="checkbox" wire:model="valido"
               class="h-6 w-11 rounded-full bg-gray-200 dark:bg-gray-700 checked:bg-violet-600
                      focus:outline-none focus:ring-2 focus:ring-violet-500 transition">
      </label>

      <label class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 p-3">
        <div>
          <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Imputación directa</span>
          <p class="text-xs text-gray-500 dark:text-gray-400">No aplica prorrateo.</p>
        </div>
        <input type="checkbox" wire:model="imputacion_directa"
               class="h-6 w-11 rounded-full bg-gray-200 dark:bg-gray-700 checked:bg-violet-600
                      focus:outline-none focus:ring-2 focus:ring-violet-500 transition">
      </label>

      <label class="flex items-center justify-between rounded-xl border border-gray-200 dark:border-gray-700 p-3">
        <div>
          <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Importes fijos</span>
          <p class="text-xs text-gray-500 dark:text-gray-400">Asignación por valor fijo.</p>
        </div>
        <input type="checkbox" wire:model="asignar_importes_fijos"
               class="h-6 w-11 rounded-full bg-gray-200 dark:bg-gray-700 checked:bg-violet-600
                      focus:outline-none focus:ring-2 focus:ring-violet-500 transition">
      </label>
    </div>

    {{-- ================= DETALLES ================= --}}
    <div class="mt-6 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow p-4">
      <div class="flex items-center justify-between mb-3">
        <div>
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Detalles de la norma</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">Agrega los centros y su valor de reparto.</p>
        </div>
        <button type="button" wire:click="addLinea"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm">
          + Añadir línea
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <tr>
              <th class="px-4 py-2 text-left">Código centro <span class="text-red-500">*</span></th>
              <th class="px-4 py-2 text-left">Nombre centro</th>
              <th class="px-4 py-2 text-left">Valor (%) <span class="text-red-500">*</span></th>
              <th class="px-4 py-2"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($detalles as $i => $detalle)
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                {{-- Código centro --}}
                <td class="px-4 py-2">
                  <input type="text" wire:model.lazy="detalles.{{ $i }}.codigo_centro" placeholder="CEN-001"
                         class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                                shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500
                                @error('detalles.'.$i.'.codigo_centro') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                  @error('detalles.'.$i.'.codigo_centro')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                  @enderror
                </td>

                {{-- Nombre centro --}}
                <td class="px-4 py-2">
                  <input type="text" wire:model.lazy="detalles.{{ $i }}.nombre_centro" placeholder="Centro Operativo Norte"
                         class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                                shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                </td>

                {{-- Valor (%) --}}
                <td class="px-4 py-2">
                  <div class="relative">
                    <input type="number" step="0.01" min="0" wire:model.lazy="detalles.{{ $i }}.valor" placeholder="0.00"
                           class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                                  shadow-sm pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500
                                  @error('detalles.'.$i.'.valor') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                    <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-gray-400">%</span>
                  </div>
                  @error('detalles.'.$i.'.valor')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                  @enderror
                </td>

                {{-- Acciones por línea --}}
                <td class="px-4 py-2 text-right">
                  <button type="button" wire:click="removeLinea({{ $i }})"
                          class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white">
                    Quitar
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                  No hay líneas. Agrega la primera con “Añadir línea”.
                </td>
              </tr>
            @endforelse
          </tbody>

          {{-- Totalizador --}}
          @php $total = collect($detalles)->sum(fn($d) => (float)($d['valor'] ?? 0)); @endphp
          <tfoot>
            <tr class="bg-gray-50 dark:bg-gray-800/40">
              <td class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300" colspan="2">Total</td>
              <td class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ number_format($total, 2) }} %
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        Si trabajas por prorrateo porcentual, procura que el total sea 100%.
      </p>
    </div>

    {{-- ================= ACCIONES FORM ================= --}}
    <div class="flex items-center justify-end gap-2 mt-6">
      <button type="button" wire:click="resetForm"
              class="px-4 py-2 rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600">
        Cancelar
      </button>
      <button type="button" wire:click="save"
              class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white shadow">
        {{ $editId ? 'Actualizar' : 'Guardar' }}
      </button>
    </div>

    {{-- ================= LISTADO ================= --}}
    <div class="mt-8 overflow-x-auto">
      <table class="min-w-full text-xs text-gray-800 dark:text-gray-300">
        <thead class="bg-gray-100 dark:bg-gray-800 text-left uppercase tracking-wider">
          <tr>
            <th class="px-4 py-2">#</th>
            <th class="px-4 py-2">Código</th>
            <th class="px-4 py-2">Descripción</th>
            <th class="px-4 py-2">Dimensión</th>
            <th class="px-4 py-2">Desde</th>
            <th class="px-4 py-2">Hasta</th>
            <th class="px-4 py-2">Estado</th>
            <th class="px-4 py-2 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($normas as $i => $n)
            <tr>
              <td class="px-4 py-2">{{ $normas->firstItem() + $i }}</td>
              <td class="px-4 py-2 font-semibold">{{ $n->codigo }}</td>
              <td class="px-4 py-2">{{ $n->descripcion }}</td>
              <td class="px-4 py-2">{{ $n->dimension }}</td>
              <td class="px-4 py-2">{{ optional($n->valido_desde)->format('d/m/Y') }}</td>
              <td class="px-4 py-2">{{ optional($n->valido_hasta)->format('d/m/Y') }}</td>
              <td class="px-4 py-2">
                @if($n->valido)
                  <span class="inline-block bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-200 px-2 py-1 rounded-full">Vigente</span>
                @else
                  <span class="inline-block bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-2 py-1 rounded-full">Inactiva</span>
                @endif
              </td>
              <td class="px-4 py-2 text-right">
                <button wire:click="edit({{ $n->id }})"
                        class="py-1.5 px-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white">
                  Editar
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="px-4 py-6 text-center italic text-gray-500 dark:text-gray-400">
                No hay normas registradas.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="mt-4">
        {{ $normas->links() }}
      </div>
    </div>

  </div>
</div>
