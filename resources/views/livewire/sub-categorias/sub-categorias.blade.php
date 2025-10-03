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
            <i class="fa-solid fa-layer-group text-2xl"></i>
          </span>
          Administrar Subcategorías
        </h1>
        <p class="text-white/80 text-xs">Crea, edita y asigna cuentas contables por subcategoría</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
          <i class="fa-solid fa-list"></i> Total: {{ $subcategorias->count() }}
        </span>

        <button
          wire:click="$set('isEdit', false)"
          class="h-11 px-4 rounded-2xl bg-white/90 hover:bg-white text-violet-700 font-semibold shadow"
        >
          <i class="fa-solid fa-plus mr-2"></i> Nueva subcategoría
        </button>
      </div>
    </div>
  </section>

  {{-- ================= CARD PRINCIPAL (Formulario + Tabla) ================= --}}
  <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 shadow-2xl overflow-hidden">

    {{-- ===== FORMULARIO ===== --}}
    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="p-6 md:p-8 space-y-8">
      <header class="mb-2">
        <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-8 h-8 md:w-9 md:h-9 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">
            <i class="fa-solid fa-pen-to-square text-[13px]"></i>
          </span>
          {{ $isEdit ? 'Editar Subcategoría' : 'Nueva Subcategoría' }}
        </h2>
      </header>

      <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        {{-- Nombre --}}
        <div class="md:col-span-5">
          <label class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 block mb-2">Nombre <span class="text-red-500">*</span></label>
          <input wire:model="nombre" type="text" placeholder="Nombre"
                 class="w-full h-12 px-4 rounded-2xl border-2 @error('nombre') border-red-500 focus:ring-red-300 @else border-gray-200 @enderror dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60" />
          @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Descripción --}}
        <div class="md:col-span-5">
          <label class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 block mb-2">Descripción</label>
          <input wire:model="descripcion" type="text" placeholder="Descripción"
                 class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60" />
          @error('descripcion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Categoría --}}
        <div class="md:col-span-2">
          <label class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 block mb-2">Categoría <span class="text-red-500">*</span></label>
          <select wire:model="categoria_id"
                  class="w-full h-12 px-4 rounded-2xl border-2 @error('categoria_id') border-red-500 focus:ring-red-300 @else border-gray-200 @enderror dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
            <option value="">— Selecciona —</option>
            @foreach ($categorias as $cat)
              <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
            @endforeach
          </select>
          @error('categoria_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
      </div>

      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
          <label class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2 block">Estado</label>
          <div class="flex items-center gap-4">
            <span class="text-sm text-gray-700 dark:text-gray-300">Inactivo</span>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" wire:model="activo" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-violet-500 dark:bg-gray-700 rounded-full peer dark:peer-focus:ring-violet-600 peer-checked:bg-violet-600 transition-all"></div>
              <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform peer-checked:translate-x-5"></div>
            </label>
            <span class="text-sm text-gray-700 dark:text-gray-300">Activo</span>
          </div>
        </div>

        <div class="flex items-center gap-2">
          @if($isEdit)
            <button type="button"
              wire:click="$set('isEdit', false)"
              class="inline-flex items-center gap-2 px-4 h-11 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200">
              <i class="fa-solid fa-rotate-left"></i> Cancelar edición
            </button>
          @endif

          <button type="submit"
                  class="inline-flex items-center gap-2 px-6 h-11 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-base font-semibold rounded-2xl shadow">
            <i class="fas fa-save"></i>
            {{ $isEdit ? 'Actualizar' : 'Guardar' }}
          </button>
        </div>
      </div>
    </form>

    {{-- ===== LISTADO ===== --}}
    <section class="p-4 md:p-6">
      <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
            <tr>
              <th class="p-3 text-left">#</th>
              <th class="p-3 text-left">Nombre</th>
              <th class="p-3 text-left">Descripción</th>
              <th class="p-3 text-left">Categoría</th>
              <th class="p-3 text-left">Cuentas</th>
              <th class="p-3 text-center">Estado</th>
              <th class="p-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse ($subcategorias as $sub)
              <tr class="hover:bg-violet-50/50 dark:hover:bg-gray-800 transition">
                <td class="p-3 text-gray-500">#{{ $sub->id }}</td>
                <td class="p-3 font-semibold text-gray-800 dark:text-gray-100">{{ $sub->nombre }}</td>
                <td class="p-3">{{ $sub->descripcion }}</td>
                <td class="p-3 text-gray-600 dark:text-gray-300">{{ $sub->categoria->nombre ?? '—' }}</td>

                {{-- Chips resumen de cuentas asignadas por tipo --}}
                <td class="p-3">
                  @if($sub->relationLoaded('cuentas') && $sub->cuentas->count())
                    <div class="flex flex-wrap gap-1">
                      @foreach($sub->cuentas as $sc)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] rounded-full bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-gray-200">
                          <i class="fa-regular fa-circle-check"></i>
                          <span class="font-semibold">{{ $sc->tipo->nombre ?? 'Tipo' }}</span>
                          <span class="opacity-50">|</span>
                          <span class="font-mono">{{ $sc->cuentaPUC?->codigo }}</span>
                        </span>
                      @endforeach
                    </div>
                  @else
                    <span class="text-gray-400 italic text-xs">Sin cuentas</span>
                  @endif
                </td>

                <td class="p-3 text-center">
                  <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full {{ $sub->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700' }}">
                    {{ $sub->activo ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>

                <td class="p-3 text-center space-x-2">
                  <button wire:click="edit({{ $sub->id }})"
                          class="px-2 py-1 text-[11px] rounded bg-indigo-600 hover:bg-indigo-700 text-white">
                    Editar
                  </button>
                  <button wire:click="abrirModalCuentas({{ $sub->id }})"
                          class="px-2 py-1 text-[11px] rounded bg-violet-600 hover:bg-violet-700 text-white">
                    Cuentas
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="p-6 text-center text-gray-500 dark:text-gray-400">
                  No hay subcategorías aún. Crea la primera con el botón <em>Nueva subcategoría</em>.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </section>

  {{-- ================= MODAL: Configurar cuentas por Subcategoría ================= --}}
  <div
    class="fixed inset-0 z-50 grid place-items-center bg-black/50 backdrop-blur-sm"
    x-show="$wire.showCuentasModal"
    x-cloak
    @keydown.escape.window="$wire.cerrarModalCuentas()"
    @click.self="$wire.cerrarModalCuentas()"
  >
    <div class="w-full max-w-5xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="text-lg font-bold flex items-center gap-2">
          <i class="fa-solid fa-sitemap text-violet-600"></i>
          @php $subSel = $subcategorias->firstWhere('id', $subcategoria_id_en_modal); @endphp
          Configurar cuentas — {{ $subSel?->nombre ? "#{$subSel->id} · {$subSel->nombre}" : ($subcategoria_id_en_modal ? "#{$subcategoria_id_en_modal}" : '') }}
        </h3>
        <button class="text-red-600" @click="$wire.cerrarModalCuentas()">
          <i class="fa-solid fa-circle-xmark"></i>
        </button>
      </div>

      <div class="p-6">
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
          <table class="min-w-[900px] w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
              <tr>
                <th class="p-3 text-left">Tipo de cuenta</th>
                <th class="p-3 text-left">Seleccionar cuenta (PUC)</th>
                <th class="p-3 text-left">Selección</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
              @foreach($tiposCuenta as $t)
                @php
                  $selId = $cuentasPorTipo[$t->id] ?? null;
                  $sel   = $selId ? $cuentasPUC->firstWhere('id', $selId) : null;
                @endphp
                <tr class="hover:bg-violet-50/40 dark:hover:bg-gray-800">
                  <td class="p-3 font-semibold text-gray-800 dark:text-gray-100">
                    {{ $t->nombre }}
                    @if($t->obligatorio)
                      <span class="text-red-500">*</span>
                    @endif
                  </td>
                  <td class="p-3">
                    <select wire:model.lazy="cuentasPorTipo.{{ $t->id }}"
                            class="w-full h-11 px-3 rounded-xl border-2
                                   @error('cuentasPorTipo.'.$t->id) border-red-500 focus:ring-red-300 @else border-gray-200 @enderror
                                   dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                      <option value="">— {{ $t->obligatorio ? 'Seleccione (obligatorio)' : 'Seleccione (opcional)' }} —</option>
                      @foreach($cuentasPUC as $c)
                        <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nombre }}</option>
                      @endforeach
                    </select>
                    @error('cuentasPorTipo.'.$t->id) <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                  </td>
                  <td class="p-3">
                    @if($sel)
                      <span class="inline-flex items-center gap-2 px-2 py-1 rounded-md bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                        <span class="font-mono font-semibold">{{ $sel->codigo }}</span>
                        <span class="opacity-60">—</span>
                        <span class="text-xs">{{ $sel->nombre }}</span>
                      </span>
                    @else
                      <span class="text-gray-400 italic text-xs">Sin seleccionar</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-end gap-3 mt-6">
          <button type="button" @click="$wire.cerrarModalCuentas()"
                  class="px-4 h-11 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200">
            Cancelar
          </button>
          <button type="button" wire:click="guardarCuentas"
                  class="px-4 h-11 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white">
            Guardar
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ================= LOADING OVERLAY ================= --}}
  <div wire:loading.flex class="fixed inset-0 z-40 items-center justify-center bg-white/50 dark:bg-gray-900/50 backdrop-blur">
    <div class="animate-spin h-8 w-8 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
  </div>
</div>
