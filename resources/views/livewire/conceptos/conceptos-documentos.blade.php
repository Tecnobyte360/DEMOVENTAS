<div class="p-4 md:p-6 lg:p-8 space-y-6 min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-950 dark:to-gray-900" x-data>
  {{-- ================= Header / Filtros ================= --}}
  <section class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 p-6 md:p-8 backdrop-blur-sm transition-all duration-300 hover:shadow-3xl">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
      <div class="space-y-2">
        <div class="flex items-center gap-3">
          <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg">
            <i class="fa-solid fa-file-invoice text-white text-xl"></i>
          </div>
          <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
              Conceptos de Documentos
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
              Administra conceptos para entradas/salidas/ajustes y asocia sus cuentas contables
            </p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-xs font-semibold mb-2 text-gray-700 dark:text-gray-300">Buscar</label>
          <div class="relative">
            <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text"
                   placeholder="código / nombre / descripción"
                   wire:model.debounce.300ms="search"
                   class="w-full h-11 pl-10 pr-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-violet-500 dark:focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 transition-all duration-200">
          </div>
        </div>

      

        <label class="inline-flex items-center gap-2 mt-6 md:mt-0 px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200 cursor-pointer">
          <input type="checkbox" wire:model="soloActivos" class="rounded text-violet-600 focus:ring-violet-500">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Solo activos</span>
        </label>

        <button type="button"
                wire:click="crear"
                class="h-11 px-5 rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 hover:from-violet-700 hover:to-purple-700 text-white font-semibold shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
          <i class="fa-solid fa-plus mr-2"></i> Nuevo Concepto
        </button>
      </div>
    </div>
  </section>

  {{-- ================= Listado ================= --}}
  <section class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200/50 dark:border-gray-700/50 overflow-hidden transition-all duration-300 hover:shadow-3xl">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-850 text-gray-700 dark:text-gray-300">
          <tr>
            <th class="px-6 py-4 text-left font-semibold">Código</th>
            <th class="px-6 py-4 text-left font-semibold">Nombre</th>
            <th class="px-6 py-4 text-left font-semibold">Tipo</th>
            <th class="px-6 py-4 text-left font-semibold">Estado</th>
            <th class="px-6 py-4 text-left font-semibold">Cuentas asociadas</th>
            <th class="px-6 py-4 text-right font-semibold">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($conceptos as $c)
            <tr wire:key="row-{{ $c->id }}" class="hover:bg-gradient-to-r hover:from-violet-50/50 hover:to-purple-50/50 dark:hover:from-violet-900/10 dark:hover:to-purple-900/10 transition-all duration-200">
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 font-mono text-xs font-semibold text-gray-700 dark:text-gray-300">
                  {{ $c->codigo }}
                </span>
              </td>
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $c->nombre }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold capitalize
                  {{ $c->tipo === 'entrada' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : '' }}
                  {{ $c->tipo === 'salida' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400' : '' }}
                  {{ $c->tipo === 'ajuste' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}">
                  {{ $c->tipo }}
                </span>
              </td>
              <td class="px-6 py-4">
                @if($c->activo)
                  <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 font-medium text-xs">
                    <i class="fa-solid fa-circle-check"></i> Activo
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 font-medium text-xs">
                    <i class="fa-solid fa-circle-xmark"></i> Inactivo
                  </span>
                @endif
              </td>
              <td class="px-6 py-4">
                <div class="flex flex-wrap gap-2">
                  @php
                    $tot = $c->cuentas()->count();
                    $items = $c->cuentas()->limit(3)->get();
                  @endphp
                  @foreach($items as $pc)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gradient-to-r from-indigo-50 to-indigo-100 text-indigo-700 dark:from-indigo-900/30 dark:to-indigo-800/30 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-xs font-medium">
                      <i class="fa-solid fa-hashtag text-[10px]"></i>
                      <span class="font-mono">{{ $pc->codigo }}</span>
                      @if($pc->pivot->rol)
                        <span class="opacity-70">· {{ $pc->pivot->rol }}</span>
                      @endif
                    </span>
                  @endforeach
                  @if($tot > 3)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs font-medium">
                      + {{ $tot - 3 }} más
                    </span>
                  @endif
                </div>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <button class="h-9 px-4 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-all duration-200 hover:scale-105"
                          wire:click="editar({{ $c->id }})">
                    <i class="fa-solid fa-pen-to-square mr-1"></i> Editar
                  </button>
                  <button class="h-9 px-4 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-medium transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg"
                          onclick="if(confirm('¿Eliminar concepto \"{{ $c->nombre }}\"?')) @this.eliminar({{ $c->id }})">
                    <i class="fa-solid fa-trash mr-1"></i> Eliminar
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td class="px-6 py-16 text-center" colspan="6">
                <div class="flex flex-col items-center justify-center space-y-3">
                  <div class="h-16 w-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <i class="fa-solid fa-inbox text-3xl text-gray-400"></i>
                  </div>
                  <p class="text-gray-500 dark:text-gray-400 font-medium">Sin registros</p>
                  <p class="text-sm text-gray-400 dark:text-gray-500">Crea tu primer concepto para comenzar</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
      {{ $conceptos->links() }}
    </div>
  </section>

  {{-- ================= Flash ================= --}}
  @if (session()->has('ok'))
    <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 shadow-lg animate-fade-in">
      <div class="flex items-center gap-3">
        <i class="fa-solid fa-circle-check text-xl"></i>
        <span class="font-medium">{{ session('ok') }}</span>
      </div>
    </div>
  @endif

  {{-- ================= Modal ================= --}}
  @if($showModal)
    <div
      class="fixed inset-0 z-50 flex items-end md:items-center justify-center p-4 animate-fade-in"
      x-cloak
      @keydown.escape.window="$wire.showModal=false"
      x-init="
        document.body.classList.add('overflow-hidden');
        $watch('$wire.showModal', v => { if(!v) document.body.classList.remove('overflow-hidden') });
      "
    >
      {{-- Backdrop --}}
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="$set('showModal', false)"></div>

      {{-- Dialog --}}
      <div class="relative w-full md:max-w-6xl rounded-3xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl
                  p-8 space-y-6 max-h-[90vh] overflow-hidden animate-slide-up">

        {{-- Header --}}
        <header class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg">
              <i class="fa-solid fa-file-invoice text-white text-xl"></i>
            </div>
            <div>
              <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $concepto_id ? 'Editar concepto' : 'Nuevo concepto' }}
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">
                {{ $concepto_id ? 'Modifica los datos del concepto' : 'Completa los datos para crear un nuevo concepto' }}
              </p>
            </div>
          </div>
          <button class="h-10 w-10 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200" 
                  wire:click="$set('showModal', false)">
            <i class="fa-solid fa-times"></i>
          </button>
        </header>

        {{-- Cuerpo scrollable (form + selector) --}}
        <div class="space-y-8 overflow-y-auto pr-2 max-h-[calc(90vh-250px)]">
          {{-- ===== Formulario del concepto ===== --}}
          <div class="space-y-6">
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
              <i class="fa-solid fa-circle-info text-violet-600"></i>
              <span>Información básica</span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Código</label>
                <input type="text" wire:model.defer="codigo"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-violet-500 dark:focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 transition-all duration-200">
                @error('codigo') <p class="text-xs text-red-600 mt-2 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</p> @enderror
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Nombre</label>
                <input type="text" wire:model.defer="nombre"
                       class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-violet-500 dark:focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 transition-all duration-200">
                @error('nombre') <p class="text-xs text-red-600 mt-2 flex items-center gap-1"><i class="fa-solid fa-circle-exclamation"></i>{{ $message }}</p> @enderror
              </div>

              <div>
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Tipo</label>
                <select wire:model.defer="tipo"
                        class="w-full h-11 px-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-violet-500 dark:focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 transition-all duration-200">
                  <option value="entrada">Entrada</option>
                  <option value="salida">Salida</option>
                  <option value="ajuste">Ajuste</option>
                </select>
              </div>

              <div class="md:col-span-4">
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Descripción</label>
                <textarea rows="3" wire:model.defer="descripcion"
                          class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-violet-500 dark:focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 transition-all duration-200 resize-none"></textarea>
              </div>

              <label class="inline-flex items-center gap-3 px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 cursor-pointer">
                <input type="checkbox" wire:model.defer="activo" class="rounded text-violet-600 focus:ring-violet-500 w-5 h-5">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Concepto activo</span>
              </label>
            </div>
          </div>

          {{-- ===== Selector de cuentas ===== --}}
          <section class="space-y-5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                <i class="fa-solid fa-link text-violet-600"></i>
                <span>Cuentas contables asociadas</span>
              </div>
              <span class="px-3 py-1 rounded-lg bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-400 text-xs font-semibold">
                {{ count($cuentasSeleccionadas) }} seleccionadas
              </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div class="md:col-span-2">
                <label class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">Buscar cuenta</label>
                <div class="relative">
                  <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                  <input type="text" wire:model.debounce.250ms="buscarCuenta"
                         placeholder="Código o nombre de la cuenta"
                         class="w-full h-11 pl-10 pr-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:border-violet-500 dark:focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 transition-all duration-200">
                </div>
              </div>
            </div>

            {{-- Contenedor de tabla con scroll propio y encabezado fijo --}}
            <div class="rounded-2xl border-2 border-gray-200 dark:border-gray-700 overflow-hidden shadow-inner">
              <div class="max-h-[40vh] overflow-y-auto">
                <table class="min-w-full text-sm">
                  <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-850 text-gray-700 dark:text-gray-300 sticky top-0 z-10 shadow-sm">
                    <tr>
                      <th class="px-4 py-3 text-left font-semibold">Cuenta</th>
                      <th class="px-4 py-3 text-center font-semibold w-32">Seleccionar</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($cuentasCatalogo as $pc)
                      @php $sel = isset($cuentasSeleccionadas[$pc->id]); @endphp
                      <tr wire:key="pc-{{ $pc->id }}" 
                          class="transition-all duration-200 {{ $sel ? 'bg-gradient-to-r from-indigo-50 to-indigo-100/50 dark:from-indigo-900/30 dark:to-indigo-800/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800/50' }}">
                        <td class="px-4 py-3">
                          <div class="flex items-center gap-3">
                            @if($sel)
                              <div class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                                <i class="fa-solid fa-check text-white text-sm"></i>
                              </div>
                            @else
                              <div class="h-8 w-8 rounded-lg bg-gray-100 dark:bg-gray-800"></div>
                            @endif
                            <div>
                              <div class="font-mono text-xs font-semibold text-gray-700 dark:text-gray-300 mb-0.5">{{ $pc->codigo }}</div>
                              <div class="text-gray-600 dark:text-gray-400">{{ $pc->nombre }}</div>
                            </div>
                          </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                          <input type="checkbox" 
                                 @if($sel) checked @endif
                                 wire:click="toggleCuenta({{ $pc->id }})"
                                 class="rounded text-indigo-600 focus:ring-indigo-500 w-5 h-5 cursor-pointer transition-all duration-200">
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td class="px-4 py-12 text-center" colspan="2">
                          <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                              <i class="fa-solid fa-search text-2xl text-gray-400"></i>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">No hay cuentas para mostrar</p>
                          </div>
                        </td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>

        {{-- Footer --}}
        <footer class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <button class="h-11 px-6 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold transition-all duration-200"
                  wire:click="$set('showModal', false)">
            <i class="fa-solid fa-times mr-2"></i> Cancelar
          </button>
          <button class="h-11 px-6 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-semibold shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
                  wire:click="guardar">
            <i class="fa-solid fa-check mr-2"></i> Guardar Concepto
          </button>
        </footer>
      </div>
    </div>
  @endif
</div>

