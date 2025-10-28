{{-- resources/views/livewire/inventario/entradas-mercancia-form.blade.php --}}

@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Alpine espera a que Livewire termine (evita race conditions)
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<form wire:submit.prevent="crearEntrada" class="p-6 md:p-10 bg-gradient-to-b from-white via-white to-violet-50/40 dark:from-gray-950 dark:via-gray-950 dark:to-violet-950/10" aria-label="Formulario de entrada de mercancía">
  {{-- ===================== ALERTAS ===================== --}}
  @if (session()->has('message'))
    <div class="mb-4 px-4 py-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-2 shadow" role="status">
      <i class="fa-solid fa-circle-check"></i>
      <span>{{ session('message') }}</span>
    </div>
  @endif

  @if (session()->has('error'))
    <div class="mb-4 px-4 py-3 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center gap-2 shadow" role="alert">
      <i class="fa-solid fa-triangle-exclamation"></i>
      <span>{{ session('error') }}</span>
    </div>
  @endif

  {{-- ===================== CARD PRINCIPAL ===================== --}}
  <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
   
    {{-- ========== PASO 1: Datos de la entrada ========== --}}
    <section class="p-6 md:p-8" aria-labelledby="datos-entrada-heading">
      <header class="mb-6">
        <h2 id="datos-entrada-heading" class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-9 md:w-10 h-9 md:h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">1</span>
          Datos de la entrada
        </h2>
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Fecha --}}
        <section>
          <label for="fecha" class="block text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Fecha <span class="text-red-500">*</span></label>
          <input id="fecha" type="date" wire:model.live="fecha_contabilizacion" class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('fecha_contabilizacion') border-rose-500 focus:ring-rose-300 @enderror" required aria-invalid="@error('fecha_contabilizacion') true @else false @enderror">
          @error('fecha_contabilizacion')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
          <p class="mt-1 text-[11px] text-gray-500">Usa la fecha contable del documento de proveedor.</p>
        </section>

        {{-- Socio de negocio --}}
        <section>
          <label for="socio" class="block text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Socio de negocio <span class="text-red-500">*</span></label>
          <select id="socio" wire:model.live.number="socio_negocio_id" class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('socio_negocio_id') border-rose-500 focus:ring-rose-300 @enderror" required aria-invalid="@error('socio_negocio_id') true @else false @enderror">
            <option value="">— Seleccione —</option>
            @foreach($socios as $socio)
              <option value="{{ $socio->id }}">{{ $socio->razon_social }}</option>
            @endforeach
          </select>
          @error('socio_negocio_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
          <p class="mt-1 text-[11px] text-gray-500">Proveedor o tercero asociado a la entrada.</p>
        </section>

        {{-- Estado visual --}}
        <section aria-live="polite" aria-label="Estado del flujo">
          <label class="block text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Estado</label>
          <div class="w-full h-12 md:h-14 flex items-center justify-between px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            <span class="text-xs text-gray-500">Flujo</span>
            <span class="inline-flex items-center gap-2 text-xs px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200"><i class="fa-solid fa-check"></i> Preparando</span>
          </div>
        </section>
      </div>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Observaciones --}}
        <section class="lg:col-span-3">
          <label for="observaciones" class="block text-[11px] font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Observaciones</label>
          <input id="observaciones" type="text" placeholder="Notas internas…" wire:model.live.debounce.300ms="observaciones" class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
        </section>
      </div>
    </section>

    {{-- ========== PASO 2: Ítems de entrada ========== --}}
    <section class="p-0" aria-labelledby="items-entrada-heading">
      {{-- Header de sección con stats --}}
      <div class="px-6 md:px-8 py-5 border-t border-b border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 flex flex-wrap items-center justify-between gap-3">
        <h2 id="items-entrada-heading" class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-9 h-9 md:w-10 md:h-10 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">2</span>
          Agrega ítems
        </h2>
        <div class="flex items-center gap-2 text-sm">
          <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"><i class="fa-solid fa-cubes"></i> <strong class="ml-1">{{ number_format(collect($entradas)->sum('cantidad') ?? 0) }}</strong> ítems</span>
          <span class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700"><i class="fa-solid fa-money-bill-wave"></i> Subtotal: <strong class="ml-1">${{ number_format(collect($entradas)->sum(fn($e) => (float)($e['cantidad'] ?? 0) * (float)($e['precio_unitario'] ?? 0)), 2, ',', '.') }}</strong></span>
        </div>
      </div>

      {{-- Tabla de líneas con header sticky --}}
      <section class="rounded-none md:rounded-2xl overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="sticky top-0 z-10 bg-white/90 dark:bg-gray-900/90 backdrop-blur border-b border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300">
            <tr>
              <th class="px-4 py-3 text-left"><i class="fa-solid fa-box"></i> Producto</th>
              <th class="px-4 py-3 text-left"><i class="fa-solid fa-info-circle"></i> Descripción</th>
              <th class="px-4 py-3 text-left"><i class="fa-solid fa-warehouse"></i> Bodega</th>
              <th class="px-4 py-3 text-right"><i class="fa-solid fa-sort-numeric-up"></i> Cant.</th>
              <th class="px-4 py-3 text-right"><i class="fa-solid fa-dollar-sign"></i> Costo</th>
              <th class="px-4 py-3 text-right"><i class="fa-solid fa-ellipsis"></i> Acciones</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($entradas as $i => $e)
              <tr wire:key="entrada-linea-{{ $i }}" class="bg-white dark:bg-gray-900 hover:bg-indigo-50/50 dark:hover:bg-gray-800/50 transition">
                {{-- Producto --}}
                <td class="px-4 py-3 min-w-[260px]">
                  <input list="productos_list_{{ $i }}" placeholder="Buscar producto…" wire:model.lazy="entradas.{{ $i }}.producto_nombre" wire:change="actualizarProductoDesdeNombre({{ $i }})" class="w-full h-11 px-3 rounded-xl border-2 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('entradas.'.$i.'.producto_id') border-rose-500 focus:ring-rose-300 @else border-gray-200 dark:border-gray-700 @enderror" aria-invalid="@error('entradas.'.$i.'.producto_id') true @else false @enderror">
                  <datalist id="productos_list_{{ $i }}">
                    @foreach ($productos as $p)
                      <option value="{{ $p->nombre }}">{{ $p->nombre }}</option>
                    @endforeach
                  </datalist>
                  @error("entradas.$i.producto_id")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </td>

                {{-- Descripción --}}
                <td class="px-4 py-3 min-w-[240px]">
                  <input type="text" disabled wire:model="entradas.{{ $i }}.descripcion" class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-gray-700 dark:text-white">
                </td>

                {{-- Bodega --}}
                <td class="px-4 py-3 min-w-[200px]">
                  <select wire:model.live.number="entradas.{{ $i }}.bodega_id" @change="$wire.recordarUltimos({{ $i }})" class="w-full h-11 px-3 rounded-xl border-2 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('entradas.'.$i.'.bodega_id') border-rose-500 focus:ring-rose-300 @else border-gray-200 dark:border-gray-700 @enderror" aria-invalid="@error('entradas.'.$i.'.bodega_id') true @else false @enderror">
                    <option value="">— Seleccione —</option>
                    @foreach($bodegas as $b)
                      <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                  </select>
                  @error("entradas.$i.bodega_id")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </td>

                {{-- Cantidad --}}
                <td class="px-4 py-3 text-right">
                  <div class="inline-flex items-center rounded-xl border-2 border-gray-200 dark:border-gray-700 overflow-hidden">
                    <button type="button" class="px-3 h-11 select-none" @click="$wire.decrementCantidad({{ $i }})" title="-1">−</button>
                    <input type="number" min="1" wire:model.live.debounce.150ms="entradas.{{ $i }}.cantidad" class="w-24 h-11 text-right border-0 bg-white dark:bg-gray-800 dark:text-white focus:outline-none"/>
                    <button type="button" class="px-3 h-11 select-none" @click="$wire.incrementCantidad({{ $i }})" title="+1">+</button>
                  </div>
                  @error("entradas.$i.cantidad")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </td>

                {{-- Costo --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="0.01" min="0" wire:model.live.debounce.150ms="entradas.{{ $i }}.precio_unitario" @change="$wire.recordarUltimos({{ $i }})" class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                  @error("entradas.$i.precio_unitario")<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </td>

                {{-- Acciones --}}
                <td class="px-4 py-3 text-right">
                  <div class="inline-flex items-center gap-2">
                    <button type="button" wire:click="agregarFila" class="h-10 px-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white">+ Línea</button>
                    <button type="button" wire:click="eliminarFila({{ $i }})" class="h-10 px-3 rounded-xl bg-rose-500 hover:bg-rose-600 text-white">Quitar</button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="px-4 py-10">
                  <div class="mx-auto max-w-md text-center border-2 border-dashed rounded-2xl p-6 text-gray-500 dark:text-gray-400">
                    <i class="fa-regular fa-box-open text-3xl mb-2"></i>
                    <p class="mb-2">No hay líneas.</p>
                    <button type="button" class="text-violet-600 hover:underline" wire:click="agregarFila">Agregar primera línea</button>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>

          {{-- Totales presentacionales --}}
          @php
            $totalItems = collect($entradas)->sum('cantidad');
            $subtotal   = collect($entradas)->sum(fn($e) => (float)($e['cantidad'] ?? 0) * (float)($e['precio_unitario'] ?? 0));
            $iva        = 0.00;
            $total      = $subtotal + $iva;
          @endphp

          <tfoot class="bg-gray-50 dark:bg-gray-800/40">
            <tr>
              <td colspan="3"></td>
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Ítems:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">{{ number_format($totalItems ?? 0) }}</td>
              <td></td>
            </tr>
            <tr>
              <td colspan="3"></td>
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Subtotal:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">${{ number_format($subtotal ?? 0, 2, ',', '.') }}</td>
              <td></td>
            </tr>
            <tr class="bg-gray-100 dark:bg-gray-800/60">
              <td colspan="3"></td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">Total:</td>
              <td class="px-4 py-2 text-right text-lg font-extrabold text-gray-900 dark:text-white">${{ number_format($total ?? 0, 2, ',', '.') }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </section>

      {{-- ===== FILTROS / HISTORIAL ===== --}}
      <div class="px-6 md:px-8 py-8">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-4 md:p-6 shadow-sm">
          <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
              <span class="inline-grid place-items-center w-9 h-9 rounded-xl bg-indigo-100 text-indigo-700"><i class="fa-solid fa-clipboard-list"></i></span>
              <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-white">Entradas registradas</h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label for="filtro-desde" class="block text-[11px] text-gray-500 mb-1">Desde</label>
                <input id="filtro-desde" type="date" wire:model.live="filtro_desde" class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
              </div>
              <div>
                <label for="filtro-hasta" class="block text-[11px] text-gray-500 mb-1">Hasta</label>
                <input id="filtro-hasta" type="date" wire:model.live="filtro_hasta" class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
              </div>
              <div class="flex items-end gap-2">
                <button type="button" wire:click="aplicarFiltros" class="h-11 px-4 rounded-2xl bg-violet-600 hover:bg-violet-700 text-white">Buscar</button>
                <button type="button" wire:click="limpiarFiltros" class="h-11 px-4 rounded-2xl border-2 border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">Limpiar</button>
              </div>
            </div>
          </header>

        
          <div class="mt-4">
            {{ $entradasMercancia->onEachSide(1)->links() }}
          </div>
        </div>
      </div>
    </section>

    {{-- ========== FOOTER STICKY ACCIONES ========== --}}
    <footer class="sticky bottom-0 inset-x-0 bg-white/85 dark:bg-gray-900/85 backdrop-blur border-t border-gray-200 dark:border-gray-800" aria-label="Acciones">
      <div class="px-4 md:px-8 py-4">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-center">
          {{-- Totales --}}
          <div class="xl:col-span-6 flex flex-wrap items-center gap-3 text-sm md:text-base text-gray-700 dark:text-gray-300">
            <span class="font-semibold">Ítems:</span>
            <span class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($totalItems ?? 0) }}</span>
            <span class="font-semibold ml-4">Total:</span>
            <span class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white">${{ number_format($total ?? 0, 2, ',', '.') }}</span>
          </div>

          {{-- Botones --}}
          <div class="xl:col-span-6 flex flex-wrap justify-end gap-2">
            <button type="button" wire:click="agregarFila" class="h-11 px-4 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100"><i class="fa-solid fa-plus mr-2"></i> Agregar ítem</button>
            <button type="button" wire:click="cancelarEntrada" class="h-11 px-4 rounded-2xl border-2 border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800"><i class="fa-solid fa-xmark mr-2"></i> Cancelar</button>
            <button type="submit" wire:loading.attr="disabled" class="h-11 px-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white"><i class="fa-solid fa-floppy-disk mr-2"></i> <span wire:loading.remove wire:target="crearEntrada">Guardar entrada</span><span wire:loading wire:target="crearEntrada">Guardando…</span></button>
          </div>
        </div>
      </div>
    </footer>
  </section>

  {{-- FAB móvil --}}
  <button type="button" wire:click="agregarFila" class="md:hidden fixed bottom-20 right-5 h-12 w-12 rounded-full shadow-2xl bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 grid place-items-center hover:scale-105 transition" title="Agregar ítem" aria-label="Agregar ítem">
    <i class="fa-solid fa-plus"></i>
  </button>
</form>
