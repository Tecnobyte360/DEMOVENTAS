{{-- resources/views/livewire/notas-credito/form-nota-credito.blade.php --}}

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
    x-on:preview-image.window="src = $event.detail.src; title = $event.detail.title || ''; open = true;"
    x-id="['imgviewer']"
  >
    <template x-teleport="body">
      <div
        x-show="open"
        x-transition.opacity
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        :aria-labelledby="$id('imgviewer')"
        role="dialog" aria-modal="true"
      >
        <div class="absolute inset-0 bg-black/70" @click="open = false"></div>

        <div
          class="relative max-w-5xl w-full bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden ring-1 ring-black/10 dark:ring-white/10"
          x-transition.scale.origin.center
        >
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate" :id="$id('imgviewer')" x-text="title || 'Vista previa'"></h3>
            <button class="inline-flex items-center justify-center w-9 h-9 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" @click="open = false" aria-label="Cerrar" title="Cerrar (Esc)">
              <i class="fa-solid fa-xmark text-lg text-gray-600 dark:text-gray-300"></i>
            </button>
          </div>

          <div class="bg-black/5 dark:bg-white/5 grid place-items-center max-h-[80vh]">
            <img x-show="!!src" :src="src" :alt="title || 'Imagen'" class="max-h-[78vh] w-auto object-contain" @click.stop>
          </div>

          <div class="flex items-center justify-end gap-2 px-4 py-3 border-t border-gray-200 dark:border-gray-800">
            <a :href="src" target="_blank" class="px-3 py-2 rounded-xl text-sm font-medium bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200">
              Abrir en pestaña nueva
            </a>
            <button @click="open = false" class="px-3 py-2 rounded-xl text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white">Cerrar</button>
          </div>
        </div>
      </div>
    </template>
  </div>
  {{-- ================= FIN PREVISUALIZADOR ================= --}}

  {{-- ================= HEADER / HERO ================= --}}
  <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-violet-600 via-indigo-600 to-blue-600 text-white shadow-2xl" aria-label="Encabezado de la nota crédito">
    <div class="px-6 md:px-8 py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center gap-3">
          <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/15 backdrop-blur">
            <i class="fas fa-file-invoice text-2xl"></i>
          </span>
          {{ $nota?->id ? 'Editar nota crédito' : 'Nueva nota crédito' }}
        </h1>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        @if($nota?->id)
          <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
            <i class="fa fa-pen-to-square"></i> Modo edición
          </span>
        @endif

        @if($proximo = $this->proximoPreview)
          <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
            <i class="fa fa-hashtag"></i> Próximo: {{ $proximo }}
          </span>
        @endif
      </div>
    </div>
  </section>

  {{-- ================= CARD PRINCIPAL ================= --}}
  <section class="mt-6 md:mt-8 rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">

    {{-- ===== PASO 1: Datos de cabecera ===== --}}
    <section class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-800" aria-label="Datos de la nota crédito">
      <header class="mb-6">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-9 md:w-10 h-9 md:h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">1</span>
          Datos de la nota crédito
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
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-base focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('socio_negocio_id') border-red-500 focus:ring-red-300 @enderror"
          >
            <option value="">— Seleccione —</option>
            @foreach($clientes as $c)
              <option value="{{ $c->id }}">{{ $c->razon_social }} ({{ $c->nit }})</option>
            @endforeach
          </select>
          @error('socio_negocio_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Serie --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Serie (NC)</label>
          <div class="flex items-center gap-3">
            <select
              wire:model.live="serie_id"
              class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-base focus:outline-none focus:ring-4 focus:ring-violet-300/60"
            >
              <option value="">— Seleccione —</option>
              @foreach($series as $s)
                <option value="{{ $s->id }}">{{ $s->nombre }} ({{ $s->prefijo }}: {{ $s->proximo }} → {{ $s->hasta }})</option>
              @endforeach
            </select>

            @if($serieDefault)
              <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200 text-xs">
                <i class="fa fa-star"></i> {{ $serieDefault->nombre }}
              </span>
            @endif
          </div>
        </section>

        {{-- Fechas --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Fecha <span class="text-red-500">*</span></label>
            <input type="date" wire:model.live="fecha" class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('fecha') border-red-500 focus:ring-red-300 @enderror">
            @error('fecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Motivo</label>
            <input type="text" wire:model.live.debounce.300ms="motivo" placeholder="Devolución, descuento, bonificación…" class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
          </div>
        </section>
      </div>

      <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Factura a afectar (opcional) --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Factura a afectar (opcional)</label>
          <select
  wire:model.live="factura_id"
  class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 ..."
>
  <option value="">— Ninguna —</option>
  @foreach($facturasCliente as $f)
    <option value="{{ $f->id }}">
      #{{ $f->prefijo ? ($f->prefijo.'-'.$f->numero) : $f->numero }}
      · {{ $f->fecha }}
      · $ {{ number_format($f->total, 0) }}
    </option>
  @endforeach
</select>

          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Si eliges una factura, podrás **aplicar** esta NC a su saldo.</p>
        </section>

        {{-- Cuenta de devolución / CxC --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Cuenta contable para devolver
          </label>

          @php
            $selectedId = (int) ($cuenta_cobro_id ?? 0);
            $tieneSel   = $selectedId && ($cuentasCXC->contains('id',$selectedId) || $cuentasCaja->contains('id',$selectedId));
            $opActual   = (!$tieneSel && $selectedId) ? \App\Models\CuentasContables\PlanCuentas::find($selectedId) : null;
          @endphp

          <select
            wire:model.live.number="cuenta_cobro_id"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          >
            @if($opActual)
              <option value="{{ $opActual->id }}"> {{ $opActual->codigo }} — {{ $opActual->nombre }} (actual) </option>
            @endif

            @if($cuentasCXC->count())
              <optgroup label="Cuentas por cobrar (CxC)">
                @foreach($cuentasCXC as $c)
                  <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nombre }}</option>
                @endforeach
              </optgroup>
            @endif

            @if($cuentasCaja->count())
              <optgroup label="Caja y Bancos">
                @foreach($cuentasCaja as $c)
                  <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nombre }}</option>
                @endforeach
              </optgroup>
            @endif
          </select>

          @error('cuenta_cobro_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
          @enderror
        </section>

        {{-- Notas --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Notas</label>
          <input type="text" placeholder="Observaciones visibles en el documento" wire:model.live.debounce.400ms="notas" class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
        </section>
      </div>
    </section>

    {{-- ===== PASO 2: Líneas ===== --}}
    <section class="p-6 md:p-8" aria-label="Líneas de la nota crédito">
     
      <section class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto shadow" aria-label="Tabla de líneas">
        <table class="min-w-full text-sm">
        
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <tr>
              <th class="px-4 py-3 text-left">Imagen</th>
              <th class="px-4 py-3 text-left">Producto</th>
              <th class="px-4 py-3 text-left">Cuenta ingreso</th>
              <th class="px-4 py-3 text-left">Descripción</th>
              <th class="px-4 py-3 text-left">Bodega</th>
              <th class="px-4 py-3 text-right">Cant.</th>
              <th class="px-4 py-3 text-right">Precio</th>
              <th class="px-4 py-3 text-right">Desc %</th>
              <th class="px-4 py-3 text-left">Impuesto</th>
              <th class="px-4 py-3 text-right">% Imp.</th>
              <th class="px-4 py-3 text-right">Imp. $</th>
              <th class="px-4 py-3 text-right">Total línea</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($lineas as $i => $l)
             @php
                    $pid = (int)($lineas[$i]['producto_id'] ?? 0);
                    $bid = (int)($lineas[$i]['bodega_id'] ?? 0);
                  @endphp
                  <div class="mt-2" wire:key="stock-linea-{{ $i }}">
                    <div
                      wire:loading.flex
                      wire:target="lineas.{{ $i }}.producto_id, lineas.{{ $i }}.bodega_id"
                      class="items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-dashed border-gray-300 dark:border-gray-600">
                      <i class="fas fa-spinner fa-spin"></i>
                      <span class="text-sm italic">Consultando stock…</span>
                    </div>

                    @if (!$pid || !$bid)
                      <div
                        wire:loading.remove
                        wire:target="lineas.{{ $i }}.producto_id, lineas.{{ $i }}.bodega_id"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-500 border border-dashed border-gray-300 dark:border-gray-600">
                        <i class="fas fa-info-circle"></i>
                        <span class="text-sm italic">Seleccione producto y bodega</span>
                      </div>
                    @else
                      @php $stock = $this->getStockDeLinea($i); @endphp
                      <div
                        wire:loading.remove
                        wire:target="lineas.{{ $i }}.producto_id, lineas.{{ $i }}.bodega_id"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl bg-green-50 border border-green-300 text-green-700">
                        <i class="fas fa-warehouse"></i>
                        <span class="text-sm font-semibold">Stock actual: {{ number_format($stock, 0) }}</span>
                      </div>
                    @endif
              @php
                // Reutiliza tus helpers de Money (positivo/negativo lo maneja el componente)
                $ivaMonto = \App\Helpers\Money::ivaImporte($l);
                $totalLin = \App\Helpers\Money::totalLinea($l);
                $prodSel  = !empty($l['producto_id']) ? $productos->firstWhere('id', $l['producto_id']) : null;
                $imgUrl   = $prodSel?->imagen_url ?? null;
              @endphp

              <tr wire:key="linea-{{ $i }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
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
                        <svg viewBox="0 0 24 24" class="h-6 w-6 text-gray-400" aria-hidden="true"><path fill="currentColor" d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14l4-4h12l2 2Zm-5-9a2 2 0 1 1-4.001-.001A2 2 0 0 1 16 10Z"/></svg>
                      </div>
                    @endif
                  </div>
                </td>

                {{-- Producto --}}
                <td class="px-4 py-3 min-w-[260px]">
                  <select
                    data-first-product
                    wire:model.live="lineas.{{ $i }}.producto_id"
                    wire:change="setProducto({{ $i }}, $event.target.value)"
                    class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                  >
                    <option value="">— Seleccione —</option>
                    @foreach($productos as $p)
                      <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                  </select>
                </td>

                {{-- Cuenta ingreso (solo visual) --}}
                <td class="px-4 py-3 min-w-[260px]">
                  @php $prodSel = !empty($l['producto_id']) ? $productos->firstWhere('id', $l['producto_id']) : null; @endphp
                  <div class="space-y-1">
                    <select
                      wire:model.live.number="lineas.{{ $i }}.cuenta_ingreso_id"
                      class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('lineas.'.$i.'.cuenta_ingreso_id') border-red-500 focus:ring-red-300 @enderror"
                      disabled
                    >
                      <option value="">— Seleccione —</option>
                      @if($prodSel && $prodSel->cuentaIngreso)
                        <option value="{{ $prodSel->cuentaIngreso->id }}">
                          {{ $prodSel->cuentaIngreso->codigo }} — {{ $prodSel->cuentaIngreso->nombre }} (del producto)
                        </option>
                      @endif
                      @if($cuentasIngresos->count())
                        <optgroup label="PUC · Ingresos">
                          @foreach($cuentasIngresos as $cu)
                            <option value="{{ $cu->id }}">{{ $cu->codigo }} — {{ $cu->nombre }}</option>
                          @endforeach
                        </optgroup>
                      @endif
                    </select>

                    @error('lineas.'.$i.'.cuenta_ingreso_id')
                      <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror

                    @if($prodSel && $prodSel->cuentaIngreso && (int)($lineas[$i]['cuenta_ingreso_id'] ?? 0) !== (int)$prodSel->cuentaIngreso->id)
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 text-[11px]">
                        Sugerida: {{ $prodSel->cuentaIngreso->codigo }}
                      </span>
                    @endif
                  </div>
                </td>

                {{-- Descripción --}}
                <td class="px-4 py-3">
                  <input type="text" placeholder="Descripción (opcional)" wire:model.live.debounce.250ms="lineas.{{ $i }}.descripcion"
                         class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                </td>

                {{-- Bodega + STOCK (si tu NC retorna a inventario) --}}
                <td class="px-4 py-3 min-w-[160px]">
                  <select wire:model.live="lineas.{{ $i }}.bodega_id"
                          class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                    <option value="">— Seleccione —</option>
                    @foreach($bodegas as $b)
                      <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                  </select>

                
                  </div>
                </td>

                {{-- Cantidad (positiva = unidades que retornan) --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="1" min="1"
                         wire:model.live.debounce.200ms="lineas.{{ $i }}.cantidad"
                         class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                </td>

                {{-- Precio --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="0.01" min="0"
                         wire:model.live.debounce.200ms="lineas.{{ $i }}.precio_unitario"
                         class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                </td>

                {{-- Descuento --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="0.001" min="0"
                         wire:model.live.debounce.200ms="lineas.{{ $i }}.descuento_pct"
                         class="w-24 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                </td>

                {{-- Impuesto (selector) --}}
                <td class="px-4 py-3 min-w-[240px]">
                  <select
                    wire:model.live="lineas.{{ $i }}.impuesto_id"
                    wire:change="setImpuesto({{ $i }}, $event.target.value)"
                    class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60" disabled
                  >
                    <option value="">— Sin impuesto / Exento —</option>

                    @if($prodSel && $prodSel->impuesto && $prodSel->impuesto->activo)
                      <option value="{{ $prodSel->impuesto->id }}">
                        (Sugerido) {{ $prodSel->impuesto->codigo }} — {{ $prodSel->impuesto->nombre }}
                        @if(!is_null($prodSel->impuesto->porcentaje))
                          ({{ number_format($prodSel->impuesto->porcentaje, 2) }}%)
                        @else
                          (monto fijo)
                        @endif
                      </option>
                    @endif

                    @foreach($impuestosVentas as $imp)
                      <option value="{{ $imp->id }}">
                        {{ $imp->codigo }} — {{ $imp->nombre }}
                        @if(!is_null($imp->porcentaje))
                          ({{ number_format($imp->porcentaje, 2) }}%)
                        @else
                          (monto fijo)
                        @endif
                      </option>
                    @endforeach
                  </select>
                </td>

                {{-- % Impuesto --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="0.001" min="0"
                         wire:model.live.debounce.200ms="lineas.{{ $i }}.impuesto_pct"
                         class="w-24 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                </td>

                {{-- Impuesto $ --}}
                <td class="px-4 py-3 text-right">${{ number_format($ivaMonto, 2) }}</td>

                {{-- Total línea (se mostrará en positivo; contablemente la NC descuenta del total de factura) --}}
                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">${{ number_format($totalLin, 2) }}</td>

                {{-- Acciones --}}
                <td class="px-4 py-3 text-right">
                  <div class="inline-flex items-center gap-2">
                    <button type="button" wire:click="addLinea" class="h-10 px-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white"
                            wire:loading.attr="disabled" wire:target="addLinea,removeLinea,guardar,emitir,abrirAplicacion">
                      + Línea
                    </button>
                    <button type="button" wire:click="removeLinea({{ $i }})" class="h-10 px-3 rounded-xl bg-red-500 hover:bg-red-600 text-white"
                            wire:loading.attr="disabled" wire:target="addLinea,removeLinea,guardar,emitir,abrirAplicacion">
                      Quitar
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="13" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                  No hay líneas. Usa <button type="button" class="text-violet-600 hover:underline" wire:click="addLinea">+ Línea</button>.
                </td>
              </tr>
            @endforelse
          </tbody>

          <tfoot class="bg-gray-50 dark:bg-gray-800/40">
            <tr>
              <td colspan="10"></td>
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Subtotal NC:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">${{ number_format($this->subtotal, 2) }}</td>
              <td></td>
            </tr>
            <tr>
              <td colspan="10"></td>
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Impuestos NC:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">${{ number_format($this->impuestosTotal, 2) }}</td>
              <td></td>
            </tr>
            <tr class="bg-gray-100 dark:bg-gray-800/60">
              <td colspan="10"></td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">Total NC:</td>
              <td class="px-4 py-2 text-right text-lg font-extrabold text-gray-900 dark:text-white">${{ number_format($this->total, 2) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </section>
    </section>

    {{-- ===== FOOTER STICKY DE ACCIONES ===== --}}
    @php
      // Para NC no hay pagos. Hay "aplicación" (contra factura) o emitir libre.
      $tieneNota    = (bool) ($nota?->id);
      $saldoAplicar = round((float)($this->total ?? 0), 2); // lo que puedes aplicar
      $bloqueaEmitir = false; // normalmente no se bloquea la emisión de NC
    @endphp

    <footer
      class="sticky bottom-0 inset-x-0 bg-white/85 dark:bg-gray-900/85 backdrop-blur border-t border-gray-200 dark:border-gray-800"
      aria-label="Acciones de nota crédito"
    >
      <div class="px-4 md:px-8 py-4">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-center">

          {{-- Totales / estado --}}
          <div class="xl:col-span-3 flex flex-wrap items-center gap-2 text-sm md:text-base text-gray-700 dark:text-gray-300">
            <span class="font-semibold">Total NC:</span>
            <span class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white">
              $ {{ number_format($this->total, 2) }}
            </span>

         @if($factura_id)
  <span class="inline-flex ...">
    <i class="fa-solid fa-link"></i>
    Afecta factura #{{ $this->numeroFacturaSeleccionada ?? $factura_id }}
  </span>
@endif

          </div>

          {{-- Stepper / Progreso --}}
          <div class="xl:col-span-6">
            <div
              x-data="{
                estado: @entangle('estado'),
                get currentStep() {
                  if (this.estado === 'anulada') return 4
                  if (this.estado === 'cerrado') return 4
                  if (this.estado === 'emitida') return 3
                  return 1
                },
                stepStatus(n) {
                  if (n < this.currentStep) return 'complete'
                  if (n === this.currentStep) return 'current'
                  return 'upcoming'
                },
                get progressPct() {
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
                         }" title="Borrador / Guardar">
                      <i class="fa-solid fa-pen text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Borrador</div>
                    <div class="hidden md:block text-xs text-slate-500">Nota Crédito Borrador</div>
                  </li>

                  <li class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                         :class="{
                           'bg-emerald-600 text-white border-emerald-600 shadow': stepStatus(2) !== 'upcoming',
                           'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(2) === 'upcoming'
                         }" title="Aplicar contra factura (opcional)">
                      <i class="fa-solid fa-arrow-rotate-left text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Aplicación</div>
                    <div class="hidden md:block text-xs text-slate-500">Contra factura</div>
                  </li>

                  <li class="flex flex-col items-center textcenter">
                    <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                         :class="{
                           'bg-violet-600 text-white border-violet-600 shadow': stepStatus(3) !== 'upcoming',
                           'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(3) === 'upcoming'
                         }" title="Emitir documento">
                      <i class="fa-solid fa-stamp text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Emitir</div>
                    <div class="hidden md:block text-xs text-slate-500">—</div>
                  </li>

                  <li class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                         :class="{
                           'bg-rose-600 text-white border-rose-600 shadow': estado === 'anulada',
                           'bg-teal-600 text-white border-teal-600 shadow': estado === 'cerrado',
                           'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': !['anulada','cerrado'].includes(estado)
                         }"
                         :title="estado === 'anulada' ? 'Anulada' : (estado === 'cerrado' ? 'Cerrada' : 'Fin')">
                      <i :class="estado === 'anulada' ? 'fa-solid fa-ban' : (estado === 'cerrado' ? 'fa-solid fa-check' : 'fa-regular fa-circle')"
                         class="text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium"
                         x-text="estado === 'anulada' ? 'Anulada' : (estado === 'cerrado' ? 'Cerrada' : 'Fin')"></div>
                    <div class="hidden md:block text-xs text-slate-500">—</div>
                  </li>
                </ol>
              </div>
            </div>
          </div>

          {{-- Acciones --}}
          <div class="xl:col-span-3">
            <div
              x-data="{
                estado: @entangle('estado'),
                tieneNota: @js($tieneNota),
                get next(){
                  if (['anulada','cerrado','emitida'].includes(this.estado)) return null
                  // si hay factura seleccionada, sugerir Aplicación primero
                  return @js((bool)$factura_id) ? 'aplicar' : 'emitir'
                },
                labelNext(){
                  return this.next === 'aplicar' ? 'Ir a Aplicación' :
                         this.next === 'emitir' ? 'Emitir documento' : ''
                },
                isNext(btn){ return this.next === btn }
              }"
              class="flex flex-col items-end gap-2"
            >
              <template x-if="labelNext()">
                <div class="flex items-center gap-2 text-xs md:text-sm text-slate-600 dark:text-slate-300">
                  <i class="fa-solid fa-arrow-turn-down-right hidden sm:inline"></i>
                  <span class="px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800">
                    Siguiente: <strong x-text="labelNext()"></strong>
                  </span>
                </div>
              </template>

              <div class="flex flex-wrap justify-end gap-2">
                <button type="button"
                        class="h-11 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed
                               transition ring-offset-2"
                        :class="isNext('aplicar') ? 'ring-4 ring-emerald-300 animate-pulse' : ''"
                        wire:click="abrirAplicacion" wire:loading.attr="disabled" wire:target="abrirAplicacion,guardar,emitir"
                        x-data="{e:@entangle('estado')}" :disabled="['anulada','cerrado'].includes(e)">
                  <i class="fa-solid fa-arrow-rotate-left mr-2"></i>
                  <span>Aplicación</span>
                </button>

                <button type="button"
                        class="h-11 px-4 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed
                               transition ring-offset-2"
                        wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar,emitir"
                        x-data="{e:@entangle('estado')}" :disabled="['anulada','cerrado'].includes(e)">
                  <i class="fa-solid fa-floppy-disk mr-2"></i>
                  <span wire:loading.remove wire:target="guardar">Nota Crédito Borrador</span>
                  <span wire:loading wire:target="guardar">Guardando…</span>
                </button>

                <button type="button"
                        class="h-11 px-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed
                               transition ring-offset-2"
                        wire:click="emitir"
                        wire:loading.attr="disabled" wire:target="emitir,guardar"
                        x-data="{e:@entangle('estado')}" :disabled="['anulada','cerrado'].includes(e)">
                  <i class="fa-solid fa-stamp mr-2"></i>
                  <span wire:loading.remove wire:target="emitir">Emitir</span>
                  <span wire:loading wire:target="emitir">Emitiendo…</span>
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </footer>
  </section>

 
</div>
