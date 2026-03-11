{{-- resources/views/livewire/productos/products.blade.php --}}
<div class="p-7 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-10">
    {{-- Form principal (no requiere multipart) --}}
    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}" class="space-y-10">
        @csrf

        {{-- Alert de errores generales --}}
        @if ($erroresFormulario)
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-2" role="alert">
                <strong class="font-bold">¡Corrige los errores!</strong>
                <span class="block sm:inline"> Por favor completa los campos requeridos antes de guardar.</span>
            </div>
        @endif

        {{-- ===================== Datos generales ===================== --}}
        <section
            class="space-y-6 p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">

            {{-- Título + subtítulo --}}
            <header class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $isEdit ? 'Editar producto' : 'Registrar nuevo producto' }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Completa la información básica del producto. Los campos marcados con (<span
                            class="text-red-500">*</span>) son obligatorios.
                    </p>
                </div>
            </header>
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="w-full md:w-auto">
                    <input type="file" wire:model="archivoExcel" accept=".xlsx,.xls,.csv"
                        class="w-full md:w-80 px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
                    @error('archivoExcel')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="button" wire:click="importarProductosExcel" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white shadow">
                    <i class="fas fa-file-excel"></i>
                    Importar Excel
                </button>

                <div wire:loading wire:target="archivoExcel,importarProductosExcel" class="text-sm text-gray-500">
                    Importando...
                </div>
            </div>
            {{-- Grid responsivo --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                {{-- Nombre --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <input wire:model.lazy="nombre" type="text" placeholder="Ej. Aceite de motor 20W50"
                            class="w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-800 dark:text-white
                     focus:ring-2 focus:ring-violet-600 focus:outline-none pr-10
                     @error('nombre') border-red-500
                     @elseif(!$errors->has('nombre') && !empty($nombre)) border-green-500
                     @else border-gray-300 dark:border-gray-700 @enderror" />
                        @if (!$errors->has('nombre') && !empty($nombre))
                            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                                <i class="fas fa-check-circle text-green-500 text-lg"></i>
                            </div>
                        @endif
                    </div>
                    @error('nombre')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Descripción --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Descripción</label>
                    <input wire:model.lazy="descripcion" type="text" placeholder="Breve descripción del producto"
                        class="mt-1 w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-800 dark:text-white
                   focus:ring-2 focus:ring-violet-600 focus:outline-none
                   @error('descripcion') border-red-500
                   @elseif(!empty($descripcion)) border-green-500
                   @else border-gray-300 dark:border-gray-700 @enderror" />
                    @error('descripcion')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Subcategoría --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Subcategoría <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.lazy="subcategoria_id"
                        class="mt-1 w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-800 dark:text-white
                   focus:ring-2 focus:ring-violet-600 focus:outline-none
                   @error('subcategoria_id') border-red-500
                   @elseif(!empty($subcategoria_id)) border-green-500
                   @else border-gray-300 dark:border-gray-700 @enderror">
                        <option value="">— Selecciona subcategoría —</option>
                        @foreach ($subcategorias as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->nombre }}</option>
                        @endforeach
                    </select>
                    @error('subcategoria_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Unidad de medida --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Unidad de medida</label>
                    <select wire:model.lazy="unidad_medida_id"
                        class="mt-1 w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-800 dark:text-white
                   focus:ring-2 focus:ring-violet-600 focus:outline-none
                   @error('unidad_medida_id') border-red-500 @else border-gray-300 dark:border-gray-700 @enderror">
                        <option value="">— Selecciona —</option>
                        @foreach ($unidades as $um)
                            <option value="{{ $um->id }}">{{ $um->nombre }} @if ($um->simbolo)
                                    ({{ $um->simbolo }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('unidad_medida_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Precio (sin IVA) --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Precio (sin IVA) <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative">
                        <span class="absolute inset-y-0 left-3 grid place-items-center text-gray-500">$</span>
                        <input wire:model.lazy="precio" type="number" step="0.01" inputmode="decimal"
                            placeholder="0.00"
                            class="w-full pl-7 pr-3 py-2.5 rounded-xl border bg-white dark:bg-gray-800 dark:text-white
                     focus:ring-2 focus:ring-violet-600 focus:outline-none
                     @error('precio') border-red-500
                     @elseif(!empty($precio)) border-green-500
                     @else border-gray-300 dark:border-gray-700 @enderror" />
                    </div>
                    @error('precio')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- IVA / Impuesto --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">IVA / Impuesto</label>
                    <select wire:model="impuesto_id"
                        class="mt-1 w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-800 dark:text-white
                   focus:ring-2 focus:ring-violet-600 focus:outline-none
                   @error('impuesto_id') border-red-500
                   @elseif(!is_null($impuesto_id)) border-green-500
                   @else border-gray-300 dark:border-gray-700 @enderror">
                        <option value="">— Sin impuesto —</option>
                        @foreach ($impuestos as $imp)
                            <option value="{{ $imp->id }}">
                                {{ $imp->nombre }}
                                @if (!is_null($imp->porcentaje))
                                    ({{ number_format($imp->porcentaje, 2) }}%)
                                @elseif(!is_null($imp->monto_fijo))
                                    (${{ number_format($imp->monto_fijo, 2) }})
                                @endif
                            </option>
                        @endforeach
                    </select>

                    {{-- Ayuda: precio con IVA dinámico --}}
                    @if (($precio ?? null) !== null && $precio !== '')
                        <p class="mt-1.5 text-[12px] text-gray-600 dark:text-gray-300">
                            Estimado c/ IVA: <span
                                class="font-semibold">${{ number_format($this->precioConIvaTmp, 2) }}</span>
                        </p>
                    @endif

                    {{-- Precio actual si estás editando --}}
                    @if ($isEdit && $producto_id)
                        @php $p = $productos->firstWhere('id', $producto_id); @endphp
                        @if ($p)
                            <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">
                                Actual c/ IVA: <span
                                    class="font-medium">${{ number_format($p->precio_con_iva, 2) }}</span>
                            </p>
                        @endif
                    @endif

                    @error('impuesto_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Imagen (Base64 directo, con preview) --}}
                <div x-data="{
                    vista: @entangle('imagen_base64').live,
                    async onFile(e) {
                        const file = e.target.files[0];
                        if (!file) { this.vista = null; return; }
                        const ok = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp', 'image/svg+xml'];
                        if (!ok.includes(file.type)) { alert('Formato no permitido');
                            e.target.value = ''; return; }
                        const maxMB = 5;
                        if (file.size > maxMB * 1024 * 1024) { alert('Máximo ' + maxMB + ' MB');
                            e.target.value = ''; return; }
                        const r = new FileReader();
                        r.onload = () => { this.vista = r.result; };
                        r.readAsDataURL(file);
                    }
                }" class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Imagen</label>
                    <div class="mt-1">
                        <input type="file" accept="image/*" @change="onFile"
                            class="block w-full text-sm file:text-sm file:font-medium
                          file:bg-gray-600 file:hover:bg-gray-700 file:text-white
                          file:border-0 file:px-4 file:py-2 file:rounded-xl
                          px-3 py-2.5 rounded-xl border bg-white dark:bg-gray-800 dark:text-white
                          focus:ring-2 focus:ring-gray-600 focus:outline-none
                          @error('imagen_base64') border-red-500 @else border-gray-300 dark:border-gray-700 @enderror" />
                        {{-- Mantiene el valor sincronizado con Livewire --}}
                        <input type="hidden" wire:model="imagen_base64">
                    </div>

                    {{-- Previews --}}
                    <div class="mt-3 flex items-center gap-3">
                        <template x-if="vista">
                            <img :src="vista"
                                class="h-16 w-16 rounded-lg object-cover ring-2 ring-violet-500/30" alt="Preview">
                        </template>

                        {{-- Imagen de BD si editas y no has elegido otra --}}
                        @if ($isEdit && $producto_id)
                            @php $p = $productos->firstWhere('id', $producto_id); @endphp
                            @if ($p && !$imagen_base64 && $p->imagen_url)
                                <img src="{{ $p->imagen_url }}"
                                    class="h-16 w-16 rounded-lg object-cover ring-1 ring-gray-300 dark:ring-gray-700"
                                    alt="Imagen actual">
                            @endif
                        @endif
                    </div>

                    @error('imagen_base64')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-[12px] text-gray-500 dark:text-gray-400">Formatos: JPG, PNG, WEBP, SVG (máx.
                        5&nbsp;MB).</p>
                </div>

                {{-- Stock Global Mínimo --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Stock mínimo
                        global</label>
                    <input wire:model.lazy="stockMinimoGlobal" type="number" min="0" placeholder="0"
                        class="mt-1 w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-800 dark:text-white
                   focus:ring-2 focus:ring-violet-600 focus:outline-none
                   @error('stockMinimoGlobal') border-red-500
                   @elseif(!is_null($stockMinimoGlobal)) border-green-500
                   @else border-gray-300 dark:border-gray-700 @enderror" />
                    @error('stockMinimoGlobal')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Stock Global Máximo --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Stock máximo
                        global</label>
                    <input wire:model.lazy="stockMaximoGlobal" type="number" min="0" placeholder="Opcional"
                        class="mt-1 w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-800 dark:text-white
                   focus:ring-2 focus:ring-violet-600 focus:outline-none
                   @error('stockMaximoGlobal') border-red-500
                   @elseif(!is_null($stockMaximoGlobal)) border-green-500
                   @else border-gray-300 dark:border-gray-700 @enderror" />
                    @error('stockMaximoGlobal')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Estado + Tipo --}}
                <div class="col-span-full">
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800">

                        {{-- Activo --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Estado</span>

                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" wire:model.lazy="activo" class="sr-only peer">

                                <!-- Fondo del switch -->
                                <div class="w-11 h-6 bg-gray-300 rounded-full transition peer-checked:bg-gray-500">
                                </div>

                                <!-- Bolita -->
                                <div
                                    class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full shadow-sm transition peer-checked:translate-x-5">
                                </div>
                            </label>
                        </div>


                        {{-- Inventariable / Servicio --}}
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Tipo de artículo</span>
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" wire:model.lazy="es_inventariable" class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-gray-600 transition">
                                </div>
                                <div
                                    class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition peer-checked:translate-x-5">
                                </div>
                            </label>
                        </div>

                        {{-- Etiqueta del tipo seleccionado --}}
                        <div class="sm:col-span-2 flex items-center gap-2">
                            <span
                                class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold
                           {{ $es_inventariable ? 'bg-gray-50 text-gray-700 dark:bg-ray-900/30 dark:text-gray-200' : 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-200' }}">
                                <i class="fas {{ $es_inventariable ? 'fa-box' : 'fa-concierge-bell' }}"></i>
                                {{ $es_inventariable ? 'Artículo inventariable' : 'Artículo de servicio' }}
                            </span>
                            <p class="text-[12px] text-gray-500 dark:text-gray-400">
                                {{ $es_inventariable ? 'Se gestionará stock por bodegas.' : 'No gestiona stock; solo facturación/contabilidad.' }}
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Resumen + abrir modal --}}
            <section class="space-y-4 mt-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Cuentas contables del producto</h4>

                    {{-- Chips resumen (si hay) --}}
                    @if (isset($tiposCuenta) && $tiposCuenta->count() && !empty($cuentasPorTipo) && $mov_contable_segun === 'ARTICULO')
                        <div class="hidden md:flex flex-wrap gap-1">
                            @foreach ($tiposCuenta as $t)
                                @php
                                    $selId = $cuentasPorTipo[$t->id] ?? null;
                                    $sel = $selId ? $cuentasPUC->firstWhere('id', $selId) : null;
                                @endphp
                                <span
                                    class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full
                             {{ $sel ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                    <span class="font-semibold mr-1">{{ $t->nombre }}</span>
                                    <span class="text-gray-400">|</span>
                                    <span class="ml-1">{{ $sel?->codigo ?? '—' }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <button type="button" wire:click="abrirModalCuentas"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-gray-600 hover:bg-violet-700 text-white text-sm shadow">
                        <i class="fas fa-cog"></i> Configurar
                    </button>
                </div>

                @if ($mov_contable_segun === 'SUBCATEGORIA')
                    <div
                        class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300">
                        Este producto <strong>heredará las cuentas desde su Subcategoría</strong>. No necesitas
                        configurarlas aquí.
                    </div>
                @endif

                @if (!$tiposCuenta || !$tiposCuenta->count())
                    <div
                        class="p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-xl text-sm text-yellow-800 dark:text-yellow-200">
                        No hay tipos de cuentas configurados. Crea registros en <code>producto_cuenta_tipos</code>.
                    </div>
                @endif
            </section>

        </section>

        {{-- ===================== Stock por bodega ===================== --}}
        @if ($es_inventariable)
            <section
                class="space-y-6 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-white">Stock Mínimo/Máximo por Bodega</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Seleccionar
                            Bodega</label>
                        <select wire:model="bodegaSeleccionada"
                            class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none">
                            <option value="">-- Selecciona Bodega --</option>
                            @foreach ($bodegas as $bodega)
                                <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Stock
                            Mínimo</label>
                        <input wire:model="stockMinimo" type="number" min="0" placeholder="0"
                            class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 dark:text-gray-300 mb-1">Stock
                            Máximo</label>
                        <input wire:model="stockMaximo" type="number" min="0" placeholder="Opcional"
                            class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none" />
                    </div>
                </div>

                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" wire:click="agregarBodega"
                        class="flex items-center gap-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-xl shadow hover:shadow-md transition">
                        <i class="fas fa-plus text-sm"></i>
                        <span>Agregar</span>
                    </button>

                    <button type="submit"
                        class="flex items-center gap-1 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-xs font-semibold rounded-xl shadow hover:shadow-md transition"
                        wire:loading.attr="disabled">
                        <i class="fas fa-save text-sm"></i>
                        <span>{{ $isEdit ? 'Actualizar' : 'Guardar' }}</span>
                    </button>
                </div>

                @if ($stocksPorBodega)
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
                                @foreach ($stocksPorBodega as $bodegaId => $datos)
                                    <tr>
                                        <td class="p-2">{{ $bodegas->find($bodegaId)->nombre ?? 'Bodega' }}</td>
                                        <td class="p-2 text-center">
                                            <input type="number" min="0"
                                                wire:model.defer="stocksPorBodega.{{ $bodegaId }}.stock_minimo"
                                                class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none" />
                                        </td>
                                        <td class="p-2 text-center">
                                            <input type="number" min="0"
                                                wire:model.defer="stocksPorBodega.{{ $bodegaId }}.stock_maximo"
                                                class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600 focus:outline-none" />
                                        </td>
                                        <td class="p-2 text-center">
                                            <button type="button" wire:click="eliminarBodega({{ $bodegaId }})"
                                                class="inline-flex items-center gap-1 text-red-600 hover:text-red-800 font-semibold transition">
                                                <i class="fas fa-trash-alt"></i> Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @else
            {{-- Nota si es servicio --}}
            <section class="p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Este artículo está marcado como <strong>Servicio</strong>, por lo que no gestiona stock por bodega.
                </div>
                <div class="mt-4">
                    <button type="submit"
                        class="flex items-center gap-1 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold rounded-xl shadow hover:shadow-md transition"
                        wire:loading.attr="disabled">
                        <i class="fas fa-save text-sm"></i>
                        <span>{{ $isEdit ? 'Actualizar' : 'Guardar' }}</span>
                    </button>
                </div>
            </section>
        @endif

    </form>

    {{-- ===================== Tabla de productos ===================== --}}
    <section
        class="space-y-6 p-4 sm:p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <h3 class="text-lg sm:text-2xl font-bold text-gray-800 dark:text-white">
                Inventario Actual
            </h3>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                <input wire:model.defer="search" type="text" placeholder="Buscar producto..."
                    class="w-full sm:w-64 h-10 px-3 border rounded-xl dark:border-gray-700 dark:bg-gray-800 dark:text-white
               focus:ring-gray-600 focus:border-violet-600 text-sm" />
                <button wire:click="$refresh"
                    class="h-10 px-4 bg-gray-600 hover:bg-gray-700 text-white rounded-xl shadow transition-all text-sm">
                    <i class="fas fa-search mr-1"></i> Buscar
                </button>
            </div>
        </div>

        {{-- Tabla compacta (SIN min-w fijo / SIN scroll forzado) --}}
        <div class="w-full overflow-x-auto overscroll-x-contain">
            {{-- ✅ Esto fuerza ancho y habilita scroll horizontal real --}}
            <div class="min-w-[1200px]">

                <table
                    class="min-w-full table-auto bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden text-xs text-gray-700 dark:text-gray-300">

                    <thead class="bg-gray-700 text-white">
                        <tr class="whitespace-nowrap">

                            {{-- ✅ STICKY IZQUIERDA --}}
                            <th class="px-2 py-2 text-left font-semibold w-[60px] sticky left-0 z-30 bg-gray-700">#ID
                            </th>
                            <th class="px-2 py-2 text-left font-semibold w-[64px] sticky left-[60px] z-30 bg-gray-700">
                                Img</th>
                            <th
                                class="px-2 py-2 text-left font-semibold w-[220px] sticky left-[124px] z-30 bg-gray-700">
                                Nombre</th>

                            {{-- ✅ Oculta en pantallas pequeñas para evitar tanta carga visual --}}
                            <th class="px-2 py-2 text-left font-semibold hidden lg:table-cell w-[260px]">Descripción
                            </th>

                            <th class="px-2 py-2 text-center font-semibold w-[120px]">Tipo</th>

                            <th class="px-2 py-2 text-center font-semibold hidden md:table-cell w-[140px]">U. Medida
                            </th>

                            <th class="px-2 py-2 text-center font-semibold w-[120px]">Precio</th>

                            <th class="px-2 py-2 text-center font-semibold hidden xl:table-cell w-[200px]">Impuesto
                            </th>

                            <th class="px-2 py-2 text-center font-semibold w-[130px]">Precio c/ IVA</th>

                            <th class="px-2 py-2 text-center font-semibold hidden xl:table-cell w-[160px]">Subcat.</th>

                            <th class="px-2 py-2 text-center font-semibold w-[120px]">Stock</th>

                            <th class="px-2 py-2 text-center font-semibold hidden 2xl:table-cell w-[150px]">CPU Global
                            </th>

                            <th class="px-2 py-2 text-center font-semibold w-[120px]">Estado</th>
                            <th class="px-2 py-2 text-center font-semibold w-[90px]">Bod.</th>

                            {{-- ✅ STICKY DERECHA --}}
                            <th class="px-2 py-2 text-center font-semibold w-[90px] sticky right-0 z-30 bg-gray-700">
                                Acc.</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($productos as $prod)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/60 transition">

                                {{-- ✅ STICKY IZQUIERDA --}}
                                <td class="px-2 py-2 sticky left-0 z-20 bg-white dark:bg-gray-800">
                                    {{ $prod->id }}
                                </td>

                                <td class="px-2 py-2 sticky left-[60px] z-20 bg-white dark:bg-gray-800">
                                    @if ($prod->imagen_url)
                                        <img src="{{ $prod->imagen_url }}" alt="img"
                                            class="h-9 w-9 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                                    @else
                                        <div
                                            class="h-9 w-9 rounded-lg bg-gray-200 dark:bg-gray-700 grid place-items-center text-gray-400">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    @endif
                                </td>

                                {{-- Nombre (sticky) --}}
                                <td class="px-2 py-2 sticky left-[124px] z-20 bg-white dark:bg-gray-800 truncate"
                                    title="{{ $prod->nombre }}">
                                    {{ $prod->nombre }}
                                </td>

                                {{-- Descripción (solo lg+) --}}
                                <td class="px-2 py-2 hidden lg:table-cell truncate" title="{{ $prod->descripcion }}">
                                    {{ $prod->descripcion }}
                                </td>

                                {{-- Tipo --}}
                                <td class="px-2 py-2 text-center">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full
                {{ $prod->es_inventariable ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                        <i
                                            class="{{ $prod->es_inventariable ? 'fas fa-box' : 'fas fa-concierge-bell' }}"></i>
                                        {{ $prod->es_inventariable ? 'Invent.' : 'Serv.' }}
                                    </span>
                                </td>

                                {{-- UM (solo md+) --}}
                                <td class="px-2 py-2 text-center hidden md:table-cell truncate"
                                    title="{{ $prod->unidadMedida?->nombre }}">
                                    @if ($prod->unidadMedida)
                                        {{ $prod->unidadMedida->nombre }}
                                        @if ($prod->unidadMedida->simbolo)
                                            ({{ $prod->unidadMedida->simbolo }})
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- Precio --}}
                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                    {{ number_format($prod->precio, 2) }}
                                </td>

                                {{-- Impuesto (solo xl+) --}}
                                <td class="px-2 py-2 text-center hidden xl:table-cell truncate"
                                    title="{{ $prod->impuesto?->nombre }}">
                                    {{ $prod->impuesto?->nombre ?? '—' }}
                                    @if ($prod->impuesto)
                                        @if (!is_null($prod->impuesto->porcentaje))
                                            ({{ number_format($prod->impuesto->porcentaje, 2) }}%)
                                        @elseif(!is_null($prod->impuesto->monto_fijo))
                                            (${{ number_format($prod->impuesto->monto_fijo, 2) }})
                                        @endif
                                    @endif
                                </td>

                                {{-- Precio con IVA --}}
                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                    ${{ number_format($prod->precio_con_iva, 2) }}
                                </td>

                                {{-- Subcategoría (solo xl+) --}}
                                <td class="px-2 py-2 text-center hidden xl:table-cell truncate"
                                    title="{{ $prod->subcategoria->nombre ?? '-' }}">
                                    {{ $prod->subcategoria->nombre ?? '-' }}
                                </td>

                                {{-- Stock total --}}
                                <td class="px-2 py-2 text-center whitespace-nowrap">
                                    {{ $prod->es_inventariable ? $prod->bodegas->sum(fn($b) => $b->pivot->stock) : '—' }}
                                </td>

                                {{-- CPU Global (solo 2xl+) --}}
                                <td class="px-2 py-2 text-center hidden 2xl:table-cell whitespace-nowrap">
                                    {{ is_null($prod->costo_promedio_global) ? '—' : number_format($prod->costo_promedio_global, 2) }}
                                </td>

                                {{-- Estado --}}
                                <td class="px-2 py-2 text-center">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full
                {{ $prod->activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        @if ($prod->activo)
                                            <i class="fas fa-check-circle"></i> Activo
                                        @else
                                            <i class="fas fa-times-circle"></i> Inactivo
                                        @endif
                                    </span>
                                </td>

                                {{-- Ver bodegas --}}
                                <td class="px-2 py-2 text-center">
                                    @if ($prod->es_inventariable && $prod->bodegas && $prod->bodegas->count())
                                        <button wire:click="$toggle('mostrarBodegas.{{ $prod->id }}')"
                                            class="text-indigo-600 hover:text-indigo-800 text-base"
                                            title="Ver bodegas">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    @elseif(!$prod->es_inventariable)
                                        <span class="text-gray-400 italic text-[11px]">Serv.</span>
                                    @else
                                        <span class="text-gray-400 italic text-[11px]">—</span>
                                    @endif
                                </td>

                                {{-- ✅ STICKY DERECHA: Acciones --}}
                                <td class="px-2 py-2 text-center sticky right-0 z-20 bg-white dark:bg-gray-800">
                                    <button wire:click="edit({{ $prod->id }})"
                                        class="text-blue-600 hover:text-blue-800 transition-colors" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>

                            </tr>

                            {{-- Detalle bodegas (solo si inventariable) --}}
                            @if ($prod->es_inventariable && !empty($mostrarBodegas[$prod->id]) && $prod->bodegas)
                                <tr>
                                    <td colspan="15" class="bg-gray-100 dark:bg-gray-700 p-3">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-xs text-gray-700 dark:text-gray-300">
                                                <thead
                                                    class="bg-violet-200 dark:bg-violet-700 text-gray-800 dark:text-white">
                                                    <tr class="whitespace-nowrap">
                                                        <th class="p-2 text-left">Código</th>
                                                        <th class="p-2 text-left">Bodega</th>
                                                        <th class="p-2 text-center">Stock</th>
                                                        <th class="p-2 text-center">Min</th>
                                                        <th class="p-2 text-center">Max</th>
                                                        <th class="p-2 text-center">Condición</th>
                                                        <th class="p-2 text-center">Últ. Costo</th>
                                                        <th class="p-2 text-center">CPU</th>
                                                        <th class="p-2 text-center">Método</th>
                                                    </tr>
                                                </thead>

                                                <tbody class="divide-y divide-gray-300 dark:divide-gray-600">
                                                    @foreach ($prod->bodegas as $bodega)
                                                        @php
                                                            $stock = $bodega->pivot->stock;
                                                            $minimo = $bodega->pivot->stock_minimo;
                                                            $maximo = $bodega->pivot->stock_maximo;
                                                            $ultimo = $bodega->pivot->ultimo_costo;
                                                            $cpu = $bodega->pivot->costo_promedio;
                                                            $metodo = $bodega->pivot->metodo_costeo;
                                                        @endphp

                                                        <tr class="whitespace-nowrap">
                                                            <td class="p-2">{{ $bodega->id }}</td>
                                                            <td class="p-2">{{ $bodega->nombre }}</td>
                                                            <td class="p-2 text-center">{{ $stock }}</td>
                                                            <td class="p-2 text-center">{{ $minimo }}</td>
                                                            <td class="p-2 text-center">{{ $maximo ?? '-' }}</td>

                                                            <td class="p-2 text-center">
                                                                @if ($stock == 0)
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold bg-red-100 text-red-700 rounded-full">
                                                                        <i class="fas fa-times-circle mr-1"></i> Sin
                                                                    </span>
                                                                @elseif ($stock < $minimo)
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold bg-yellow-100 text-yellow-800 rounded-full">
                                                                        <i
                                                                            class="fas fa-exclamation-triangle mr-1"></i>
                                                                        Bajo
                                                                    </span>
                                                                @elseif (!is_null($maximo) && $stock > $maximo)
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold bg-blue-100 text-blue-800 rounded-full">
                                                                        <i class="fas fa-boxes mr-1"></i> Alto
                                                                    </span>
                                                                @else
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold bg-green-100 text-green-700 rounded-full">
                                                                        <i class="fas fa-check-circle mr-1"></i> OK
                                                                    </span>
                                                                @endif
                                                            </td>

                                                            <td class="p-2 text-center">
                                                                {{ is_null($ultimo) ? '—' : number_format((float) $ultimo, 6) }}
                                                            </td>
                                                            <td class="p-2 text-center">
                                                                {{ is_null($cpu) ? '—' : number_format((float) $cpu, 6) }}
                                                            </td>
                                                            <td class="p-2 text-center">
                                                                {{ $metodo ?: '—' }}
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
                                <td colspan="15" class="p-4 text-center text-gray-500 dark:text-gray-400 italic">
                                    No hay productos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

            </div>
        </div>
    </section>

    {{-- ===================== MODAL: Configurar Cuentas ===================== --}}
    @if ($showCuentasModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/60" wire:click="cerrarModalCuentas"></div>

            <div
                class="relative w-full max-w-4xl bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-white">Configurar cuentas contables</h4>
                    <button class="text-gray-500 hover:text-gray-800 dark:hover:text-gray-200"
                        wire:click="cerrarModalCuentas">
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
                            <button type="button" wire:click="$set('mov_contable_segun','ARTICULO')"
                                class="px-4 py-2 text-sm rounded-lg transition
                             {{ $mov_contable_segun === 'ARTICULO' ? 'bg-violet-600 text-white shadow' : 'text-gray-700 dark:text-gray-300' }}">
                                Artículo
                            </button>
                            <button type="button" wire:click="$set('mov_contable_segun','SUBCATEGORIA')"
                                class="px-4 py-2 text-sm rounded-lg transition
                             {{ $mov_contable_segun === 'SUBCATEGORIA' ? 'bg-violet-600 text-white shadow' : 'text-gray-700 dark:text-gray-300' }}">
                                Subcategoría
                            </button>
                        </div>
                        @error('mov_contable_segun')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    @if ($mov_contable_segun === 'SUBCATEGORIA')
                        <div
                            class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-dashed border-gray-300 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300">
                            Las cuentas contables se <strong>heredarán de la Subcategoría</strong>. No es necesario
                            configurarlas a nivel de producto.
                        </div>
                    @endif

                    {{-- Tabla de cuentas --}}
                    <div class="overflow-x-auto opacity-{{ $mov_contable_segun === 'ARTICULO' ? '100' : '50' }}">
                        <table
                            class="min-w-[900px] w-full bg-white dark:bg-gray-900 rounded-xl overflow-hidden text-sm">
                            <thead class="bg-violet-600 text-white">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold">Movimiento contable según</th>
                                    <th class="px-3 py-2 text-left font-semibold">Cuenta (PUC)</th>
                                    <th class="px-3 py-2 text-left font-semibold">Código seleccionado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($tiposCuenta as $t)
                                    @php
                                        $seleccionId = $cuentasPorTipo[$t->id] ?? null;
                                        $seleccion = $seleccionId ? $cuentasPUC->firstWhere('id', $seleccionId) : null;
                                        $disabled = $mov_contable_segun === 'SUBCATEGORIA';
                                    @endphp
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-800 dark:text-gray-100">
                                            {{ $t->nombre }}
                                            @if ($t->obligatorio && !$disabled)
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <select wire:model.lazy="cuentasPorTipo.{{ $t->id }}"
                                                {{ $disabled ? 'disabled' : '' }}
                                                class="w-full px-3 py-2 rounded-lg border
                          @error('cuentasPorTipo.' . $t->id) border-red-500
                          @elseif(!empty($seleccionId)) border-green-500
                          @else border-gray-300 @enderror
                          dark:border-gray-700 dark:bg-gray-800 dark:text-white
                          focus:ring-2 focus:ring-violet-600 focus:outline-none disabled:bg-gray-100 disabled:text-gray-400 disabled:dark:bg-gray-700 disabled:dark:text-gray-500">
                                                <option value="">— Selecciona cuenta —</option>
                                                @foreach ($cuentasPUC as $c)
                                                    <option value="{{ $c->id }}">{{ $c->codigo }} —
                                                        {{ $c->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @if (!$disabled)
                                                @error('cuentasPorTipo.' . $t->id)
                                                    <span class="text-red-600 text-xs">{{ $message }}</span>
                                                @enderror
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-700 dark:text-gray-200">
                                            @if ($seleccion)
                                                <span
                                                    class="inline-flex items-center px-2 py-1 rounded-md bg-gray-100 dark:bg-gray-700">
                                                    <span class="font-semibold mr-2">{{ $seleccion->codigo }}</span>
                                                    <span
                                                        class="text-xs text-gray-500">{{ $seleccion->nombre }}</span>
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

                <div
                    class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
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

{{-- ===================== Scripts auxiliares ===================== --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.addEventListener('producto-creado', e => {
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: e.detail.mensaje,
            timer: 2000,
            showConfirmButton: false
        });
    });
    window.addEventListener('producto-actualizado', e => {
        Swal.fire({
            icon: 'success',
            title: '¡Actualizado!',
            text: e.detail.mensaje,
            timer: 2000,
            showConfirmButton: false
        });
    });
    window.addEventListener('producto-eliminado', e => {
        Swal.fire({
            icon: 'success',
            title: '¡Eliminado!',
            text: e.detail.mensaje,
            timer: 2000,
            showConfirmButton: false
        });
    });
    window.addEventListener('error', e => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: e.detail.mensaje
        });
    });
</script>
