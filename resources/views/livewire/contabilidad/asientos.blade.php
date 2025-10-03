{{-- resources/views/livewire/contabilidad/asientos.blade.php --}}

@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Alpine espera a Livewire (como en tu factura-form)
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div x-data="{}" class="p-6 md:p-8">
  {{-- ================= HERO / HEADER ================= --}}
  <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-violet-600 via-indigo-600 to-blue-600 text-white shadow-2xl" aria-label="Encabezado de asientos">
    <div class="px-6 md:px-8 py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center gap-3">
          <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/15 backdrop-blur">
            <i class="fa-solid fa-book text-2xl"></i>
          </span>
          Asientos contables
        </h1>
        <p class="text-white/80 text-sm">Consulta, filtra y revisa los movimientos (débito / crédito) de cada asiento.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
          <i class="fa-solid fa-shield-halved"></i> Doble partida
        </span>
      </div>
    </div>
  </section>

  {{-- ================= CARD FILTROS ================= --}}
  <section class="mt-6 md:mt-8 rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden p-6 md:p-8" aria-label="Filtros">
    <header class="mb-4">
      <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
        <span class="inline-grid place-items-center w-8 h-8 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">
          <i class="fa-solid fa-filter text-sm"></i>
        </span>
        Filtros
      </h2>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
      <div class="md:col-span-2">
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Buscar</label>
        <input type="text" wire:model.defer="search" placeholder="ID, glosa, origen..."
               class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Desde</label>
        <input type="date" wire:model="desde"
               class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Hasta</label>
        <input type="date" wire:model="hasta"
               class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Origen</label>
        <input type="text" wire:model="origen" placeholder="factura, manual…"
               class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Tercero</label>
        <input type="text" wire:model="tercero" placeholder="nombre o NIT…"
               class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-4 focus:ring-violet-300/60">
      </div>
    </div>

    <div class="mt-4 flex items-center gap-3">
      <button wire:click="$refresh"
              class="px-4 h-11 rounded-2xl bg-slate-900 text-white font-semibold shadow hover:bg-black">
        Aplicar
      </button>

      <div class="ml-auto flex items-center gap-2">
        <span class="text-sm text-gray-500">Por página</span>
        <select wire:model="perPage"
                class="h-11 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
          <option>10</option><option>15</option><option>25</option><option>50</option>
        </select>
      </div>
    </div>
  </section>

  {{-- ================= TABLA ================= --}}
  <section class="mt-6 md:mt-8 rounded-3xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden" aria-label="Lista de asientos">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
          <tr>
            <th class="px-4 py-3 text-left">Fecha</th>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Glosa</th>
            <th class="px-4 py-3 text-left">Origen</th>
            <th class="px-4 py-3 text-left">Tercero</th>
            <th class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
          @forelse($asientos as $a)
            @php $desbalance = round(($a->total_debe ?? 0) - ($a->total_haber ?? 0), 2); @endphp
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
              <td class="px-4 py-3">{{ \Illuminate\Support\Carbon::parse($a->fecha)->toDateString() }}</td>
              <td class="px-4 py-3 font-semibold">#{{ $a->id }}</td>
              <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100 truncate max-w-[42ch]" title="{{ $a->glosa }}">{{ $a->glosa }}</td>

              {{-- Origen con número de factura formateado --}}
             <td class="px-4 py-3">
  @php
    $textoOrigen = null;
    $abrirFacturaId = null;

    // Caso 1: asiento de FACTURA
    if (($a->origen ?? null) === 'factura' && $a->origen_id) {
        $ff = \App\Models\Factura\Factura::with('serie')->find($a->origen_id);
        if ($ff) {
            $len = $ff->serie->longitud ?? 6;
            $num = $ff->numero !== null
                ? str_pad((string)$ff->numero, $len, '0', STR_PAD_LEFT)
                : '—';
            $textoOrigen   = ($ff->prefijo ? "{$ff->prefijo}-" : '') . $num;
            $abrirFacturaId = $ff->id;
        }
    }

    // Caso 2: asiento de PAGO_FACTURA → enlazar a su factura
    if (($a->origen ?? null) === 'pago_factura' && $a->origen_id) {
        $fp = \App\Models\Factura\FacturaPago::with('factura.serie')->find($a->origen_id);
        if ($fp && $fp->factura) {
            $f  = $fp->factura;
            $len = $f->serie->longitud ?? 6;
            $num = $f->numero !== null
                ? str_pad((string)$f->numero, $len, '0', STR_PAD_LEFT)
                : '—';
            $textoOrigen    = ($f->prefijo ? "{$f->prefijo}-" : '') . $num;
            $abrirFacturaId = $f->id;
        }
    }
  @endphp

  @if($textoOrigen && $abrirFacturaId)
    <button
      type="button"
      wire:click="$dispatch('abrir-factura', { id: {{ (int)$abrirFacturaId }} })"
      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold
             bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-200
             hover:brightness-95 transition focus:outline-none focus:ring-2 focus:ring-indigo-400"
      title="Abrir Factura {{ $textoOrigen }}"
    >
      @if(($a->origen ?? null) === 'pago_factura')
        pago factura
      @else
        factura
      @endif
      <span class="ml-1 font-semibold text-indigo-700 font-mono tracking-wider">{{ $textoOrigen }}</span>
      <i class="fa-solid fa-up-right-from-square text-[10px] ml-1"></i>
    </button>

    {{-- Si quieres mostrar también el ID del pago cuando el origen es pago_factura --}}
    @if(($a->origen ?? null) === 'pago_factura')
      <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px]
                   bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
        pago #{{ $a->origen_id }}
      </span>
    @endif
  @else
    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold
                bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
      {{ $a->origen ?? '—' }}@if($a->origen_id) <span class="ml-1">#{{ $a->origen_id }}</span>@endif
    </span>
  @endif
