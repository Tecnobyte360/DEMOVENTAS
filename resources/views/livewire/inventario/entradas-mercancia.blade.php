@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>[x-cloak]{display:none!important}</style>
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Cargar Alpine después de Livewire
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit);
      };
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
  class="p-6 md:p-8 space-y-6"
>

  {{-- ================= CARD PRINCIPAL ================= --}}
  <section class="mt-2 rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">

    {{-- ===== PASO 1: Datos de cabecera ===== --}}
    <section class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-800" aria-label="Datos de la Entrada">
      <header class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-9 md:w-10 h-9 md:h-10 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">1</span>
          Entrada de Mercancía Manual
        </h2>

        {{-- Serie + Próximo --}}
        <div class="flex items-center gap-2">
          <div class="flex items-center gap-2">
            <label class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Serie</label>
            <select
              wire:model.live="serie_id"
              class="h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-indigo-300/60"
            >
              @foreach(($series ?? collect()) as $s)
                <option value="{{ $s->id }}">
                  {{ $s->nombre }} ({{ $s->prefijo ?: '—' }})
                </option>
              @endforeach
            </select>
          </div>

          <span class="inline-flex items-center gap-2 h-10 px-3 rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800">
            <i class="fa-solid fa-hashtag"></i>
            <span class="text-sm">Próx:</span>
            <strong class="tracking-wider">{{ $this->proximoPreview ?? '—' }}</strong>
          </span>
        </div>
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Fecha contabilización --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Fecha contabilización <span class="text-red-500">*</span>
          </label>
          <input
            type="date"
            wire:model.live="fecha_contabilizacion"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('fecha_contabilizacion') border-red-500 focus:ring-red-300 @enderror"
          >
          @error('fecha_contabilizacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Proveedor --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Proveedor <span class="text-red-500">*</span>
          </label>
          <select wire:model="socio_negocio_id"
            class="w-full rounded-xl border-gray-300 dark:bg-gray-800 dark:border-gray-700 text-sm">
            <option value="">— Seleccione —</option>
            @foreach($socios as $s)
              <option value="{{ $s->id }}">{{ $s->razon_social }}</option>
            @endforeach
          </select>
          @error('socio_negocio_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Lista de precio (opcional) --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Lista de precio (opcional)
          </label>
          <input
            type="text"
            placeholder="Texto libre / referencia"
            wire:model.live.debounce.400ms="lista_precio"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          >
        </section>
      </div>

      {{-- Concepto y rol --}}
      <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Concepto --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Concepto de entrada <span class="text-red-500">*</span>
          </label>
          <select
            wire:model.live="concepto_documento_id"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60 @error('concepto_documento_id') border-red-500 focus:ring-red-300 @enderror"
          >
            <option value="">— Seleccione —</option>
            @foreach(($conceptos ?? collect()) as $c)
              <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nombre }}</option>
            @endforeach
          </select>
          @error('concepto_documento_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </section>

        {{-- Rol --}}
        <section>
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Rol del concepto
          </label>
          <select
            wire:model.live="concepto_rol"
            class="w-full h-12 md:h-14 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
          >
            <option value="inventario">Inventario</option>
            <option value="gasto">Gasto</option>
            <option value="costo">Costo</option>
            <option value="ingreso">Ingreso</option>
            <option value="gasto_devolucion">Gasto Devolución</option>
            <option value="ingreso_devolucion">Ingreso Devolución</option>
          </select>
          <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Se toma la primera cuenta del concepto con ese rol (menor prioridad).
          </p>
        </section>
      </div>

      {{-- Observaciones --}}
      <div class="mt-6">
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
          Observaciones
        </label>
        <textarea
          rows="2"
          placeholder="Notas de la entrada…"
          wire:model.live.debounce.400ms="observaciones"
          class="w-full px-4 py-3 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
        ></textarea>
      </div>
    </section>

    {{-- ===== PASO 2: Detalle ===== --}}
    <section class="p-6 md:p-8" aria-label="Detalle de la Entrada">
      <header class="mb-6 flex items-center justify-between gap-4">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-grid place-items-center w-9 h-9 md:w-10 md:h-10 rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200">2</span>
          Agrega productos
        </h2>

        <div class="flex items-center gap-2">
          <button type="button"
                  wire:click="abrirMulti"
                  class="h-10 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow">
            <i class="fa-solid fa-list-check mr-2"></i> Selección múltiple
          </button>
          <button type="button"
                  wire:click="agregarFila"
                  class="h-10 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 text-white">
            + Fila
          </button>
        </div>
      </header>

      {{-- Tabla de líneas --}}
      <section class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto shadow">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
            <tr>
              <th class="px-4 py-3 text-left">Producto</th>
              <th class="px-4 py-3 text-left">Cuenta</th>
              <th class="px-4 py-3 text-left">Descripción</th>
              <th class="px-4 py-3 text-left">Bodega</th>
              <th class="px-4 py-3 text-right">Cant.</th>
              <th class="px-4 py-3 text-right">Precio</th>
              <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            @forelse($entradas as $i => $fila)
              @php
                $pid = (int)($entradas[$i]['producto_id'] ?? 0);
                $prodSel = $pid ? $productos->firstWhere('id', $pid) : null;
              @endphp
              <tr wire:key="fila-{{ $i }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                {{-- Producto --}}
                <td class="px-4 py-3 min-w-[260px]">
                  <select
                    data-first-product
                    wire:model.live="entradas.{{ $i }}.producto_id"
                    wire:change="productoSeleccionado({{ $i }})"
                    class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                  >
                    <option value="">— Seleccione —</option>
                    @foreach($productos as $p)
                      <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                  </select>
                </td>

                {{-- Cuenta (solo lectura) --}}
                <td class="px-4 py-3 min-w-[260px]">
                  <input type="text" readonly
                         value="{{ $entradas[$i]['cuenta_str'] ?? '' }}"
                         class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/60 text-gray-700 dark:text-gray-200">
                </td>

                {{-- Descripción (si tu BD no tiene 'descripcion', puedes dejarlo como ayuda visual) --}}
                <td class="px-4 py-3 min-w-[220px]">
                  <input type="text"
                         placeholder="Descripción"
                         wire:model.live.debounce.300ms="entradas.{{ $i }}.descripcion"
                         class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                  {{-- Si tu tabla usa 'detalle', NO es obligatorio guardar esta columna; el componente ya mapea al payload --}}
                </td>

                {{-- Bodega --}}
                <td class="px-4 py-3 min-w-[200px]">
                  <select
                    wire:model.live="entradas.{{ $i }}.bodega_id"
                    wire:change="recordarUltimos({{ $i }})"
                    class="w-full h-11 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60"
                  >
                    <option value="">— Seleccione —</option>
                    @foreach($bodegas as $b)
                      <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                    @endforeach
                  </select>
                </td>

                {{-- Cantidad --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="1" min="1"
                         wire:model.live.debounce.200ms="entradas.{{ $i }}.cantidad"
                         class="w-24 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                </td>

                {{-- Precio --}}
                <td class="px-4 py-3 text-right">
                  <input type="number" step="0.01" min="0"
                         wire:model.live.debounce.200ms="entradas.{{ $i }}.precio_unitario"
                         wire:change="recordarUltimos({{ $i }})"
                         class="w-28 h-11 text-right px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
                </td>

                {{-- Acciones --}}
                <td class="px-4 py-3 text-right">
                  <div class="inline-flex items-center gap-2">
                    <button type="button" wire:click="agregarFila"
                            class="h-10 px-3 rounded-xl bg-violet-600 hover:bg-violet-700 text-white">
                      + Fila
                    </button>
                    <button type="button" wire:click="eliminarFila({{ $i }})"
                            class="h-10 px-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white">
                      Quitar
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                  No hay líneas. Usa <button type="button" class="text-violet-600 hover:underline" wire:click="agregarFila">+ Fila</button> o “Selección múltiple”.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </section>
    </section>

    {{-- ===== FOOTER STICKY (acciones) ===== --}}
    <footer class="sticky bottom-0 inset-x-0 bg-white/85 dark:bg-gray-900/85 backdrop-blur border-t border-gray-200 dark:border-gray-800">
      <div class="px-4 md:px-8 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap gap-2">
            <button type="button"
                    wire:click="cancelarEntrada"
                    class="h-11 px-4 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
              Cancelar
            </button>
            <button type="button"
                    wire:click="crearEntrada"
                    wire:loading.attr="disabled"
                    class="h-11 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white shadow">
              <i class="fa-solid fa-floppy-disk mr-2"></i>
              Guardar (Borrador)
            </button>
          </div>
        </div>
      </div>
    </footer>
  </section>

  {{-- =============== MODAL SELECCIÓN MÚLTIPLE =============== --}}
  @if($showMulti)
    <div class="fixed inset-0 z-50 flex items-end md:items-center justify-center p-4" x-data @keydown.escape.window="$wire.cerrarMulti()">
      <div class="absolute inset-0 bg-black/40" wire:click="cerrarMulti"></div>
      <div class="relative w-full md:max-w-5xl rounded-3xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl p-6 space-y-4">
        <header class="flex items-center justify-between">
          <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
            <i class="fa-solid fa-boxes-stacked mr-2"></i> Selección múltiple de productos
          </h4>
          <button class="h-9 px-3 rounded-xl bg-gray-100 dark:bg-gray-800" wire:click="cerrarMulti">Cerrar</button>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Buscar</label>
            <input type="text" wire:model.live.debounce.250ms="multi_buscar"
                   placeholder="Nombre del producto…"
                   class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Bodega (lote)</label>
            <select wire:model.live="multi_bodega_id"
                    class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
              <option value="">—</option>
              @foreach($bodegas as $b)
                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Precio (lote)</label>
            <input type="number" step="0.01" wire:model.live="multi_precio"
                   class="w-full h-10 px-3 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
          </div>
        </div>

        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
              <tr>
                <th class="px-3 py-2 text-left">Producto</th>
                <th class="px-3 py-2 text-right w-28">Cant.</th>
                <th class="px-3 py-2 text-left w-56">Bodega</th>
                <th class="px-3 py-2 text-right w-32">Precio</th>
                <th class="px-3 py-2 text-center w-24">Sel.</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              @php
                $lista = $productos->when($multi_buscar, fn($c)=>$c->filter(
                  fn($p)=>\Illuminate\Support\Str::contains(
                    \Illuminate\Support\Str::lower($p->nombre),
                    \Illuminate\Support\Str::lower($multi_buscar)
                  )
                ));
              @endphp
              @foreach($lista as $p)
                @php $sel = isset($multi_items[$p->id]); @endphp
                <tr class="{{ $sel ? 'bg-indigo-50/50 dark:bg-indigo-900/20' : '' }}">
                  <td class="px-3 py-2">{{ $p->nombre }}</td>
                  <td class="px-3 py-2 text-right">
                    <div class="flex items-center justify-end gap-1">
                      <button class="h-8 w-8 rounded-lg bg-gray-100 dark:bg-gray-800"
                              wire:click="decCantidadMulti({{ $p->id }})">-</button>
                      <input type="number" min="1" step="1"
                             wire:model.live="multi_items.{{ $p->id }}.cantidad"
                             class="w-16 h-8 text-right rounded-lg border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                      <button class="h-8 w-8 rounded-lg bg-gray-100 dark:bg-gray-800"
                              wire:click="incCantidadMulti({{ $p->id }})">+</button>
                    </div>
                  </td>
                  <td class="px-3 py-2">
                    <select wire:model.live="multi_items.{{ $p->id }}.bodega_id"
                            class="w-full h-8 px-2 rounded-lg border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
                      <option value="">—</option>
                      @foreach($bodegas as $b)
                        <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td class="px-3 py-2 text-right">
                    <input type="number" step="0.01"
                           wire:model.live="multi_items.{{ $p->id }}.precio"
                           class="w-28 h-8 text-right rounded-lg border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                  </td>
                  <td class="px-3 py-2 text-center">
                    <input type="checkbox"
                           wire:click="toggleMultiProducto({{ $p->id }})"
                           @if($sel) checked @endif
                    >
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <button class="h-10 px-3 rounded-xl bg-gray-100 dark:bg-gray-800"
                    wire:click="aplicarPorLote('bodega_id')">
              Aplicar bodega a seleccionados
            </button>
            <button class="h-10 px-3 rounded-xl bg-gray-100 dark:bg-gray-800"
                    wire:click="aplicarPorLote('precio')">
              Aplicar precio a seleccionados
            </button>
          </div>
          <div class="flex items-center gap-2">
            <button class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white"
                    wire:click="agregarSeleccionados">
              Agregar al detalle
            </button>
            <button class="h-10 px-4 rounded-xl bg-gray-100 dark:bg-gray-800"
                    wire:click="cerrarMulti">
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

</div>
