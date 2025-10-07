{{-- resources/views/livewire/bodegas/index.blade.php --}}
@once
  @push('styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Evita choque Alpine ↔ Livewire (igual que en Medios de pago)
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div
  x-data="{
    // Filtros (solo visuales con Alpine para no depender del backend)
    q: '',
    estado: '',
    deleteOpen: false,
    showModal: @entangle('showModal')
  }"
  class="p-6 md:p-8 space-y-6"
>
  @php
    $total   = collect($bodegas)->count();
    $activos = collect($bodegas)->where('activo', true)->count();
  @endphp

  {{-- ================= HEADER / HERO ================= --}}
  <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-violet-600 via-indigo-600 to-blue-600 text-white shadow-2xl">
    <div class="px-6 md:px-8 py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center gap-3">
          <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/15 backdrop-blur">
            <i class="fas fa-warehouse text-2xl"></i>
          </span>
          Bodegas
        </h1>
        <p class="text-sm text-white/80">Administra tus bodegas: crear, editar, activar/desactivar y eliminar.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
          <i class="fa-solid fa-list"></i> Total: {{ $total }}
        </span>
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
          <i class="fa-solid fa-toggle-on"></i> Activas: {{ $activos }}
        </span>
        <button
          @click="$wire.abrirCrear()"
          class="h-11 px-4 rounded-2xl bg-white/90 hover:bg-white text-violet-700 font-semibold shadow"
        >
          <i class="fa-solid fa-plus mr-2"></i> Nueva bodega
        </button>
      </div>
    </div>
  </section>

  {{-- ================= CARD PRINCIPAL ================= --}}
  <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 shadow-2xl overflow-hidden">

    {{-- ===== FILTROS (visual, con Alpine) ===== --}}
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
              x-model.debounce.300ms="q"
              placeholder="Nombre o ubicación…"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
            />
            <template x-if="q || estado!==''">
              <button
                type="button"
                @click="q=''; estado=''"
                class="h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
              >
                Limpiar
              </button>
            </template>
          </div>
        </div>

        {{-- Estado --}}
        <div class="md:col-span-3">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Estado</label>
          <select
            x-model="estado"
            class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          >
            <option value="">— Todos —</option>
            <option value="1">Activas</option>
            <option value="0">Inactivas</option>
          </select>
        </div>

        {{-- Info --}}
        <div class="md:col-span-2 flex items-end">
          <div class="text-[12px] text-gray-500 dark:text-gray-400">
            Total: <span class="font-semibold">{{ $total }}</span> |
            Activas: <span class="font-semibold">{{ $activos }}</span>
          </div>
        </div>
      </div>
    </section>

    {{-- ===== LISTA ===== --}}
    <section class="p-4 md:p-6" aria-label="Listado de bodegas">
      {{-- Móvil: Cards --}}
      <div class="md:hidden space-y-3">
        @forelse ($bodegas as $b)
          @php
            $textIndex = Str::lower(($b['nombre'] ?? '').' '.($b['ubicacion'] ?? ''));
          @endphp
          <article
            class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900"
            x-show="(!q || '{{ $textIndex }}'.includes(q.toLowerCase())) && (estado==='' || estado==='{{ $b['activo'] ? 1 : 0 }}')"
            x-cloak
          >
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full {{ $b['activo'] ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700' }}">
                    <i class="fa-solid {{ $b['activo'] ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    {{ $b['activo'] ? 'Activa' : 'Inactiva' }}
                  </span>
                  <h3 class="font-semibold text-gray-900 dark:text-white">{{ $b['nombre'] }}</h3>
                </div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">
                  <i class="fa fa-location-dot mr-1"></i>{{ $b['ubicacion'] ?: '—' }}
                </div>
              </div>

              <div class="text-right space-y-2">
                <button
                  @click="$wire.toggleEstado({{ $b['id'] }})"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] {{ $b['activo'] ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}"
                  title="Cambiar estado"
                >
                  <i class="fa-solid {{ $b['activo'] ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                  {{ $b['activo'] ? 'Activa' : 'Inactiva' }}
                </button>

                <div class="flex items-center justify-end gap-2">
                  <button @click="$wire.abrirEditar({{ $b['id'] }})" class="text-blue-600 hover:text-blue-800" title="Editar">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <button
                    @click="deleteOpen = true; $wire.set('confirmingDeleteId', {{ $b['id'] }})"
                    class="text-red-600 hover:text-red-800"
                    title="Eliminar"
                  >
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </article>
        @empty
          <div class="rounded-xl border border-dashed p-6 text-center text-gray-500">No hay bodegas registradas.</div>
        @endforelse
      </div>

      {{-- Desktop: Tabla --}}
      <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
            <tr>
              <th class="p-3 text-left">Nombre</th>
              <th class="p-3 text-left">Ubicación</th>
              <th class="p-3 text-center">Estado</th>
              <th class="p-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($bodegas as $b)
              @php
                $rowText = Str::lower(($b['nombre'] ?? '').' '.($b['ubicacion'] ?? ''));
                $isActive = $b['activo'] ? '1' : '0';
              @endphp
              <tr
                class="hover:bg-violet-50/50 dark:hover:bg-gray-800 transition"
                x-show="(!q || '{{ $rowText }}'.includes(q.toLowerCase())) && (estado==='' || estado==='{{ $isActive }}')"
                x-cloak
              >
                <td class="p-3">
                  <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $b['nombre'] }}</div>
                </td>
                <td class="p-3 text-gray-700 dark:text-gray-300">{{ $b['ubicacion'] ?: '—' }}</td>

                <td class="p-3 text-center">
                  <button
                    @click="$wire.toggleEstado({{ $b['id'] }})"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] {{ $b['activo'] ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}"
                    title="Cambiar estado"
                  >
                    <i class="fa-solid {{ $b['activo'] ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    {{ $b['activo'] ? 'Activa' : 'Inactiva' }}
                  </button>
                </td>

                <td class="p-3 text-center space-x-2">
                  <button
                    @click="$wire.abrirEditar({{ $b['id'] }})"
                    class="px-2 py-1 text-[11px] rounded bg-indigo-600 hover:bg-indigo-700 text-white"
                    title="Editar"
                  >
                    Editar
                  </button>
                  <button
                    @click="deleteOpen = true; $wire.set('confirmingDeleteId', {{ $b['id'] }})"
                    class="px-2 py-1 text-[11px] rounded bg-rose-600 hover:bg-rose-700 text-white"
                    title="Eliminar"
                  >
                    Eliminar
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="p-6 text-center text-gray-500 dark:text-gray-400">No hay bodegas registradas.</td>
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
    x-show="showModal"
    x-cloak
    @keydown.escape.window="showModal = false"
    @click.self="showModal = false"
  >
    <div class="w-full max-w-2xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="text-lg font-bold flex items-center gap-2">
          <i class="fa-solid fa-warehouse text-violet-600"></i>
          {{ $bodega_id ? 'Editar bodega' : 'Nueva bodega' }}
        </h3>
        <button class="text-red-600" @click="showModal = false">
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
              placeholder="Bodega Central"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('nombre') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('nombre') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Ubicación --}}
          <div class="md:col-span-5">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Ubicación <span class="text-red-500">*</span></label>
            <input
              type="text"
              wire:model.defer="ubicacion"
              placeholder="Calle 123 #45-67"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('ubicacion') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('ubicacion') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Activo --}}
          <div class="md:col-span-6 flex items-end">
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
              <input type="checkbox" wire:model="activo" class="rounded">
              <span class="text-sm">Activa</span>
            </label>
          </div>
        </div>

        <div class="pt-2 flex items-center justify-end gap-3">
          <button type="button"
            class="px-4 py-2 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800"
            @click="showModal = false">
            Cancelar
          </button>
          <button type="submit"
            class="px-4 py-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white shadow">
            {{ $bodega_id ? 'Actualizar' : 'Guardar' }}
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
        <h3 class="text-lg font-bold">Eliminar bodega</h3>
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
