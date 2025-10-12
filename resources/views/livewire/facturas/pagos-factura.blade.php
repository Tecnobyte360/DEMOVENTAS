{{-- resources/views/livewire/facturas/pagos-factura.blade.php --}}
@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Evita conflicto entre Alpine y Livewire
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div x-data="{ open: @entangle('show') }" x-cloak>
  {{-- Overlay --}}
  <div x-show="open" class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm transition-opacity"></div>

  {{-- Modal --}}
  <div x-show="open" class="fixed inset-0 z-50 grid place-items-center p-4 transition">
    <div
      class="w-full max-w-3xl rounded-3xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transform transition-all duration-300 scale-100">

      {{-- Header --}}
      <div
        class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between
               bg-gradient-to-r from-gray-100 via-gray-200 to-gray-300
               dark:from-gray-800 dark:via-gray-900 dark:to-gray-800
               text-gray-800 dark:text-white rounded-t-3xl shadow-sm">
        <h3 class="text-lg font-semibold flex items-center gap-2">
          <i class="fa-solid fa-cash-register text-gray-700 dark:text-gray-300"></i>
          Registrar pago de factura
        </h3>
        <button class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition"
                @click="open=false" wire:click="cerrar" title="Cerrar">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>

      {{-- Body --}}
      <div class="p-6 space-y-6">
        {{-- ====== Resumen ====== --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50/60 dark:bg-gray-800/40 shadow-inner">
          <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
            @if($facturaId)
              <span class="px-3 py-1 rounded-full bg-gray-200 dark:bg-gray-700 font-semibold">
                Factura #{{ $facturaId }}
              </span>
            @endif
          </div>
          <div class="mt-3 grid grid-cols-3 gap-3 text-sm">
            <div class="flex justify-between">
              <span>Total</span>
              <strong>${{ number_format($fac_total, 2, ',', '.') }}</strong>
            </div>
            <div class="flex justify-between">
              <span>Pagado</span>
              <strong>${{ number_format($fac_pagado, 2, ',', '.') }}</strong>
            </div>
            <div class="flex justify-between">
              <span>Saldo</span>
              <strong class="text-emerald-600">${{ number_format($fac_saldo, 2, ',', '.') }}</strong>
            </div>
          </div>
        </div>

        {{-- ====== Fecha / Notas ====== --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          {{-- Fecha --}}
          <div>
            <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">Fecha</label>
            <input type="date"
                   class="w-full h-11 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3 focus:outline-none focus:ring-4 focus:ring-gray-300/40"
                   wire:model.live="fecha">
            @error('fecha') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
          </div>

          {{-- Notas --}}
          <div class="md:col-span-2">
            <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">Notas</label>
            <input type="text"
                   class="w-full h-11 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3 focus:outline-none focus:ring-4 focus:ring-gray-300/40"
                   wire:model.defer="notas"
                   placeholder="Observaciones del pago (opcional)">
            @error('notas') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- ====== Distribución por medio de pago ====== --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-inner">
          <div class="bg-gray-100 dark:bg-gray-800 px-4 py-2 text-[12px] font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
            Distribución por medio de pago
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 uppercase text-xs">
                  <th class="p-3 text-left">Medio</th>
                  <th class="p-3 text-center w-24">% </th>
                  <th class="p-3 text-right w-32">Monto</th>
                  <th class="p-3 text-left w-48">Referencia</th>
                  <th class="p-3 text-center w-12">—</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($items as $idx => $row)
                  <tr wire:key="pago-row-{{ $idx }}" class="hover:bg-gray-100/50 dark:hover:bg-gray-800/50 transition">
                    {{-- Medio --}}
                    <td class="p-3">
                      <select wire:model.live="items.{{ $idx }}.medio_pago_id"
                              class="w-full h-10 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-2">
                        <option value="">— Selecciona —</option>
                        @foreach($medios as $m)
                          <option value="{{ $m->id }}">{{ $m->codigo ?? '' }}{{ $m->codigo ? ' — ' : '' }}{{ $m->nombre }}</option>
                        @endforeach
                      </select>
                      @error('items.'.$idx.'.medio_pago_id') <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div> @enderror
                    </td>

                    {{-- Porcentaje --}}
                    <td class="p-3">
                      <input type="number" step="0.01" min="0" max="100"
                             wire:model.live="items.{{ $idx }}.porcentaje"
                             class="w-full h-10 text-center rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
                      @error('items.'.$idx.'.porcentaje') <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div> @enderror
                    </td>

                    {{-- Monto --}}
                    <td class="p-3">
                      <input type="number" step="0.01" min="0.01"
                             wire:model.live="items.{{ $idx }}.monto"
                             class="w-full h-10 text-right rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
                      @error('items.'.$idx.'.monto') <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div> @enderror
                    </td>

                    {{-- Referencia --}}
                    <td class="p-3">
                      <input type="text" maxlength="120"
                             wire:model.defer="items.{{ $idx }}.referencia"
                             placeholder="Comprobante, #autorización…"
                             class="w-full h-10 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-2">
                    </td>

                    {{-- Quitar --}}
                    <td class="p-3 text-center">
                      <button type="button"
                              wire:click="removeItem({{ $idx }})"
                              class="px-2 py-1 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-900/40 dark:text-rose-300">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>

              {{-- Totales --}}
              <tfoot class="bg-gray-50 dark:bg-gray-800/30">
                <tr>
                  <td class="p-3">
                    <button type="button"
                            wire:click="addItem"
                            class="px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs shadow">
                      <i class="fa-solid fa-plus mr-1"></i> Añadir medio
                    </button>
                  </td>
                  <td class="p-3 text-center font-semibold">{{ number_format($sumPct, 2, ',', '.') }}%</td>
                  <td class="p-3 text-right font-semibold">${{ number_format($sumMonto, 2, ',', '.') }}</td>
                  <td class="p-3 text-right font-semibold" colspan="2">
                    <span class="{{ $diff == 0 ? 'text-emerald-600' : ($diff > 0 ? 'text-amber-600' : 'text-rose-600') }}">
                      Diff: ${{ number_format($diff, 2, ',', '.') }}
                    </span>
                    @error('items') <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div> @enderror
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      {{-- Footer --}}
      <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/40 flex items-center justify-between rounded-b-3xl">
        <div class="text-sm">
          <span class="font-medium">Diferencia:</span>
          <span class="{{ $diff == 0 ? 'text-emerald-600' : ($diff > 0 ? 'text-amber-600' : 'text-rose-600') }}">
            ${{ number_format($diff, 2, ',', '.') }}
          </span>
        </div>

        <div class="flex gap-3">
          <button @click="open=false" wire:click="cerrar"
                  class="px-4 py-2 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800">
            Cancelar
          </button>

          <button wire:click="guardarPago"
                  :disabled="$wire.diff !== 0"
                  :title="$wire.diff !== 0 ? 'La suma debe igualar el saldo' : 'Guardar pago'"
                  class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-50 disabled:cursor-not-allowed shadow">
            <i class="fa-solid fa-check mr-2"></i> Guardar pago
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
