{{-- resources/views/livewire/cotizaciones/cotizacion.blade.php --}}

@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Evita conflictos: Alpine espera a que Livewire inicie
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

@php
  // ====== Helpers visuales (sin tocar tu componente) ======
  $estado = $estado ?? 'borrador';

  $tieneCotizacion = (bool) ($cotizacion?->id ?? false);

  // Totales en vivo desde $lineas (para que funcione incluso sin persistir)
  $calcLinea = function(array $l){
    $cant  = (float)($l['cantidad'] ?? 0);
    $precio= (float)($l['precio_unitario'] ?? 0);
    $desc  = (float)($l['descuento_pct'] ?? 0);
    $impP  = (float)($l['impuesto_pct'] ?? 0);

    $base = ($cant * $precio) * (1 - ($desc/100));
    $imp  = $base * ($impP/100);
    $tot  = $base + $imp;

    return [
      'base' => round($base, 2),
      'imp'  => round($imp, 2),
      'tot'  => round($tot, 2),
    ];
  };

  $subtotalVista  = round(collect($lineas ?? [])->sum(fn($l) => $calcLinea($l)['base']), 2);
  $impuestosVista = round(collect($lineas ?? [])->sum(fn($l) => $calcLinea($l)['imp']), 2);
  $totalVista     = round($subtotalVista + $impuestosVista, 2);

  $estadoBadge = match($estado){
    'borrador'   => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    'enviada'    => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200',
    'aprobada'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
    'convertida' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/40 dark:text-teal-200',
    'cancelada'  => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200',
    default      => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
  };

  $bloqueada = in_array($estado, ['cancelada','convertida'], true);
