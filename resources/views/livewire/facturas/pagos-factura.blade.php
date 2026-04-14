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

<div x-data="{ open: @entangle('show') }" x-cloak>
    <div x-show="open" class="fixed inset-0 z-40 bg-black/40"></div>

    <div x-show="open" class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-2 sm:p-4 overflow-y-auto">
        <div class="w-full max-w-4xl rounded-none sm:rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border dark:border-gray-700 overflow-hidden my-2 sm:my-6 max-h-[95vh] flex flex-col">

            <div class="px-3 sm:px-5 py-3 sm:py-4 border-b border-gray-200 dark:border-gray-700 flex items-start justify-between gap-2
                        bg-gradient-to-r from-gray-100 via-gray-200 to-gray-300
                        dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 text-gray-800 dark:text-white rounded-t-none sm:rounded-t-2xl">
                <div class="min-w-0 flex-1">
                    <h3 class="text-base sm:text-lg font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-cash-register"></i>
                        <span class="truncate">Registrar pago de factura</span>
                    </h3>
                    <p class="text-[11px] sm:text-xs mt-1 text-gray-600 dark:text-gray-300">
                        {{ $modoDocumento === 'compra' ? 'Pago aplicado a factura de compra' : 'Pago aplicado a factura de venta' }}
                    </p>
                </div>

                <button class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition shrink-0 p-1"
                    @click="open=false" wire:click="cerrar" title="Cerrar">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-3 sm:p-5 space-y-4 sm:space-y-5 overflow-y-auto flex-1">

                @if (!$facturaId)
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">
                                Tipo de documento
                            </label>
                            <select wire:model.live="modoDocumento"
                                class="w-full h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700
                                       dark:bg-gray-800 dark:text-white px-3 text-sm">
                                <option value="venta">Factura de venta</option>
                                <option value="compra">Factura de compra</option>
                            </select>
                            @error('modoDocumento')
                                <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">
                                Serie
                            </label>
                            <select wire:model.live="serieId"
                                class="w-full h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700
                                       dark:bg-gray-800 dark:text-white px-3 text-sm">
                                <option value="">— Todas —</option>
                                @foreach ($seriesDisponibles as $tipoNombre => $series)
                                    <optgroup label="{{ $tipoNombre }}">
                                        @foreach ($series as $s)
                                            <option value="{{ $s['id'] }}">
                                                {{ $s['prefijo'] }} — {{ $s['nombre'] }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            @error('serieId')
                                <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">
                                Buscar factura
                            </label>
                            <input type="text" wire:model.live.debounce.400ms="buscarFactura"
                                placeholder="Cliente/Proveedor · documento · número · prefijo"
                                class="w-full h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700
                                       dark:bg-gray-800 dark:text-white px-3 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">
                            Seleccionar factura pendiente

                            <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-medium
                                {{ $modoDocumento === 'compra' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
                                {{ $modoDocumento === 'compra' ? 'COMPRA' : 'VENTA' }}
                            </span>

                            @if ($serieNombre !== '—')
                                <span class="ml-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 normal-case font-medium">
                                    {{ $serieNombre }}
                                </span>
                            @endif
                        </label>

                        <select wire:model.live="facturaId"
                            class="w-full h-11 rounded-xl border-2 border-indigo-400
                                   dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 text-sm">
                            <option value="">— Selecciona una factura —</option>

                            @foreach ($facturasPendientes as $f)
                                @php
                                    $numero  = str_pad($f->numero ?? 0, $f->serie?->longitud ?? 6, '0', STR_PAD_LEFT);
                                    $codigo  = trim(($f->prefijo ? $f->prefijo . '-' : '') . $numero);
                                    $tercero = $f->socioNegocio?->razon_social ?? 'Sin tercero';
                                    $fecha   = \Carbon\Carbon::parse($f->fecha)->format('Y-m-d');
                                @endphp
                                <option value="{{ $f->id }}">
                                    {{ $codigo }} — {{ $tercero }} — {{ $fecha }}
                                    — Total: ${{ number_format((float) $f->total, 0, ',', '.') }}
                                    — Saldo: ${{ number_format((float) $f->saldo, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>

                        @error('facturaId')
                            <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                @if ($facturaId)
                    <div class="flex items-center gap-2 text-sm">
                        <i class="fa-solid fa-file-invoice text-indigo-400"></i>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Factura seleccionada</span>
                        <button
                            wire:click="cambiarFactura"
                            class="ml-auto text-xs px-3 py-1 rounded-lg border border-gray-300 dark:border-gray-600
                                   text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Cambiar factura
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2 sm:gap-4">
                        <div class="rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-2 sm:p-4">
                            <div class="text-[10px] sm:text-xs uppercase tracking-wide text-slate-500 dark:text-slate-300">
                                Total factura
                            </div>
                            <div class="mt-1 text-sm sm:text-2xl font-bold text-slate-800 dark:text-white truncate">
                                ${{ number_format($fac_total, 2, ',', '.') }}
                            </div>
                        </div>

                        <div class="rounded-xl sm:rounded-2xl border border-amber-200 dark:border-amber-700 bg-amber-50 dark:bg-amber-900/20 p-2 sm:p-4">
                            <div class="text-[10px] sm:text-xs uppercase tracking-wide text-amber-600 dark:text-amber-300">
                                Total pagado
                            </div>
                            <div class="mt-1 text-sm sm:text-2xl font-bold text-amber-700 dark:text-amber-200 truncate">
                                ${{ number_format($fac_pagado, 2, ',', '.') }}
                            </div>
                        </div>

                        <div class="rounded-xl sm:rounded-2xl border border-emerald-200 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/20 p-2 sm:p-4">
                            <div class="text-[10px] sm:text-xs uppercase tracking-wide text-emerald-600 dark:text-emerald-300">
                                Saldo pendiente
                            </div>
                            <div class="mt-1 text-sm sm:text-2xl font-bold text-emerald-700 dark:text-emerald-200 truncate">
                                ${{ number_format($fac_saldo, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                    <div>
                        <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">
                            Fecha del pago
                        </label>
                        <input type="date"
                            class="w-full h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700
                                   dark:bg-gray-800 dark:text-white px-3"
                            wire:model.live="fecha">
                        @error('fecha')
                            <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">
                            Notas
                        </label>
                        <input type="text"
                            class="w-full h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700
                                   dark:bg-gray-800 dark:text-white px-3"
                            wire:model.defer="notas"
                            placeholder="Observaciones del pago (opcional)">
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gray-100 dark:bg-gray-800 px-3 py-2 text-[12px] font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                        Pago de la factura
                    </div>

                    {{-- VISTA DESKTOP: tabla --}}
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left bg-gray-50 dark:bg-gray-800/50">
                                    <th class="p-3">Medio de pago</th>
                                    <th class="p-3 w-44 text-right">Monto a pagar</th>
                                    <th class="p-3 w-56">Referencia</th>
                                    <th class="p-3 w-16 text-center">Acción</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($items as $idx => $row)
                                    <tr wire:key="pago-row-desktop-{{ $idx }}">
                                        <td class="p-3">
                                            <select wire:model.live="items.{{ $idx }}.medio_pago_id"
                                                class="w-full h-10 rounded-lg border-2 border-gray-200 dark:border-gray-700
                                                       dark:bg-gray-800 dark:text-white px-2">
                                                <option value="">— Selecciona —</option>
                                                @foreach ($medios as $m)
                                                    <option value="{{ $m->id }}">
                                                        {{ $m->codigo ? $m->codigo . ' — ' : '' }}{{ $m->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("items.$idx.medio_pago_id")
                                                <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <td class="p-3">
                                            <input type="number" step="0.01" min="0.01"
                                                wire:model.live="items.{{ $idx }}.monto"
                                                class="w-full h-10 text-right rounded-lg border-2 border-gray-200
                                                       dark:border-gray-700 dark:bg-gray-800
                                                       dark:text-white px-2 font-semibold">
                                            @error("items.$idx.monto")
                                                <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <td class="p-3">
                                            <input type="text" maxlength="120"
                                                wire:model.defer="items.{{ $idx }}.referencia"
                                                class="w-full h-10 rounded-lg border-2 border-gray-200 dark:border-gray-700
                                                       dark:bg-gray-800 dark:text-white px-2"
                                                placeholder="Comprobante o # autorización">
                                            @error("items.$idx.referencia")
                                                <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <td class="p-3 text-center">
                                            <button type="button"
                                                wire:click="removeItem({{ $idx }})"
                                                @if (count($items) <= 1) disabled @endif
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg
                                                       bg-rose-50 hover:bg-rose-100 text-rose-600
                                                       dark:bg-rose-900/20 dark:hover:bg-rose-900/40 dark:text-rose-300
                                                       border border-rose-200 dark:border-rose-700
                                                       disabled:opacity-40 disabled:cursor-not-allowed transition"
                                                title="Quitar medio de pago">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <tfoot class="bg-gray-50 dark:bg-gray-800/30">
                                <tr>
                                    <td class="p-3 text-right font-semibold">Total del pago:</td>
                                    <td class="p-3 text-right font-semibold">
                                        ${{ number_format($sumMonto, 2, ',', '.') }}
                                    </td>
                                    <td class="p-3 text-right font-semibold" colspan="2">
                                        <span class="{{ $diff == 0 ? 'text-emerald-600' : ($diff > 0 ? 'text-amber-600' : 'text-rose-600') }}">
                                            Diferencia: ${{ number_format($diff, 2, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- VISTA MOBILE: tarjetas --}}
                    <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($items as $idx => $row)
                            <div wire:key="pago-row-mobile-{{ $idx }}" class="p-3 space-y-3 bg-white dark:bg-gray-900">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] uppercase font-semibold text-gray-500 dark:text-gray-400">
                                        Medio de pago #{{ $idx + 1 }}
                                    </span>
                                    <button type="button"
                                        wire:click="removeItem({{ $idx }})"
                                        @if (count($items) <= 1) disabled @endif
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                                               bg-rose-50 hover:bg-rose-100 text-rose-600
                                               dark:bg-rose-900/20 dark:hover:bg-rose-900/40 dark:text-rose-300
                                               border border-rose-200 dark:border-rose-700
                                               disabled:opacity-40 disabled:cursor-not-allowed transition"
                                        title="Quitar medio de pago">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>

                                <div>
                                    <label class="text-[11px] font-medium text-gray-600 dark:text-gray-300 block mb-1">
                                        Medio de pago
                                    </label>
                                    <select wire:model.live="items.{{ $idx }}.medio_pago_id"
                                        class="w-full h-10 rounded-lg border-2 border-gray-200 dark:border-gray-700
                                               dark:bg-gray-800 dark:text-white px-2 text-sm">
                                        <option value="">— Selecciona —</option>
                                        @foreach ($medios as $m)
                                            <option value="{{ $m->id }}">
                                                {{ $m->codigo ? $m->codigo . ' — ' : '' }}{{ $m->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("items.$idx.medio_pago_id")
                                        <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 gap-3">
                                    <div>
                                        <label class="text-[11px] font-medium text-gray-600 dark:text-gray-300 block mb-1">
                                            Monto a pagar
                                        </label>
                                        <input type="number" step="0.01" min="0.01" inputmode="decimal"
                                            wire:model.live="items.{{ $idx }}.monto"
                                            class="w-full h-10 text-right rounded-lg border-2 border-gray-200
                                                   dark:border-gray-700 dark:bg-gray-800
                                                   dark:text-white px-2 font-semibold text-sm">
                                        @error("items.$idx.monto")
                                            <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="text-[11px] font-medium text-gray-600 dark:text-gray-300 block mb-1">
                                            Referencia
                                        </label>
                                        <input type="text" maxlength="120"
                                            wire:model.defer="items.{{ $idx }}.referencia"
                                            class="w-full h-10 rounded-lg border-2 border-gray-200 dark:border-gray-700
                                                   dark:bg-gray-800 dark:text-white px-2 text-sm"
                                            placeholder="Comprobante o # autorización">
                                        @error("items.$idx.referencia")
                                            <div class="text-rose-600 text-xs mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Totales en mobile --}}
                        <div class="p-3 bg-gray-50 dark:bg-gray-800/30 space-y-1">
                            <div class="flex justify-between text-sm">
                                <span class="font-semibold text-gray-700 dark:text-gray-200">Total del pago:</span>
                                <span class="font-semibold">${{ number_format($sumMonto, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="font-semibold text-gray-700 dark:text-gray-200">Diferencia:</span>
                                <span class="font-semibold {{ $diff == 0 ? 'text-emerald-600' : ($diff > 0 ? 'text-amber-600' : 'text-rose-600') }}">
                                    ${{ number_format($diff, 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800/30 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <button type="button"
                            wire:click="addItem"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg
                                   bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium
                                   shadow-sm transition w-full sm:w-auto justify-center">
                            <i class="fa-solid fa-plus"></i>
                            Agregar medio de pago
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-3 sm:px-5 py-3 sm:py-4 border-t dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3
                        bg-gray-50/60 dark:bg-gray-800/40">
                <div class="text-sm text-center sm:text-left">
                    <span class="font-medium">Diferencia:</span>
                    <span class="{{ $diff == 0 ? 'text-emerald-600' : ($diff > 0 ? 'text-amber-600' : 'text-rose-600') }} font-semibold">
                        ${{ number_format($diff, 2, ',', '.') }}
                    </span>
                </div>

                <div class="flex flex-col-reverse sm:flex-row gap-2 sm:gap-2">
                    <button class="px-4 py-2 rounded-xl bg-white border dark:bg-gray-900 dark:border-gray-700 w-full sm:w-auto"
                        @click="open=false" wire:click="cerrar">
                        Cancelar
                    </button>

                    <button
                        class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed w-full sm:w-auto"
                        wire:click="guardarPago"
                        wire:loading.attr="disabled"
                        title="Guardar el pago y actualizar factura">
                        <i class="fa-solid fa-check mr-2"></i> Guardar pago
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>