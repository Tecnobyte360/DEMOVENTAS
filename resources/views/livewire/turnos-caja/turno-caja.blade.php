<div class="p-8 space-y-8 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700">

  {{-- ====== Si NO hay turno abierto ====== --}}
  @if(!$turno || $turno->estado === 'cerrado')
    <div class="text-center space-y-6">
      <h2 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center justify-center gap-3">
        <i class="fa-solid fa-cash-register text-indigo-500 text-3xl"></i>
        Apertura de Turno de Caja
      </h2>

      <div class="max-w-md mx-auto space-y-4">
        <div>
          <label class="block text-sm text-gray-500 dark:text-gray-400 mb-1">Bodega (opcional)</label>
          <input type="number" wire:model="bodega_id"
                 class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
          <label class="block text-sm text-gray-500 dark:text-gray-400 mb-1">Base inicial</label>
          <input type="number" step="0.01" wire:model="base_inicial"
                 class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <button wire:click="abrir"
                class="w-full mt-4 py-3 px-6 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow-md transition-all duration-200">
          <i class="fa-solid fa-lock-open"></i> Abrir Turno
        </button>
      </div>
    </div>

  {{-- ====== Si HAY turno abierto ====== --}}
  @else
    <div class="space-y-8">

      {{-- ====== RESUMEN DEL TURNO ====== --}}
      <section class="rounded-2xl bg-gradient-to-br from-indigo-50 to-white dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700 shadow-lg p-6">
        <header class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-indigo-500"></i> Resumen del Turno
          </h2>
          <span class="px-3 py-1 text-sm rounded-full font-medium
            {{ $turno->estado === 'abierto' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
            {{ ucfirst($turno->estado) }}
          </span>
        </header>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-gray-700 dark:text-gray-200">
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <span class="block text-xs text-gray-500 dark:text-gray-400">Inicio</span>
            <span class="font-semibold">{{ $turno->fecha_inicio }}</span>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <span class="block text-xs text-gray-500 dark:text-gray-400">Base inicial</span>
            <span class="font-semibold">${{ number_format($turno->base_inicial, 0, ',', '.') }}</span>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <span class="block text-xs text-gray-500 dark:text-gray-400">Total ventas</span>
            <span class="font-semibold text-indigo-600 dark:text-indigo-400">${{ number_format($turno->total_ventas, 0, ',', '.') }}</span>
          </div>

          <div class="col-span-full border-t border-gray-200 dark:border-gray-700 my-2"></div>

          <div class="flex justify-between"><span>Efectivo</span><span>${{ number_format($turno->ventas_efectivo,0,',','.') }}</span></div>
          <div class="flex justify-between"><span>Débito</span><span>${{ number_format($turno->ventas_debito,0,',','.') }}</span></div>
          <div class="flex justify-between"><span>Crédito</span><span>${{ number_format($turno->ventas_credito_tarjeta,0,',','.') }}</span></div>
          <div class="flex justify-between"><span>Transferencias</span><span>${{ number_format($turno->ventas_transferencias,0,',','.') }}</span></div>
          <div class="flex justify-between"><span>Ventas a crédito</span><span>${{ number_format($turno->ventas_a_credito,0,',','.') }}</span></div>
          <div class="flex justify-between"><span>Devoluciones</span><span>${{ number_format($turno->devoluciones,0,',','.') }}</span></div>
          <div class="flex justify-between"><span>Ingresos</span><span>${{ number_format($turno->ingresos_efectivo,0,',','.') }}</span></div>
          <div class="flex justify-between"><span>Retiros</span><span class="text-red-600 dark:text-red-400">-${{ number_format($turno->retiros_efectivo,0,',','.') }}</span></div>
        </div>
      </section>

      {{-- ====== MOVIMIENTOS MANUALES ====== --}}
      <section class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
          <i class="fa-solid fa-wallet text-indigo-500"></i> Registrar movimiento
        </h3>

        <div class="grid md:grid-cols-4 gap-4 items-end">
          <div>
            <label class="text-sm text-gray-500 dark:text-gray-400">Tipo</label>
            <select wire:model="tipo_mov"
                    class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <option>INGRESO</option>
              <option>RETIRO</option>
              <option>DEVOLUCION</option>
            </select>
          </div>

          <div>
            <label class="text-sm text-gray-500 dark:text-gray-400">Monto</label>
            <input type="number" step="0.01" wire:model="monto"
                   class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          </div>

          <div class="md:col-span-2">
            <label class="text-sm text-gray-500 dark:text-gray-400">Motivo</label>
            <input type="text" wire:model="motivo"
                   class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
          </div>

          <button wire:click="agregarMovimiento"
                  class="md:col-span-2 py-2.5 rounded-xl bg-indigo-500 hover:bg-indigo-600 text-white font-semibold transition-all duration-200 shadow">
            <i class="fa-solid fa-plus"></i> Agregar movimiento
          </button>

          <button wire:click="cerrar"
                  class="md:col-span-2 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold transition-all duration-200 shadow">
            <i class="fa-solid fa-lock"></i> Cerrar turno
          </button>
        </div>
      </section>
    </div>
  @endif
</div>
