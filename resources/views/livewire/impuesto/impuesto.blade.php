<div class="p-4 md:p-6" x-data="{ abrirTipos:false }">
  <div class="rounded-2xl shadow-xl border border-gray-200 dark:border-gray-800 overflow-hidden bg-white dark:bg-gray-900">

    {{-- ================= ENCABEZADO ================= --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
      <div>
        <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-percent text-purple-600"></i>
          Impuestos
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Gestiona los impuestos y su cuenta contable asociada.</p>
      </div>

      <div class="flex items-center gap-2">
        <button type="button"
                @click="abrirTipos = true"
                class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-700">
          <i class="fa-solid fa-layer-group mr-1.5"></i>
          Gestionar tipos
        </button>

        <button type="button" wire:click="openCreate"
                class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow">
          <i class="fa-solid fa-plus mr-1.5"></i>
          Nuevo impuesto
        </button>
      </div>
    </div>

    {{-- ================= FILTROS ================= --}}
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
        <input type="text" wire:model.debounce.400ms="q" placeholder="Buscar código o nombre…"
               class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">

        <select wire:model="filtroTipo"
                class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
          <option value="TODOS">Todos los tipos</option>
          @foreach($tipos as $t)
            <option value="{{ $t->id }}">{{ $t->nombre }}</option>
          @endforeach
        </select>

        <select wire:model="filtroAplica"
                class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
          <option value="TODOS">Ventas y Compras</option>
          <option value="VENTAS">Solo Ventas</option>
          <option value="COMPRAS">Solo Compras</option>
          <option value="AMBOS">Ambos</option>
        </select>

        <label class="inline-flex items-center gap-2 px-2">
          <input type="checkbox" wire:model="soloActivos" class="rounded">
          <span>Solo activos</span>
        </label>
      </div>
    </div>

    {{-- ================= TABLA ================= --}}
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
          <tr>
            <th class="px-3 py-2 text-left">Código</th>
            <th class="px-3 py-2 text-left">Nombre</th>
            <th class="px-3 py-2 text-left">Tipo</th>
            <th class="px-3 py-2 text-left">Aplica</th>
            <th class="px-3 py-2 text-right">Porcentaje</th>
            <th class="px-3 py-2 text-right">Monto fijo</th>
            <th class="px-3 py-2 text-left">Cuenta</th>
            <th class="px-3 py-2 text-center">Activo</th>
            <th class="px-3 py-2 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          @forelse($items as $i)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
              <td class="px-3 py-2 font-mono">{{ $i->codigo }}</td>
              <td class="px-3 py-2">{{ $i->nombre }}</td>
              <td class="px-3 py-2">{{ $i->tipo->nombre ?? '—' }}</td>
              <td class="px-3 py-2">{{ $i->aplica_sobre }}</td>
              <td class="px-3 py-2 text-right">
                {{ $i->porcentaje !== null ? number_format($i->porcentaje, 2).' %' : '—' }}
              </td>
              <td class="px-3 py-2 text-right">
                {{ $i->monto_fijo !== null ? '$'.number_format($i->monto_fijo, 2) : '—' }}
              </td>
              <td class="px-3 py-2 font-mono">{{ $i->cuenta?->codigo ?? '—' }}</td>
              <td class="px-3 py-2 text-center">
                @if($i->activo)
                  <i class="fa-solid fa-circle-check text-emerald-600"></i>
                @else
                  <i class="fa-regular fa-circle text-gray-400"></i>
                @endif
              </td>
              <td class="px-3 py-2 text-right">
                <button wire:click="openEdit({{ $i->id }})"
                        class="px-3 py-1.5 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white text-xs">
                  Editar
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="px-3 py-8">
                <div class="flex flex-col items-center justify-center text-center gap-2 text-gray-500 dark:text-gray-400">
                  <i class="fa-regular fa-folder-open text-2xl"></i>
                  <p class="text-sm">No se encontraron impuestos con los filtros aplicados.</p>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ================= MODAL CREAR/EDITAR ================= --}}
  @if($showModal)
    <div class="fixed inset-0 z-40 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/50" wire:click="$set('showModal', false)"></div>

      <div class="relative z-50 w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-xl">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
            {{ $editingId ? 'Editar impuesto' : 'Nuevo impuesto' }}
          </h3>
          <button class="text-gray-500 hover:text-gray-700" wire:click="$set('showModal', false)">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <form wire:submit.prevent="save" class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          {{-- Tipo --}}
          <div>
            <label class="block text-xs mb-1">Tipo</label>
            <select wire:model="tipo_id"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
              <option value="">— Seleccione —</option>
              @foreach($tipos as $t)
                <option value="{{ $t->id }}">{{ $t->nombre }}</option>
              @endforeach
            </select>
            @error('tipo_id') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
          </div>

          {{-- Código --}}
          <div>
            <label class="block text-xs mb-1">Código</label>
            <input type="text" wire:model.defer="codigo"
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
            @error('codigo') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
          </div>

          {{-- Nombre --}}
          <div class="md:col-span-2">
            <label class="block text-xs mb-1">Nombre</label>
            <input type="text" wire:model.defer="nombre"
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
            @error('nombre') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
          </div>

          {{-- Aplica sobre --}}
          <div>
            <label class="block text-xs mb-1">Aplica sobre</label>
            <select wire:model="aplica_sobre"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
              <option value="VENTAS">Ventas</option>
              <option value="COMPRAS">Compras</option>
              <option value="AMBOS">Ambos</option>
            </select>
          </div>

          {{-- Porcentaje --}}
          <div>
            <label class="block text-xs mb-1">Porcentaje (%)</label>
            <input type="number" step="0.01" wire:model.defer="porcentaje"
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
            @error('porcentaje') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
          </div>

          {{-- Monto fijo --}}
          <div>
            <label class="block text-xs mb-1">Monto fijo</label>
            <input type="number" step="0.01" wire:model.defer="monto_fijo"
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
            @error('monto_fijo') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
          </div>

          {{-- Cuenta contable --}}
          <div>
            <label class="block text-xs mb-1">Cuenta asociada</label>
            <select wire:model="cuenta_id"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
              <option value="">— Seleccione —</option>
              @foreach($cuentas as $c)
                <option value="{{ $c->id }}">{{ $c->codigo }} - {{ $c->nombre }}</option>
              @endforeach
            </select>
            @error('cuenta_id') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
          </div>

          {{-- Contracuenta --}}
          <div>
            <label class="block text-xs mb-1">Contracuenta</label>
            <select wire:model="contracuenta_id"
                    class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
              <option value="">— Ninguna —</option>
              @foreach($cuentas as $c)
                <option value="{{ $c->id }}">{{ $c->codigo }} - {{ $c->nombre }}</option>
              @endforeach
            </select>
          </div>

          {{-- Fechas --}}
          <div>
            <label class="block text-xs mb-1">Vigente desde</label>
            <input type="date" wire:model="vigente_desde"
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
          </div>
          <div>
            <label class="block text-xs mb-1">Vigente hasta</label>
            <input type="date" wire:model="vigente_hasta"
                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2">
          </div>

          {{-- Flags --}}
          <div class="md:col-span-2 grid grid-cols-2 gap-2 mt-2">
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" wire:model="activo" class="rounded">
              <span>Activo</span>
            </label>
            <label class="inline-flex items-center gap-2">
              <input type="checkbox" wire:model="incluido_en_precio" class="rounded">
              <span>Incluido en precio</span>
            </label>
          </div>

          {{-- Acciones --}}
          <div class="md:col-span-2 flex items-center justify-end gap-2 mt-3">
            <button type="button" wire:click="$set('showModal', false)"
                    class="px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600">
              Cancelar
            </button>
            <button type="submit"
                    class="px-4 py-2 rounded-md bg-indigo-600 hover:bg-indigo-700 text-white">
              {{ $editingId ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  @endif

  {{-- ================= SLIDE-OVER: TIPOS DE IMPUESTO ================= --}}
  <div x-cloak x-show="abrirTipos" @keydown.escape.window="abrirTipos = false"
       class="fixed inset-0 z-50 flex">
    <div class="w-full h-full bg-black/40" @click="abrirTipos = false"></div>
    <section class="relative ml-auto h-full w-full max-w-xl bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 shadow-2xl">
      <header class="sticky top-0 z-10 flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-800 bg-white/90 dark:bg-gray-900/90 backdrop-blur">
        <h3 class="font-semibold text-gray-800 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-layer-group text-indigo-600"></i>
          Tipos de impuesto
        </h3>
        <button class="text-gray-500 hover:text-gray-700" @click="abrirTipos=false">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </header>

      <div class="p-4">
        {{-- Render del componente hijo --}}
      <livewire:impuesto.tipos-impuestos />
      </div>
    </section>
  </div>

  {{-- Loading overlay --}}
  <div wire:loading.delay
       class="fixed inset-0 z-50 bg-black/20 backdrop-blur-sm flex items-center justify-center">
    <div class="px-4 py-2 rounded-lg bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-100 shadow">
      Procesando…
    </div>
  </div>
</div>
