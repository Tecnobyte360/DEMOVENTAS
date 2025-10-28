{{-- resources/views/livewire/cuentas-contables/plan-cuentas.blade.php (versión completa: organizada + fullscreen) --}}

<div class="h-[100dvh] w-full overflow-hidden bg-transparent"
     x-data="planCuentasUI()"
     x-init="init()"
     @keydown.arrow-down.prevent="live.selectNext()"
     @keydown.arrow-up.prevent="live.selectPrev()"
     @keydown.arrow-right.prevent="live.toggleSelected(true)"
     @keydown.arrow-left.prevent="live.toggleSelected(false)">

  @once
    @push('styles')
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    @endpush
  @endonce

  @once
    @push('scripts')
      <script>
        // Evita carreras con Alpine al iniciar junto con Livewire
        window.deferLoadingAlpine = (alpineInit) => { document.addEventListener('livewire:init', alpineInit) }
      </script>
      <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @endpush
  @endonce

  <style>
    .sap-tree-row { position: relative; line-height: 1.25rem; }
    .sap-indent { position: relative; height: 100%; display: inline-block; }
    .sap-indent::before { content: ""; position: absolute; top: -14px; bottom: -14px; left: 9px; border-left: 1px dashed rgba(99,102,241,.28); }
    .sap-connector { width: 18px; height: 18px; border-bottom: 1px dashed rgba(99,102,241,.28); margin-right: .25rem; }
    [x-cloak] { display: none !important; }

    /* Bandas por nivel (sutileza, no mancha) */
    .lvl-1  { background: linear-gradient(90deg, rgba(99,102,241,.04),  transparent 180px); }
    .lvl-2  { background: linear-gradient(90deg, rgba(99,102,241,.035), transparent 180px); }
    .lvl-3  { background: linear-gradient(90deg, rgba(99,102,241,.03),  transparent 180px); }
    .lvl-4  { background: linear-gradient(90deg, rgba(99,102,241,.025), transparent 180px); }
    .lvl-5  { background: linear-gradient(90deg, rgba(99,102,241,.022), transparent 180px); }
    .lvl-6  { background: linear-gradient(90deg, rgba(99,102,241,.02),  transparent 180px); }
    .lvl-7  { background: linear-gradient(90deg, rgba(99,102,241,.018), transparent 180px); }
    .lvl-8  { background: linear-gradient(90deg, rgba(99,102,241,.016), transparent 180px); }
    .lvl-9  { background: linear-gradient(90deg, rgba(99,102,241,.014), transparent 180px); }
    .lvl-10 { background: linear-gradient(90deg, rgba(99,102,241,.012), transparent 180px); }

    /* Resaltado de búsqueda */
    mark.pc-hit { background: #fde68a; color: #7c2d12; padding: 0 .15rem; border-radius: .25rem; }

    /* Organización avanzada */
    .pc-grid-cols { 
      display: grid;
      grid-template-columns: 16rem 1fr 9rem 5rem auto; /* Código | Cuenta | Naturaleza | Activa | Saldos */
      gap: .5rem;
      align-items: center;
    }
    /* Columnas pegajosas (congeladas) */
    .pc-sticky-code   { position: sticky; left: 0;     z-index: 10; }
    .pc-sticky-name   { position: sticky; left: 16rem; z-index: 10; }

    /* Fondo para columnas pegajosas */
    .pc-bg {
      background: #ffffff;
    }
    .dark .pc-bg { background: #0b0f19; }

    /* Cabecera pegajosa dentro del contenedor scrolleable */
    .pc-head-sticky { position: sticky; top: 0; z-index: 20; }

    /* Separadores por Naturaleza */
    .pc-section {
      position: sticky;
      top: 0; /* queda debajo del encabezado de columnas */
      z-index: 15;
      padding: .35rem .75rem;
      font-size: .75rem;
      font-weight: 700;
      border-top: 1px solid rgba(148,163,184,.35);
      border-bottom: 1px solid rgba(148,163,184,.35);
    }
    .pc-section.light { background: #f1f5f9aa; }
    .dark .pc-section.light { background: #0f172aaa; }

    /* Zebra suave (además de bandas por nivel) */
    .pc-row-odd { background: rgba(2,6,23,0.00); }
    .pc-row-even { background: rgba(2,6,23,0.03); }
    .dark .pc-row-even { background: rgba(148,163,184,0.06); }

    /* Evita jitter del blur sticky */
    .backdrop-blur { will-change: transform; }
  </style>

  <div class="h-full w-full flex flex-col rounded-none md:rounded-2xl shadow-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">

    {{-- HEADER PRINCIPAL (fijo) --}}
    <header class="sticky top-0 z-20 px-4 md:px-6 py-3 bg-white/90 dark:bg-gray-900/90 backdrop-blur
                   border-b border-gray-200 dark:border-gray-700 text-gray-800 dark:text-white">
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-3">
          <span class="inline-grid place-items-center w-9 h-9 rounded-lg bg-indigo-100 dark:bg-indigo-900/40">
            <i class="fa-solid fa-book-open text-indigo-600 dark:text-indigo-400 text-sm"></i>
          </span>
          <div>
            <h1 class="text-base md:text-lg font-semibold leading-none">Plan de Cuentas</h1>
            <p class="text-gray-500 dark:text-gray-400 text-[11px] mt-1">Explora y administra con claridad</p>
          </div>
        </div>

        <div class="flex-1"></div>

        {{-- Buscador y acciones --}}
        <div class="flex flex-wrap items-center gap-2">
          <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 dark:text-gray-500 text-xs"></i>
            <input type="text" wire:model.debounce.300ms="q" placeholder="Buscar código o nombre…"
                   class="pl-8 pr-7 h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm
                          placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <button type="button" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                    @click="$wire.set('q','')"><i class="fa-solid fa-xmark text-xs"></i></button>
          </div>

          <select wire:model.live="nivelMax"
                  class="h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-xs px-2">
            <option value="">Nivel: Todos</option>
            @for($i=1;$i<=10;$i++)
              <option value="{{ $i }}">Nivel {{ $i }}</option>
            @endfor
          </select>

          <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
            <input type="checkbox" wire:model.live="soloTitulos" class="rounded text-indigo-600 focus:ring-indigo-500">
            <span>Solo títulos</span>
          </label>

          <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
            <input type="checkbox" wire:model.live="verSaldos" class="rounded text-indigo-600 focus:ring-indigo-500">
            <span>Mostrar saldos</span>
          </label>

          <div class="hidden md:flex items-center gap-1">
            <button type="button" class="h-9 px-3 rounded-lg border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                    @click="$wire.expandToLevel(1)">L1</button>
            <button type="button" class="h-9 px-3 rounded-lg border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                    @click="$wire.expandToLevel(3)">L3</button>
            <button type="button" class="h-9 px-3 rounded-lg border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                    @click="$wire.expandToLevel(5)">L5</button>
          </div>

          <button type="button" class="h-9 px-3 rounded-lg border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                  @click="$wire.expandAll()">
            <i class="fa-solid fa-arrows-to-circle mr-1"></i> Expandir
          </button>
          <button type="button" class="h-9 px-3 rounded-lg border border-gray-300 dark:border-gray-700 text-xs hover:bg-gray-100 dark:hover:bg-gray-800"
                  @click="$wire.collapseAll()">
            <i class="fa-solid fa-compress mr-1"></i> Colapsar
          </button>

          <button type="button" wire:click="openCreate(@js($selectedId))"
                  class="h-9 px-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs shadow-sm">
            <i class="fa-solid fa-plus mr-1"></i> Nueva
          </button>
        </div>
      </div>

      {{-- Etiquetas de naturaleza --}}
      <div class="mt-3 flex flex-wrap gap-1.5">
        @foreach(['TODAS','ACTIVOS','PASIVOS','PATRIMONIO','INGRESOS','COSTOS','GASTOS'] as $nat)
          <button type="button"
                  wire:click="setNaturaleza('{{ $nat }}')"
                  class="px-2.5 py-1 rounded-full text-[11px] border transition
                         {{ $naturaleza===$nat
                             ? 'bg-indigo-100 text-indigo-700 border-indigo-400 dark:bg-indigo-900/40 dark:text-indigo-300 dark:border-indigo-700'
                             : 'bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700' }}">
            {{ ucfirst(strtolower($nat)) }}
          </button>
        @endforeach
      </div>
    </header>

    {{-- SUB-BARRA: filtro de factura --}}
    <div class="shrink-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur border-b border-gray-200 dark:border-gray-800 px-4 md:px-6">
      <div class="flex flex-wrap items-end gap-3 py-2">
        <div class="block">
          <span class="block text-[11px] text-gray-500 mb-1">Factura</span>
          <div class="flex items-center gap-2">
            <button type="button" wire:click="$toggle('soloCuentasMovidas')"
                    class="px-3 py-1.5 text-[11px] rounded-lg
                           {{ $soloCuentasMovidas ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100' }}">
              Solo cuentas movidas
            </button>
            <input type="number" min="1" placeholder="ID" wire:model.lazy="factura_id"
                   class="w-24 h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-2 text-[11px]">
            <span class="text-[11px] text-gray-400">o</span>
            <input type="text" placeholder="Prefijo" wire:model.lazy="factura_prefijo"
                   class="w-20 h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-2 text-[11px]">
            <input type="number" min="1" placeholder="Número" wire:model.lazy="factura_numero"
                   class="w-24 h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-2 text-[11px]">
            <button type="button" wire:click="limpiarFiltroFactura"
                    class="px-2 py-1.5 text-[11px] rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700">
              Limpiar
            </button>
          </div>
        </div>

        <div class="ml-auto text-[11px] text-gray-500 dark:text-gray-400">
          Nivel actual: <span class="font-semibold">{{ $nivelMax ?? 'Todos' }}</span>
        </div>
      </div>
    </div>

    {{-- PANEL PRINCIPAL: Tabla/Árbol organizado --}}
    <section class="flex-1 min-h-0 p-4" x-bind:class="compact ? 'text-[13px]' : 'text-sm'">
      <div class="h-full border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden relative flex flex-col">

        {{-- Encabezado columnas (pegajoso dentro del panel) --}}
        <div class="pc-head-sticky pc-grid-cols px-3 py-2 text-[12px] font-medium
                    bg-slate-100/85 dark:bg-gray-800/85 text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-800">
          <div class="pc-sticky-code pc-bg">Código</div>
          <div class="pc-sticky-name pc-bg">Cuenta</div>
          <div>Naturaleza</div>
          <div class="text-center">Activa</div>
          @if($verSaldos)
            <div class="text-right pr-2">Saldos</div>
          @endif
        </div>

        {{-- Lista con scroll que usa TODO el alto disponible --}}
        <div class="flex-1 min-h-0 overflow-auto" x-ref="scroll">
          @php $lastNat = null; $rowIndex = 0; @endphp

          @forelse($items as $row)
            @php
              $expanded = in_array($row->id, $this->expandidos ?? []);
              $hasKids  = $row->hijos_count ?? ($row->tiene_hijos ?? $row->hijos()->count());
              $indent   = max(0, ((int)($row->nivel_visual ?? $row->nivel) - 1) * 18);
              $lvlClass = 'lvl-'.min(10, (int)($row->nivel_visual ?? $row->nivel));
              $rowIndex++;
              $isEven = $rowIndex % 2 === 0;
              $nat = $row->naturaleza ?: 'OTROS';
            @endphp

            {{-- Separador cuando cambia la Naturaleza --}}
            @if($lastNat !== $nat)
              <div class="pc-section light">{{ strtoupper($nat) }}</div>
              @php $lastNat = $nat; @endphp
            @endif

            <div id="row-{{ $row->id }}"
                 class="group sap-tree-row pc-grid-cols px-3 py-2 border-t border-gray-100 dark:border-gray-800
                        hover:bg-slate-50 dark:hover:bg-gray-800/60 {{ $lvlClass }} {{ $isEven ? 'pc-row-even' : 'pc-row-odd' }}
                        {{ $selectedId===$row->id ? 'ring-1 ring-inset ring-indigo-500/40 bg-indigo-50/60 dark:bg-indigo-900/20' : '' }}"
                 @click="$wire.select({{ $row->id }}); $nextTick(()=>scrollIntoViewIfNeeded('row-{{ $row->id }}'))">

              {{-- Código (pegajoso) --}}
              <div class="pc-sticky-code pc-bg font-mono text-[13px] text-gray-800 dark:text-gray-100 truncate">
                {{ $row->codigo }}
              </div>

              {{-- Cuenta/árbol (pegajoso) --}}
              <div class="pc-sticky-name pc-bg flex items-center min-w-0"
                   @click.stop="{{ $hasKids ? "\$wire.toggle($row->id)" : '' }}">
                <span class="sap-indent" style="width: {{ $indent }}px"></span>

                @if($hasKids)
                  <button type="button" wire:click.stop="toggle({{ $row->id }})"
                          class="mr-1 inline-flex h-6 w-6 items-center justify-center rounded border border-gray-300 dark:border-gray-700
                                 bg-white/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800"
                          aria-label="{{ $expanded ? 'Colapsar' : 'Expandir' }}">
                    <i class="fa-solid fa-caret-{{ $expanded ? 'down' : 'right' }}"></i>
                  </button>
                @else
                  <span class="sap-connector mr-1"></span>
                @endif

                <i class="fa-regular {{ $row->titulo ? 'fa-folder' : 'fa-file-lines' }} mr-2 text-sky-700 dark:text-sky-400"></i>

                @php
                  $needle = trim($q ?? '');
                  $hitNombre = $row->nombre; $hitCodigo = $row->codigo;
                  if ($needle !== '') {
                    $rx = '/(' . preg_quote($needle, '/') . ')/i';
                    $hitNombre = preg_replace($rx, '<mark class=\"pc-hit\">$1</mark>', e($row->nombre));
                    $hitCodigo = preg_replace($rx, '<mark class=\"pc-hit\">$1</mark>', e($row->codigo));
                  } else { $hitNombre = e($hitNombre); $hitCodigo = e($hitCodigo); }
                @endphp

                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="inline-flex items-center rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 font-mono text-[11px] text-gray-800 dark:text-gray-100"
                          x-html="'{!! $hitCodigo !!}'"></span>
                    <span class="truncate {{ $row->titulo ? 'italic text-gray-700 dark:text-gray-300' : 'text-gray-900 dark:text-gray-100' }}"
                          x-html="'{!! $hitNombre !!}'"></span>

                    @if($hasKids)
                      <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ (int)$hasKids }}
                      </span>
                    @endif
                  </div>
                </div>

                {{-- Acciones al hover --}}
                <div class="ml-2 items-center gap-1 hidden md:flex opacity-0 group-hover:opacity-100 transition-opacity">
                  <button type="button" title="Añadir hija" wire:click.stop="openCreate({{ $row->id }})"
                          class="px-2 py-1 text-[11px] rounded bg-violet-600 hover:bg-violet-700 text-white">Hija</button>
                  <button type="button" title="Editar" wire:click.stop="openEdit({{ $row->id }})"
                          class="px-2 py-1 text-[11px] rounded bg-indigo-600 hover:bg-indigo-700 text-white">Editar</button>
                </div>
              </div>

              {{-- Naturaleza --}}
              <div>
                <span class="inline-block text-[11px] px-2 py-0.5 rounded-full
                  @class([
                    'bg-emerald-100 text-emerald-700' => $row->naturaleza==='ACTIVOS',
                    'bg-rose-100 text-rose-700'       => $row->naturaleza==='PASIVOS',
                    'bg-sky-100 text-sky-700'         => $row->naturaleza==='PATRIMONIO',
                    'bg-amber-100 text-amber-700'     => $row->naturaleza==='INGRESOS',
                    'bg-indigo-100 text-indigo-700'   => $row->naturaleza==='COSTOS',
                    'bg-gray-200 text-gray-800'       => $row->naturaleza==='GASTOS',
                    'bg-purple-100 text-purple-700'   => !in_array($row->naturaleza, ['ACTIVOS','PASIVOS','PATRIMONIO','INGRESOS','COSTOS','GASTOS']),
                  ])">
                  {{ $row->naturaleza }}
                </span>
              </div>

              {{-- Activa --}}
              <div class="text-center">
                @if($row->cuenta_activa)
                  <i class="fa-solid fa-circle-check text-emerald-600"></i>
                @else
                  <i class="fa-regular fa-circle text-gray-400"></i>
                @endif
              </div>

              {{-- Saldos (opcionales) --}}
              @if($verSaldos)
                <div class="text-right pr-2 font-mono">
                  <div class="text-[11px] text-gray-500">ant: ${{ number_format($row->saldo_antes ?? $row->saldo, 2) }}</div>
                  <div class="text-[11px] {{ ($row->saldo_delta ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ ($row->saldo_delta ?? 0) >= 0 ? '+' : '' }}${{ number_format($row->saldo_delta ?? 0, 2) }}
                  </div>
                  <div class="font-semibold">${{ number_format($row->saldo_despues ?? $row->saldo, 2) }}</div>
                </div>
              @endif
            </div>
          @empty
            <div class="px-6 py-10 text-center">
              <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-400 mb-3">
                <i class="fa-regular fa-folder-open"></i>
              </div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Sin resultados para el filtro aplicado.</p>
            </div>
          @endforelse

          {{-- Loading --}}
          <div wire:loading.flex class="absolute inset-0 bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm items-center justify-center">
            <div class="animate-spin h-6 w-6 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
          </div>
        </div>
      </div>
    </section>
  </div>

  {{-- Modal crear/editar --}}
  <div x-data="{ open: @entangle('showModal').live }" x-show="open" x-cloak class="fixed inset-0 z-[100]" wire:ignore.self @keydown.escape.window="open=false">
    <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
    <div class="relative z-10 w-full max-w-2xl mx-auto mt-12 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">{{ $editingId ? 'Editar cuenta' : 'Nueva cuenta' }}</h3>
        <button class="text-gray-500 hover:text-gray-700" @click="open=false" type="button"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <form wire:submit.prevent="save" class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Cuenta padre</label>
          <select wire:model.live="padre_id" class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            <option value="">— Sin padre (raíz) —</option>
            @foreach($posiblesPadres as $p)
              <option value="{{ $p->id }}">{{ $p->codigo }} — {{ $p->nombre }}</option>
            @endforeach
          </select>
          @error('padre_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          @if(!$editingId)
            <p class="mt-1 text-[11px] text-gray-500">Al cambiar el padre se sugiere el código y se heredan naturaleza y moneda.</p>
          @endif
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Código</label>
          <input type="text" wire:model.defer="codigo" placeholder="11050501" class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 @error('codigo') border-red-500 @enderror">
          @error('codigo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nombre</label>
          <input type="text" wire:model.defer="nombre" placeholder="CAJA GENERAL" class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 @error('nombre') border-red-500 @enderror">
          @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Naturaleza</label>
          <select wire:model.live="naturaleza_form" class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            <option value="ACTIVOS">ACTIVOS</option><option value="PASIVOS">PASIVOS</option><option value="PATRIMONIO">PATRIMONIO</option>
            <option value="INGRESOS">INGRESOS</option><option value="COSTOS">COSTOS</option><option value="GASTOS">GASTOS</option>
            <option value="OTROS_INGRESOS">OTROS_INGRESOS</option><option value="OTROS_GASTOS">OTROS_GASTOS</option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3 md:col-span-2">
          <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="cuenta_activa" class="rounded"><span class="text-sm">Activa</span></label>
          <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="titulo" class="rounded"><span class="text-sm">Título (no imputable)</span></label>
          <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="requiere_tercero" class="rounded"><span class="text-sm">Requiere tercero</span></label>
          <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="confidencial" class="rounded"><span class="text-sm">Confidencial</span></label>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Moneda</label>
          <input type="text" wire:model.defer="moneda" class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Saldo inicial</label>
          <input type="number" step="0.01" min="0" wire:model.defer="saldo" class="w-full h-10 text-right rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
        </div>

        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-3">
          @for($d=1;$d<=4;$d++)
            <div>
              <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300 mb-1">Dimensión {{ $d }}</label>
              <input type="text" wire:model.defer="{{ 'dimension'.$d }}" class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3">
            </div>
          @endfor
        </div>

        <div class="md:col-span-2 flex items-center justify-end gap-2 mt-2">
          <button type="button" @click="open=false" class="px-4 h-10 rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600">Cancelar</button>
          <button type="submit" class="px-4 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white">{{ $editingId ? 'Actualizar' : 'Guardar' }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function planCuentasUI() {
    const lw = () => window.Livewire.find('{{ $_instance->id() }}');
    return {
      compact: false,
      expandLevel: 2,
      live: {
        expandToLevel: (level) => lw()?.call('expandToLevel', level),
        selectNext:     () => lw()?.call('selectNext'),
        selectPrev:     () => lw()?.call('selectPrev'),
        toggleSelected: (expand) => lw()?.call('toggleSelectedByKey', expand),
      },
      init() {
        window.addEventListener('pc-scroll-to-selected', (e) => {
          this.scrollIntoViewIfNeeded('row-' + e.detail.id);
        });
      },
      copy(text) { navigator.clipboard.writeText(text); },
      scrollIntoViewIfNeeded(id) {
        const el = document.getElementById(id);
        const sc = this.$refs.scroll;
        if (!el || !sc) return;
        const eb = el.getBoundingClientRect(), sb = sc.getBoundingClientRect();
        if (eb.top < sb.top + 60 || eb.bottom > sb.bottom - 60) {
          sc.scrollTo({ top: sc.scrollTop + (eb.top - sb.top) - 80, behavior: 'smooth' });
        }
      }
    }
  }
</script>