@endphp

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
    x-id="['imgviewer']"
  >
    <div
      x-show="open"
      x-transition.opacity
      x-cloak
      class="fixed inset-0 z-[200] flex items-center justify-center"
      @keydown.escape.window="open = false"
    >
      <div class="absolute inset-0 bg-black/70" @click="open = false"></div>

      <div class="relative z-10 max-w-4xl w-[90vw] max-h-[90vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden">
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
          <img :src="src" :alt="title || 'Imagen de producto'" class="max-h-[80vh] w-auto object-contain rounded-xl">
        </div>
      </div>
    </div>
  </div>
  {{-- ================= FIN PREVISUALIZADOR ================= --}}

  {{-- ================= HEADER SUPERIOR ================= --}}
  <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-slate-200 dark:border-slate-700 bg-white/70 dark:bg-slate-900/60 backdrop-blur">
        <span class="h-2 w-2 rounded-full bg-indigo-600"></span>
        <span class="text-xs font-semibold text-slate-700 dark:text-slate-200">Cotizaciones</span>
        <span class="text-xs text-slate-400">/</span>
        <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $estadoBadge }}">
          {{ ucfirst($estado) }}
        </span>
      </div>

      <h1 class="mt-3 text-2xl md:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
        Cotización
        <span class="text-slate-400 font-semibold">
          {{ $tieneCotizacion ? ('#'.$cotizacion->id) : '(nueva)' }}
        </span>
      </h1>

      <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
        Usa <span class="font-semibold">Ctrl + K</span> para saltar al selector de producto.
      </p>
    </div>

    <div class="flex items-center gap-2">
      <span class="inline-flex items-center gap-2 px-3 py-2 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm">
        <i class="fa-solid fa-file-lines text-slate-500"></i>
        <span class="text-sm text-slate-700 dark:text-slate-200">
          Total: <strong class="text-slate-900 dark:text-white">$ {{ number_format($totalVista, 2) }}</strong>
        </span>
      </span>
    </div>
  </div>

  {{-- ================= CARD PRINCIPAL ================= --}}
  <section class="mt-3 rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">

    {{-- ===== PASO 1: Datos de cabecera ===== --}}
    <section class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-800" aria-label="Datos de la cotización">
      <header class="mb-6">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-9 md:w-10 h-9 md:h-10 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">1</span>
          Datos de cotización
        </h2>
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Cliente --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Cliente <span class="text-red-500">*</span>
          </label>

          <select
            wire:model.live="socio_negocio_id"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-base
                   focus:outline-none focus:ring-4 focus:ring-indigo-300/60
                   @error('socio_negocio_id') border-red-500 focus:ring-red-300 @enderror"
            @if($bloqueada) disabled @endif
          >
            <option value="">— Seleccione —</option>
            @foreach($clientes as $c)
              <option value="{{ $c->id }}">{{ $c->razon_social }} ({{ $c->nit }})</option>
            @endforeach
          </select>

          @error('socio_negocio_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Fechas --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
              Fecha <span class="text-red-500">*</span>
            </label>
            <input type="date"
              wire:model.live="fecha"
              class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                     focus:outline-none focus:ring-4 focus:ring-indigo-300/60
                     @error('fecha') border-red-500 focus:ring-red-300 @enderror"
              @if($bloqueada) disabled @endif
            >
            @error('fecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Vencimiento</label>
            <input type="date"
              wire:model.live="vencimiento"
              class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                     focus:outline-none focus:ring-4 focus:ring-indigo-300/60"
              @if($bloqueada) disabled @endif
            >
          </div>
        </section>

        {{-- Lista de precio --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Lista de precio</label>
          <input
            type="text"
            placeholder="Ej: Mayorista / Mostrador…"
            wire:model.live.debounce.400ms="lista_precio"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                   focus:outline-none focus:ring-4 focus:ring-indigo-300/60"
            @if($bloqueada) disabled @endif
          >
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Si defines lista, al elegir producto se sugerirá precio.</p>
        </section>

      </div>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Términos / Notas --}}
        <section class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Términos</label>
            <input
              type="text"
              placeholder="Condiciones, validez de oferta, etc."
              wire:model.live.debounce.400ms="terminos_pago"
              class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                     focus:outline-none focus:ring-4 focus:ring-indigo-300/60"
              @if($bloqueada) disabled @endif
            >
          </div>
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Notas</label>
            <input
              type="text"
              placeholder="Observaciones visibles en el documento"
              wire:model.live.debounce.400ms="notas"
              class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                     focus:outline-none focus:ring-4 focus:ring-indigo-300/60"
              @if($bloqueada) disabled @endif
            >
          </div>
        </section>
      </div>
    </section>

    {{-- ===== PASO 2: Líneas ===== --}}
    <section class="p-6 md:p-8" aria-label="Líneas de la cotización">
      <header class="mb-6 flex items-center justify-between gap-3">
        <div>
          <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="inline-grid place-items-center w-9 h-9 md:w-10 md:h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">2</span>
            Agrega productos
          </h2>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Incluye bodega, descuentos e impuestos por línea.</p>
        </div>

        <button
          type="button"
          wire:click="addLinea"
          class="h-11 px-4 rounded-2xl bg-violet-600 hover:bg-violet-700 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed"
          wire:loading.attr="disabled"
          wire:target="addLinea,removeLinea,guardar,enviar,aprobarYGenerarPedido,cancelar"
          @if($bloqueada) disabled @endif
        >
          <i class="fa-solid fa-plus mr-2"></i>
          + Línea
        </button>
      </header>

      <section class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto shadow" aria-label="Tabla de líneas">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <tr>
              <th class="px-4 py-3 text-left">Imagen</th>
              <th class="px-4 py-3 text-left">Producto</th>
              <th class="px-4 py-3 text-left">Bodega</th>
              <th class="px-4 py-3 text-right">Cant.</th>
              <th class="px-4 py-3 text-right">Precio</th>
              <th class="px-4 py-3 text-right">Desc %</th>
              <th class="px-4 py-3 text-right">Imp %</th>
              <th class="px-4 py-3 text-right">Imp $</th>
              <th class="px-4 py-3 text-right">Total línea</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($lineas as $i => $l)
              @php
                $prodSel = !empty($l['producto_id']) ? $productos->firstWhere('id', $l['producto_id']) : null;
                $imgUrl  = $prodSel?->imagen_url ?? null;
                $calc    = $calcLinea($l);
              @endphp

              <tr wire:key="cot-linea-{{ $i }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">

                {{-- Imagen --}}
                <td class="px-4 py-3 w-[72px]">
                  <div class="h-12 w-12 rounded-xl bg-gray-100 dark:bg-gray-800 grid place-items-center overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700">
                    @if($imgUrl)
                      <button type="button" class="group relative h-full w-full"
                        @click.stop="$dispatch('preview-image', { src: '{{ $imgUrl }}', title: '{{ e($prodSel->nombre ?? 'Producto') }}' })"
                        aria-label="Ver imagen de {{ $prodSel->nombre ?? 'producto' }}" title="Ver imagen">
                        <img src="{{ $imgUrl }}" class="h-full w-full object-cover transition group-hover:scale-105" alt="img-{{ $prodSel->nombre ?? 'producto' }}">
                        <span class="pointer-events-none absolute inset-0 ring-2 ring-indigo-400/0 group-hover:ring-indigo-400/60 transition"></span>
                        <span class="pointer-events-none absolute bottom-1 right-1 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded-md opacity-0 group-hover:opacity-100 transition">Ver</span>
                      </button>
                    @else
                      <div class="h-full w-full grid place-items-center">
                        <svg viewBox="0 0 24 24" class="h-6 w-6 text-gray-400" aria-hidden="true">
                          <path fill="currentColor" d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14l4-4h12l2 2Zm-5-9a2 2 0 1 1-4.001-.001A2 2 0 0 1 16 10Z"/>
                        </svg>
                      </div>
                    @endif
                  </div>
                </td>

                {{-- Producto --}}
                <td class="px-4 py-3 min-w-[300px]">
                  <select
                    data-first-product
                    wire:model.live="lineas.{{ $i }}.producto_id"
                    wire:change="setProducto({{ $i }}, $event.target.value)"
                    class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60
                           @error('lineas.'.$i.'.producto_id') border-red-500 focus:ring-red-300 @enderror"
                    @if($bloqueada) disabled @endif
                  >
                    <option value="">— Seleccione —</option>
                    @foreach($productos as $p)
                      <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                  </select>
                  @error('lineas.'.$i.'.producto_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </td>

                {{-- Bodega --}}
                <td class="px-4 py-3 min-w-[200px]">
                  <select
                    wire:model.live="lineas.{{ $i }}.bodega_id"
                    class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                    @if($bloqueada) disabled @endif
                  >
                    <option value="">— Seleccione —</option>
                    @foreach($bodegas as $b)
                      <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                  </select>
                </td>

                {{-- Cantidad --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="0.001" min="0.001"
                    wire:model.live.debounce.250ms="lineas.{{ $i }}.cantidad"
                    wire:change="setCantidad({{ $i }}, $event.target.value)"
                    class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60
                           @error('lineas.'.$i.'.cantidad') border-red-500 focus:ring-red-300 @enderror"
                    @if($bloqueada) disabled @endif
                  >
                  @error('lineas.'.$i.'.cantidad') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </td>

                {{-- Precio --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="0.01" min="0"
                    wire:model.live.debounce.250ms="lineas.{{ $i }}.precio_unitario"
                    wire:change="setPrecio({{ $i }}, $event.target.value)"
                    class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60
                           @error('lineas.'.$i.'.precio_unitario') border-red-500 focus:ring-red-300 @enderror"
                    @if($bloqueada) disabled @endif
                  >
                  @error('lineas.'.$i.'.precio_unitario') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </td>

                {{-- Descuento --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="0.001" min="0"
                    wire:model.live.debounce.250ms="lineas.{{ $i }}.descuento_pct"
                    wire:change="setDescuento({{ $i }}, $event.target.value)"
                    class="w-24 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                    @if($bloqueada) disabled @endif
                  >
                </td>

                {{-- Impuesto --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="0.001" min="0"
                    wire:model.live.debounce.250ms="lineas.{{ $i }}.impuesto_pct"
                    wire:change="setImpuesto({{ $i }}, $event.target.value)"
                    class="w-24 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                           focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                    @if($bloqueada) disabled @endif
                  >
                </td>

                {{-- Impuesto $ --}}
                <td class="px-4 py-3 text-right">
                  $ {{ number_format($calc['imp'], 2) }}
                </td>

                {{-- Total línea --}}
                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                  $ {{ number_format($calc['tot'], 2) }}
                </td>

                {{-- Acciones --}}
                <td class="px-4 py-3 text-right">
                  <button type="button"
                    wire:click="removeLinea({{ $i }})"
                    class="h-10 px-3 rounded-xl bg-rose-500 hover:bg-rose-600 text-white disabled:opacity-50 disabled:cursor-not-allowed"
                    wire:loading.attr="disabled"
                    wire:target="removeLinea,guardar,enviar,aprobarYGenerarPedido,cancelar"
                    @if($bloqueada) disabled @endif
                    title="Quitar línea"
                  >
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </td>

              </tr>
            @empty
              <tr>
                <td colspan="10" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                  No hay líneas. Usa <button type="button" class="text-violet-600 hover:underline" wire:click="addLinea">+ Línea</button>.
                </td>
              </tr>
            @endforelse
          </tbody>

          <tfoot class="bg-gray-50 dark:bg-gray-800/40">
            <tr>
              <td colspan="7"></td>
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Subtotal:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">$ {{ number_format($subtotalVista, 2) }}</td>
              <td></td>
            </tr>
            <tr>
              <td colspan="7"></td>
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Impuestos:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">$ {{ number_format($impuestosVista, 2) }}</td>
              <td></td>
            </tr>
            <tr class="bg-gray-100 dark:bg-gray-800/60">
              <td colspan="7"></td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">Total:</td>
              <td class="px-4 py-2 text-right text-lg font-extrabold text-gray-900 dark:text-white">$ {{ number_format($totalVista, 2) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </section>
    </section>

    {{-- ===== FOOTER STICKY DE ACCIONES (Stepper + Botones) ===== --}}
    <footer class="sticky bottom-0 inset-x-0 bg-white/85 dark:bg-gray-900/85 backdrop-blur border-t border-gray-200 dark:border-gray-800">
      <div class="px-4 md:px-8 py-4">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-center">

          {{-- Totales / Estado --}}
          <div class="xl:col-span-3 flex flex-wrap items-center gap-2 text-sm md:text-base text-gray-700 dark:text-gray-300">
            <span class="font-semibold">Total:</span>
            <span class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white">
              $ {{ number_format($totalVista, 2) }}
            </span>

            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] {{ $estadoBadge }}">
              <i class="fa-solid fa-circle-info"></i>
              {{ ucfirst($estado) }}
            </span>

            @if($bloqueada)
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-gray-200 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                <i class="fa-solid fa-lock"></i>
                Bloqueada
              </span>
            @endif
          </div>

          {{-- Stepper --}}
          <div class="xl:col-span-6">
            <div
              x-data="{
                estado: @entangle('estado'),
                get currentStep(){
                  if (this.estado === 'cancelada') return 4
                  if (this.estado === 'convertida') return 4
                  if (this.estado === 'aprobada') return 3
                  if (this.estado === 'enviada') return 2
                  return 1
                },
                stepStatus(n){
                  if (n < this.currentStep) return 'complete'
                  if (n === this.currentStep) return 'current'
                  return 'upcoming'
                },
                get progressPct(){
                  return [0,33,66,100][this.currentStep - 1] || 0
                }
              }"
              class="w-full"
            >
              <div class="relative mx-auto max-w-3xl">
                <div class="absolute left-0 right-0 top-5 h-1 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                  <div class="h-1 bg-indigo-600 dark:bg-indigo-400 transition-all duration-500"
                       :style="`width: ${progressPct}%`"></div>
                </div>

                <ol class="relative z-10 grid grid-cols-4 gap-3 md:gap-6">
                  <li class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                         :class="{
                           'bg-indigo-600 text-white border-indigo-600 shadow': stepStatus(1) !== 'upcoming',
                           'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(1) === 'upcoming'
                         }"
                         title="Borrador">
                      <i class="fa-solid fa-pen text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Borrador</div>
                    <div class="hidden md:block text-xs text-slate-500">Guardar</div>
                  </li>

                  <li class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                         :class="{
                           'bg-violet-600 text-white border-violet-600 shadow': stepStatus(2) !== 'upcoming',
                           'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(2) === 'upcoming'
                         }"
                         title="Enviar">
                      <i class="fa-solid fa-paper-plane text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Enviar</div>
                    <div class="hidden md:block text-xs text-slate-500">Correo</div>
                  </li>

                  <li class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                         :class="{
                           'bg-emerald-600 text-white border-emerald-600 shadow': stepStatus(3) !== 'upcoming',
                           'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(3) === 'upcoming'
                         }"
                         title="Aprobar / Convertir">
                      <i class="fa-solid fa-check text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Aprobar</div>
                    <div class="hidden md:block text-xs text-slate-500">Convertir</div>
                  </li>

                  <li class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                         :class="{
                           'bg-rose-600 text-white border-rose-600 shadow': estado === 'cancelada',
                           'bg-teal-600 text-white border-teal-600 shadow': estado === 'convertida',
                           'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': !['cancelada','convertida'].includes(estado)
                         }"
                         :title="estado === 'cancelada' ? 'Cancelada' : (estado === 'convertida' ? 'Convertida' : 'Fin')">
                      <i :class="estado === 'cancelada' ? 'fa-solid fa-ban' : (estado === 'convertida' ? 'fa-solid fa-right-left' : 'fa-regular fa-circle')"
                         class="text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium"
                         x-text="estado === 'cancelada' ? 'Cancelada' : (estado === 'convertida' ? 'Convertida' : 'Fin')"></div>
                    <div class="hidden md:block text-xs text-slate-500">—</div>
                  </li>
                </ol>
              </div>
            </div>
          </div>

          {{-- Acciones --}}
          <div class="xl:col-span-3">
            <div class="flex flex-col items-end gap-2">

              <div class="flex flex-wrap justify-end gap-2">

                {{-- Guardar --}}
                <button type="button"
                  class="h-11 px-4 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed transition"
                  wire:click="guardar"
                  wire:loading.attr="disabled"
                  wire:target="guardar,enviar,aprobarYGenerarPedido,cancelar"
                  @if($bloqueada) disabled @endif
                >
                  <i class="fa-solid fa-floppy-disk mr-2"></i>
                  <span wire:loading.remove wire:target="guardar">Guardar</span>
                  <span wire:loading wire:target="guardar">Guardando…</span>
                </button>

                {{-- Enviar --}}
                <button type="button"
                  class="h-11 px-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed transition"
                  wire:click="enviar"
                  wire:loading.attr="disabled"
                  wire:target="enviar,guardar,aprobarYGenerarPedido,cancelar"
                  @if($bloqueada) disabled @endif
                  title="Enviar por correo"
                >
                  <i class="fa-solid fa-paper-plane mr-2"></i>
                  <span wire:loading.remove wire:target="enviar">Enviar</span>
                  <span wire:loading wire:target="enviar">Preparando…</span>
                </button>

                {{-- Aprobar y generar pedido --}}
                <button type="button"
                  class="h-11 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed transition"
                  wire:click="aprobarYGenerarPedido"
                  wire:loading.attr="disabled"
                  wire:target="aprobarYGenerarPedido,guardar,enviar,cancelar"
                  @if($bloqueada) disabled @endif
                  title="Convierte la cotización en un pedido"
                >
                  <i class="fa-solid fa-cart-shopping mr-2"></i>
                  <span wire:loading.remove wire:target="aprobarYGenerarPedido">Convertir</span>
                  <span wire:loading wire:target="aprobarYGenerarPedido">Convirtiendo…</span>
                </button>

                {{-- Cancelar --}}
                <button type="button"
                  class="h-11 px-4 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed transition"
                  wire:click="cancelar"
                  wire:loading.attr="disabled"
                  wire:target="cancelar,guardar,enviar,aprobarYGenerarPedido"
                  @if($bloqueada) disabled @endif
                  title="Cancelar la cotización"
                >
                  <i class="fa-solid fa-ban mr-2"></i>
                  <span wire:loading.remove wire:target="cancelar">Cancelar</span>
                  <span wire:loading wire:target="cancelar">Cancelando…</span>
                </button>

              </div>

              @if(!$tieneCotizacion)
                <p class="text-xs text-slate-500 dark:text-slate-400">
                  * Se crea al guardar o enviar.
                </p>
              @endif

            </div>
          </div>

        </div>
      </div>
    </footer>

  </section>

  
  {{-- <livewire:cotizaciones.enviar-cotizacion-correo /> --}}
</div>
