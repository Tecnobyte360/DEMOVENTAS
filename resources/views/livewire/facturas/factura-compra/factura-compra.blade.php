{{-- resources/views/livewire/facturas/form-factura.blade.php --}}

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
    {{-- contenido del lightbox si lo necesitas --}}
  </div>

  {{-- ================= CARD PRINCIPAL ================= --}}
  <section class="mt-6 md:mt-8 rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">

    {{-- ===== PASO 1: Datos de cabecera ===== --}}
    <section class="p-6 md:pb-2 md:px-8 border-b border-gray-100 dark:border-gray-800" aria-label="Datos de la factura">
      <header class="mb-6">
        <div class="flex items-center justify-between gap-4">
          <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="inline-grid place-items-center w-9 md:w-10 h-9 md:h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">1</span>
            Factura de compra
          </h2>

          {{-- Preview del próximo consecutivo (serie seleccionada o default) --}}
          @php $preview = $this->proximoPreview; @endphp
          @if($preview)
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200 text-xs">
              <i class="fa-regular fa-eye" aria-hidden="true"></i> Próximo: <strong>{{ $preview }}</strong>
            </span>
          @endif
        </div>
      </header>

      {{-- FILA 1: Proveedor / Serie / Moneda --}}
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Proveedor --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Proveedor <span class="text-red-500">*</span>
          </label>
         <select
  wire:model.live.number="socio_negocio_id"
  wire:change="onClienteChange($event.target.value)"
  class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-base focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('socio_negocio_id') border-red-500 focus:ring-red-300 @enderror"
  aria-invalid="@error('socio_negocio_id') true @else false @enderror"
>
  <option value="">— Seleccione —</option>
  @foreach($clientes as $c)
    <option value="{{ $c->id }}">{{ $c->razon_social }} @if($c->nit) ({{ $c->nit }}) @endif</option>
  @endforeach
