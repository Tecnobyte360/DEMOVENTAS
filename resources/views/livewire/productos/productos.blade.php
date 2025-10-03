<div class="p-7 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-10">

  <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="space-y-10" enctype="multipart/form-data">

    {{-- Alert de errores generales --}}
    @if($erroresFormulario)
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
        <strong class="font-bold">¡Corrige los errores!</strong>
        <span class="block sm:inline">Por favor completa los campos requeridos antes de guardar.</span>
      </div>
    @endif

    {{-- Datos generales --}}
    <section class="space-y-6 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
      <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Registrar nuevos productos</h3>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Nombre --}}
        <div class="relative">
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Nombre *</label>
          <input wire:model.lazy="nombre" type="text" placeholder="Nombre del producto"
                 class="w-full px-4 py-2 rounded-xl border
                 @error('nombre') border-red-500
                 @elseif(!$errors->has('nombre') && !empty($nombre)) border-green-500
                 @else border-gray-300 @enderror
                 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                 focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10"/>
          @if(!$errors->has('nombre') && !empty($nombre))
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
              <i class="fas fa-check-circle text-green-500"></i>
            </div>
          @endif
          @error('nombre') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Descripción --}}
        <div class="relative">
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Descripción</label>
          <input wire:model.lazy="descripcion" type="text" placeholder="Descripción del producto"
                 class="w-full px-4 py-2 rounded-xl border
                 @error('descripcion') border-red-500
                 @elseif(!empty($descripcion)) border-green-500
                 @else border-gray-300 @enderror
                 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                 focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10"/>
          @error('descripcion') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Subcategoría --}}
        <div class="relative">
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Subcategoría *</label>
          <select wire:model.lazy="subcategoria_id"
                  class="w-full px-4 py-2 rounded-xl border
                   @error('subcategoria_id') border-red-500
                   @elseif(!empty($subcategoria_id)) border-green-500
                   @else border-gray-300 @enderror
                   dark:border-gray-700 dark:bg-gray-800 dark:text-white
                   focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10">
            <option value="">-- Selecciona Subcategoría --</option>
            @foreach($subcategorias as $sub)
              <option value="{{ $sub->id }}">{{ $sub->nombre }}</option>
            @endforeach
          </select>
          @error('subcategoria_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Unidad de medida --}}
        <div class="relative">
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Unidad de medida</label>
          <select wire:model.lazy="unidad_medida_id"
                  class="w-full px-4 py-2 rounded-xl border
                   @error('unidad_medida_id') border-red-500 @else border-gray-300 @enderror
                   dark:border-gray-700 dark:bg-gray-800 dark:text-white
                   focus:ring-2 focus:ring-violet-600 focus:outline-none">
            <option value="">— Selecciona —</option>
            @foreach($unidades as $um)
              <option value="{{ $um->id }}">
                {{ $um->nombre }} @if($um->simbolo) ({{ $um->simbolo }}) @endif
              </option>
            @endforeach
          </select>
          @error('unidad_medida_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Precio (sin IVA) --}}
        <div class="relative">
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Precio (sin IVA) *</label>
          <input wire:model.lazy="precio" type="number" step="0.01" placeholder="Precio de venta"
                 class="w-full px-4 py-2 rounded-xl border
                 @error('precio') border-red-500
                 @elseif(!empty($precio)) border-green-500
                 @else border-gray-300 @enderror
                 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                 focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10"/>
          @error('precio') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- IVA / Impuesto --}}
        <div class="relative">
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">IVA / Impuesto</label>
          <select wire:model="impuesto_id"
                  class="w-full px-4 py-2 rounded-xl border
                  @error('impuesto_id') border-red-500
                  @elseif(!is_null($impuesto_id)) border-green-500
                  @else border-gray-300 @enderror
                  dark:border-gray-700 dark:bg-gray-800 dark:text-white
                  focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10">
            <option value="">— Sin impuesto —</option>
            @foreach($impuestos as $imp)
              <option value="{{ $imp->id }}">
                {{ $imp->nombre }}
                @if(!is_null($imp->porcentaje))
                  ({{ number_format($imp->porcentaje, 2) }}%)
                @elseif(!is_null($imp->monto_fijo))
                  (${{ number_format($imp->monto_fijo, 2) }})
                @endif
              </option>
            @endforeach
          </select>

          @if(($precio ?? null) !== null && $precio !== '')
            <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">
              Precio con IVA: <strong>${{ number_format($this->precioConIvaTmp, 2) }}</strong>
            </p>
          @endif
          @error('impuesto_id') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

          @if($isEdit && $producto_id)
            @php $p = $productos->firstWhere('id', $producto_id); @endphp
            @if($p)
              <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">
                Precio actual con IVA: <strong>${{ number_format($p->precio_con_iva, 2) }}</strong>
              </p>
            @endif
          @endif
        </div>

       
        <div class="relative">
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Imagen</label>
          <input type="file" accept="image/*" wire:model="imagen"
                 class="w-full px-4 py-2 rounded-xl border
                 @error('imagen') border-red-500 @else border-gray-300 @enderror
                 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                 focus:ring-2 focus:ring-violet-600 focus:outline-none"/>
          @error('imagen') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror

          {{-- Preview temporal --}}
          @if($imagen)
            <img src="{{ $imagen->temporaryUrl() }}" class="mt-2 h-20 w-20 rounded-lg object-cover ring-2 ring-violet-500/30" alt="Preview">
          @endif
        </div>

        {{-- Stock Global Mínimo --}}
        <div class="relative">
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Stock Mínimo Global</label>
          <input wire:model.lazy="stockMinimoGlobal" type="number" placeholder="Mínimo todas las bodegas"
                 class="w-full px-4 py-2 rounded-xl border
                 @error('stockMinimoGlobal') border-red-500
                 @elseif(!is_null($stockMinimoGlobal)) border-green-500
                 @else border-gray-300 @enderror
                 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                 focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10"/>
          @error('stockMinimoGlobal') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Stock Global Máximo --}}
        <div class="relative">
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Stock Máximo Global</label>
          <input wire:model.lazy="stockMaximoGlobal" type="number" placeholder="Máximo todas las bodegas"
                 class="w-full px-4 py-2 rounded-xl border
                 @error('stockMaximoGlobal') border-red-500
                 @elseif(!is_null($stockMaximoGlobal)) border-green-500
                 @else border-gray-300 @enderror
                 dark:border-gray-700 dark:bg-gray-800 dark:text-white
                 focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10"/>
          @error('stockMaximoGlobal') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
        </div>

        {{-- Estado --}}
        <div class="flex flex-wrap items-center gap-6 col-span-full pt-4">
          @foreach (['Activo' => 'activo'] as $label => $model)
            <label class="flex items-center space-x-3">
              <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
              <div class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" wire:model.lazy="{{ $model }}" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-violet-600 transition"></div>
                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition peer-checked:translate-x-5"></div>
              </div>
            </label>
          @endforeach
        </div>
      </div>

      {{-- Resumen + abrir modal --}}
      <section class="space-y-4 mt-6">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Cuentas contables del producto</h3>
          <div class="flex items-center gap-2">
            {{-- chips resumen (si hay) --}}
            @if(isset($tiposCuenta) && $tiposCuenta->count() && !empty($cuentasPorTipo) && $mov_contable_segun==='ARTICULO')
              <div class="hidden md:flex flex-wrap gap-1 mr-2">
                @foreach($tiposCuenta as $t)
                  @php
                    $selId = $cuentasPorTipo[$t->id] ?? null;
                    $sel   = $selId ? $cuentasPUC->firstWhere('id', $selId) : null;
                  @endphp
                  <span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full
                               {{ $sel ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    <span class="font-semibold mr-1">{{ $t->nombre }}</span>
                    <span class="text-gray-400">|</span>
                    <span class="ml-1">{{ $sel?->codigo ?? '—' }}</span>
                  </span>
                @endforeach
              </div>
            @endif

            <button type="button" wire:click="abrirModalCuentas"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm shadow">
              <i class="fas fa-cog"></i> Configurar
            </button>
          </div>
        </div>

        @if($mov_contable_segun==='SUBCATEGORIA')
          <p class="text-sm text-gray-600 dark:text-gray-300">
            Este producto heredará las cuentas desde su <strong>Subcategoría</strong>. No necesitas configurarlas aquí.
          </p>
        @endif

        @if(!$tiposCuenta || !$tiposCuenta->count())
          <div class="p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-xl text-sm text-yellow-800 dark:text-yellow-200">
            No hay tipos de cuentas configurados. Crea registros en <code>producto_cuenta_tipos</code>.
          </div>
        @endif
      </section>
    </section>

    {{-- Stock por bodega --}}
    <section class="space-y-6 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
      <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Stock Mínimo/Máximo por Bodega</h3>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Seleccionar Bodega</label>
          <select wire:model="bodegaSeleccionada"
                  class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none">
            <option value="">-- Selecciona Bodega --</option>
            @foreach($bodegas as $bodega)
              <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Stock Mínimo</label>
          <input wire:model="stockMinimo" type="number" placeholder="Stock mínimo"
                 class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none"/>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Stock Máximo</label>
          <input wire:model="stockMaximo" type="number" placeholder="Stock máximo"
                 class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none"/>
        </div>
      </div>

      <div class="flex justify-end gap-4 pt-4">
        <button type="button" wire:click="agregarBodega"
                class="flex items-center gap-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-xl shadow hover:shadow-md transition">
          <i class="fas fa-plus text-sm"></i>
          <span>Agregar</span>
        </button>

        <button type="submit"
                class="flex items-center gap-1 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-xl shadow hover:shadow-md transition"
                wire:loading.attr="disabled">
          <i class="fas fa-save text-sm"></i>
          <span>{{ $isEdit ? 'Actualizar' : 'Guardar' }}</span>
        </button>
      </div>

      @if($stocksPorBodega)
        <div class="overflow-x-auto mt-6">
          <table class="min-w-full text-sm">
            <thead class="bg-violet-600 text-white">
            <tr>
              <th class="p-2 text-left">Bodega</th>
              <th class="p-2 text-center">Stock Mínimo</th>
              <th class="p-2 text-center">Stock Máximo</th>
              <th class="p-2 text-center">Acciones</th>
            </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
            @foreach($stocksPorBodega as $bodegaId => $datos)
              <tr>
                <td class="p-2">{{ $bodegas->find($bodegaId)->nombre ?? 'Bodega' }}</td>
                <td class="p-2 text-center">
                  <input type="number" wire:model.defer="stocksPorBodega.{{ $bodegaId }}.stock_minimo"
                         class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none"/>
                </td>
                <td class="p-2 text-center">
                  <input type="number" wire:model.defer="stocksPorBodega.{{ $bodegaId }}.stock_maximo"
                         class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none"/>
                </td>
                <td class="p-2 text-center">
                  <button type="button" wire:click="eliminarBodega({{ $bodegaId }})"
                          class="flex items-center gap-2 text-red-600 hover:text-red-800 font-bold transition">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>

  </form>

  {{-- Tabla de productos --}}
  <section class="space-y-6 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4">
      <h3 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white">Inventario Actual</h3>
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
        <input wire:model.defer="search" type="text" placeholder="Buscar producto..."
               class="w-full sm:w-64 px-4 py-2 border rounded-xl dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-violet-600 focus:border-violet-600"/>
        <button wire:click="$refresh" class="px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white rounded-xl shadow transition-all text-sm">
          <i class="fas fa-search mr-1"></i> Buscar
        </button>
      </div>
    </div>

    <div class="w-full overflow-x-auto">
      <table class="min-w-[1300px] w-full bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden text-sm text-gray-700 dark:text-gray-300">
        <thead class="bg-violet-600 text-white">
          <tr>
            <th class="p-3 text-left font-semibold">#ID</th>
            <th class="p-3 text-left font-semibold">Imagen</th>
            <th class="p-3 text-left font-semibold">Nombre</th>
            <th class="p-3 text-left font-semibold">Descripción</th>
            <th class="p-3 text-center font-semibold">U. Medida</th>
            <th class="p-3 text-center font-semibold">Precio (sin IVA)</th>
            <th class="p-3 text-center font-semibold">Impuesto</th>
            <th class="p-3 text-center font-semibold">Precio c/ IVA</th>
            <th class="p-3 text-center font-semibold">Cuentas</th>
            <th class="p-3 text-center font-semibold">Subcategoría</th>
            <th class="p-3 text-center font-semibold">Stock Total</th>
            <th class="p-3 text-center font-semibold">Estado</th>
            <th class="p-3 text-center font-semibold">Alerta</th>
            <th class="p-3 text-center font-semibold">Bodegas</th>
            <th class="p-3 text-center font-semibold">Acciones</th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
          @forelse($productos as $prod)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
              <td class="p-3">{{ $prod->id }}</td>

              {{-- Imagen --}}
              <td class="p-3">
                @php
                  $img = $prod->imagen_path ? asset('storage/'.$prod->imagen_path) : null;
                @endphp
                @if($img)
                  <img src="{{ $img }}" alt="img" class="h-10 w-10 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                @else
                  <div class="h-10 w-10 rounded-lg bg-gray-200 dark:bg-gray-700 grid place-items-center text-gray-400">
                    <i class="fas fa-image"></i>
                  </div>
                @endif
              </td>

              <td class="p-3">{{ $prod->nombre }}</td>
              <td class="p-3">{{ $prod->descripcion }}</td>

              {{-- UM --}}
              <td class="p-3 text-center">
                @if($prod->unidadMedida)
                  {{ $prod->unidadMedida->nombre }}
                  @if($prod->unidadMedida->simbolo) ({{ $prod->unidadMedida->simbolo }}) @endif
                @else
                  —
                @endif
              </td>

              <td class="p-3 text-center">{{ number_format($prod->precio, 2) }}</td>
              <td class="p-3 text-center">
                {{ $prod->impuesto?->nombre ?? '—' }}
                @if($prod->impuesto)
                  @if(!is_null($prod->impuesto->porcentaje))
                    ({{ number_format($prod->impuesto->porcentaje, 2) }}%)
                  @elseif(!is_null($prod->impuesto->monto_fijo))
                    (${{ number_format($prod->impuesto->monto_fijo, 2) }})
                  @endif
                @endif
              </td>
              <td class="p-3 text-center">${{ number_format($prod->precio_con_iva, 2) }}</td>

              {{-- Resumen cuentas --}}
              <td class="p-3">
                @if(($prod->mov_contable_segun ?? null) === \App\Models\Productos\Producto::MOV_SEGUN_SUBCATEGORIA)
                  <span class="text-xs inline-flex items-center px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700">
                    Según subcategoría
                  </span>
                @elseif($prod->relationLoaded('cuentas') && $prod->cuentas->count())
                  <div class="flex flex-wrap gap-1">
                    @foreach($prod->cuentas as $pc)
                      <span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-gray-100 dark:bg-gray-700">
                        <span class="font-semibold mr-1">{{ $pc->tipo->nombre ?? 'Tipo' }}</span>
                        <span class="text-gray-500">|</span>
                        <span class="ml-1">{{ $pc->cuentaPUC?->codigo }}</span>
                      </span>
                    @endforeach
                  </div>
                @else
                  <span class="text-gray-400 italic text-xs">Sin cuentas</span>
                @endif
              </td>

              <td class="p-3 text-center">{{ $prod->subcategoria->nombre ?? '-' }}</td>
              <td class="p-3 text-center">{{ $prod->bodegas->sum(fn($b) => $b->pivot->stock) }}</td>

              <td class="p-3 text-center">
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full {{ $prod->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                  @if ($prod->activo) <i class="fas fa-check-circle"></i> Activo @else <i class="fas fa-times-circle"></i> Inactivo @endif
                </span>
              </td>

              {{-- Alerta de stock --}}
              <td class="p-3 text-center">
                @php
                  $bodegasSinStock = []; $bodegasStockBajo = []; $bodegasSobreStock = [];
                  foreach ($prod->bodegas as $bodega) {
                    $stock = $bodega->pivot->stock;
                    $stock_minimo = $bodega->pivot->stock_minimo;
                    $stock_maximo = $bodega->pivot->stock_maximo;
                    if ($stock == 0) $bodegasSinStock[] = $bodega->nombre;
                    elseif ($stock < $stock_minimo) $bodegasStockBajo[] = $bodega->nombre;
                    elseif (!is_null($stock_maximo) && $stock > $stock_maximo) $bodegasSobreStock[] = $bodega->nombre;
                  }
                  $status = 'Abastecido'; $color='green'; $icon='fas fa-check-circle';
                  if (count($bodegasSinStock)>0) { $status='Sin stock'; $color='red'; $icon='fas fa-times-circle'; }
                  elseif (count($bodegasStockBajo)>0) { $status='Stock bajo'; $color='yellow'; $icon='fas fa-exclamation-triangle'; }
                  elseif (count($bodegasSobreStock)>0) { $status='Sobre stock'; $color='blue'; $icon='fas fa-boxes'; }
                @endphp
                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-{{ $color }}-100 text-{{ $color }}-700 rounded-full">
                  <i class="{{ $icon }} mr-1"></i> {{ $status }}
                </span>
              </td>

              <td class="p-3 text-center">
                @if ($prod->bodegas && $prod->bodegas->count())
                  <button wire:click="$toggle('mostrarBodegas.{{ $prod->id }}')" class="text-indigo-600 hover:text-indigo-800 text-lg" title="Ver bodegas">
                    <i class="fas fa-eye"></i>
                  </button>
                @else
                  <span class="text-gray-400 italic text-xs">Sin Bodegas</span>
                @endif
              </td>

              <td class="p-3 text-center space-x-2">
                <button wire:click="edit({{ $prod->id }})" class="text-blue-600 hover:text-blue-800 transition-colors" title="Editar">
                  <i class="fas fa-edit"></i>
                </button>
              </td>
            </tr>

            {{-- Detalle bodegas --}}
            @if (!empty($mostrarBodegas[$prod->id]) && $prod->bodegas)
              <tr>
                <td colspan="15" class="bg-gray-100 dark:bg-gray-700 p-4">
                  <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-gray-700 dark:text-gray-300">
                      <thead class="bg-violet-200 dark:bg-violet-700 text-gray-800 dark:text-white">
                        <tr>
                          <th class="p-2 text-left">Código</th>
                          <th class="p-2 text-left">Bodega</th>
                          <th class="p-2 text-center">Stock</th>
                          <th class="p-2 text-center">Stock Mínimo</th>
                          <th class="p-2 text-center">Stock Máximo</th>
                          <th class="p-2 text-center">Condición</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-300 dark:divide-gray-600">
                        @foreach($prod->bodegas as $bodega)
                          @php $stock=$bodega->pivot->stock; $minimo=$bodega->pivot->stock_minimo; $maximo=$bodega->pivot->stock_maximo; @endphp
                          <tr>
                            <td class="p-2">{{ $bodega->id }}</td>
                            <td class="p-2">{{ $bodega->nombre }}</td>
                            <td class="p-2 text-center">{{ $stock }}</td>
                            <td class="p-2 text-center">{{ $minimo }}</td>
                            <td class="p-2 text-center">{{ $maximo ?? '-' }}</td>
                            <td class="p-2 text-center">
                              @if ($stock == 0)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded-full">
                                  <i class="fas fa-times-circle mr-1"></i> Sin stock
                                </span>
                              @elseif ($stock < $minimo)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-800 rounded-full">
                                  <i class="fas fa-exclamation-triangle mr-1"></i> Stock bajo
                                </span>
                              @elseif (!is_null($maximo) && $stock > $maximo)
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">
                                  <i class="fas fa-boxes mr-1"></i> Sobre stock
                                </span>
                              @else
                                <span class="inline-flex items-center px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded-full">
                                  <i class="fas fa-check-circle mr-1"></i> Abastecido
                                </span>
                              @endif
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </td>
              </tr>
            @endif
          @empty
            <tr>
              <td colspan="15" class="p-4 text-center text-gray-500 dark:text-gray-400 italic">No hay productos registrados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  {{-- MODAL: Configurar Cuentas --}}
  @if($showCuentasModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/60" wire:click="cerrarModalCuentas"></div>

      <div class="relative w-full max-w-4xl bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <h4 class="text-lg font-semibold text-gray-800 dark:text-white">Configurar cuentas contables</h4>
          <button class="text-gray-500 hover:text-gray-800 dark:hover:text-gray-200" wire:click="cerrarModalCuentas">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="p-6 space-y-4">
          {{-- Switch Movimiento contable según --}}
          <div>
            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
              Movimiento contable según
            </label>
            <div class="inline-flex bg-gray-200 dark:bg-gray-700 rounded-xl p-1 select-none">
              <button type="button"
                      wire:click="$set('mov_contable_segun','ARTICULO')"
                      class="px-4 py-2 text-sm rounded-lg transition
                             {{ $mov_contable_segun==='ARTICULO' ? 'bg-violet-600 text-white shadow' : 'text-gray-700 dark:text-gray-300' }}">
                Artículo
              </button>
              <button type="button"
                      wire:click="$set('mov_contable_segun','SUBCATEGORIA')"
                      class="px-4 py-2 text-sm rounded-lg transition
                             {{ $mov_contable_segun==='SUBCATEGORIA' ? 'bg-violet-600 text-white shadow' : 'text-gray-700 dark:text-gray-300' }}">
                Subcategoría
              </button>
            </div>
            @error('mov_contable_segun') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
          </div>

          @if($mov_contable_segun==='SUBCATEGORIA')
            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-dashed border-gray-300 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300">
              Las cuentas contables se <strong>heredarán de la Subcategoría</strong>. No es necesario configurarlas a nivel de producto.
            </div>
          @endif

          {{-- Tabla de cuentas --}}
          <div class="overflow-x-auto opacity-{{ $mov_contable_segun==='ARTICULO' ? '100' : '50' }}">
            <table class="min-w-[900px] w-full bg-white dark:bg-gray-900 rounded-xl overflow-hidden text-sm">
              <thead class="bg-violet-600 text-white">
                <tr>
                  <th class="px-3 py-2 text-left font-semibold">Movimiento contable según</th>
                  <th class="px-3 py-2 text-left font-semibold">Cuenta (PUC)</th>
                  <th class="px-3 py-2 text-left font-semibold">Código seleccionado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($tiposCuenta as $t)
                  @php
                    $seleccionId = $cuentasPorTipo[$t->id] ?? null;
                    $seleccion   = $seleccionId ? $cuentasPUC->firstWhere('id', $seleccionId) : null;
                    $disabled    = $mov_contable_segun==='SUBCATEGORIA';
                  @endphp
                  <tr class="bg-white dark:bg-gray-800">
                    <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-100">
                      {{ $t->nombre }}
                      @if($t->obligatorio && !$disabled)
                        <span class="text-red-500">*</span>
                      @endif
                    </td>
                    <td class="px-3 py-2">
                      <select
                        wire:model.lazy="cuentasPorTipo.{{ $t->id }}"
                        {{ $disabled ? 'disabled' : '' }}
                        class="w-full px-3 py-2 rounded-lg border
                          @error('cuentasPorTipo.'.$t->id) border-red-500
                          @elseif(!empty($seleccionId)) border-green-500
                          @else border-gray-300 @enderror
                          dark:border-gray-700 dark:bg-gray-800 dark:text-white
                          focus:ring-2 focus:ring-violet-600 focus:outline-none disabled:bg-gray-100 disabled:text-gray-400 disabled:dark:bg-gray-700 disabled:dark:text-gray-500">
                        <option value="">— Selecciona cuenta —</option>
                        @foreach($cuentasPUC as $c)
                          <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nombre }}</option>
                        @endforeach
                      </select>
                      @if(!$disabled)
                        @error('cuentasPorTipo.'.$t->id) <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                      @endif
                    </td>
                    <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                      @if($seleccion)
                        <span class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700">
                          <span class="font-semibold mr-2">{{ $seleccion->codigo }}</span>
                          <span class="text-xs text-gray-500">{{ $seleccion->nombre }}</span>
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

        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
          <button type="button" wire:click="cerrarModalCuentas"
                  class="px-4 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200">
            Cancelar
          </button>
          <button type="button" wire:click="guardarModalCuentas"
                  class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white">
            Guardar
          </button>
        </div>
      </div>
    </div>
  @endif

</div>

{{-- SweetAlert listeners --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  window.addEventListener('producto-creado', e => {
    Swal.fire({ icon: 'success', title: '¡Éxito!', text: e.detail.mensaje, timer: 2000, showConfirmButton: false });
  });
  window.addEventListener('producto-actualizado', e => {
    Swal.fire({ icon: 'success', title: '¡Actualizado!', text: e.detail.mensaje, timer: 2000, showConfirmButton: false });
  });
  window.addEventListener('producto-eliminado', e => {
    Swal.fire({ icon: 'success', title: '¡Eliminado!', text: e.detail.mensaje, timer: 2000, showConfirmButton: false });
  });
  window.addEventListener('error', e => {
    Swal.fire({ icon: 'error', title: 'Error', text: e.detail.mensaje });
  });
</script>
