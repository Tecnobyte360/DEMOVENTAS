{{-- resources/views/livewire/medios-pagos/medios-pagos.blade.php --}}
@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Evita choque Alpine ↔ Livewire
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div
  x-data="{ deleteOpen:false }"
  class="p-6 md:p-8 space-y-6"
>
  {{-- ================= HEADER / HERO ================= --}}
  <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-violet-600 via-indigo-600 to-blue-600 text-white shadow-2xl">
    <div class="px-6 md:px-8 py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center gap-3">
          <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/15 backdrop-blur">
            <i class="fa-solid fa-credit-card text-2xl"></i>
          </span>
          Medios de pago
        </h1>
        <p class="text-sm text-white/80">Administra y asocia cuentas contables (PUC) a cada medio de pago.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
          <i class="fa-solid fa-list"></i> Total: {{ $items?->count() ?? 0 }}
        </span>
        <button
          wire:click="abrirCrear"
          class="h-11 px-4 rounded-2xl bg-white/90 hover:bg-white text-violet-700 font-semibold shadow"
        >
          <i class="fa-solid fa-plus mr-2"></i> Nuevo medio
        </button>
      </div>
    </div>
  </section>

  {{-- ================= CARD PRINCIPAL ================= --}}
  <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 shadow-2xl overflow-hidden">

    {{-- ===== FILTROS ===== --}}
    <section class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-800" aria-label="Filtros de búsqueda">
      <header class="mb-4">
        <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-8 h-8 md:w-9 md:h-9 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">
            <i class="fa-solid fa-magnifying-glass text-[13px]"></i>
          </span>
          Filtros
        </h2>
      </header>

      <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        {{-- Buscar --}}
        <div class="md:col-span-7">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Buscar</label>
          <div class="flex gap-2">
            <input
              type="text"
              wire:model.debounce.400ms="filters.q"
              placeholder="Código o nombre…"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
            />
            @if(($filters['q'] ?? '') || ($filters['activo'] ?? '')!=='')
              <button type="button"
                      wire:click="$set('filters.q',''); $set('filters.activo','')"
                      class="h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Limpiar
              </button>
            @endif
          </div>
        </div>

        {{-- Estado --}}
        <div class="md:col-span-3">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Estado</label>
          <select
            wire:model.live="filters.activo"
            class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          >
            <option value="">— Todos —</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
          </select>
        </div>

        {{-- Info --}}
        <div class="md:col-span-2 flex items-end">
          <div class="text-[12px] text-gray-500 dark:text-gray-400">
            Mostrando: <span class="font-semibold">{{ $items?->count() ?? 0 }}</span>
          </div>
        </div>
      </div>
    </section>

    {{-- ===== LISTA ===== --}}
    <section class="p-4 md:p-6" aria-label="Listado de medios">
      {{-- Móvil: Cards --}}
      <div class="md:hidden space-y-3">
        @forelse($items as $it)
          @php $cu = $it->cuenta?->cuentaPUC; @endphp
          <article class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full
                    {{ $it->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700' }}">
                    <i class="fa-solid {{ $it->activo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    {{ $it->activo ? 'Activo' : 'Inactivo' }}
                  </span>
                  <h3 class="font-semibold text-gray-900 dark:text-white">{{ $it->nombre }}</h3>
                </div>

                <div class="flex flex-wrap gap-1 text-[11px]">
                  <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700" title="Código">
                    Código: {{ $it->codigo }}
                  </span>
                  <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700" title="Orden">
                    Orden: {{ $it->orden }}
                  </span>
                  <span class="px-2 py-0.5 rounded-full {{ $cu ? 'bg-sky-100 text-sky-700' : 'bg-gray-100 text-gray-600' }}" title="Cuenta asociada">
                    @if($cu)
                      PUC: {{ $cu->codigo }}
                    @else
                      Sin cuenta
                    @endif
                  </span>
                </div>

                @if($cu)
                  <div class="text-[11px] text-gray-500">
                    {{ $cu->nombre }}
                  </div>
                @endif
              </div>

              <div class="text-right space-y-2">
                <button
                  wire:click="toggleActivo({{ $it->id }})"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px]
                    {{ $it->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}"
                  title="Cambiar estado"
                >
                  <i class="fa-solid {{ $it->activo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                  {{ $it->activo ? 'Activo' : 'Inactivo' }}
                </button>

                <div class="flex items-center justify-end gap-2">
                  <button wire:click="abrirEditar({{ $it->id }})" class="text-blue-600 hover:text-blue-800" title="Editar">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <button wire:click="confirmarEliminar({{ $it->id }})" x-on:click="deleteOpen = true" class="text-red-600 hover:text-red-800" title="Eliminar">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </article>
        @empty
          <div class="rounded-xl border border-dashed p-6 text-center text-gray-500">No hay medios de pago.</div>
        @endforelse
      </div>

      {{-- Desktop: Tabla --}}
      <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
            <tr>
              <th class="p-3 text-left">Nombre</th>
              <th class="p-3 text-left">Código</th>
              <th class="p-3 text-left">Cuenta PUC</th>
              <th class="p-3 text-center">Estado</th>
              <th class="p-3 text-right">Orden</th>
              <th class="p-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($items as $it)
              @php $cu = $it->cuenta?->cuentaPUC; @endphp
              <tr class="hover:bg-violet-50/50 dark:hover:bg-gray-800 transition">
                <td class="p-3">
                  <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $it->nombre }}</div>
                </td>
                <td class="p-3 font-mono">{{ $it->codigo }}</td>
                <td class="p-3">
                  @if($cu)
                    <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700">
                      <span class="font-semibold mr-2">{{ $cu->codigo }}</span>
                      <span class="text-xs text-gray-500">{{ $cu->nombre }}</span>
                    </span>
                  @else
                    <span class="text-gray-400 italic text-xs">Sin cuenta</span>
                  @endif
                </td>
                <td class="p-3 text-center">
                  <button
                    wire:click="toggleActivo({{ $it->id }})"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px]
                      {{ $it->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}"
                    title="Cambiar estado"
                  >
                    <i class="fa-solid {{ $it->activo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    {{ $it->activo ? 'Activo' : 'Inactivo' }}
                  </button>
                </td>
                <td class="p-3 text-right">{{ $it->orden }}</td>
                <td class="p-3 text-center space-x-2">
                  <button wire:click="abrirEditar({{ $it->id }})"
                          class="px-2 py-1 text-[11px] rounded bg-indigo-600 hover:bg-indigo-700 text-white">
                    Editar
                  </button>
                  <button wire:click="confirmarEliminar({{ $it->id }})" x-on:click="deleteOpen = true"
                          class="px-2 py-1 text-[11px] rounded bg-rose-600 hover:bg-rose-700 text-white">
                    Eliminar
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="p-6 text-center text-gray-500 dark:text-gray-400">No hay medios de pago.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </section>

  {{-- ================= MODAL CREAR / EDITAR ================= --}}
  <div
    class="fixed inset-0 z-50 grid place-items-center bg-black/50 backdrop-blur-sm"
    x-show="$wire.showModal"
    x-cloak
    @keydown.escape.window="$wire.set('showModal', false)"
    @click.self="$wire.set('showModal', false)"
  >
    <div class="w-full max-w-2xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="text-lg font-bold flex items-center gap-2">
          <i class="fa-solid fa-credit-card text-violet-600"></i>
          {{ $editingId ? 'Editar medio de pago' : 'Nuevo medio de pago' }}
        </h3>
        <button class="text-red-600" @click="$wire.set('showModal', false)">
          <i class="fa-solid fa-circle-xmark"></i>
        </button>
      </div>

      <form wire:submit.prevent="guardar" class="p-6 space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          {{-- Nombre --}}
          <div class="md:col-span-7">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Nombre <span class="text-red-500">*</span></label>
            <input
              type="text"
              wire:model.defer="nombre"
              placeholder="Efectivo"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('nombre') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('nombre') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Código --}}
          <div class="md:col-span-5">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Código <span class="text-red-500">*</span></label>
            <input
              type="text"
              wire:model.defer="codigo"
              placeholder="EFECTIVO"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('codigo') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('codigo') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Activo --}}
          <div class="md:col-span-6 flex items-end">
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
              <input type="checkbox" wire:model="activo" class="rounded">
              <span class="text-sm">Activo</span>
            </label>
          </div>

          {{-- Orden --}}
          <div class="md:col-span-6">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Orden</label>
            <input
              type="number" min="1" step="1"
              wire:model.defer="orden"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('orden') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('orden') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Cuenta contable asociada (1–1) --}}
          <div class="md:col-span-12">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
              Cuenta contable (PUC)
            </label>
            <select
              wire:model.defer="plan_cuentas_id"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('plan_cuentas_id') border-red-500 focus:ring-red-300 @enderror"
            >
              <option value="">— Ninguna —</option>
              @foreach($cuentasPUC as $c)
                <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nombre }}</option>
              @endforeach
            </select>
            @error('plan_cuentas_id') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
           
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end gap-3">
          <button type="button"
            class="px-4 py-2 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
            @click="$wire.set('showModal', false)">
            Cancelar
          </button>
          <button type="submit"
            class="px-4 py-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white shadow">
            {{ $editingId ? 'Actualizar' : 'Guardar' }}
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- ================= MODAL CONFIRMAR ELIMINAR ================= --}}
  <div
    class="fixed inset-0 z-50 grid place-items-center bg-black/50 backdrop-blur-sm"
    x-show="deleteOpen && $wire.confirmingDeleteId"
    x-cloak
    @keydown.escape.window="deleteOpen=false; $wire.set('confirmingDeleteId', null)"
    @click.self="deleteOpen=false; $wire.set('confirmingDeleteId', null)"
  >
    <div class="w-full max-w-md mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800">
        <h3 class="text-lg font-bold">Eliminar medio de pago</h3>
      </div>
      <div class="p-6">
        <p class="text-sm text-gray-600 dark:text-gray-300">
          Esta acción no se puede deshacer. ¿Deseas continuar?
        </p>
      </div>
      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3">
        <button class="px-4 py-2 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
                @click="deleteOpen=false; $wire.set('confirmingDeleteId', null)">
          Cancelar
        </button>
        <button class="px-4 py-2 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white"
                @click="$wire.eliminar(); deleteOpen=false;">
          Eliminar
        </button>
      </div>
    </div>
  </div>

  
</div>
