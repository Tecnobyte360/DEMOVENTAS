<div class="px-4 sm:px-6 lg:px-10 py-8 w-full">

    {{-- =========================
        STEPPER (arriba)
    ========================= --}}
    <div class="mb-6">
        <div class="flex items-center justify-between gap-4">
            {{-- Step 1 --}}
            <div class="flex items-center gap-3">
                <div
                    class="w-9 h-9 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-bold shadow">
                    1
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Transferir Stock</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Producto, bodegas y cantidad</p>
                </div>
            </div>

            {{-- Step 2 (disabled visual) --}}
            <div class="hidden md:flex items-center gap-3 opacity-40">
                <div
                    class="w-9 h-9 rounded-2xl bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-200 flex items-center justify-center font-bold">
                    2
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Confirmación</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Revisar y ejecutar</p>
                </div>
            </div>

            {{-- Step 3 (disabled visual) --}}
            <div class="hidden lg:flex items-center gap-3 opacity-40">
                <div
                    class="w-9 h-9 rounded-2xl bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-200 flex items-center justify-center font-bold">
                    3
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Histórico</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Transferencias registradas</p>
                </div>
            </div>
        </div>

        <div class="mt-4 h-px bg-gray-200 dark:bg-slate-700/60"></div>

        <div class="mt-4 rounded-2xl border border-gray-200 dark:border-slate-700 bg-white/70 dark:bg-slate-900/60 p-4">
            <div class="flex items-start gap-3">
                <div
                    class="mt-0.5 w-8 h-8 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-200 flex items-center justify-center">
                    <i class="fa-solid fa-circle-info text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Transferencia de inventario entre
                        bodegas</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Selecciona el producto, bodega origen, bodega destino y cantidad. El sistema descuenta del
                        origen y suma en destino.
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================
        CARD PRINCIPAL
    ========================= --}}
    <div
        class="relative overflow-hidden bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-700 rounded-3xl shadow-xl">
        {{-- barra superior gradiente --}}
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-sky-400 to-emerald-400"></div>

        <div class="px-6 pt-6 pb-7 space-y-6">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div
                        class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-100 flex items-center justify-center">
                        <i class="fa-solid fa-warehouse text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-base md:text-lg font-extrabold text-gray-900 dark:text-white">
                            Transferencia de Stock por Bodega
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Mueve inventario entre bodegas con control de stock y costos.
                        </p>
                    </div>
                </div>

                {{-- Mini KPIs --}}
                <div class="grid grid-cols-1 gap-2 text-right">
                    <div
                        class="px-3 py-2 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Stock Origen</p>
                        <p class="text-sm font-extrabold text-gray-900 dark:text-white">
                            {{ is_null($stock_origen) ? '—' : number_format($stock_origen, 6, ',', '.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Grid de campos --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                {{-- Producto --}}
                <div class="lg:col-span-6">
                    <label class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        Producto <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-2 relative">
                        <select wire:model.live="producto_id"
                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-700
                                       bg-white dark:bg-slate-800 text-gray-900 dark:text-white
                                       px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Seleccione —</option>
                            @foreach ($productos as $p)
                                <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                        @error('producto_id')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Cantidad --}}
                <div class="lg:col-span-6">
                    <label class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        Cantidad a transferir <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-2 relative">
                        <input type="number" step="0.000001" wire:model.live="cantidad"
                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-700
                                      bg-white dark:bg-slate-800 text-gray-900 dark:text-white
                                      px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Ej: 5">
                        @error('cantidad')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                            Se validará contra el stock disponible en la bodega origen.
                        </p>
                    </div>
                </div>

                {{-- Bodega origen --}}
                <div class="lg:col-span-6">
                    <label class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        Bodega origen <span class="text-red-500">*</span>
                    </label>

                    <div class="mt-2">
                        <select wire:model.live="bodega_origen_id"
                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-700
                                       bg-white dark:bg-slate-800 text-gray-900 dark:text-white
                                       px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Seleccione —</option>
                            @foreach ($bodegas as $b)
                                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                            @endforeach
                        </select>

                        @error('bodega_origen_id')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        {{-- Panel stock origen --}}
                        <div
                            class="mt-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 px-4 py-3">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-200 flex items-center justify-center">
                                        <i class="fa-solid fa-boxes-stacked text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Disponible en origen
                                        </p>

                                        @if (is_null($stock_origen))
                                            <p class="text-sm font-semibold text-gray-400">
                                                —
                                            </p>
                                        @elseif((float) $stock_origen <= 0)
                                            <p
                                                class="text-sm font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1">
                                                <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                                                Sin stock
                                            </p>
                                        @else
                                            <p class="text-sm font-extrabold text-gray-900 dark:text-white">
                                                {{ number_format($stock_origen, 3, ',', '.') }}
                                            </p>
                                        @endif
                                    </div>

                                </div>

                                @if (!is_null($stock_origen) && (float) $stock_origen <= 0)
                                    <span
                                        class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-200">
                                        Sin stock
                                    </span>
                                @else
                                    <span
                                        class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                                        OK
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bodega destino --}}
                <div class="lg:col-span-6">
                    <label class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        Bodega destino <span class="text-red-500">*</span>
                    </label>

                    <div class="mt-2">
                        <select wire:model.live="bodega_destino_id"
                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-700
                                       bg-white dark:bg-slate-800 text-gray-900 dark:text-white
                                       px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Seleccione —</option>
                            @foreach ($bodegas as $b)
                                <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                            @endforeach
                        </select>

                        @error('bodega_destino_id')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror

                        <div
                            class="mt-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 px-4 py-3">
                            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                En destino se crea la fila si no existe y se suma el stock.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Observación --}}
                <div class="lg:col-span-12">
                    <label class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        Observación (opcional)
                    </label>
                    <div class="mt-2">
                        <textarea wire:model.live="observacion" rows="3"
                            class="w-full rounded-2xl border border-slate-200 dark:border-slate-700
                                         bg-white dark:bg-slate-800 text-gray-900 dark:text-white
                                         px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Ej: Transferencia para abastecer punto de venta..."></textarea>
                        @error('observacion')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Footer actions --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fa-solid fa-shield-halved"></i>
                    Se ejecuta en transacción y valida stock del origen.
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" wire:click="$refresh"
                        class="px-4 py-2 rounded-2xl border border-slate-200 dark:border-slate-700
                                   bg-white dark:bg-slate-800 text-gray-800 dark:text-gray-100
                                   hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        <i class="fa-solid fa-rotate-right mr-2"></i>Refrescar
                    </button>

                    <button type="button" wire:click="transferir" wire:loading.attr="disabled"
                        @disabled($disabledTransferir ?? false)
                        class="px-5 py-2.5 rounded-2xl bg-slate-900 text-white font-semibold
                                   hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <span wire:loading.remove>
                            Transferir <i class="fa-solid fa-arrow-right-arrow-left ml-2"></i>
                        </span>
                        <span wire:loading class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"></svg>
                            Procesando...
                        </span>
                    </button>
                </div>
            </div>

        </div>
    </div>

</div>
