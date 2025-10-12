{{-- resources/views/livewire/facturas/pagos-factura.blade.php --}}
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
  {{-- Overlay --}}
  <div x-show="open" class="fixed inset-0 z-40 bg-black/40"></div>

  {{-- Modal --}}
  <div x-show="open" class="fixed inset-0 z-50 grid place-items-center p-4">
    <div class="w-full max-w-3xl rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border dark:border-gray-700 overflow-hidden">

      {{-- Header --}}
      <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between 
                  bg-gradient-to-r from-gray-100 via-gray-200 to-gray-300 
                  dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 text-gray-800 dark:text-white rounded-t-2xl">
        <h3 class="text-lg font-semibold flex items-center gap-2">
          <i class="fa-solid fa-cash-register"></i>
          Registrar pago de factura
        </h3>
        <button class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition" 
                @click="open=false" wire:click="cerrar" title="Cerrar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      {{-- Cuerpo --}}
      <div class="p-5 space-y-5">

        {{-- 🧾 Selector de factura (solo si no hay factura preseleccionada) --}}
        @if(!$facturaId)
          <div>
            <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">
              Seleccionar factura
            </label>
            <select wire:model="facturaId"
                    class="w-full h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700 
                           dark:bg-gray-800 dark:text-white px-3 focus:ring-2 focus:ring-indigo-500">
              <option value="">— Selecciona una factura —</option>
              @foreach(\App\Models\Factura\Factura::where('saldo', '>', 0)->orderByDesc('id')->get(['id','numero','total','saldo']) as $f)
                <option value="{{ $f->id }}">
                  #{{ $f->numero }} — Saldo: ${{ number_format($f->saldo, 0, ',', '.') }}
                </option>
              @endforeach
            </select>
            @error('facturaId') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
          </div>
        @endif

        {{-- 🧮 Resumen de montos --}}
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-3 bg-gray-50/50 dark:bg-gray-800/40">
          <div class="flex flex-wrap gap-3 text-sm">
            @if($facturaId)
              <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">
                Factura #{{ $facturaId }}
              </span>
            @else
              <span class="text-sm text-gray-500 dark:text-gray-400">
                Selecciona una factura para ver su saldo
              </span>
            @endif
          </div>

          <div class="mt-2 grid grid-cols-3 gap-3 text-sm">
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

        {{-- 📅 Fecha / Notas --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">Fecha</label>
            <input type="date"
                   class="w-full h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700 
                          dark:bg-gray-800 dark:text-white px-3"
                   wire:model.live="fecha">
            @error('fecha') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="md:col-span-2">
            <label class="text-xs font-semibold uppercase text-gray-600 dark:text-gray-300 mb-1 block">Notas</label>
            <input type="text"
                   class="w-full h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700 
                          dark:bg-gray-800 dark:text-white px-3"
                   wire:model.defer="notas"
                   placeholder="Observaciones del pago (opcional)">
            @error('notas') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
          </div>
        </div>

        {{-- 💳 Distribución por medio de pago --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div class="bg-gray-100 dark:bg-gray-800 px-3 py-2 text-[12px] font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
            Distribución por medio de pago
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left bg-gray-50 dark:bg-gray-800/50">
                  <th class="p-3">Medio</th>
                  <th class="p-3 w-32 text-center">% </th>
                  <th class="p-3 w-40 text-right">Monto</th>
                  <th class="p-3 w-48">Referencia</th>
                  <th class="p-3 w-12 text-center">—</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($items as $idx => $row)
                  <tr wire:key="pago-row-{{ $idx }}">
                    {{-- Medio --}}
                    <td class="p-3">
                      <select wire:model.live="items.{{ $idx }}.medio_pago_id"
                              class="w-full h-10 rounded-lg border-2 border-gray-200 dark:border-gray-700 
                                     dark:bg-gray-800 dark:text-white px-2">
                        <option value="">— Selecciona —</option>
                        @foreach($medios as $m)
                          <option value="{{ $m->id }}">
                            {{ $m->codigo ?? '' }}{{ $m->codigo ? ' — ' : '' }}{{ $m->nombre }}
                          </option>
                        @endforeach
                      </select>
                      @error('items.'.$idx.'.medio_pago_id')
                        <div class="text-rose-600 text-[11px] mt-1">{{ $message }}</div>
                      @enderror
                    </td>

                    {{-- Porcentaje --}}
                    <td class="p-3">
                      <input type="number" step="0.01" min="0" max="100"
                             wire:model.live="items.{{ $idx }}.porcentaje"
                             class="w-full h-10 text-center rounded-lg border-2 border-gray-200 
                                    dark:border-gray-700 dark:bg-gray-800 dark:text-white px-2">
                    </td>

                    {{-- Monto --}}
                    <td class="p-3">
                      <input type="number" step="0.01" min="0.01"
                             wire:model.live="items.{{ $idx }}.monto"
                             class="w-full h-10 text-right rounded-lg border-2 border-gray-200 
                                    dark:border-gray-700 dark:bg-gray-800 dark:text-white px-2">
                    </td>

                    {{-- Referencia --}}
                    <td class="p-3">
                      <input type="text" maxlength="120"
                             wire:model.defer="items.{{ $idx }}.referencia"
                             class="w-full h-10 rounded-lg border-2 border-gray-200 
                                    dark:border-gray-700 dark:bg-gray-800 dark:text-white px-2"
                             placeholder="Comprobante o #autorización">
                    </td>

                    {{-- Quitar --}}
                    <td class="p-3 text-center">
                      <button type="button" wire:click="removeItem({{ $idx }})"
                              class="px-2 py-1 rounded-lg bg-rose-100 text-rose-700 hover:bg-rose-200"
                              title="Quitar fila">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>

              <tfoot class="bg-gray-50 dark:bg-gray-800/30">
                <tr>
                  <td class="p-3">
                    <button type="button" wire:click="addItem"
                            class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs">
                      <i class="fa-solid fa-plus mr-1"></i> Añadir medio
                    </button>
                  </td>
                  <td class="p-3 text-center font-semibold">{{ number_format($sumPct, 2, ',', '.') }}%</td>
                  <td class="p-3 text-right font-semibold">${{ number_format($sumMonto, 2, ',', '.') }}</td>
                  <td class="p-3 text-right font-semibold" colspan="2">
                    <span class="{{ $diff == 0 ? 'text-emerald-600' : ($diff > 0 ? 'text-amber-600' : 'text-rose-600') }}">
                      Diff: ${{ number_format($diff, 2, ',', '.') }}
                    </span>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      {{-- Footer --}}
      <div class="px-5 py-4 border-t dark:border-gray-700 flex items-center justify-between gap-2 
                  bg-gray-50/60 dark:bg-gray-800/40">
        <div class="text-sm">
          <span class="font-medium">Diferencia:</span>
          <span class="{{ $diff == 0 ? 'text-emerald-600' : ($diff > 0 ? 'text-amber-600' : 'text-rose-600') }}">
            ${{ number_format($diff, 2, ',', '.') }}
          </span>
        </div>

        <div class="flex gap-2">
          <button class="px-4 py-2 rounded-xl bg-white border dark:bg-gray-900 dark:border-gray-700"
                  @click="open=false" wire:click="cerrar">
            Cancelar
          </button>

          <button class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 
                         disabled:opacity-50 disabled:cursor-not-allowed"
                  wire:click="guardarPago"
                  :disabled="$wire.diff !== 0 || !$wire.facturaId"
                  :title="$wire.diff !== 0 ? 'La suma debe igualar el saldo' : (!$wire.facturaId ? 'Selecciona una factura' : 'Guardar pago')">
            <i class="fa-solid fa-check mr-2"></i> Guardar pago
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
