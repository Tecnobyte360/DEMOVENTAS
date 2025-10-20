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
    @php
      // Datos dinámicos que dejó el cierre:
      $porTipo  = (array) data_get($turno, 'resumen.por_tipo', []);
      $porMedio = (array) data_get($turno, 'resumen.por_medio', []);
      // Formateador que evita "-$0"
      $fmt = function($v){ $v = abs((float)$v) < 0.005 ? 0 : (float)$v; return number_format($v, 0, ',', '.'); };
    @endphp

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

        {{-- Cabecera: inicio / base / total ventas --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-gray-700 dark:text-gray-200">
          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <span class="block text-xs text-gray-500 dark:text-gray-400">Inicio</span>
            <span class="font-semibold">{{ $turno->fecha_inicio }}</span>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <span class="block text-xs text-gray-500 dark:text-gray-400">Base inicial</span>
            <span class="font-semibold">${{ $fmt($turno->base_inicial) }}</span>
          </div>

          <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm">
            <span class="block text-xs text-gray-500 dark:text-gray-400">Total ventas</span>
            <span class="font-semibold text-indigo-600 dark:text-indigo-400">${{ $fmt($turno->total_ventas) }}</span>
          </div>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

        {{-- Bloque dinámico: totales por TIPO de medio de pago --}}
        <div class="space-y-2">
          <h4 class="text-xs uppercase tracking-wider text-gray-500">Por tipo de medio</h4>
          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2 text-sm">
            @forelse($porTipo as $tipo => $total)
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                <span class="text-gray-600 dark:text-gray-300">{{ ucfirst(strtolower($tipo)) }}</span>
                <span class="font-semibold">${{ $fmt($total) }}</span>
              </div>
            @empty
              <div class="col-span-full text-gray-500">Sin movimientos aún…</div>
            @endforelse
          </div>
        </div>

        {{-- Bloque dinámico: totales por MEDIO configurado --}}
        <div class="space-y-2 mt-5">
          <h4 class="text-xs uppercase tracking-wider text-gray-500">Por medio de pago</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
            @forelse(collect($porMedio)->sortBy('nombre') as $m)
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                <div class="truncate">
                  <div class="font-medium text-gray-700 dark:text-gray-200">
                    {{ $m['nombre'] ?? ($m['codigo'] ?? 'Sin nombre') }}
                  </div>
                  <div class="text-xs text-gray-400">Tipo: {{ ucfirst(strtolower($m['tipo'] ?? 'OTRO')) }}</div>
                </div>
                <div class="font-semibold whitespace-nowrap">
                  ${{ $fmt($m['total'] ?? 0) }}
                </div>
              </div>
            @empty
              <div class="text-gray-500">Aún no hay pagos por medio.</div>
            @endforelse
          </div>
        </div>

        {{-- Totales operativos (compatibles con tus columnas clásicas) --}}
        <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
          <div class="flex justify-between"><span>Ventas a crédito</span><span class="font-semibold">${{ $fmt($turno->ventas_a_credito) }}</span></div>
          <div class="flex justify-between"><span>Devoluciones</span><span class="font-semibold">${{ $fmt($turno->devoluciones) }}</span></div>
          <div class="flex justify-between"><span>Ingresos</span><span class="font-semibold">${{ $fmt($turno->ingresos_efectivo) }}</span></div>
          <div class="flex justify-between"><span>Retiros</span><span class="font-semibold text-red-600 dark:text-red-400">-${{ $fmt($turno->retiros_efectivo) }}</span></div>
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
