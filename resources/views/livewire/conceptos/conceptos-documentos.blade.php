{{-- resources/views/livewire/conceptos/index.blade.php --}}
<div class="p-4 md:p-6 lg:p-8 space-y-6 min-h-screen bg-gray-50 dark:bg-gray-900" x-data>
  {{-- ================= Header / Filtros ================= --}}
  <section class="bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
      <div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Conceptos de Documentos</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Administra conceptos y sus cuentas contables.
        </p>
      </div>

      <div class="flex flex-wrap items-end gap-3">
        <div class="flex-1 min-w-[200px]">
          <label class="block text-xs font-semibold mb-2 text-gray-700 dark:text-gray-300">Buscar</label>
          <input type="text"
                 placeholder="código / nombre / descripción"
                 wire:model.debounce.300ms="search"
                 class="w-full h-10 px-3 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-gray-400">
        </div>

        <label class="inline-flex items-center gap-2 mt-6 md:mt-0">
          <input type="checkbox" wire:model="soloActivos" class="rounded border-gray-300 text-gray-700 focus:ring-gray-400">
          <span class="text-sm text-gray-700 dark:text-gray-300">Solo activos</span>
        </label>

        <button type="button"
                wire:click="crear"
                class="h-10 px-4 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">
          <i class="fa-solid fa-plus mr-2"></i> Nuevo Concepto
        </button>
      </div>
    </div>
  </section>

  {{-- ================= Listado ================= --}}
  <section class="bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
          <tr>
            <th class="px-6 py-3 text-left font-medium">Código</th>
            <th class="px-6 py-3 text-left font-medium">Nombre</th>
            <th class="px-6 py-3 text-left font-medium">Tipo</th>
            <th class="px-6 py-3 text-left font-medium">Estado</th>
            <th class="px-6 py-3 text-left font-medium">Cuentas asociadas</th>
            <th class="px-6 py-3 text-right font-medium">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($conceptos as $c)
            <tr wire:key="row-{{ $c->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800">
              <td class="px-6 py-3">
                <span class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $c->codigo }}</span>
              </td>
              <td class="px-6 py-3 text-gray-900 dark:text-white">{{ $c->nombre }}</td>
              <td class="px-6 py-3 capitalize text-gray-700 dark:text-gray-300">{{ $c->tipo }}</td>
              <td class="px-6 py-3">
                @if($c->activo)
                  <span class="text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Activo</span>
                @else
                  <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">Inactivo</span>
                @endif
              </td>
              <td class="px-6 py-3">
                @php
                  $tot = $c->cuentas()->count();
                  $items = $c->cuentas()->limit(3)->get();
                @endphp
                <div class="flex flex-wrap gap-2">
                  @foreach($items as $pc)
                    <span class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                      <span class="font-mono">{{ $pc->codigo }}</span>
                      @if($pc->pivot?->rol)
                        <span class="opacity-70">· {{ $pc->pivot->rol }}</span>
                      @endif
                    </span>
                  @endforeach
                  @if($tot > 3)
                    <span class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">+ {{ $tot - 3 }} más</span>
                  @endif
                </div>
              </td>
              <td class="px-6 py-3 text-right">
                <div class="inline-flex gap-2">
                  <button class="h-9 px-3 rounded-md border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                          wire:click="editar({{ $c->id }})">
                    Editar
                  </button>
                  <button class="h-9 px-3 rounded-md bg-rose-600 hover:bg-rose-700 text-white"
                          onclick="if(confirm('¿Eliminar concepto \"{{ $c->nombre }}\"?')) @this.eliminar({{ $c->id }})">
                    Eliminar
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td class="px-6 py-12 text-center text-gray-500 dark:text-gray-400" colspan="6">
                Sin registros
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
      {{ $conceptos->links() }}
    </div>
  </section>

  {{-- ================= Flash ================= --}}
  @if (session()->has('ok'))
    <div class="p-3 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800">
      {{ session('ok') }}
    </div>
  @endif

  {{-- ================= Modal (simple y sobrio) ================= --}}
  @if($showModal)
    <div
      class="fixed inset-0 z-50 flex items-end md:items-center justify-center p-4"
      x-cloak
      @keydown.escape.window="$wire.showModal=false"
      x-init="
        document.body.classList.add('overflow-hidden');
        $watch('$wire.showModal', v => { if(!v) document.body.classList.remove('overflow-hidden') });
      "
    >
      <div class="absolute inset-0 bg-black/40" wire:click="$set('showModal', false)"></div>

      <div class="relative w-full md:max-w-5xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg">
        <div class="flex items-start justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ $concepto_id ? 'Editar concepto' : 'Nuevo concepto' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Completa los datos</p>
          </div>
          <button class="h-9 w-9 inline-flex items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                  wire:click="$set('showModal', false)">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[70vh] overflow-y-auto">
          {{-- Formulario izquierda --}}
          <section class="space-y-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Información básica</h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Código</label>
                <input type="text" wire:model.defer="codigo"
                       class="w-full h-10 px-3 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-gray-400">
                @error('codigo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
              <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nombre</label>
                <input type="text" wire:model.defer="nombre"
                       class="w-full h-10 px-3 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-gray-400">
                @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Tipo</label>
                <select wire:model.defer="tipo"
                        class="w-full h-10 px-3 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-gray-400">
                  <option value="entrada">Entrada</option>
                  <option value="salida">Salida</option>
                  <option value="ajuste">Ajuste</option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Descripción</label>
              <textarea rows="3" wire:model.defer="descripcion"
                        class="w-full px-3 py-2 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-gray-400 resize-none"></textarea>
            </div>

            <label class="inline-flex items-center gap-2 select-none">
              <input type="checkbox" wire:model.defer="activo"
                     class="rounded border-gray-300 text-gray-700 focus:ring-gray-400">
              <span class="text-sm text-gray-700 dark:text-gray-300">Concepto activo</span>
            </label>
          </section>

          {{-- Catálogo de cuentas derecha --}}
          <section class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Cuentas contables asociadas</h4>
              <span class="text-xs text-gray-500">{{ count($cuentasSeleccionadas) }} seleccionadas</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buscar cuenta</label>
                <input type="text" wire:model.debounce.250ms="buscarCuenta"
                       placeholder="Código o nombre"
                       class="w-full h-10 px-3 rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-1 focus:ring-gray-400">
              </div>

              <div class="flex items-end">
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" wire:model="soloImputables"
                         class="rounded border-gray-300 text-gray-700 focus:ring-gray-400">
                  <span class="text-sm text-gray-700 dark:text-gray-300">Solo imputables</span>
                </label>
              </div>
            </div>

            <div class="border border-gray-200 dark:border-gray-700 rounded-md overflow-hidden">
              <div class="max-h-[48vh] overflow-y-auto">
                <table class="min-w-full text-sm">
                  <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 sticky top-0 z-10">
                    <tr>
                      <th class="px-3 py-2 text-left font-medium">Cuenta</th>
                      <th class="px-3 py-2 text-center font-medium w-28">Seleccionar</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($cuentasCatalogo as $pc)
                      @php $sel = isset($cuentasSeleccionadas[$pc->id]); @endphp
                      <tr wire:key="pc-{{ $pc->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-3 py-2">
                          <div class="flex items-center gap-3">
                            <div class="text-xs font-mono text-gray-600 dark:text-gray-300">{{ $pc->codigo }}</div>
                            <div class="text-gray-800 dark:text-gray-200">{{ $pc->nombre }}</div>
                            @if(!empty($pc->titulo))
                              <span class="ml-2 text-[11px] text-amber-600">(título)</span>
                            @endif
                          </div>
                        </td>
                        <td class="px-3 py-2 text-center">
                          <input type="checkbox"
                                 @if($sel) checked @endif
                                 wire:click="toggleCuenta({{ $pc->id }})"
                                 class="rounded border-gray-300 text-gray-700 focus:ring-gray-400"
                                 @if(!empty($pc->titulo)) disabled @endif>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td class="px-3 py-10 text-center text-gray-500 dark:text-gray-400" colspan="2">
                          No hay cuentas para mostrar.
                        </td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
          <button class="h-10 px-4 rounded-md border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
                  wire:click="$set('showModal', false)">
            Cancelar
          </button>
          <button class="h-10 px-4 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white"
                  wire:click="guardar">
            Guardar Concepto
          </button>
        </div>
      </div>
    </div>
  @endif
</div>
