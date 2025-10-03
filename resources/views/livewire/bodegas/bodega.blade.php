{{-- resources/views/livewire/bodegas/index.blade.php --}}
@once
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@endonce

<div
  x-data="{
    showModal: @entangle('showModal'),
    q:'', // filtro solo visual para móvil
    listen() {
      window.addEventListener('toast', (e) => {
        const d = e.detail || {};
        console.log(`[${d.type ?? 'info'}] ${d.message ?? ''}`);
      });
    }
  }"
  x-init="listen()"
  class="p-6 md:p-8 space-y-6"
>
  @php
    $total   = collect($bodegas)->count();
    $activos = collect($bodegas)->where('activo', true)->count();
  @endphp

  {{-- ================= HERO ================= --}}
  <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-violet-600 via-indigo-600 to-blue-600 text-white shadow-2xl">
    <div class="px-6 md:px-8 py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center gap-3">
          <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/15 backdrop-blur">
            <i class="fas fa-warehouse text-2xl"></i>
          </span>
          Bodegas
        </h1>
        <p class="text-sm text-white/80">Crea, edita, activa/desactiva y elimina bodegas.</p>
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
          <i class="fas fa-plus mr-2"></i> Nueva bodega
        </button>
      </div>
    </div>
  </section>

  {{-- ================= LISTA (Cards móvil / Tabla desktop) ================= --}}
  <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 shadow-2xl overflow-hidden">
    <header class="px-6 md:px-8 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <span class="inline-grid place-items-center w-9 h-9 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">
          <i class="fas fa-boxes-stacked"></i>
        </span>
        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Listado</h3>
      </div>

      {{-- buscador visual (no requiere propiedad Livewire) en móvil --}}
      <div class="md:hidden">
        <input
          x-model="q"
          type="text"
          placeholder="Buscar por nombre/ubicación…"
          class="h-11 w-56 px-3 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-sm"
        >
      </div>
    </header>

    <div class="p-4 md:p-6">
      {{-- ===== Móvil (cards) ===== --}}
      <div class="md:hidden grid grid-cols-1 gap-3">
        @forelse ($bodegas as $b)
          <article
            class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900"
            x-show="!q || '{{ Str::lower($b['nombre'].' '.$b['ubicacion']) }}'.includes(q.toLowerCase())"
            x-cloak
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center justify-center text-[10px] px-2 py-0.5 rounded-full {{ $b['activo'] ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700' }}">
                    {{ $b['activo'] ? 'Activa' : 'Inactiva' }}
                  </span>
                  <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $b['nombre'] }}</h4>
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  <i class="fa fa-location-dot mr-1"></i>{{ $b['ubicacion'] ?: '—' }}
                </div>
              </div>

              <div class="text-right">
                <label class="relative inline-flex items-center cursor-pointer select-none">
                  <input type="checkbox" class="sr-only peer" @change="$wire.toggleEstado({{ $b['id'] }})" {{ $b['activo'] ? 'checked' : '' }}>
                  <div class="w-12 h-7 bg-gray-300 dark:bg-gray-700 rounded-full peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300/50 transition peer-checked:bg-emerald-600 relative">
                    <span class="absolute top-0.5 left-0.5 h-6 w-6 bg-white rounded-full shadow transform transition peer-checked:translate-x-5"></span>
                  </div>
                </label>
              </div>
            </div>

            <div class="mt-3 flex items-center justify-end gap-2">
              <button
                @click="$wire.abrirEditar({{ $b['id'] }})"
                class="text-blue-600 hover:text-blue-800 text-sm"
              >
                <i class="fas fa-pen-to-square mr-1"></i> Editar
              </button>
              <button
                onclick="if(confirm('¿Eliminar esta bodega?')) { Livewire.dispatch('call', { fn: 'eliminar', args: [{{ $b['id'] }}] }) }"
                class="text-red-600 hover:text-red-800 text-sm"
              >
                <i class="fas fa-trash mr-1"></i> Eliminar
              </button>
            </div>
          </article>
        @empty
          <div class="rounded-xl border border-dashed p-6 text-center text-gray-500 dark:text-gray-400">
            No hay bodegas registradas.
          </div>
        @endforelse
      </div>

      {{-- ===== Desktop (tabla) ===== --}}
      <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800 shadow">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
            <tr>
              <th class="px-4 py-3 text-left"><i class="fas fa-hashtag mr-1"></i> ID</th>
              <th class="px-4 py-3 text-left"><i class="fas fa-warehouse mr-1"></i> Nombre</th>
              <th class="px-4 py-3 text-left"><i class="fas fa-map-marker-alt mr-1"></i> Ubicación</th>
              <th class="px-4 py-3 text-center"><i class="fas fa-toggle-on mr-1"></i> Estado</th>
              <th class="px-4 py-3 text-center"><i class="fas fa-cogs mr-1"></i> Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
            @forelse ($bodegas as $b)
              <tr class="hover:bg-violet-50/50 dark:hover:bg-gray-800 transition">
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $b['id'] }}</td>
                <td class="px-4 py-3">
                  <div class="font-semibold">{{ $b['nombre'] }}</div>
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $b['ubicacion'] ?: '—' }}</td>

                <td class="px-4 py-3 text-center">
                  <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input
                      type="checkbox"
                      class="sr-only peer"
                      @change="$wire.toggleEstado({{ $b['id'] }})"
                      {{ $b['activo'] ? 'checked' : '' }}
                    >
                    <div class="w-12 h-7 bg-gray-300 dark:bg-gray-700 rounded-full peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300/50 transition peer-checked:bg-emerald-600 relative">
                      <span class="absolute top-0.5 left-0.5 h-6 w-6 bg-white rounded-full shadow transform transition peer-checked:translate-x-5"></span>
                    </div>
                    <span class="sr-only">Cambiar estado</span>
                  </label>
                </td>

                <td class="px-4 py-3">
                  <div class="flex items-center justify-center gap-2">
                    <button
                      @click="$wire.abrirEditar({{ $b['id'] }})"
                      class="p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700"
                      title="Editar"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      onclick="if(confirm('¿Eliminar esta bodega?')) { Livewire.dispatch('call', { fn: 'eliminar', args: [{{ $b['id'] }}] }) }"
                      class="p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-red-600 hover:bg-red-50 dark:hover:bg-gray-700"
                      title="Eliminar"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                  No hay bodegas registradas.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>

  {{-- ================= MODAL Crear/Editar ================= --}}
  <div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
    x-show="showModal"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    @keydown.escape.window="showModal = false"
    aria-modal="true"
    role="dialog"
  >
    <div class="w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white">
          <i class="fas fa-warehouse text-indigo-600 mr-2"></i>
          {{ $bodega_id ? 'Editar Bodega' : 'Nueva Bodega' }}
        </h3>
        <button @click="showModal = false" class="text-gray-400 hover:text-red-500" aria-label="Cerrar">
          <i class="fas fa-times-circle text-lg"></i>
        </button>
      </div>

      <div class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre <span class="text-red-500">*</span></label>
          <input
            type="text"
            wire:model.defer="nombre"
            class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-base
                   focus:outline-none focus:ring-4 focus:ring-indigo-300/50 @error('nombre') border-red-500 focus:ring-red-300 @enderror"
            placeholder="Ej. Bodega Central"
          >
          @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ubicación <span class="text-red-500">*</span></label>
          <input
            type="text"
            wire:model.defer="ubicacion"
            class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-base
                   focus:outline-none focus:ring-4 focus:ring-indigo-300/50 @error('ubicacion') border-red-500 focus:ring-red-300 @enderror"
            placeholder="Ej. Calle 123 #45-67"
          >
          @error('ubicacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-2 pt-1">
          <input id="chk-activo" type="checkbox" wire:model.defer="activo" class="h-4 w-4">
          <label for="chk-activo" class="text-sm text-gray-700 dark:text-gray-300">Activo</label>
        </div>
      </div>

      <div class="px-6 py-4 flex items-center justify-end gap-2 border-t border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40">
        <button @click="showModal = false" class="h-11 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-800 dark:text-white">
          Cancelar
        </button>
        <button wire:click="guardar" class="h-11 px-5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow">
          Guardar
        </button>
      </div>
    </div>
  </div>
</div>
