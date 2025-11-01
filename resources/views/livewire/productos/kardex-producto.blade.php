@once
  @push('styles')
    <style>[x-cloak]{display:none!important}</style>
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Alpine después de Livewire para evitar bloqueos de eventos
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit);
      };
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div class="space-y-6">

  {{-- ================= Filtros ================= --}}
  <section class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6">
    <header class="mb-4">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filtros del Kardex</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona producto, bodega y rango de fechas.</p>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Producto</label>
        <select
          class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600"
          wire:model.live="producto_id"
          wire:loading.attr="disabled"
        >
          <option value="">— Selecciona —</option>
          @foreach($productos as $p)
            <option value="{{ $p->id }}">{{ $p->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Bodega</label>
        <select
          class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600"
          wire:model.live="bodega_id"
          wire:loading.attr="disabled"
        >
          <option value="">— Todas —</option>
          @foreach($bodegas as $b)
            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Desde</label>
        <input type="date"
          class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600"
          wire:model.live="desde" wire:loading.attr="disabled">
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Hasta</label>
        <input type="date"
          class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600"
          wire:model.live="hasta" wire:loading.attr="disabled">
      </div>

      <div>
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Buscar doc/ref</label>
        <input type="text" placeholder="FAC-123, OC, REF…"
          class="mt-1 w-full rounded-xl border bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-violet-600"
          wire:model.debounce.500ms="buscarDoc">
      </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-4">
      <div class="flex items-center gap-4">
        <label class="flex items-center gap-2">
          <input type="radio" class="text-violet-600" value="kardex" wire:model.live="fuenteDatos">
          <span class="text-sm text-gray-700 dark:text-gray-200">Solo Kardex</span>
        </label>
        <label class="flex items-center gap-2">
          <input type="radio" class="text-violet-600" value="costos" wire:model.live="fuenteDatos">
          <span class="text-sm text-gray-700 dark:text-gray-200">Solo Costos</span>
        </label>
        <label class="flex items-center gap-2">
          <input type="radio" class="text-violet-600" value="ambas" wire:model.live="fuenteDatos">
          <span class="text-sm text-gray-700 dark:text-gray-200">Ambas</span>
        </label>
      </div>

      <div class="ml-auto flex items-center gap-3">
        <select wire:model.live="perPage"
          class="rounded-xl border bg-white dark:bg-gray-800 dark:text-white">
          @foreach([10,25,50,100] as $n)
            <option value="{{ $n }}">{{ $n }}/página</option>
          @endforeach
        </select>

        {{-- Saldos rápidos --}}
        <div class="px-3 py-1 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
          <span class="text-xs text-gray-500 dark:text-gray-400">Saldo inicial</span>
          <span class="ml-2 text-sm font-semibold text-gray-900 dark:text-white">
            {{ number_format(($saldoInicialCant ?? 0), 2) }} und · ${{ number_format(($saldoInicialVal ?? 0), 2) }}
          </span>
        </div>
        <div class="px-3 py-1 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
          <span class="text-xs text-gray-500 dark:text-gray-400">Saldo final</span>
          <span class="ml-2 text-sm font-semibold text-gray-900 dark:text-white">
            {{ number_format(($saldoFinalCant ?? 0), 2) }} und · ${{ number_format(($saldoFinalVal ?? 0), 2) }}
          </span>
        </div>
      </div>
    </div>
  </section>

  {{-- ================= Tabla/Acordeón ================= --}}
  @php
    $filas = $filas ?? null;
    $gruposCol = collect($grupos ?? []);
  @endphp

  <section class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6"
           x-data="{ open: null }">
    <header class="flex justify-between mb-4">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-layer-group text-violet-600"></i>
          Movimientos de Kardex
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Agrupado por documento (clic para ver detalle)</p>
      </div>
    </header>

    <div class="overflow-x-auto">
      <table class="w-full text-sm border-collapse">
        <thead class="bg-violet-600 text-white">
          <tr>
            <th class="px-3 py-2 w-10"></th>
            <th class="px-3 py-2 text-left">Documento</th>
            <th class="px-3 py-2 text-left">Fecha</th>
            <th class="px-3 py-2 text-left">Bodega</th>
            <th class="px-3 py-2 text-right">Entrada total</th>
            <th class="px-3 py-2 text-right">Salida total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @forelse($gruposCol as $g)
            @php
              $uid    = (string) ($g['uid'] ?? \Illuminate\Support\Str::uuid());
              $doc    = (string) ($g['doc'] ?? 'Documento');
              $fecha  = (string) ($g['fecha'] ?? '—');
              $bodega = (string) ($g['bodega'] ?? '—');
              $entTot = (float)  ($g['entrada_total'] ?? 0);
              $salTot = (float)  ($g['salida_total'] ?? 0);
              $rowsDet= collect($g['rows'] ?? []);
            @endphp

            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                @click="open === '{{ $uid }}' ? open = null : open = '{{ $uid }}'">
              <td class="px-3 py-2 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block transition-transform"
                     :class="open === '{{ $uid }}' ? 'rotate-90' : ''"
                     viewBox="0 0 24 24" fill="currentColor">
                  <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/>
                </svg>
              </td>
              <td class="px-3 py-2 font-medium text-violet-700 dark:text-violet-300">{{ $doc }}</td>
              <td class="px-3 py-2">{{ $fecha }}</td>
              <td class="px-3 py-2">{{ $bodega }}</td>
              <td class="px-3 py-2 text-right">{{ number_format($entTot, 2) }}</td>
              <td class="px-3 py-2 text-right">{{ number_format($salTot, 2) }}</td>
            </tr>

            <tr x-show="open === '{{ $uid }}'" x-cloak>
              <td colspan="6" class="bg-gray-50 dark:bg-gray-800 p-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                  <table class="w-full text-xs">
                    <thead class="text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/30">
                      <tr>
                        <th class="text-left px-3 py-2">Tipo</th>
                        <th class="text-right px-3 py-2">Entrada</th>
                        <th class="text-right px-3 py-2">Salida</th>
                        <th class="text-right px-3 py-2">Costo unit.</th>
                        <th class="text-right px-3 py-2">Saldo cant.</th>
                        <th class="text-right px-3 py-2">Saldo val.</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                      @forelse($rowsDet as $r)
                        <tr class="hover:bg-white dark:hover:bg-gray-700/40">
                          <td class="px-3 py-1">{{ $r['tipo'] ?? '—' }}</td>
                          <td class="px-3 py-1 text-right">{{ ($r['entrada'] ?? null) ? number_format($r['entrada'],2) : '—' }}</td>
                          <td class="px-3 py-1 text-right">{{ ($r['salida'] ?? null) ? number_format($r['salida'],2) : '—' }}</td>
                          <td class="px-3 py-1 text-right">{{ ($r['costo_unit'] ?? null) ? number_format($r['costo_unit'],2) : '—' }}</td>
                          <td class="px-3 py-1 text-right">{{ ($r['saldo_cant'] ?? null) ? number_format($r['saldo_cant'],2) : '—' }}</td>
                          <td class="px-3 py-1 text-right">{{ ($r['saldo_val'] ?? null) ? number_format($r['saldo_val'],2) : '—' }}</td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="6" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400 italic">
                            Documento sin líneas para mostrar.
                          </td>
                        </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400 italic">
                Sin movimientos disponibles.
                @if(empty($producto_id))
                  <span class="block mt-1">Selecciona un producto y aplica los filtros.</span>
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($filas instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="mt-4">
        {{ $filas->links() }}
      </div>
    @endif
  </section>
</div>
