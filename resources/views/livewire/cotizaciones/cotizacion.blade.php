{{-- resources/views/livewire/cotizaciones/cotizacion-form.blade.php --}}

@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div
  x-data="{
    goPicker(){
      const el = document.querySelector('[data-first-product]');
      if (el) { el.focus(); el.scrollIntoView({behavior:'smooth', block:'center'}); }
    }
  }"
  x-on:keydown.window.ctrl.k.prevent="goPicker()"
  x-on:keydown.window.meta.k.prevent="goPicker()"
  class="p-6 md:p-8"
>

  {{-- ================= PREVISUALIZADOR DE IMÁGENES (Lightbox) ================= --}}
  <div
    x-data="{ open:false, src:null, title:'' }"
    x-on:preview-image.window="
      src   = $event.detail.src;
      title = $event.detail.title || '';
      open  = true;
    "
    x-cloak
  >
    <div
      x-show="open"
      x-transition.opacity
      class="fixed inset-0 z-[200] flex items-center justify-center"
      @keydown.escape.window="open = false"
    >
      <div class="absolute inset-0 bg-black/70" @click="open = false"></div>

      <div class="relative z-10 max-w-4xl w-[92vw] max-h-[92vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-800">
          <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100" x-text="title || 'Imagen de producto'"></h3>
          <button
            type="button"
            class="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700"
            @click="open = false"
            aria-label="Cerrar"
          >
            <i class="fa-solid fa-xmark text-sm"></i>
          </button>
        </div>

        <div class="p-3 flex items-center justify-center bg-gray-50 dark:bg-gray-900">
          <img :src="src" :alt="title || 'Imagen de producto'" class="max-h-[82vh] w-auto object-contain rounded-xl">
        </div>
      </div>
    </div>
  </div>
 

  {{-- ================= CARD PRINCIPAL ================= --}}
  <section class="mt-6 md:mt-8 rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">

    {{-- ================= HEADER TOP (título + estado + acciones rápidas) ================= --}}
    <header class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-800">
      <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">

        <div>
          <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-2xl grid place-items-center bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
              <i class="fa-solid fa-file-invoice"></i>
            </div>

            <div>
              <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white">
                Cotización
                @if($cotizacion && !empty($cotizacion->id))
                  <span class="text-indigo-600">#{{ $cotizacion->numero ?? $cotizacion->id }}</span>
                @endif
              </h1>

              <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Documento comercial (no mueve inventario, no genera asientos).
              </p>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold
                         bg-indigo-50 text-indigo-700 border border-indigo-200
                         dark:bg-indigo-900/30 dark:text-indigo-200 dark:border-indigo-700">
              <span class="h-2 w-2 rounded-full bg-indigo-600"></span>
              Estado: {{ strtoupper($estado ?? 'BORRADOR') }}
            </span>

            @if($cotizacion && !empty($cotizacion->id))
              <span class="text-xs text-gray-500 dark:text-gray-300">
                ID: {{ $cotizacion->id }}
              </span>
            @endif

            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px]
                         bg-emerald-50 text-emerald-700 border border-emerald-200
                         dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-700">
              <i class="fa-solid fa-circle-info"></i>
              Ctrl/⌘ + K para ir al primer producto
            </span>
          </div>
        </div>

       
      </div>
    </header>


    {{-- ================= PASO 1: CABECERA ================= --}}
    <section class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-800" aria-label="Datos de la cotización">

      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-9 md:w-10 h-9 md:h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">
            1
          </span>
          Datos generales
        </h2>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Cliente --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Cliente <span class="text-red-500">*</span>
          </label>

          <select
            wire:model.live="socio_negocio_id"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 bg-white dark:bg-gray-800 dark:text-white
                   focus:outline-none focus:ring-4
                   @error('socio_negocio_id') border-red-500 focus:ring-red-300 @else border-gray-200 dark:border-gray-700 focus:ring-violet-300/60 @enderror"
          >
            <option value="">— Seleccione —</option>
            @foreach($clientes as $c)
              <option value="{{ $c->id }}">{{ $c->razon_social }}@if(!empty($c->nit)) ({{ $c->nit }}) @endif</option>
            @endforeach
          </select>

          @error('socio_negocio_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Fechas --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:col-span-2">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
              Fecha <span class="text-red-500">*</span>
            </label>
            <input
              type="date"
              wire:model.live="fecha"
              class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 bg-white dark:bg-gray-800 dark:text-white
                     focus:outline-none focus:ring-4
                     @error('fecha') border-red-500 focus:ring-red-300 @else border-gray-200 dark:border-gray-700 focus:ring-violet-300/60 @enderror"
            >
            @error('fecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
              Vencimiento
            </label>
            {{-- ✅ tu propiedad real es "vencimiento" --}}
            <input
              type="date"
              wire:model.live="vencimiento"
              class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                     focus:outline-none focus:ring-4 focus:ring-violet-300/60"
            >
            @error('vencimiento') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>
        </section>

      </div>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Términos de pago --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Términos de pago
          </label>
          <input
            type="text"
            placeholder="Ej: Contado, 30 días..."
            wire:model.live.debounce.350ms="terminos_pago"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                   focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          >
          @error('terminos_pago') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Lista de precio (opcional) --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Lista de precio (opcional)
          </label>
          <input
            type="text"
            placeholder="Ej: General, Mayorista..."
            wire:model.live.debounce.350ms="lista_precio"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                   focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          >
          @error('lista_precio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

      </div>

      {{-- Notas --}}
      <div class="mt-6">
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
          Notas
        </label>
        <textarea
          rows="3"
          wire:model.live.debounce.350ms="notas"
          class="w-full px-4 py-3 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                 focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          placeholder="Observaciones adicionales…"
        ></textarea>
        @error('notas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
      </div>

    </section>
    {{-- ================= FIN PASO 1 ================= --}}


    {{-- ================= PASO 2: LÍNEAS ================= --}}
    <section class="p-6 md:p-8" aria-label="Líneas de la cotización">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h2 class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-9 md:w-10 h-9 md:h-10 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
            2
          </span>
          Agrega productos
        </h2>

        <button
          type="button"
          wire:click="addLinea"
          wire:loading.attr="disabled"
          wire:target="addLinea"
          class="h-11 px-4 rounded-2xl bg-indigo-50 text-indigo-700 border-2 border-indigo-200
                 dark:bg-indigo-900/30 dark:text-indigo-200 dark:border-indigo-700
                 text-sm font-extrabold hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
        >
          <i class="fa-solid fa-plus"></i>
          Nueva línea
        </button>
      </div>

      <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto shadow" aria-label="Tabla de líneas">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <tr>
              <th class="px-4 py-3 text-left">Producto</th>
              <th class="px-4 py-3 text-right">Cant.</th>
              <th class="px-4 py-3 text-right">Precio</th>
              <th class="px-4 py-3 text-right">Desc %</th>
              <th class="px-4 py-3 text-right">% IVA</th>
              <th class="px-4 py-3 text-right">Base</th>
              <th class="px-4 py-3 text-right">IVA $</th>
              <th class="px-4 py-3 text-right">Total línea</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($lineas as $i => $l)
              @php
                $cant   = max(1, (float)($l['cantidad'] ?? 1));
                $precio = max(0, (float)($l['precio_unitario'] ?? 0));
                $desc   = min(100, max(0, (float)($l['descuento_pct'] ?? 0)));
                $ivaPct = min(100, max(0, (float)($l['impuesto_pct'] ?? 0)));

                $base     = $cant * $precio * (1 - $desc/100);
                $ivaMonto = round($base * $ivaPct/100, 2);
                $totalLin = round($base + $ivaMonto, 2);

                $prodSel = !empty($l['producto_id']) ? $productos->firstWhere('id', $l['producto_id']) : null;
              @endphp

              <tr wire:key="cot-linea-{{ $i }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">

                {{-- Producto --}}
                <td class="px-4 py-3 min-w-[360px]">
                  <select
                    @if($i === 0) data-first-product @endif
                    wire:model.live="lineas.{{ $i }}.producto_id"
                    class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                  >
                    <option value="">— Seleccione —</option>
                    @foreach($productos as $p)
                      <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                  </select>
                  @error('lineas.'.$i.'.producto_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                  @if($prodSel)
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-300">
                      Precio base: <span class="font-semibold">${{ number_format((float)($prodSel->precio ?? 0), 2) }}</span>
                    </div>
                  @endif
                </td>

                {{-- Cantidad --}}
                <td class="px-4 py-3 text-right">
                  <input
                    type="number" step="0.001" min="1"
                    wire:model.lazy="lineas.{{ $i }}.cantidad"
                    class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                  >
                  @error('lineas.'.$i.'.cantidad') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </td>

                {{-- Precio --}}
                <td class="px-4 py-3 text-right">
                  <input
                    type="number" step="0.01" min="0"
                    wire:model.lazy="lineas.{{ $i }}.precio_unitario"
                    class="w-32 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                  >
                  @error('lineas.'.$i.'.precio_unitario') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </td>

                {{-- Descuento --}}
                <td class="px-4 py-3 text-right">
                  <input
                    type="number" step="0.001" min="0" max="100"
                    wire:model.live.debounce.200ms="lineas.{{ $i }}.descuento_pct"
                    class="w-24 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                  >
                  @error('lineas.'.$i.'.descuento_pct') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </td>

                {{-- IVA % --}}
                <td class="px-4 py-3 text-right">
                  <input
                    type="number" step="0.001" min="0" max="100"
                    wire:model.live.debounce.200ms="lineas.{{ $i }}.impuesto_pct"
                    class="w-24 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                  >
                  @error('lineas.'.$i.'.impuesto_pct') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </td>

                {{-- Base --}}
                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                  ${{ number_format($base, 2) }}
                </td>

                {{-- IVA $ --}}
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">
                  ${{ number_format($ivaMonto, 2) }}
                </td>

                {{-- Total línea --}}
                <td class="px-4 py-3 text-right font-extrabold text-gray-900 dark:text-white">
                  ${{ number_format($totalLin, 2) }}
                </td>

                {{-- Acciones --}}
                <td class="px-4 py-3 text-right">
                  <div class="inline-flex items-center gap-2">
                    <button
                      type="button"
                      wire:click="addLinea"
                      wire:loading.attr="disabled"
                      wire:target="addLinea,removeLinea,guardar,actualizar"
                      class="h-10 px-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      + Línea
                    </button>
                    <button
                      type="button"
                      wire:click="removeLinea({{ $i }})"
                      wire:loading.attr="disabled"
                      wire:target="addLinea,removeLinea,guardar,actualizar"
                      class="h-10 px-3 rounded-xl bg-red-500 hover:bg-red-600 text-white disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Quitar
                    </button>
                  </div>
                </td>

              </tr>
            @empty
              <tr>
                <td colspan="9" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                  No hay líneas. Usa
                  <button type="button" class="text-violet-600 hover:underline font-semibold" wire:click="addLinea">
                    + Línea
                  </button>.
                </td>
              </tr>
            @endforelse
          </tbody>

          <tfoot class="bg-gray-50 dark:bg-gray-800/40">
            <tr>
              <td colspan="6"></td>
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Subtotal:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                ${{ number_format($this->subtotal, 2) }}
              </td>
              <td></td>
            </tr>
            <tr>
              <td colspan="6"></td>
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Impuestos:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">
                ${{ number_format($this->impuestosTotal, 2) }}
              </td>
              <td></td>
            </tr>
            <tr class="bg-gray-100 dark:bg-gray-800/60">
              <td colspan="6"></td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">Total:</td>
              <td class="px-4 py-2 text-right text-lg font-extrabold text-gray-900 dark:text-white">
                ${{ number_format($this->total, 2) }}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>

    </section>
    {{-- ================= FIN PASO 2 ================= --}}


    {{-- ================= FOOTER STICKY ================= --}}
    <footer class="sticky bottom-0 inset-x-0 bg-white/85 dark:bg-gray-900/85 backdrop-blur border-t border-gray-200 dark:border-gray-800">
      <div class="px-4 md:px-8 py-4">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-center">

          {{-- Total --}}
          <div class="xl:col-span-4 flex flex-wrap items-center gap-2 text-sm md:text-base text-gray-700 dark:text-gray-300">
            <span class="font-semibold">Total:</span>
            <span class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white">
              $ {{ number_format($this->total, 2) }}
            </span>

            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px]
                         bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">
              <i class="fa-solid fa-file-lines"></i>
              Cotización
            </span>
          </div>

          {{-- Estado / hint --}}
          <div class="xl:col-span-5">
            <div class="text-xs md:text-sm text-slate-600 dark:text-slate-300">
              Estado:
              <span class="font-semibold">{{ $estado ?? 'borrador' }}</span>
              <span class="mx-2 text-slate-400">•</span>
              Este documento no genera inventario ni contabilidad.
            </div>
          </div>

          {{-- Acciones --}}
          <div class="xl:col-span-3">
            <div class="flex flex-wrap justify-end gap-2">

              @if($cotizacion && !empty($cotizacion->id))
                <button
                  type="button"
                  wire:click="actualizar"
                  wire:loading.attr="disabled"
                  wire:target="actualizar"
                  @disabled(!$habilitarActualizar)
                  class="h-11 px-4 rounded-2xl bg-amber-600 hover:bg-amber-700 text-white shadow
                         disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <i class="fa-solid fa-rotate mr-2"></i>
                  <span wire:loading.remove wire:target="actualizar">Actualizar cambios</span>
                  <span wire:loading wire:target="actualizar">Actualizando…</span>
                </button>
              @endif

              <button
                type="button"
                wire:click="guardar"
                wire:loading.attr="disabled"
                wire:target="guardar"
                class="h-11 px-4 rounded-2xl bg-slate-900 hover:bg-black text-white shadow
                       disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <i class="fa-solid fa-floppy-disk mr-2"></i>
                <span wire:loading.remove wire:target="guardar">Guardar cotización</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
              </button>

            </div>
          </div>

        </div>
      </div>
    </footer>
    {{-- ================= FIN FOOTER STICKY ================= --}}

  </section>
  
</div>
