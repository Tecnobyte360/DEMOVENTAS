{{-- resources/views/livewire/inventario/conceptos-documentos.blade.php --}}

@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>[x-cloak]{display:none!important}</style>
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Cargar Alpine DESPUÉS de Livewire para evitar choques de init
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:load', alpineInit);
      };
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div class="p-6 space-y-6" x-data>
  {{-- ================= Header / Filtros ================= --}}
  <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Conceptos de Documentos</h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">
          Administra conceptos para entradas/salidas/ajustes y asocia sus cuentas contables.
        </p>
      </div>

      <div class="flex flex-wrap items-end gap-3">
        <div>
          <label class="block text-xs font-semibold mb-1">Buscar</label>
          <input type="text"
                 placeholder="código / nombre / descripción"
                 wire:model.debounce.300ms="search"
                 class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
        </div>

        <div>
          <label class="block text-xs font-semibold mb-1">Tipo</label>
          <select wire:model="tipoFiltro"
                  class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
            <option value="">— Todos —</option>
            <option value="entrada">Entrada</option>
            <option value="salida">Salida</option>
            <option value="ajuste">Ajuste</option>
          </select>
        </div>

        <label class="inline-flex items-center gap-2 mt-6 md:mt-0">
          <input type="checkbox" wire:model="soloActivos" class="rounded">
          <span class="text-sm text-gray-700 dark:text-gray-300">Solo activos</span>
        </label>

        <button type="button"
                wire:click="crear"
                class="h-10 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white shadow">
          <i class="fa-solid fa-plus mr-1"></i> Nuevo
        </button>
      </div>
    </div>
  </section>

  {{-- ================= Listado ================= --}}
  <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
        <tr>
          <th class="px-4 py-3 text-left">Código</th>
          <th class="px-4 py-3 text-left">Nombre</th>
          <th class="px-4 py-3 text-left">Tipo</th>
          <th class="px-4 py-3 text-left">Estado</th>
          <th class="px-4 py-3 text-left">Cuentas asociadas</th>
          <th class="px-4 py-3 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($conceptos as $c)
          <tr wire:key="row-{{ $c->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td class="px-4 py-3 font-mono">{{ $c->codigo }}</td>
            <td class="px-4 py-3">{{ $c->nombre }}</td>
            <td class="px-4 py-3 capitalize">{{ $c->tipo }}</td>
            <td class="px-4 py-3">
              @if($c->activo)
                <span class="inline-flex items-center px-2 py-1 rounded bg-emerald-100 text-emerald-700">Activo</span>
              @else
                <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-700">Inactivo</span>
              @endif
            </td>
            <td class="px-4 py-3">
              @php
                $tot = $c->cuentas()->count();
                $items = $c->cuentas()->limit(3)->get();
              @endphp
              @foreach($items as $pc)
                <span class="inline-flex items-center gap-1 mr-1 mb-1 px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800">
                  <span class="font-mono">{{ $pc->codigo }}</span>
                  @if($pc->pivot->rol)
                    <span class="opacity-70">· {{ $pc->pivot->rol }}</span>
                  @endif
                </span>
              @endforeach
              @if($tot > 3)
                <span class="text-xs text-gray-500">+ {{ $tot - 3 }} más</span>
              @endif
            </td>
            <td class="px-4 py-3 text-right">
              <div class="inline-flex items-center gap-2">
                <button class="h-9 px-3 rounded-xl bg-gray-100 dark:bg-gray-800"
                        wire:click="editar({{ $c->id }})">
                  Editar
                </button>
                <button class="h-9 px-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white"
                        onclick="if(confirm('¿Eliminar concepto \"{{ $c->nombre }}\"?')) @this.eliminar({{ $c->id }})">
                  Eliminar
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td class="px-4 py-8 text-center text-gray-500 dark:text-gray-400" colspan="6">
              Sin registros.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="p-4">
      {{ $conceptos->links() }}
    </div>
  </section>

  {{-- ================= Flash ================= --}}
  @if (session()->has('ok'))
    <div class="p-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
      {{ session('ok') }}
    </div>
  @endif

  {{-- ================= Modal ================= --}}
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
      {{-- Backdrop --}}
      <div class="absolute inset-0 bg-black/40" wire:click="$set('showModal', false)"></div>

      {{-- Dialog --}}
      <div class="relative w-full md:max-w-5xl rounded-3xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl
                  p-6 space-y-6 max-h-[85vh] overflow-hidden">

        {{-- Header --}}
        <header class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $concepto_id ? 'Editar concepto' : 'Nuevo concepto' }}
          </h3>
          <button class="h-9 px-3 rounded-xl bg-gray-100 dark:bg-gray-800" wire:click="$set('showModal', false)">
            Cerrar
          </button>
        </header>

        {{-- Cuerpo scrollable (form + selector) --}}
        <div class="space-y-6 overflow-y-auto pr-2 max-h-[70vh]">
          {{-- ===== Formulario del concepto ===== --}}
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-semibold mb-1">Código</label>
              <input type="text" wire:model.defer="codigo"
                     class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
              @error('codigo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
              <label class="block text-xs font-semibold mb-1">Nombre</label>
              <input type="text" wire:model.defer="nombre"
                     class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
              @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
              <label class="block text-xs font-semibold mb-1">Tipo</label>
              <select wire:model.defer="tipo"
                      class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
                <option value="entrada">Entrada</option>
                <option value="salida">Salida</option>
                <option value="ajuste">Ajuste</option>
              </select>
            </div>

            <div class="md:col-span-4">
              <label class="block text-xs font-semibold mb-1">Descripción</label>
              <textarea rows="2" wire:model.defer="descripcion"
                        class="w-full px-3 py-2 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white"></textarea>
            </div>

            <label class="inline-flex items-center gap-2">
              <input type="checkbox" wire:model.defer="activo" class="rounded">
              <span class="text-sm">Activo</span>
            </label>
          </div>

          {{-- ===== Selector de cuentas ===== --}}
          <section class="space-y-3">
            <h4 class="font-semibold text-gray-900 dark:text-white">Cuentas contables asociadas</h4>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
              <div class="md:col-span-2">
                <label class="block text-xs font-semibold mb-1">Buscar cuenta</label>
                <input type="text" wire:model.debounce.250ms="buscarCuenta"
                       placeholder="Código o nombre de la cuenta"
                       class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
              </div>
            </div>

            {{-- Contenedor de tabla con scroll propio y encabezado fijo --}}
            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-x-auto">
              <div class="max-h-[55vh] overflow-y-auto">
                <table class="min-w-full text-xs md:text-sm">
                  <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 sticky top-0 z-10">
                    <tr>
                      <th class="px-3 py-2 text-left">Cuenta</th>
                      <th class="px-3 py-2 text-center">Seleccionar.</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($cuentasCatalogo as $pc)
                      @php $sel = isset($cuentasSeleccionadas[$pc->id]); @endphp
                      <tr wire:key="pc-{{ $pc->id }}" class="{{ $sel ? 'bg-indigo-50/50 dark:bg-indigo-900/20' : '' }}">
                        <td class="px-3 py-2">
                          <div class="font-mono">{{ $pc->codigo }}</div>
                          <div class="text-gray-600 dark:text-gray-300">{{ $pc->nombre }}</div>
                        </td>

                       

                
                        <td class="px-3 py-2 text-center">
                          <input type="checkbox" @if($sel) checked @endif
                                 wire:click="toggleCuenta({{ $pc->id }})">
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td class="px-3 py-6 text-center text-gray-500" colspan="6">No hay cuentas para mostrar.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </section>
        </div>

        {{-- Footer --}}
        <footer class="flex items-center justify-end gap-2">
          <button class="h-10 px-4 rounded-xl bg-gray-100 dark:bg-gray-800"
                  wire:click="$set('showModal', false)">Cancelar</button>
          <button class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white"
                  wire:click="guardar">Guardar</button>
        </footer>
      </div>
    </div>
  @endif
</div>