</select>

          @error('socio_negocio_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Serie --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Serie</label>
          <div class="flex items-center gap-3">
            <select
              wire:model.live="serie_id"
              class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white text-base focus:outline-none focus:ring-4 focus:ring-violet-300/60"
            >
              <option value="">— Seleccione —</option>
              @foreach($series as $s)
                <option value="{{ $s->id }}">
                  {{ $s->nombre }}
                  @if($s->prefijo) ({{ $s->prefijo }}: {{ str_pad($s->proximo, $s->longitud ?? 6, '0', STR_PAD_LEFT) }} → {{ $s->hasta }}) @endif
                  @if($s->es_default) ★ @endif
                </option>
              @endforeach
            </select>

            @if($serieDefault)
              <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200 text-xs">
                <i class="fa fa-star" aria-hidden="true"></i> {{ $serieDefault->nombre }}
              </span>
            @endif
          </div>
        </section>

        {{-- Moneda --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Moneda</label>
          <input type="text" wire:model.live="moneda" class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60" maxlength="3">
        </section>
      </div>

      {{-- FILA 2: Fechas + Condición de pago --}}
      <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Fechas --}}
        <section class="grid grid-cols-1 sm:grid-cols-2 gap-4 lg:col-span-2">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Fecha <span class="text-red-500">*</span></label>
            <input type="date" wire:model.live="fecha" class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('fecha') border-red-500 focus:ring-red-300 @enderror">
            @error('fecha') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Vencimiento</label>
            <input type="date" wire:model.live="vencimiento" class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
          </div>
        </section>

        {{-- Condición de pago + plazo --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Condición de pago <span class="text-red-500">*</span>
          </label>
          <div class="space-y-2">
            <select
              wire:model.live="condicion_pago_id"
              class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('condicion_pago_id') border-red-500 focus:ring-red-300 @enderror"
              aria-invalid="@error('condicion_pago_id') true @else false @enderror"
            >
              <option value="">— Seleccione —</option>
              @foreach($condicionesPago as $cp)
                <option value="{{ $cp->id }}">{{ $cp->nombre }} @if(!is_null($cp->dias)) ({{ $cp->dias }} días) @endif</option>
              @endforeach
            </select>
            @error('condicion_pago_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="flex items-center gap-3">
              <div class="flex-1">
                <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-1">Plazo (días)</label>
                <input type="number" readonly
                       value="{{ (int)($plazo_dias ?? 0) }}"
                       class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 dark:text-white focus:outline-none">
              </div>

              <div class="mt-6">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-700 text-xs">
                  <i class="fa-regular fa-clock" aria-hidden="true"></i>
                  Vence en {{ (int)($plazo_dias ?? 0) }} {{ (int)($plazo_dias ?? 0) === 1 ? 'día' : 'días' }}
                </span>
              </div>
            </div>
          </div>
        </section>
      </div>

      {{-- FILA 3: CxP / Notas --}}
      <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Cuenta por pagar (CxP) --}}
        {{-- ==================== CUENTA POR PAGAR (CxP) ==================== --}}
<section>
  <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
    Cuenta por pagar (CxP)
  </label>

  @php
    // Evita errores si el array no tiene claves definidas aún
    $cxpSug     = $cuentasProveedor['cxp']    ?? null;
    $todasProv  = $cuentasProveedor['todas']  ?? collect();
    $selectedId = (int) ($cuenta_cobro_id ?? 0);
  @endphp

  <select
    wire:key="cxp-select-{{ (int)($socio_negocio_id ?? 0) }}"
    wire:model.live.number="cuenta_cobro_id"
    class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('cuenta_cobro_id') border-red-500 focus:ring-red-300 @enderror"
  >
    <option value="">— Seleccione —</option>

    @if($todasProv->count())
      @foreach($todasProv as $c)
        <option value="{{ $c->id }}">
          {{ $c->codigo }} — {{ $c->nombre }}
          @if($cxpSug && (int)$cxpSug->id === (int)$c->id)
            (sugerida)
          @endif
        </option>
      @endforeach
    @endif
  </select>

  @error('cuenta_cobro_id')
    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
  @enderror

  @if(!$socio_negocio_id)
    <p class="mt-2 text-xs text-slate-500">
      Selecciona un proveedor para sugerir automáticamente su cuenta por pagar.
    </p>
  @endif
</section>


        {{-- Notas --}}
        <section class="lg:col-span-2">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Notas</label>
          <input type="text" placeholder="Observaciones visibles en el documento"
                 wire:model.live.debounce.400ms="notas"
                 class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
        </section>
      </div>
    </section>

    {{-- ===== PASO 2: Líneas ===== --}}
    <section class="p-6 md:p-8" aria-label="Líneas de la factura">
      <header class="mb-4 md:mb-6">
        <div class="flex items-center justify-between gap-4">
          <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="inline-grid place-items-center w-9 h-9 md:w-10 md:h-10 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">2</span>
            Agrega productos
          </h2>

          {{-- ================== PANEL "CONSULTAR STOCK" ARRIBA ================== --}}
          @php
            $iStock = $stockCheck ?? 0;
            $pid = (int)($lineas[$iStock]['producto_id'] ?? 0);
            $bid = (int)($lineas[$iStock]['bodega_id'] ?? 0);
            $stock = $pid && $bid ? $this->getStockDeLinea($iStock) : 0;
          @endphp

          <div class="min-w-[320px]" wire:key="stock-top-{{ $iStock }}">
            {{-- Cargando cuando se cambia producto/bodega de la línea seguida --}}
            <div
              wire:loading.flex
              wire:target="lineas.{{ $iStock }}.producto_id, lineas.{{ $iStock }}.bodega_id"
              class="items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-dashed border-gray-300 dark:border-gray-600">
              <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
              <span class="text-sm italic">Consultando stock…</span>
            </div>

            {{-- Mensaje según selección/stock --}}
            <div
              wire:loading.remove
              wire:target="lineas.{{ $iStock }}.producto_id, lineas.{{ $iStock }}.bodega_id"
              class="flex items-center gap-2 px-4 py-2 rounded-xl
                {{ (!$pid || !$bid) ? 'bg-gray-100 text-gray-500 border border-dashed border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600'
                                     : ($stock > 0 ? 'bg-green-50 text-green-700 border border-green-300'
                                                   : 'bg-red-50 text-red-700 border border-red-300') }}">
              <i class="fas {{ (!$pid || !$bid) ? 'fa-info-circle' : ($stock > 0 ? 'fa-check-circle' : 'fa-exclamation-circle') }}" aria-hidden="true"></i>
              <span class="text-sm">
                @if(!$pid || !$bid)
                  Selecciona <strong>producto</strong> y <strong>bodega</strong> en la línea {{ $iStock + 1 }} para consultar stock.
                @else
                  Stock disponible (Línea {{ $iStock + 1 }}): <strong>{{ number_format($stock, 0) }}</strong>
                @endif
              </span>
            </div>
          </div>
          {{-- ================== /PANEL "CONSULTAR STOCK" ================== --}}
        </div>
      </header>

      <section class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto shadow" aria-label="Tabla de líneas">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <tr>
              <th class="px-4 py-3 text-left">Imagen</th>
              <th class="px-4 py-3 text-left">Producto</th>
              <th class="px-4 py-3 text-left">Cuenta contable</th>
              <th class="px-4 py-3 text-left">Descripción</th>
              <th class="px-4 py-3 text-left">Bodega</th>
              <th class="px-4 py-3 text-right">Cant.</th>
              <th class="px-4 py-3 text-right">Costo</th>
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
                $cant   = max(1, (float)($l['cantidad'] ?? 1));
                $costo  = max(0, (float)($l['precio_unitario'] ?? $l['costo_unitario'] ?? 0));
                $desc   = min(100, max(0, (float)($l['descuento_pct'] ?? 0)));
                $ivaP   = min(100, max(0, (float)($l['impuesto_pct'] ?? 0)));
                $base   = $cant * $costo * (1 - $desc/100);
                $ivaMonto = round($base * $ivaP / 100, 2);
                $totalLin = round($base + $ivaMonto, 2);

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
                        <svg viewBox="0 0 24 24" class="h-6 w-6 text-gray-400" aria-hidden="true"><path fill="currentColor" d="M21 19V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14l4-4h12l2 2Zm-5-9a2 2 0 1 1-4.001-.001A 2 2 0 0 1 16 10Z"/></svg>
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

                {{-- Cuenta contable (solo lectura) --}}
                <td class="px-4 py-3 min-w-[260px]">
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
                  </div>
                </td>

                {{-- Descripción --}}
                <td class="px-4 py-3">
                  <input type="text" placeholder="Descripción (opcional)" wire:model.live.debounce.250ms="lineas.{{ $i }}.descripcion"
                         class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                </td>

                {{-- Bodega --}}
                <td class="px-4 py-3 min-w-[200px]">
                  <select wire:model.live="lineas.{{ $i }}.bodega_id"
                          class="w-full h-12 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                    <option value="">— Seleccione —</option>
                    @foreach($bodegas as $b)
                      <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                  </select>
                </td>

                {{-- Cantidad --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="1" min="1"
                         wire:model.live.debounce.200ms="lineas.{{ $i }}.cantidad"
                         class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                </td>

                {{-- Costo --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="any" min="0" inputmode="decimal"
                         wire:model.live.debounce.300ms="lineas.{{ $i }}.precio_unitario"
                         wire:blur="normalizarPrecio({{ $i }})"
                         class="w-44 h-11 text-right px-3 tabular-nums tracking-tight rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                         placeholder="0.00">
                </td>

                {{-- Impuesto (solo lectura en compras) --}}
                <td class="px-4 py-3 min-w-[240px]">
                  <select
                    wire:model.live="lineas.{{ $i }}.impuesto_id"
                    wire:change="setImpuesto({{ $i }}, $event.target.value)"
                    class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                    disabled
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

                {{-- Total línea --}}
                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">${{ number_format($totalLin, 2) }}</td>

                {{-- Acciones --}}
                <td class="px-4 py-3 text-right">
                  <div class="inline-flex items-center gap-2">
                    <button type="button" wire:click="addLinea" class="h-10 px-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white"
                            wire:loading.attr="disabled" wire:target="addLinea,removeLinea,guardar,emitir">
                      + Línea
                    </button>
                    <button type="button" wire:click="removeLinea({{ $i }})" class="h-10 px-3 rounded-xl bg-red-500 hover:bg-red-600 text-white"
                            wire:loading.attr="disabled" wire:target="addLinea,removeLinea,guardar,emitir">
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
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Subtotal:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">${{ number_format($this->subtotal, 2) }}</td>
              <td></td>
            </tr>
            <tr>
              <td colspan="10"></td>
              <td class="px-4 py-2 text-right font-medium text-gray-700 dark:text-gray-300">Impuestos:</td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">${{ number_format($this->impuestosTotal, 2) }}</td>
              <td></td>
            </tr>
            <tr class="bg-gray-100 dark:bg-gray-800/60">
              <td colspan="10"></td>
              <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-gray-100">Total:</td>
              <td class="px-4 py-2 text-right text-lg font-extrabold text-gray-900 dark:text-white">${{ number_format($this->total, 2) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </section>
    </section>

    {{-- ===== FOOTER STICKY DE ACCIONES ===== --}}
    <footer
      class="sticky bottom-0 inset-x-0 bg-white/85 dark:bg-gray-900/85 backdrop-blur border-t border-gray-200 dark:border-gray-800"
      aria-label="Acciones de factura"
    >
      <div class="px-4 md:px-8 py-4">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-center">

          {{-- Totales --}}
          <div class="xl:col-span-3 flex flex-wrap items-center gap-2 text-sm md:text-base text-gray-700 dark:text-gray-300">
            <span class="font-semibold">Total:</span>
            <span class="text-lg md:text-xl font-extrabold text-gray-900 dark:text-white">
              $ {{ number_format($this->total, 2) }}
            </span>
          </div>

          {{-- Stepper --}}
          <div class="xl:col-span-6">
            <div
              x-data="{
                estado: @entangle('estado'),
                get currentStep() {
                  if (this.estado === 'anulada') return 3
                  if (this.estado === 'cerrado') return 3
                  if (this.estado === 'emitida') return 2
                  return 1
                },
                stepStatus(n) {
                  if (n < this.currentStep) return 'complete'
                  if (n === this.currentStep) return 'current'
                  return 'upcoming'
                },
                get progressPct() { return [0,50,100][this.currentStep - 1] || 0 }
              }"
              class="w-full"
            >
              <div class="relative mx-auto max-w-3xl">
                <div class="absolute left-0 right-0 top-5 h-1 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                  <div class="h-1 bg-indigo-600 dark:bg-indigo-400 transition-all duration-500"
                       :style="`width: ${progressPct}%`"></div>
                </div>

                <ol class="relative z-10 grid grid-cols-3 gap-3 md:gap-6">
                  <li class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                         :class="{
                           'bg-indigo-600 text-white border-indigo-600 shadow': stepStatus(1) !== 'upcoming',
                           'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(1) === 'upcoming'
                         }"
                         title="Borrador / Guardar">
                      <i class="fa-solid fa-pen text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Borrador</div>
                    <div class="hidden md:block text-xs text-slate-500">Factura Borrador</div>
                  </li>

                  <li class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-full border-2"
                         :class="{
                           'bg-violet-600 text-white border-violet-600 shadow': stepStatus(2) !== 'upcoming',
                           'bg-white dark:bg-slate-900 text-slate-500 border-slate-300 dark:border-slate-600': stepStatus(2) === 'upcoming'
                         }"
                         title="Emitir documento">
                      <i class="fa-solid fa-stamp text-sm md:text-base"></i>
                    </div>
                    <div class="mt-1 md:mt-2 text-xs md:text-sm font-medium">Emitir</div>
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
              x-data="{ estado: @entangle('estado') }"
              class="flex flex-wrap justify-end gap-2"
            >
              {{-- Guardar --}}
              <button type="button"
                class="h-11 px-4 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white shadow disabled:opacity-50 disabled:cursor-not-allowed transition ring-offset-2"
                wire:click="guardar"
                wire:loading.attr="disabled"
                wire:target="guardar,emitir"
                :disabled="['anulada','cerrado'].includes(estado)">
                <i class="fa-solid fa-floppy-disk mr-2"></i>
                <span wire:loading.remove wire:target="guardar">Factura Borrador</span>
                <span wire:loading wire:target="guardar">Guardando…</span>
              </button>

              {{-- Emitir --}}
              <button type="button"
                wire:click="emitir"
                wire:loading.attr="disabled"
                wire:target="emitir,guardar"
                :disabled="['anulada','cerrado'].includes(estado)"
                class="h-11 px-4 rounded-2xl transition ring-offset-2 shadow bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fa-solid fa-stamp mr-2"></i>
                <span wire:loading.remove wire:target="emitir">Emitir</span>
                <span wire:loading wire:target="emitir">Emitiendo…</span>
              </button>
            </div>
          </div>

        </div>
      </div>
    </footer>
  </section>
</div>
