{{-- resources/views/livewire/condicion-pagos/condiciones-pagos.blade.php --}}
@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Evita choque Alpine ↔ Livewire (igual que en factura-form)
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
            <i class="fa-solid fa-file-invoice-dollar text-2xl"></i>
          </span>
          Condiciones de pago
        </h1>
       
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
          <i class="fa-solid fa-list"></i> Total: {{ $condiciones->count() }}
        </span>
        <button
          wire:click="crear"
          class="h-11 px-4 rounded-2xl bg-white/90 hover:bg-white text-violet-700 font-semibold shadow"
        >
          <i class="fa-solid fa-plus mr-2"></i> Nueva condición
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
        <div class="md:col-span-6">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Buscar</label>
          <input
            type="text"
            wire:model.debounce.500ms="filters.q"
            placeholder="Nombre o notas…"
            class="w-full h-12 md:h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          />
        </div>

        {{-- Tipo --}}
        <div class="md:col-span-3">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Tipo</label>
          <select
            wire:model.live="filters.tipo"
            class="w-full h-12 md:h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          >
            <option value="">— Todos —</option>
            <option value="contado">Contado</option>
            <option value="credito">Crédito</option>
          </select>
        </div>

        {{-- Estado --}}
        <div class="md:col-span-3">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Estado</label>
          <select
            wire:model.live="filters.activo"
            class="w-full h-12 md:h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          >
            <option value="">— Todos —</option>
            <option value="1">Activas</option>
            <option value="0">Inactivas</option>
          </select>
        </div>
      </div>
    </section>

    {{-- ===== LISTA ===== --}}
    <section class="p-4 md:p-6" aria-label="Listado de condiciones">
      {{-- Móvil: Cards --}}
      <div class="md:hidden space-y-3">
        @forelse($condiciones as $c)
          <article class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full
                    {{ $c->tipo === 'credito' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }}">
                    <i class="fa-solid {{ $c->tipo === 'credito' ? 'fa-hand-holding-dollar' : 'fa-bolt' }}"></i>
                    {{ ucfirst($c->tipo) }}
                  </span>
                  <h3 class="font-semibold text-gray-900 dark:text-white">{{ $c->nombre }}</h3>
                </div>

                <div class="flex flex-wrap gap-1 text-[11px]">
                  <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700" title="Plazo (días)">
                    Plazo: {{ $c->plazo_dias ?? '—' }}
                  </span>
                  <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700" title="Interés de mora mensual">
                    Mora: {{ is_null($c->interes_mora_pct) ? '—' : number_format($c->interes_mora_pct,2) }}%
                  </span>
                  <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700" title="Límite de crédito">
                    Límite: {{ is_null($c->limite_credito) ? '—' : '$'.number_format($c->limite_credito,2,',','.') }}
                  </span>
                  <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700" title="Tolerancia mora (días)">
                    Tol: {{ $c->tolerancia_mora_dias ?? '—' }}
                  </span>
                  <span class="px-2 py-0.5 rounded-full bg-fuchsia-100 text-fuchsia-700" title="Día de corte">
                    Corte: {{ $c->dia_corte ?? '—' }}
                  </span>
                </div>

                @if($c->notas)
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($c->notas, 160) }}</p>
                @endif
              </div>

              <div class="text-right space-y-2">
                <button
                  wire:click="toggleActivo({{ $c->id }})"
                  class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px]
                    {{ $c->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}"
                  title="Cambiar estado"
                >
                  <i class="fa-solid {{ $c->activo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                  {{ $c->activo ? 'Activa' : 'Inactiva' }}
                </button>

                <div class="flex items-center justify-end gap-2">
                  <button wire:click="editar({{ $c->id }})" class="text-blue-600 hover:text-blue-800" title="Editar">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <button wire:click="confirmarEliminar({{ $c->id }})" x-on:click="deleteOpen = true" class="text-red-600 hover:text-red-800" title="Eliminar">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </article>
        @empty
          <div class="rounded-xl border border-dashed p-6 text-center text-gray-500">No hay condiciones para mostrar.</div>
        @endforelse
      </div>

      {{-- Desktop: Tabla --}}
      <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
            <tr>
              <th class="p-3 text-left">Nombre</th>
              <th class="p-3 text-left">Tipo</th>
              <th class="p-3 text-center">Plazo (d)</th>
              <th class="p-3 text-center">Mora %/mes</th>
              <th class="p-3 text-right">Límite (COP)</th>
              <th class="p-3 text-center">Tol. Mora (d)</th>
              <th class="p-3 text-center">Día corte</th>
              <th class="p-3 text-center">Estado</th>
              <th class="p-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($condiciones as $c)
              <tr class="hover:bg-violet-50/50 dark:hover:bg-gray-800 transition">
                <td class="p-3">
                  <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $c->nombre }}</div>
                  @if($c->notas)
                    <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($c->notas, 120) }}</div>
                  @endif
                </td>
                <td class="p-3">
                  <span class="text-[11px] px-2 py-0.5 rounded-full
                    {{ $c->tipo === 'credito' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ ucfirst($c->tipo) }}
                  </span>
                </td>
                <td class="p-3 text-center">{{ $c->plazo_dias ?? '—' }}</td>
                <td class="p-3 text-center">{{ is_null($c->interes_mora_pct) ? '—' : number_format($c->interes_mora_pct, 2) }}</td>
                <td class="p-3 text-right">{{ is_null($c->limite_credito) ? '—' : number_format($c->limite_credito, 2, ',', '.') }}</td>
                <td class="p-3 text-center">{{ $c->tolerancia_mora_dias ?? '—' }}</td>
                <td class="p-3 text-center">{{ $c->dia_corte ?? '—' }}</td>
                <td class="p-3 text-center">
                  <button
                    wire:click="toggleActivo({{ $c->id }})"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px]
                      {{ $c->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}"
                    title="Cambiar estado"
                  >
                    <i class="fa-solid {{ $c->activo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    {{ $c->activo ? 'Activa' : 'Inactiva' }}
                  </button>
                </td>
                <td class="p-3 text-center space-x-2">
                  <button wire:click="editar({{ $c->id }})" class="text-blue-600 hover:text-blue-800" title="Editar">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <button wire:click="confirmarEliminar({{ $c->id }})" x-on:click="deleteOpen = true" class="text-red-600 hover:text-red-800" title="Eliminar">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="p-6 text-center text-gray-500 dark:text-gray-400">No hay condiciones para mostrar.</td>
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
    <div class="w-full max-w-3xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="text-lg font-bold flex items-center gap-2">
          <i class="fa-solid fa-bolt text-violet-600"></i>
          {{ $editingId ? 'Editar condición' : 'Nueva condición' }}
        </h3>
        <button class="text-red-600" @click="$wire.set('showModal', false)">
          <i class="fa-solid fa-circle-xmark"></i>
        </button>
      </div>

      <form wire:submit.prevent="guardar" class="p-6 space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
          {{-- Nombre --}}
          <div class="md:col-span-12">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Nombre <span class="text-red-500">*</span></label>
            <input
              type="text"
              wire:model.live.debounce.300ms="form.nombre"
              placeholder="Ej: Crédito 30 días"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('form.nombre') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('form.nombre') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Tipo --}}
          <div class="md:col-span-6">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Tipo <span class="text-red-500">*</span></label>
            <select
              wire:model.live="form.tipo"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('form.tipo') border-red-500 focus:ring-red-300 @enderror"
            >
              <option value="contado">Contado</option>
              <option value="credito">Crédito</option>
            </select>
            @error('form.tipo') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Activa --}}
          <div class="md:col-span-6 flex items-end">
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
              <input type="checkbox" wire:model.live="form.activo" class="rounded">
              <span class="text-sm">Activa</span>
            </label>
          </div>

          {{-- Plazo (solo crédito) --}}
          <div class="md:col-span-6">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
              Plazo (días) @if(($form['tipo'] ?? '') === 'credito')<span class="text-red-500">*</span>@endif
            </label>
            <input
              type="number" min="1" step="1"
              wire:model.live.debounce.300ms="form.plazo_dias"
              placeholder="Ej: 30"
              @if(($form['tipo'] ?? '')!=='credito')
                disabled
                class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/60 text-gray-500"
              @else
                class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('form.plazo_dias') border-red-500 focus:ring-red-300 @enderror"
              @endif
            />
            @error('form.plazo_dias') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Interés de mora --}}
          <div class="md:col-span-6">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Interés de mora (% mensual)</label>
            <input
              type="number" min="0" step="0.01"
              wire:model.live.debounce.300ms="form.interes_mora_pct"
              placeholder="Ej: 2.5"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('form.interes_mora_pct') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('form.interes_mora_pct') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Límite crédito --}}
          <div class="md:col-span-6">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Límite de crédito (COP)</label>
            <input
              type="number" min="0" step="0.01"
              wire:model.live.debounce.300ms="form.limite_credito"
              placeholder="Ej: 2.000.000"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('form.limite_credito') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('form.limite_credito') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Tolerancia mora --}}
          <div class="md:col-span-6">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Tolerancia mora (días)</label>
            <input
              type="number" min="0" step="1"
              wire:model.live.debounce.300ms="form.tolerancia_mora_dias"
              placeholder="Ej: 5"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('form.tolerancia_mora_dias') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('form.tolerancia_mora_dias') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Día de corte --}}
          <div class="md:col-span-6">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Día de corte (1–31)</label>
            <input
              type="number" min="1" max="31" step="1"
              wire:model.live.debounce.300ms="form.dia_corte"
              placeholder="Ej: 30"
              class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('form.dia_corte') border-red-500 focus:ring-red-300 @enderror"
            />
            @error('form.dia_corte') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Notas --}}
          <div class="md:col-span-12">
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Notas</label>
            <textarea
              rows="3"
              wire:model.live.debounce.400ms="form.notas"
              placeholder="Ej: Pago a 30 días con 2% de mora mensual a partir del día 31."
              class="w-full px-4 py-3 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('form.notas') border-red-500 focus:ring-red-300 @enderror"
            ></textarea>
            @error('form.notas') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
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
            Guardar
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
        <h3 class="text-lg font-bold">Eliminar condición</h3>
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
