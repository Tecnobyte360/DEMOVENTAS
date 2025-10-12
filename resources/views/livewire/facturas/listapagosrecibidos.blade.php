{{-- resources/views/livewire/facturas/listapagosrecibidos.blade.php --}}
@assets
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
@endassets

<div class="space-y-5 md:space-y-6">

  {{-- Encabezado / Totales --}}
  <section class="rounded-2xl md:rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60 backdrop-blur p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div class="space-y-1">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
          <i class="fa-solid fa-wallet"></i>
          Pagos recibidos
        </h2>
        <p class="text-sm text-gray-600 dark:text-gray-300">
          Revisa los pagos aplicados a facturas. Filtra por rango de fechas, medio, método, montos y búsqueda libre.
        </p>
      </div>

      <div class="grid grid-cols-2 gap-2 md:flex md:items-end md:gap-3">
        <div class="px-4 py-2 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
          <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Total página</div>
          <div class="text-lg font-semibold">$ {{ number_format($totalPagina, 2, ',', '.') }}</div>
        </div>
        <div class="px-4 py-2 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-100">
          <div class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Total general</div>
          <div class="text-lg font-semibold">$ {{ number_format($totalGeneral, 2, ',', '.') }}</div>
        </div>
      </div>
    </div>
  </section>

  {{-- Filtros --}}
  <section class="rounded-2xl md:rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60 backdrop-blur p-4 md:p-6">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-3">

      <div class="md:col-span-2">
        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Buscar</label>
        <input type="text" wire:model.debounce.500ms="buscar" placeholder="Cliente, referencia, # factura, método..."
               class="w-full px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Desde</label>
        <input type="date" wire:model.lazy="fecha_inicio"
               class="w-full px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Hasta</label>
        <input type="date" wire:model.lazy="fecha_fin"
               class="w-full px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Medio de pago</label>
        <select wire:model.lazy="medio_pago_id"
                class="w-full px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
          <option value="">Todos</option>
          @foreach($mediosPago as $mp)
            <option value="{{ $mp['id'] }}">{{ $mp['nombre'] }}{{ $mp['codigo'] ? ' — '.$mp['codigo'] : '' }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Método</label>
        <input type="text" wire:model.lazy="metodo" placeholder="p.ej. transferencia, efectivo..."
               class="w-full px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
      </div>

      <div class="grid grid-cols-2 gap-2 md:col-span-2">
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Monto mín.</label>
          <input type="number" step="0.01" wire:model.lazy="monto_min"
                 class="w-full px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Monto máx.</label>
          <input type="number" step="0.01" wire:model.lazy="monto_max"
                 class="w-full px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
        </div>
      </div>

      <div class="flex items-end gap-2 md:col-span-2">
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Por página</label>
          <select wire:model="porPagina"
                  class="px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option>10</option>
            <option>15</option>
            <option>25</option>
            <option>50</option>
            <option>100</option>
          </select>
        </div>

        <button wire:click="$refresh"
                class="ml-auto inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-md">
          <i class="fa-solid fa-magnifying-glass"></i>
          Buscar
        </button>

        <button wire:click="$set('buscar','');$set('medio_pago_id',null);$set('metodo',null);$set('monto_min',null);$set('monto_max',null)"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
          <i class="fa-solid fa-eraser"></i>
          Limpiar
        </button>
      </div>

    </div>
  </section>

  {{-- Tabla Desktop --}}
  <section class="hidden md:block rounded-2xl md:rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60 backdrop-blur">
    <div class="overflow-x-auto rounded-2xl">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-700 dark:text-gray-200">
          <tr>
            <th class="px-4 py-3 text-left cursor-pointer" wire:click="ordenarPor('fecha')">
              Fecha
              @if($ordenarPor==='fecha') <i class="fa-solid fa-arrow-{{ $direccion==='asc'?'up':'down' }}"></i> @endif
            </th>
            <th class="px-4 py-3 text-left">Factura</th>
            <th class="px-4 py-3 text-left">Cliente</th>
            <th class="px-4 py-3 text-left cursor-pointer" wire:click="ordenarPor('metodo')">
              Método
              @if($ordenarPor==='metodo') <i class="fa-solid fa-arrow-{{ $direccion==='asc'?'up':'down' }}"></i> @endif
            </th>
            <th class="px-4 py-3 text-left">Medio</th>
            <th class="px-4 py-3 text-left">Referencia</th>
            <th class="px-4 py-3 text-right cursor-pointer" wire:click="ordenarPor('monto')">
              Monto
              @if($ordenarPor==='monto') <i class="fa-solid fa-arrow-{{ $direccion==='asc'?'up':'down' }}"></i> @endif
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-800 dark:text-gray-100">
          @forelse($pagos as $p)
            @php
              $fact = $p->factura;
              $num  = $fact?->numero ? ($fact?->numero_formateado ?? ($fact?->prefijo ? ($fact?->prefijo.'-'.str_pad($fact->numero, $fact?->serie?->longitud ?? 6, '0', STR_PAD_LEFT)) : $fact->numero)) : '—';
              $cli  = $fact?->cliente?->razon_social ?: '—';
              $medio= $p->medioPago?->nombre ?? ($p->medioPago?->codigo ?? '—');
            @endphp
            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
              <td class="px-4 py-2 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($p->fecha)->format('Y-m-d') }}</td>
              <td class="px-4 py-2">{{ $num }}</td>
              <td class="px-4 py-2">{{ $cli }}</td>
              <td class="px-4 py-2">{{ $p->metodo ?: '—' }}</td>
              <td class="px-4 py-2">{{ $medio }}</td>
              <td class="px-4 py-2">{{ $p->referencia ?: '—' }}</td>
              <td class="px-4 py-2 text-right font-semibold">$ {{ number_format($p->monto, 2, ',', '.') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No hay pagos para los filtros seleccionados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
      <div class="text-sm text-gray-600 dark:text-gray-300">
        Mostrando <span class="font-semibold">{{ $pagos->firstItem() }}</span> - <span class="font-semibold">{{ $pagos->lastItem() }}</span> de <span class="font-semibold">{{ $pagos->total() }}</span>
      </div>
      <div>
        {{ $pagos->onEachSide(1)->links() }}
      </div>
    </div>
  </section>

  {{-- Lista en tarjetas (móvil) --}}
  <section class="md:hidden space-y-3">
    @forelse($pagos as $p)
      @php
        $fact = $p->factura;
        $num  = $fact?->numero ? ($fact?->numero_formateado ?? ($fact?->prefijo ? ($fact?->prefijo.'-'.str_pad($fact->numero, $fact?->serie?->longitud ?? 6, '0', STR_PAD_LEFT)) : $fact->numero)) : '—';
        $cli  = $fact?->cliente?->razon_social ?: '—';
        $medio= $p->medioPago?->nombre ?? ($p->medioPago?->codigo ?? '—');
      @endphp
      <article class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60 backdrop-blur p-4 space-y-1">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($p->fecha)->format('Y-m-d') }}</div>
          <div class="text-base font-semibold">$ {{ number_format($p->monto, 2, ',', '.') }}</div>
        </div>
        <div class="text-sm text-gray-800 dark:text-gray-100"><span class="font-semibold">Factura:</span> {{ $num }}</div>
        <div class="text-sm text-gray-800 dark:text-gray-100"><span class="font-semibold">Cliente:</span> {{ $cli }}</div>
        <div class="text-sm text-gray-800 dark:text-gray-100"><span class="font-semibold">Medio:</span> {{ $medio }}</div>
        <div class="text-sm text-gray-800 dark:text-gray-100"><span class="font-semibold">Método:</span> {{ $p->metodo ?: '—' }}</div>
        <div class="text-sm text-gray-800 dark:text-gray-100"><span class="font-semibold">Referencia:</span> {{ $p->referencia ?: '—' }}</div>
      </article>
    @empty
      <div class="text-center text-gray-500 dark:text-gray-400">No hay pagos para los filtros seleccionados.</div>
    @endforelse

    <div>
      {{ $pagos->onEachSide(1)->links() }}
    </div>
  </section>

  {{-- Loading --}}
  <div wire:loading.delay class="fixed inset-x-0 bottom-4 mx-auto w-fit px-4 py-2 rounded-full bg-indigo-600 text-white shadow-lg">
    <i class="fa-solid fa-spinner animate-spin mr-2"></i> Cargando...
  </div>
</div>
