@once
  @push('styles')
    <style>[x-cloak]{display:none!important}</style>
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Evita conflictos entre Alpine y Livewire (Alpine se inicia después de Livewire)
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit);
      };
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

@php
  // Normaliza variables para evitar "undefined"
  /** @var \Illuminate\Pagination\LengthAwarePaginator|null $filas */
  $filas = $filas ?? null;

  // $grupos puede venir como array o Collection; lo normalizamos a Collection
  $gruposCol = collect($grupos ?? []);
@endphp

<section class="bg-white dark:bg-gray-900 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 p-6"
         x-data="{ open: null }">

  <header class="flex items-center justify-between mb-4">
    <div>
      <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-600" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5a2 2 0 012-2h4a2 2 0 012 2v2H3V5zm0 4h10v6H3V9zm12 0h6v10h-6V9zM3 17h10v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2z"/></svg>
        Movimientos de Kardex
      </h2>
      <p class="text-sm text-gray-500 dark:text-gray-400">Agrupado por documento (clic para ver detalle)</p>
    </div>

    {{-- Info de saldos si existen en el componente --}}
    <div class="flex flex-wrap gap-3">
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
            // Campos seguros
            $uid     = (string) ($g['uid'] ?? \Illuminate\Support\Str::uuid());
            $doc     = (string) ($g['doc'] ?? 'Documento');
            $fecha   = (string) ($g['fecha'] ?? '—');
            $bodega  = (string) ($g['bodega'] ?? '—');
            $entTot  = (float)  ($g['entrada_total'] ?? 0);
            $salTot  = (float)  ($g['salida_total'] ?? 0);
            $rowsDet = collect($g['rows'] ?? []);
          @endphp

          {{-- Fila principal (cabecera del documento) --}}
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
              @click="open === '{{ $uid }}' ? open = null : open = '{{ $uid }}'">
            <td class="px-3 py-2 text-center align-middle">
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

          {{-- Detalle expandible --}}
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
                      @php
                        $tipo   = (string) ($r['tipo'] ?? '—');
                        $ent    = $r['entrada'] ?? null;
                        $sal    = $r['salida'] ?? null;
                        $cu     = $r['costo_unit'] ?? null;
                        $scant  = $r['saldo_cant'] ?? null;
                        $sval   = $r['saldo_val'] ?? null;
                      @endphp
                      <tr class="hover:bg-white dark:hover:bg-gray-700/40">
                        <td class="px-3 py-1">{{ $tipo }}</td>
                        <td class="px-3 py-1 text-right">{{ $ent ? number_format($ent,2) : '—' }}</td>
                        <td class="px-3 py-1 text-right">{{ $sal ? number_format($sal,2) : '—' }}</td>
                        <td class="px-3 py-1 text-right">{{ $cu ? number_format($cu,2) : '—' }}</td>
                        <td class="px-3 py-1 text-right">{{ $scant ? number_format($scant,2) : '—' }}</td>
                        <td class="px-3 py-1 text-right">{{ $sval ? number_format($sval,2) : '—' }}</td>
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

  {{-- Paginación (si $filas existe) --}}
  @if($filas instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="mt-4">
      {{ $filas->links() }}
    </div>
  @endif
</section>
