{{-- resources/views/livewire/facturas/listapagosrecibidos.blade.php --}}

@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Evita conflicto Alpine ↔ Livewire
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div x-data="{ deleteOpen:false }" class="p-6 md:p-8 space-y-6">

  {{-- ================= HEADER / HERO ================= --}}
  <section
    class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-gray-200 via-gray-100 to-white 
               dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 text-gray-800 dark:text-white shadow-2xl">
    <div class="px-6 md:px-8 py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center gap-3">
          <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/70 dark:bg-gray-700/60 backdrop-blur">
            <i class="fa-solid fa-wallet text-2xl text-gray-700 dark:text-gray-200"></i>
          </span>
          Pagos recibidos
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Consulta, filtra y analiza todos los pagos asociados a facturas emitidas.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <span
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/70 dark:bg-gray-800/60
                 font-semibold text-xs md:text-sm text-gray-700 dark:text-gray-200 shadow-sm">
          <i class="fa-solid fa-money-bill-wave"></i>
          Total general: ${{ number_format($totalGeneral,2,',','.') }}
        </span>
      </div>
    </div>
  </section>

  {{-- ================= CARD PRINCIPAL ================= --}}
  <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 shadow-2xl overflow-hidden">

    {{-- ===== FILTROS ===== --}}
    <section class="p-6 md:p-8 border-b border-gray-100 dark:border-gray-800" aria-label="Filtros de búsqueda">
      <header class="mb-4">
        <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
          <span
            class="inline-grid place-items-center w-8 h-8 md:w-9 md:h-9 rounded-xl bg-gray-200 text-gray-700
                   dark:bg-gray-800 dark:text-gray-200">
            <i class="fa-solid fa-filter text-[13px]"></i>
          </span>
          Filtros
        </h2>
      </header>

      <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        {{-- Buscar --}}
        <div class="md:col-span-4">
          <label
            class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Buscar
          </label>
          <input type="text" wire:model.debounce.400ms="buscar"
                 placeholder="Cliente, referencia, factura..."
                 class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-gray-300/40 dark:focus:ring-gray-700/40">
        </div>

        {{-- Fecha desde --}}
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Desde
          </label>
          <input type="date" wire:model.lazy="fecha_inicio"
                 class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-gray-300/40 dark:focus:ring-gray-700/40">
        </div>

        {{-- Fecha hasta --}}
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Hasta
          </label>
          <input type="date" wire:model.lazy="fecha_fin"
                 class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-gray-300/40 dark:focus:ring-gray-700/40">
        </div>

        {{-- Medio de pago --}}
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">
            Medio de pago
          </label>
          <select wire:model.lazy="medio_pago_id"
                  class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700
                         bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-gray-300/40 dark:focus:ring-gray-700/40">
            <option value="">— Todos —</option>
            @foreach($mediosPago as $mp)
              <option value="{{ $mp['id'] }}">{{ $mp['nombre'] }}</option>
            @endforeach
          </select>
        </div>

        {{-- Botones --}}
        <div class="md:col-span-2 flex items-end gap-3">
          <button wire:click="$refresh"
                  class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-gray-700 hover:bg-gray-800 text-white shadow">
            <i class="fa-solid fa-magnifying-glass"></i> Buscar
          </button>
          <button
            wire:click="$set('buscar','');$set('medio_pago_id',null);$set('fecha_inicio',null);$set('fecha_fin',null)"
            class="px-4 py-2 rounded-2xl border-2 border-gray-200 dark:border-gray-700
                   hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200">
            Limpiar
          </button>
        </div>
      </div>
    </section>

    {{-- ===== LISTADO ===== --}}
    <section class="p-4 md:p-6" aria-label="Listado de pagos recibidos">
      {{-- Móvil: Cards --}}
      <div class="md:hidden space-y-3">
        @forelse($pagos as $p)
          <article class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
            <div class="flex justify-between items-start mb-2">
              <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $p->metodo ?: '—' }}</span>
              <span class="text-base font-bold text-gray-900 dark:text-white">$ {{ number_format($p->monto,2,',','.') }}</span>
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-200">
              <p><span class="font-semibold">Factura:</span> {{ $p->factura?->numero_formateado ?? '—' }}</p>
              <p><span class="font-semibold">Cliente:</span> {{ $p->factura?->cliente?->razon_social ?? '—' }}</p>
              <p><span class="font-semibold">Fecha:</span> {{ $p->fecha->format('Y-m-d') }}</p>
              <p><span class="font-semibold">Referencia:</span> {{ $p->referencia ?: '—' }}</p>
            </div>
          </article>
        @empty
          <div class="rounded-xl border border-dashed p-6 text-center text-gray-500">No hay pagos registrados.</div>
        @endforelse
      </div>

      {{-- Desktop: Tabla --}}
      <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
            <tr>
              <th class="p-3 text-left">Fecha</th>
              <th class="p-3 text-left">Factura</th>
              <th class="p-3 text-left">Cliente</th>
              <th class="p-3 text-left">Método</th>
              <th class="p-3 text-left">Medio</th>
              <th class="p-3 text-left">Referencia</th>
              <th class="p-3 text-right">Monto</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($pagos as $p)
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <td class="p-3">{{ $p->fecha->format('Y-m-d') }}</td>
                <td class="p-3">{{ $p->factura?->numero_formateado ?? '—' }}</td>
                <td class="p-3">{{ $p->factura?->cliente?->razon_social ?? '—' }}</td>
                <td class="p-3">{{ $p->metodo ?: '—' }}</td>
                <td class="p-3">{{ $p->medioPago?->nombre ?? '—' }}</td>
                <td class="p-3">{{ $p->referencia ?: '—' }}</td>
                <td class="p-3 text-right font-semibold">$ {{ number_format($p->monto,2,',','.') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="p-6 text-center text-gray-500 dark:text-gray-400">
                  No hay pagos registrados.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div
        class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
        <div>
          Mostrando <span class="font-semibold">{{ $pagos->firstItem() }}</span> -
          <span class="font-semibold">{{ $pagos->lastItem() }}</span> de
          <span class="font-semibold">{{ $pagos->total() }}</span>
        </div>
        <div>{{ $pagos->onEachSide(1)->links() }}</div>
      </div>
    </section>
  </section>

  {{-- Loading --}}
  <div wire:loading.delay
       class="fixed inset-x-0 bottom-4 mx-auto w-fit px-4 py-2 rounded-full bg-gray-700 text-white shadow-lg">
    <i class="fa-solid fa-spinner animate-spin mr-2"></i> Cargando...
  </div>
</div>
