{{-- resources/views/livewire/conceptos/conceptos-documentos.blade.php --}}
<div class="p-7 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-10">

  {{-- ===================== Filtros / Header ===================== --}}
  <section class="space-y-6 p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Conceptos de documentos</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
          Administra conceptos para <strong>entradas / salidas / ajustes</strong> y asocia sus cuentas contables.
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 text-sm">
          <label class="text-gray-700 dark:text-gray-300">Solo activos</label>
          <input type="checkbox" wire:model="soloActivos" class="rounded text-violet-600 focus:ring-violet-600">
        </div>
        <div>
          <select wire:model="tipoFiltro"
                  class="h-10 px-3 rounded-xl border bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-violet-600">
            <option value="">— Todos los tipos —</option>
            <option value="entrada">Entrada</option>
            <option value="salida">Salida</option>
            <option value="ajuste">Ajuste</option>
          </select>
        </div>
        <div class="relative">
          <input type="text" wire:model.defer="search" placeholder="Buscar (código / nombre / descripción)"
                 class="w-72 h-10 pl-3 pr-9 rounded-xl border bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-violet-600">
          <button wire:click="$refresh" class="absolute right-2 top-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-300">
            <i class="fas fa-search"></i>
          </button>
        </div>
        <button type="button" wire:click="crear"
                class="h-10 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white font-medium shadow">
          <i class="fas fa-plus mr-2"></i> Nuevo concepto
        </button>
      </div>
    </header>
  </section>

  {{-- ===================== Listado ===================== --}}
  <section class="space-y-6 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
    <div class="w-full overflow-x-auto">
      <table class="min-w-[1100px] w-full bg-white dark:bg-gray-900 rounded-xl overflow-hidden text-sm">
        <thead class="bg-violet-600 text-white">
          <tr>
            <th class="p-3 text-left font-semibold">Código</th>
            <th class="p-3 text-left font-semibold">Nombre</th>
            <th class="p-3 text-left font-semibold">Tipo</th>
            <th class="p-3 text-left font-semibold">Descripción</th>
            <th class="p-3 text-center font-semibold">Estado</th>
            <th class="p-3 text-center font-semibold">Cuentas</th>
            <th class="p-3 text-center font-semibold">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @forelse($conceptos as $c)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
              <td class="p-3 font-mono">{{ $c->codigo }}</td>
              <td class="p-3">{{ $c->nombre }}</td>
              <td class="p-3 capitalize">
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
                  @if($c->tipo==='entrada') bg-emerald-100 text-emerald-700
                  @elseif($c->tipo==='salida') bg-rose-100 text-rose-700
                  @else bg-amber-100 text-amber-700 @endif">
                  {{ $c->tipo }}
                </span>
              </td>
              <td class="p-3 text-gray-700 dark:text-gray-300">{{ $c->descripcion }}</td>
              <td class="p-3 text-center">
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full {{ $c->activo ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                  @if($c->activo) <i class="fas fa-check-circle"></i> Activo @else <i class="fas fa-ban"></i> Inactivo @endif
                </span>
              </td>
              <td class="p-3 text-center">
                @php $tot = $c->cuentas()->count(); @endphp
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $tot }}</span>
              </td>
              <td class="p-3 text-center space-x-2">
                <button wire:click="editar({{ $c->id }})" class="text-blue-600 hover:text-blue-800" title="Editar">
                  <i class="fas fa-edit"></i>
                </button>
                <button onclick="if(confirm('¿Eliminar concepto \"{{ $c->nombre }}\"?')) @this.eliminar({{ $c->id }})"
                        class="text-red-600 hover:text-red-800" title="Eliminar">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="p-6 text-center text-gray-500 dark:text-gray-400 italic">Sin registros</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div>{{ $conceptos->links() }}</div>
  </section>

  {{-- ===================== Flash ===================== --}}
  @if (session()->has('ok'))
    <div class="px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 shadow">
      <i class="fas fa-check-circle mr-2"></i>{{ session('ok') }}
    </div>
  @endif

  {{-- ===================== MODAL: Crear / Editar ===================== --}}
  @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/60" wire:click="$set('showModal', false)"></div>

      <div class="relative w-full max-w-6xl bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <h4 class="text-lg font-semibold text-gray-800 dark:text-white">
            {{ $concepto_id ? 'Editar concepto' : 'Nuevo concepto' }}
          </h4>
          <button class="text-gray-500 hover:text-gray-800 dark:hover:text-gray-200" wire:click="$set('showModal', false)">
            <i class="fas fa-times"></i>
          </button>
        </div>

        {{-- Body --}}
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[70vh] overflow-y-auto">
          {{-- ====== Form ====== --}}
          <section class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Código *</label>
              <input type="text" wire:model.defer="codigo"
                     class="mt-1 w-full px-3 py-2 rounded-xl border bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-violet-600
                     @error('codigo') border-red-500 @enderror">
              @error('codigo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nombre *</label>
              <input type="text" wire:model.defer="nombre"
                     class="mt-1 w-full px-3 py-2 rounded-xl border bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-violet-600
                     @error('nombre') border-red-500 @enderror">
              @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tipo *</label>
                <select wire:model.defer="tipo"
                        class="mt-1 w-full px-3 py-2 rounded-xl border bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-violet-600">
                  <option value="entrada">Entrada</option>
                  <option value="salida">Salida</option>
                  <option value="ajuste">Ajuste</option>
                </select>
              </div>
              <label class="flex items-center gap-2 mt-6">
                <input type="checkbox" wire:model.defer="activo" class="rounded text-violet-600 focus:ring-violet-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">Concepto activo</span>
              </label>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Descripción</label>
              <textarea rows="4" wire:model.defer="descripcion"
                        class="mt-1 w-full px-3 py-2 rounded-xl border bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-violet-600"></textarea>
            </div>
          </section>

          {{-- ====== Selector de cuentas ====== --}}
          <section class="space-y-4">
            <div class="flex items-center justify-between">
              <h5 class="font-semibold text-gray-800 dark:text-gray-100">Cuentas contables asociadas</h5>
              <span class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                {{ count($cuentasSeleccionadas) }} seleccionadas
              </span>
            </div>

            <div class="flex items-center gap-3">
              <input type="text" wire:model.debounce.300ms="buscarCuenta" placeholder="Buscar cuenta (código o nombre)"
                     class="w-full h-10 px-3 rounded-xl border bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-violet-600">
              <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="soloImputables" class="rounded text-violet-600 focus:ring-violet-600">
                <span class="text-gray-700 dark:text-gray-300">Solo imputables</span>
              </label>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div class="max-h-[38vh] overflow-y-auto">
                <table class="min-w-full text-sm">
                  <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 sticky top-0">
                    <tr>
                      <th class="px-3 py-2 text-left font-semibold">Cuenta</th>
                      <th class="px-3 py-2 text-center font-semibold w-28">Seleccionar</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($cuentasCatalogo as $pc)
                      @php $sel = isset($cuentasSeleccionadas[$pc->id]); @endphp
                      <tr class="{{ $sel ? 'bg-indigo-50 dark:bg-indigo-900/20' : 'bg-white dark:bg-gray-900' }}">
                        <td class="px-3 py-2">
                          <div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $pc->codigo }}</div>
                          <div class="text-gray-700 dark:text-gray-300">{{ $pc->nombre }}</div>
                          @if($pc->titulo)
                            <div class="text-[11px] text-orange-600">título</div>
                          @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                          <input type="checkbox"
                                 @if($sel) checked @endif
                                 wire:click="toggleCuenta({{ $pc->id }})"
                                 class="w-5 h-5 rounded text-indigo-600 focus:ring-indigo-600">
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="2" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">Sin resultados</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
          <button type="button" wire:click="$set('showModal', false)"
                  class="px-4 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200">
            Cancelar
          </button>
          <button type="button" wire:click="guardar"
                  class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white">
            Guardar concepto
          </button>
        </div>
      </div>
    </div>
  @endif

</div>