</td>


              <td class="px-4 py-3">
                @if(isset($a->tercero) && $a->tercero)
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold
                               bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                        title="NIT: {{ $a->tercero->nit ?? '' }}">
                    {{ $a->tercero->razon_social }}
                  </span>
                @else
                  <span class="text-gray-400">—</span>
                @endif
              </td>

              <td class="px-4 py-3 text-right">
                <button wire:click="ver({{ $a->id }})"
                        class="px-3 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-xs font-semibold shadow hover:from-indigo-700 hover:to-violet-700">
                  <i class="fa-solid fa-magnifying-glass mr-1"></i> Ver detalle
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                No se encontraron asientos…
              </td>
            </tr>
          @endforelse
        </tbody>

        @php
          $pageDebe = $asientos->getCollection()->sum('total_debe');
          $pageHaber = $asientos->getCollection()->sum('total_haber');
          $pageDif = round(($pageDebe ?? 0) - ($pageHaber ?? 0), 2);
        @endphp
        <tfoot class="bg-gray-50 dark:bg-gray-800/60">
          <tr>
            <td class="px-4 py-3 text-right" colspan="6">
              <span class="px-2 py-1 rounded-lg text-xs font-semibold {{ $pageDif == 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                Dif: {{ number_format($pageDif, 2) }}
              </span>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="p-4 border-t border-gray-100 dark:border-gray-800 flex flex-wrap items-center gap-3">
      <span class="text-sm text-gray-600 dark:text-gray-300">
        Registros en página: <strong>{{ $asientos->count() }}</strong>
      </span>
      <span class="text-sm text-gray-600 dark:text-gray-300">
        Total Débito: <strong>{{ number_format($pageDebe, 2) }}</strong>
      </span>
      <span class="text-sm text-gray-600 dark:text-gray-300">
        Total Crédito: <strong>{{ number_format($pageHaber, 2) }}</strong>
      </span>
      <span class="text-sm">
        <span class="px-2 py-1 rounded-lg text-xs font-semibold {{ $pageDif == 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
          Balance: {{ number_format($pageDif, 2) }}
        </span>
      </span>

      <span class="ml-auto">
        {{ $asientos->onEachSide(1)->links() }}
      </span>
    </div>
  </section>

  {{-- ================= MODAL DETALLE ================= --}}
  <div x-data="{ open: @entangle('showModal') }" x-cloak>
    <template x-teleport="body">
      <div x-show="open" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="open=false"></div>

        <div x-show="open" x-transition.scale.origin.center
             class="relative max-w-6xl w-full bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden ring-1 ring-black/10 dark:ring-white/10">
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
              <h3 class="text-lg font-bold">
                Asiento #{{ $asientoDetalle['id'] ?? '' }}
              </h3>
              <p class="text-xs text-gray-500">
                {{ $asientoDetalle['fecha'] ?? '' }} — {{ $asientoDetalle['glosa'] ?? '' }}
                @if(!empty($asientoDetalle['origen']))
                  — Origen:
                  @if($asientoDetalle['origen'] === 'factura' && !empty($asientoDetalle['origen_id']))
                    @php
                      $factModalTxt = null;
                      $ffm = \App\Models\Factura\Factura::with('serie')->find((int)$asientoDetalle['origen_id']);
                      if ($ffm) {
                          $lenm = $ffm->serie->longitud ?? 6;
                          $numm = $ffm->numero !== null
                              ? str_pad((string)$ffm->numero, $lenm, '0', STR_PAD_LEFT)
                              : '—';
                          $factModalTxt = ($ffm->prefijo ? "{$ffm->prefijo}-" : '') . $numm;
                      }
                    @endphp
                    <button
                      type="button"
                      wire:click="$dispatch('abrir-factura', {{ (int)$asientoDetalle['origen_id'] }})"
                      class="underline underline-offset-2 decoration-dotted hover:decoration-solid text-indigo-600 dark:text-indigo-300 font-mono"
                      title="Abrir Factura {{ $factModalTxt ?? ('#'.$asientoDetalle['origen_id']) }}"
                    >
                      factura {{ $factModalTxt ?? ('#'.$asientoDetalle['origen_id']) }}
                    </button>
                  @else
                    {{ $asientoDetalle['origen'] }}@if(!empty($asientoDetalle['origen_id'])) (#{{ $asientoDetalle['origen_id'] }}) @endif
                  @endif
                @endif
                @if(!empty($asientoDetalle['moneda'])) — Moneda: {{ $asientoDetalle['moneda'] }} @endif
                @if(!empty($asientoDetalle['tercero']))
                  — Tercero: {{ $asientoDetalle['tercero']['razon_social'] ?? '' }} @if(!empty($asientoDetalle['tercero']['nit'])) (NIT: {{ $asientoDetalle['tercero']['nit'] }}) @endif
                @endif
              </p>
            </div>

            <div class="flex items-center gap-2">
              <button wire:click="cerrarModal"
                      class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-gray-900 text-white hover:bg-black"
                      aria-label="Cerrar">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          </div>

          <div class="p-6">
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                  <tr>
                    <th class="px-4 py-3 text-left">Mov ID</th>
                    <th class="px-4 py-3 text-left">Cuenta ID</th>
                    <th class="px-4 py-3 text-left">Código</th>
                    <th class="px-4 py-3 text-left">Cuenta</th>
                    <th class="px-4 py-3 text-right">Débito</th>
                    <th class="px-4 py-3 text-right">Crédito</th>
                    <th class="px-4 py-3 text-right">Base</th>
                    <th class="px-4 py-3 text-right">Tarifa %</th>
                    <th class="px-4 py-3 text-left">Impuesto</th>
                    <th class="px-4 py-3 text-left">Tercero</th>
                    <th class="px-4 py-3 text-left">Detalle</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                  @foreach($movimientos as $m)
                    <tr class="hover:bg-gray-50 dark:hoverbg-gray-800/40">
                      <td class="px-4 py-2 font-mono">#{{ $m['mov_id'] }}</td>
                      <td class="px-4 py-2 font-mono">{{ $m['cuenta_id'] }}</td>
                      <td class="px-4 py-2 font-mono">{{ $m['codigo'] }}</td>
                      <td class="px-4 py-2">{{ $m['nombre'] }}</td>
                      <td class="px-4 py-2 text-right tabular-nums">{{ number_format($m['debito'], 2) }}</td>
                      <td class="px-4 py-2 text-right tabular-nums">{{ number_format($m['credito'], 2) }}</td>
                      <td class="px-4 py-2 text-right tabular-nums">{{ number_format($m['base_gravable'] ?? 0, 2) }}</td>
                      <td class="px-4 py-2 text-right tabular-nums">{{ number_format($m['tarifa_pct'] ?? 0, 4) }}</td>
                      <td class="px-4 py-2">
                        @if(!empty($m['impuesto_codigo']) || !empty($m['impuesto_nombre']))
                          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200" title="{{ $m['impuesto_nombre'] ?? $m['impuesto_codigo'] }}">
                            {{ $m['impuesto_codigo'] ?? $m['impuesto_nombre'] }}
                          </span>
                        @else
                          <span class="text-gray-400">—</span>
                        @endif
                      </td>
                      <td class="px-4 py-2">
                        @if(!empty($m['tercero_nombre']))
                          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200" title="{{ !empty($m['tercero_nit']) ? 'NIT: '.$m['tercero_nit'] : '' }}">
                            {{ $m['tercero_nombre'] }}
                          </span>
                        @else
                          <span class="text-gray-400">—</span>
                        @endif
                      </td>
                      <td class="px-4 py-2 text-gray-500">{{ $m['detalle'] }}</td>
                    </tr>
                  @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-800/60">
                  @php $dif = round(($modalTotalDebito ?? 0) - ($modalTotalCredito ?? 0), 2); @endphp
                  <tr>
                    <td class="px-4 py-3 font-semibold" colspan="4">Totales movimientos</td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format($modalTotalDebito, 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format($modalTotalCredito, 2) }}</td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format($modalTotalBase ?? 0, 2) }}</td>
                    <td class="px-4 py-3"></td>
                    <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ number_format($modalTotalImpuesto ?? 0, 2) }}</td>
                    <td class="px-4 py-3" colspan="2">
                      <span class="px-2 py-1 rounded-lg text-xs font-semibold {{ $dif == 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        Dif: {{ number_format($dif, 2) }}
                      </span>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div class="mt-4 flex items-center justify-end gap-2">
              <button wire:click="cerrarModal"
                      class="px-4 h-11 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white shadow">
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</div>
