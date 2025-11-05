{{-- resources/views/livewire/turnos-caja/turno-caja.blade.php --}}
@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
      [x-cloak]{display:none!important}
      .chip{ @apply px-2 py-0.5 rounded-full text-xs font-medium; }
      .badge{ @apply px-2 py-0.5 rounded-full text-xs font-semibold; }
      .tab-btn{ @apply px-3 py-2 rounded-xl text-sm font-medium transition; }
      .tab-active{ @apply bg-indigo-600 text-white shadow; }
      .tab-idle{ @apply bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700; }
      .card{ @apply bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow; }
      .title{ @apply text-gray-900 dark:text-white font-semibold; }
      .muted{ @apply text-gray-500 dark:text-gray-400; }
      .thead{ @apply bg-gray-50 dark:bg-gray-900/40; }
      .th{ @apply text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 py-2; }
      .td{ @apply py-2; }
    </style>
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

@php
  $fmt = fn($v) => number_format(max(0,(float)$v), 0, ',', '.');
@endphp

<div class="p-6 md:p-8 space-y-8 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700"
     wire:poll.8s
     x-data="{ tab: 'resumen' }">

  {{-- ============================ SIN TURNO ============================ --}}
  @if(!$turno || $turno->estado === 'cerrado')
    <div class="max-w-2xl mx-auto text-center space-y-6">
      <div class="flex items-center justify-center gap-3">
        <i class="fa-solid fa-cash-register text-3xl text-indigo-600"></i>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Apertura de Turno de Caja</h2>
      </div>

      <div class="card p-6 text-left">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm muted mb-1 block">Bodega (opcional)</label>
            <input type="number" wire:model.defer="bodega_id"
              class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            @error('bodega_id') <div class="text-xs text-rose-600 mt-1">{{ $message }}</div> @enderror
          </div>
          <div>
            <label class="text-sm muted mb-1 block">Base inicial</label>
            <input type="number" step="0.01" wire:model.defer="base_inicial"
              class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            @error('base_inicial') <div class="text-xs text-rose-600 mt-1">{{ $message }}</div> @enderror
          </div>
        </div>

        <div class="mt-5 flex items-center justify-center gap-3">
          <button wire:click="abrir"
            class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow transition">
            <i class="fa-solid fa-lock-open mr-2"></i> Abrir turno
          </button>
        </div>

        @if (session()->has('message'))
          <div class="text-sm text-emerald-600 mt-4 text-center">{{ session('message') }}</div>
        @endif
        @if (session()->has('error'))
          <div class="text-sm text-rose-600 mt-4 text-center">{{ session('error') }}</div>
        @endif
      </div>

      <p class="muted text-sm">Cuando abras el turno, todos los <strong>pagos</strong> que registres en facturas/notas quedarán vinculados a este turno.</p>
    </div>

  {{-- ============================ CON TURNO ============================ --}}
  @else
    {{-- Header del turno --}}
    <section class="card p-5">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="space-y-1">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-cash-register text-indigo-600"></i>
            Turno de Caja #{{ $turno->id }}
          </h2>
          <div class="text-sm muted">
            Inicio: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $turno->fecha_inicio }}</span>
            @if($turno->bodega) · Bodega: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $turno->bodega->nombre }}</span>@endif
          </div>
        </div>

        <div class="flex items-center gap-2">
          <span class="badge {{ $turno->estado==='abierto' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
            {{ ucfirst($turno->estado) }}
          </span>

          <button wire:click="cerrar"
            class="px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow transition">
            <i class="fa-solid fa-lock mr-2"></i> Cerrar turno
          </button>
        </div>
      </div>

      {{-- Tabs --}}
      <div class="mt-4 flex flex-wrap gap-2">
        <button @click="tab='resumen'" :class="tab==='resumen' ? 'tab-btn tab-active' : 'tab-btn tab-idle'">
          <i class="fa-solid fa-chart-pie mr-2"></i> Resumen
        </button>
        <button @click="tab='pagos'" :class="tab==='pagos' ? 'tab-btn tab-active' : 'tab-btn tab-idle'">
          <i class="fa-solid fa-money-bill-wave mr-2"></i> Pagos
        </button>
        <button @click="tab='movs'" :class="tab==='movs' ? 'tab-btn tab-active' : 'tab-btn tab-idle'">
          <i class="fa-solid fa-wallet mr-2"></i> Movimientos
        </button>
        <button @click="tab='cierre'" :class="tab==='cierre' ? 'tab-btn tab-active' : 'tab-btn tab-idle'">
          <i class="fa-solid fa-calculator mr-2"></i> Cierre
        </button>
      </div>
    </section>

    {{-- ===== TAB: RESUMEN ===== --}}
    <section x-show="tab==='resumen'" x-cloak class="space-y-6">
      {{-- Cards principales --}}
      <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4">
          <div class="muted text-xs">Base inicial</div>
          <div class="text-2xl font-bold">${{ $fmt($resumen['base_inicial'] ?? 0) }}</div>
        </div>
        <div class="card p-4">
          <div class="muted text-xs">Total cobrado</div>
          <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
            ${{ $fmt($resumen['total_ventas'] ?? 0) }}
          </div>
        </div>
        <div class="card p-4">
          <div class="muted text-xs">Ingresos caja chica</div>
          <div class="text-2xl font-bold">${{ $fmt($resumen['ingresos'] ?? 0) }}</div>
        </div>
        <div class="card p-4">
          <div class="muted text-xs">Retiros</div>
          <div class="text-2xl font-bold text-rose-600">-${{ $fmt($resumen['retiros'] ?? 0) }}</div>
        </div>
      </div>

      {{-- Por Tipo de Medio --}}
      <div class="grid md:grid-cols-2 gap-4">
        <div class="card p-4">
          <h3 class="title mb-2 flex items-center gap-2">
            <i class="fa-solid fa-layer-group text-indigo-500"></i> Por tipo de medio
          </h3>
          <div class="grid grid-cols-2 gap-2 text-sm">
            @forelse((array)$porTipo as $tipo => $total)
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                <span class="muted capitalize">{{ strtolower($tipo) }}</span>
                <span class="font-semibold">${{ $fmt($total) }}</span>
              </div>
            @empty
              <div class="muted">Sin pagos aún…</div>
            @endforelse
          </div>
        </div>

        {{-- Por medio --}}
        <div class="card p-4">
          <h3 class="title mb-2 flex items-center gap-2">
            <i class="fa-solid fa-credit-card text-indigo-500"></i> Por medio de pago
          </h3>
          <div class="space-y-2 text-sm">
            @forelse((array)$porMedio as $m)
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                <div>
                  <div class="font-medium text-gray-800 dark:text-gray-100">{{ $m['nombre'] ?? $m['codigo'] ?? '—' }}</div>
                  <div class="muted text-xs">Tipo: {{ ucfirst(strtolower($m['tipo'] ?? 'OTRO')) }}</div>
                </div>
                <div class="font-semibold">${{ $fmt($m['total'] ?? 0) }}</div>
              </div>
            @empty
              <div class="muted">Sin medios registrados…</div>
            @endforelse
          </div>
        </div>
      </div>
    </section>

    {{-- ===== TAB: PAGOS ===== --}}
    <section x-show="tab==='pagos'" x-cloak class="space-y-4">
      <div class="card p-4 overflow-x-auto">
        <header class="flex items-center justify-between mb-3">
          <h3 class="title flex items-center gap-2">
            <i class="fa-solid fa-money-bill"></i> Pagos del turno
          </h3>
          <div class="muted text-sm">Total: ${{ $fmt($resumen['total_ventas'] ?? 0) }}</div>
        </header>

        <table class="w-full text-sm">
          <thead class="thead">
            <tr>
              <th class="th">Fecha</th>
              <th class="th">Documento</th>
              <th class="th">Cliente</th>
              <th class="th text-right">Monto</th>
              <th class="th">Medio</th>
              <th class="th">Tipo</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($turno->pagos()->with(['factura.cliente'])->latest()->take(200)->get() as $p)
              <tr>
                <td class="td muted">{{ $p->created_at }}</td>
                <td class="td">
                  <span class="font-medium">{{ $p->factura?->prefijo }}-{{ str_pad($p->factura?->numero ?? 0, 6, '0', STR_PAD_LEFT) }}</span>
                </td>
                <td class="td">
                  <span class="text-gray-800 dark:text-gray-100">{{ $p->factura?->cliente?->razon_social ?? '—' }}</span>
                </td>
                <td class="td text-right font-semibold">${{ $fmt($p->monto) }}</td>
                <td class="td">
                  <span class="chip bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $p->medio_codigo ?? '—' }}</span>
                </td>
                <td class="td">
                  <span class="chip bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 capitalize">{{ strtolower($p->medio_tipo ?? '—') }}</span>
                </td>
              </tr>
            @empty
              <tr><td class="td muted" colspan="6">Sin pagos aún…</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    {{-- ===== TAB: MOVIMIENTOS ===== --}}
    <section x-show="tab==='movs'" x-cloak class="space-y-4">
      {{-- Form registrar movimiento --}}
      <div class="card p-4">
        <h3 class="title mb-3 flex items-center gap-2">
          <i class="fa-solid fa-wallet text-indigo-500"></i> Registrar movimiento
        </h3>

        <div class="grid md:grid-cols-4 gap-3 items-end">
          <div>
            <label class="text-sm muted mb-1 block">Tipo</label>
            <select wire:model.defer="tipo_mov"
              class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="INGRESO">INGRESO</option>
              <option value="RETIRO">RETIRO</option>
              <option value="DEVOLUCION">DEVOLUCIÓN</option>
            </select>
            @error('tipo_mov') <div class="text-xs text-rose-600 mt-1">{{ $message }}</div> @enderror
          </div>
          <div>
            <label class="text-sm muted mb-1 block">Monto</label>
            <input type="number" step="0.01" wire:model.defer="monto"
              class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            @error('monto') <div class="text-xs text-rose-600 mt-1">{{ $message }}</div> @enderror
          </div>
          <div class="md:col-span-2">
            <label class="text-sm muted mb-1 block">Motivo</label>
            <input type="text" wire:model.defer="motivo"
              class="w-full rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            @error('motivo') <div class="text-xs text-rose-600 mt-1">{{ $message }}</div> @enderror
          </div>

          <div class="md:col-span-2 flex gap-2">
            <button wire:click="agregarMovimiento"
              class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow">
              <i class="fa-solid fa-plus mr-2"></i> Agregar
            </button>
            <button wire:click="cerrar"
              class="flex-1 py-2.5 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow">
              <i class="fa-solid fa-lock mr-2"></i> Cerrar turno
            </button>
          </div>
        </div>

        @if (session()->has('message'))
          <div class="text-sm text-emerald-600 mt-3">{{ session('message') }}</div>
        @endif
        @if (session()->has('error'))
          <div class="text-sm text-rose-600 mt-3">{{ session('error') }}</div>
        @endif
      </div>

      {{-- Tabla de movimientos --}}
      <div class="card p-4 overflow-x-auto">
        <h3 class="title mb-3 flex items-center gap-2">
          <i class="fa-solid fa-list"></i> Movimientos de caja
        </h3>

        <table class="w-full text-sm">
          <thead class="thead">
          <tr>
            <th class="th">Fecha</th>
            <th class="th">Tipo</th>
            <th class="th">Motivo</th>
            <th class="th text-right">Monto</th>
            <th class="th">Usuario</th>
          </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          {{-- @forelse($turno->movimientos()->with('user')->latest()->take(200)->get() as $m)
            <tr>
              <td class="td muted">{{ $m->created_at }}</td>
              <td class="td">
                @php
                  $tone = match($m->tipo){
                    'INGRESO' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                    'RETIRO' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                    default => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
                  };
                @endphp
                <span class="chip {{ $tone }}">{{ $m->tipo }}</span>
              </td>
              <td class="td text-gray-700 dark:text-gray-200">{{ $m->motivo ?? '—' }}</td>
              <td class="td text-right font-semibold ${}">${{ $fmt($m->monto) }}</td>
              <td class="td muted">{{ $m->user?->name ?? '—' }}</td>
            </tr>
          @empty
            <tr><td class="td muted" colspan="5">Sin movimientos…</td></tr>
          @endforelse --}}
          </tbody>
        </table>
      </div>
    </section>

    {{-- ===== TAB: CIERRE ===== --}}
    <section x-show="tab==='cierre'" x-cloak class="space-y-4">
      <div class="grid md:grid-cols-3 gap-4">
        {{-- Resumen derecho/izq --}}
        <div class="md:col-span-2 card p-4">
          <h3 class="title mb-3 flex items-center gap-2">
            <i class="fa-solid fa-scale-balanced text-indigo-500"></i> Resumen de cierre
          </h3>

          <div class="grid sm:grid-cols-2 gap-3 text-sm">
            <div class="flex justify-between"><span class="muted">Base inicial</span><span class="font-semibold">${{ $fmt($resumen['base_inicial'] ?? 0) }}</span></div>
            <div class="flex justify-between"><span class="muted">Cobrado (todos los medios)</span><span class="font-semibold">${{ $fmt($resumen['total_ventas'] ?? 0) }}</span></div>
            <div class="flex justify-between"><span class="muted">Ingresos</span><span class="font-semibold">${{ $fmt($resumen['ingresos'] ?? 0) }}</span></div>
            <div class="flex justify-between"><span class="muted">Retiros</span><span class="font-semibold text-rose-600">-${{ $fmt($resumen['retiros'] ?? 0) }}</span></div>
            <div class="flex justify-between"><span class="muted">Devoluciones</span><span class="font-semibold">-${{ $fmt($resumen['devoluciones'] ?? 0) }}</span></div>
          </div>

          <div class="border-t border-gray-200 dark:border-gray-700 my-3"></div>

          <div class="grid sm:grid-cols-2 gap-3 text-sm">
            <div class="flex justify-between">
              <span class="muted">Efectivo esperado</span>
              <span class="font-bold">${{ $fmt($turno->efectivoEsperado()) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="muted">Cobrado sin CxC</span>
              <span class="font-bold">${{ $fmt($turno->totalCobrado()) }}</span>
            </div>
          </div>

          <div class="mt-4">
            <button wire:click="cerrar"
              class="w-full md:w-auto px-5 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold shadow">
              <i class="fa-solid fa-lock mr-2"></i> Confirmar cierre
            </button>
          </div>
        </div>

        {{-- Por tipo en cierre --}}
        <div class="card p-4">
          <h3 class="title mb-3">Cobrado por tipo</h3>
          <div class="space-y-2 text-sm">
            @foreach((array)$porTipo as $tipo => $total)
              <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-gray-900/50">
                <span class="muted capitalize">{{ strtolower($tipo) }}</span>
                <span class="font-semibold">${{ $fmt($total) }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>
  @endif
</div>
