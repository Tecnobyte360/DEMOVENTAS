<div class="p-4 md:p-6">
  @once
    @push('styles')
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    @endpush
  @endonce

  @once
    @push('scripts')
      <script>
        // Evita conflicto: Alpine inicia después de Livewire
        window.deferLoadingAlpine = (alpineInit) => {
          document.addEventListener('livewire:init', alpineInit)
        }
      </script>
      <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @endpush
  @endonce

  {{-- ====== ESTILOS AUXILIARES DEL ÁRBOL ====== --}}
  <style>
    .sap-tree-row { position: relative; }
    .sap-indent { position: relative; height: 100%; display: inline-block; }
    .sap-indent::before {
      content: ""; position: absolute; top: -14px; bottom: -14px; left: 9px;
      border-left: 1px dashed rgba(107,114,128,.25);
    }
    .sap-connector {
      width: 18px; height: 18px; border-bottom: 1px dashed rgba(107,114,128,.25);
      margin-right: .25rem;
    }
    [x-cloak] { display: none !important; }
  </style>

  {{-- ====== CARD CONTENEDOR ====== --}}
  <div class="rounded-2xl shadow-xl border border-gray-200 dark:border-gray-800 overflow-hidden bg-white dark:bg-gray-900">

    {{-- ====== HEADER ====== --}}
    <header class="px-4 md:px-6 py-4 flex items-center justify-between bg-gradient-to-r from-indigo-600 to-violet-600 text-white">
      <div class="flex items-center gap-3">
        <span class="inline-grid place-items-center w-10 h-10 rounded-xl bg-white/20">
          <i class="fa-solid fa-book-open"></i>
        </span>
        <div>
          <h1 class="text-lg md:text-xl font-bold leading-none">Plan de Cuentas</h1>
          <p class="text-white/80 text-xs mt-1">Explora, filtra y administra tus cuentas contables</p>
        </div>
      </div>

      <div class="hidden sm:flex items-center gap-2">
        <button type="button"
                class="px-3 py-1.5 text-xs rounded-lg bg-white/10 hover:bg-white/20 backdrop-blur border border-white/20"
                wire:click="expandAll">
          <i class="fa-solid fa-arrows-to-circle mr-1"></i> Expandir
        </button>
        <button type="button"
                class="px-3 py-1.5 text-xs rounded-lg bg-white/10 hover:bg-white/20 backdrop-blur border border-white/20"
                wire:click="collapseAll">
          <i class="fa-solid fa-compress mr-1"></i> Colapsar
        </button>
        <button type="button"
                wire:click="openCreate(@js($selectedId))"
                class="px-3 py-1.5 text-xs rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white">
          <i class="fa-solid fa-plus mr-1"></i> Nueva
        </button>
      </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-12">
      {{-- ====== LADO IZQUIERDO: FICHA ====== --}}
      <aside class="lg:col-span-3 p-4 border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-900/40">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-3">Detalle de cuenta</h3>

        <div class="space-y-3 text-sm">
          <label class="block">
            <span class="text-[11px] text-gray-500">Cuenta de mayor</span>
            <input type="text" class="w-full h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3"
                   wire:model="f_codigo" readonly>
          </label>

          <label class="block">
            <span class="text-[11px] text-gray-500">Nombre</span>
            <input type="text" class="w-full h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3"
                   wire:model="f_nombre" readonly>
          </label>

          <label class="block">
            <span class="text-[11px] text-gray-500">Moneda</span>
            <input type="text" class="w-full h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3"
                   wire:model="f_moneda" readonly>
          </label>

          <div class="grid grid-cols-2 gap-2">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-800 px-3 py-2">
              <span class="text-[11px] text-gray-500">Requiere tercero</span>
              <input type="checkbox" class="h-4 w-4" wire:model="f_requiere_tercero" disabled>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-800 px-3 py-2">
              <span class="text-[11px] text-gray-500">Cuenta activa</span>
              <input type="checkbox" class="h-4 w-4" wire:model="f_cuenta_activa" disabled>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2">
            <label class="block">
              <span class="text-[11px] text-gray-500">Nivel</span>
              <input type="number" class="w-full h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3"
                     wire:model="f_nivel" readonly>
            </label>
            <div class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-800 px-3">
              <span class="text-[11px] text-gray-500">Título</span>
              <input type="checkbox" class="h-4 w-4 my-2" wire:model="f_titulo" disabled>
            </div>
          </div>

          <div class="flex gap-2 pt-1 sm:hidden">
            <button type="button" wire:click="expandAll"
                    class="flex-1 px-3 py-1.5 text-xs rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100">
              Expandir
            </button>
            <button type="button" wire:click="collapseAll"
                    class="flex-1 px-3 py-1.5 text-xs rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100">
              Colapsar
            </button>
          </div>
        </div>
      </aside>

      {{-- ====== LADO DERECHO: FILTROS + ÁRBOL ====== --}}
      <main class="lg:col-span-9">
        {{-- Filtros sticky --}}
        <div class="sticky top-0 z-10 bg-white/80 dark:bg-gray-900/80 backdrop-blur border-b border-gray-200 dark:border-gray-800 px-4 md:px-6">
          <div class="flex flex-wrap items-end gap-3 py-3">
            <label class="block">
              <span class="block text-[11px] text-gray-500">Buscar</span>
              <input type="text" wire:model.debounce.400ms="q" placeholder="Código o nombre…"
                     class="w-64 h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3">
            </label>

            <label class="block">
              <span class="block text-[11px] text-gray-500">Nivel</span>
              <select wire:model.live="nivelMax"
                      class="w-28 h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3">
                <option value="">Todos</option>
                @for($i=1;$i<=10;$i++)
                  <option value="{{ $i }}">{{ $i }}</option>
                @endfor
              </select>
            </label>

            <div class="block">
              <span class="block text-[11px] text-gray-500 mb-1">Naturaleza</span>
              <div class="inline-flex rounded-xl overflow-hidden border border-gray-300 dark:border-gray-700">
                <button type="button"
                        wire:click="setNaturaleza('TODAS')"
                        class="px-3 py-1.5 text-xs {{ $naturaleza==='TODAS' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200' }}">
                  Todas
                </button>
                <button type="button"
                        wire:click="setNaturaleza('ACTIVOS')"
                        class="px-3 py-1.5 text-xs border-l border-gray-300 dark:border-gray-700 {{ $naturaleza==='ACTIVOS' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200' }}">
                  Activos
                </button>
                <button type="button"
                        wire:click="setNaturaleza('PASIVOS')"
                        class="px-3 py-1.5 text-xs border-l border-gray-300 dark:border-gray-700 {{ $naturaleza==='PASIVOS' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200' }}">
                  Pasivos
                </button>
                <button type="button"
                        wire:click="setNaturaleza('PATRIMONIO')"
                        class="px-3 py-1.5 text-xs border-l border-gray-300 dark:border-gray-700 {{ $naturaleza==='PATRIMONIO' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200' }}">
                  Patrimonio
                </button>
                <button type="button"
                        wire:click="setNaturaleza('INGRESOS')"
                        class="px-3 py-1.5 text-xs border-l border-gray-300 dark:border-gray-700 {{ $naturaleza==='INGRESOS' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200' }}">
                  Ingresos
                </button>
                <button type="button"
                        wire:click="setNaturaleza('COSTOS')"
                        class="px-3 py-1.5 text-xs border-l border-gray-300 dark:border-gray-700 {{ $naturaleza==='COSTOS' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200' }}">
                  Costos
                </button>
                <button type="button"
                        wire:click="setNaturaleza('GASTOS')"
                        class="px-3 py-1.5 text-xs border-l border-gray-300 dark:border-gray-700 {{ $naturaleza==='GASTOS' ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200' }}">
                  Gastos
                </button>
              </div>
            </div>

            {{-- Filtro factura --}}
            <div class="block">
              <span class="block text-[11px] text-gray-500 mb-1">Filtro factura</span>
              <div class="flex items-center gap-2">
                <button type="button"
                        wire:click="$toggle('soloCuentasMovidas')"
                        class="px-3 py-1.5 text-xs rounded-lg
                               {{ $soloCuentasMovidas ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100' }}">
                  Solo cuentas movidas
                </button>

                <input type="number" min="1" placeholder="Factura ID"
                       wire:model.lazy="factura_id"
                       class="w-28 h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-2 text-xs">

                <span class="text-xs text-gray-400">o</span>

                <input type="text" placeholder="Prefijo" wire:model.lazy="factura_prefijo"
                       class="w-20 h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-2 text-xs">
                <input type="number" min="1" placeholder="Número" wire:model.lazy="factura_numero"
                       class="w-24 h-9 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-2 text-xs">

                <button type="button" wire:click="limpiarFiltroFactura"
                        class="px-2 py-1.5 text-xs rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-700">
                  Limpiar
                </button>
              </div>
            </div>

            <div class="ml-auto text-[11px] text-gray-500 dark:text-gray-400">
              Nivel actual: <span class="font-semibold">{{ $nivelMax ?? 'Todos' }}</span>
            </div>
          </div>
        </div>

        {{-- Tabla/árbol --}}
        <section class="p-4">
          <div class="border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden relative">
            <div class="bg-slate-100/70 dark:bg-gray-800 px-3 py-2 text-[12px] font-medium text-gray-700 dark:text-gray-300 flex sticky top-[56px]">
              <div class="w-44">Cuenta</div>
              <div class="flex-1">Descripción</div>
              <div class="w-28">Naturaleza</div>
              <div class="w-16 text-center">Activa</div>
              <div class="w-40 text-right pr-2">Saldos (ant / Δ / desp)</div>
            </div>

            <div class="max-h-[65vh] overflow-auto text-sm">
              @forelse($items as $row)
                @php
                  $expanded = in_array($row->id, $this->expandidos ?? []);
                  $hasKids  = $row->tiene_hijos ?? $row->hijos()->exists();
                  $indent   = max(0, ((int)($row->nivel_visual ?? $row->nivel) - 1) * 18);
                @endphp

                <div class="sap-tree-row flex items-center px-3 py-2 border-t border-gray-100 dark:border-gray-800 backdrop-blur-sm
                            odd:bg-white/60 dark:odd:bg-gray-900/60 hover:bg-slate-50 dark:hover:bg-gray-800/40
                            {{ $selectedId===$row->id ? 'ring-1 ring-inset ring-indigo-500/40 bg-indigo-50/60 dark:bg-indigo-900/20' : '' }}">
                  {{-- Código --}}
                  <div class="w-44 font-mono text:[13px] text-gray-800 dark:text-gray-100">{{ $row->codigo }}</div>

                  {{-- Nombre + árbol --}}
                  <div class="flex-1 flex items-center">
                    <span class="sap-indent" style="width: {{ $indent }}px"></span>

                    @if($hasKids)
                      <button type="button" wire:click="toggle({{ $row->id }})"
                              class="mr-1 inline-flex h-5 w-5 items-center justify-center rounded border border-gray-300 dark:border-gray-700
                                     bg-white/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800"
                              aria-label="{{ $expanded ? 'Colapsar' : 'Expandir' }}">
                        <i class="fa-solid fa-caret-{{ $expanded ? 'down' : 'right' }}"></i>
                      </button>
                    @else
                      <span class="sap-connector"></span>
                    @endif

                    <i class="fa-regular {{ $row->titulo ? 'fa-folder' : 'fa-file-lines' }} mr-2 text-sky-700 dark:text-sky-400"></i>

                    <button type="button" wire:click="select({{ $row->id }})"
                            class="text-left flex-1 truncate {{ $row->titulo ? 'italic text-gray-700 dark:text-gray-300' : 'text-gray-900 dark:text-gray-100' }}">
                      {{ $row->nombre }}
                      @if($row->titulo)
                        <span class="ml-2 text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Título</span>
                      @endif
                    </button>

                    {{-- Acciones --}}
                    <div class="ml-2 hidden sm:flex items-center gap-1">
                      <button type="button" title="Añadir hija" wire:click="openCreate({{ $row->id }})"
                              class="px-2 py-1 text-[11px] rounded bg-violet-600 hover:bg-violet-700 text-white">Hija</button>
                      <button type="button" title="Editar" wire:click="openEdit({{ $row->id }})"
                              class="px-2 py-1 text-[11px] rounded bg-indigo-600 hover:bg-indigo-700 text-white">Editar</button>
                    </div>
                  </div>

                  {{-- Naturaleza --}}
                  <div class="w-28">
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
                  <div class="w-16 text-center">
                    @if($row->cuenta_activa)
                      <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    @else
                      <i class="fa-regular fa-circle text-gray-400"></i>
                    @endif
                  </div>

                  {{-- Saldos (antes / delta / después) --}}
                  <div class="w-40 text-right pr-2 font-mono">
                    <div class="text-[11px] text-gray-500">
                      ant: ${{ number_format($row->saldo_antes ?? $row->saldo, 2) }}
                    </div>
                    <div class="text-[11px] {{ ($row->saldo_delta ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                      {{ ($row->saldo_delta ?? 0) >= 0 ? '+' : '' }}${{ number_format($row->saldo_delta ?? 0, 2) }}
                    </div>
                    <div class="font-semibold">
                      ${{ number_format($row->saldo_despues ?? $row->saldo, 2) }}
                    </div>
                  </div>
                </div>
              @empty
                <div class="px-6 py-10 text-center">
                  <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-400 mb-3">
                    <i class="fa-regular fa-folder-open"></i>
                  </div>
                  <p class="text-sm text-gray-500 dark:text-gray-400">Sin resultados para el filtro aplicado.</p>
                </div>
              @endforelse

              {{-- Loading overlay --}}
              <div wire:loading.flex class="absolute inset-0 bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm items-center justify-center">
                <div class="animate-spin h-6 w-6 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  {{-- ====== MODAL CREAR/EDITAR (siempre en DOM) ====== --}}
  <div x-data="{ open: @entangle('showModal').live }"
       x-show="open"
       x-cloak
       class="fixed inset-0 z-[100]"
       wire:ignore.self
       @keydown.escape.window="open=false">

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" @click="open=false"></div>

    {{-- Dialog --}}
    <div class="relative z-10 w-full max-w-2xl mx-auto mt-12 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
          {{ $editingId ? 'Editar cuenta' : 'Nueva cuenta' }}
        </h3>
        <button class="text-gray-500 hover:text-gray-700" @click="open=false" type="button">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form wire:submit.prevent="save" class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Padre --}}
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Cuenta padre</label>
          <select wire:model.live="padre_id"
                  class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                         shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            <option value="">— Sin padre (raíz) —</option>
            @foreach($posiblesPadres as $p)
              <option value="{{ $p->id }}">{{ $p->codigo }} — {{ $p->nombre }}</option>
            @endforeach
          </select>
          @error('padre_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          @if(!$editingId)
            <p class="mt-1 text-[11px] text-gray-500">Al cambiar el padre, se <strong>sugerirá el código</strong> y se <strong>heredarán</strong> naturaleza y moneda.</p>
          @endif
        </div>

        {{-- Código --}}
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Código</label>
          <input type="text" wire:model.defer="codigo" placeholder="11050501"
                 class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                        shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 @error('codigo') border-red-500 @enderror">
          @error('codigo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Nombre --}}
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Nombre</label>
          <input type="text" wire:model.defer="nombre" placeholder="CAJA GENERAL"
                 class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                        shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 @error('nombre') border-red-500 @enderror">
          @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Naturaleza --}}
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Naturaleza</label>
          <select wire:model.live="naturaleza_form"
                  class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                         shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            <option value="ACTIVOS">ACTIVOS</option>
            <option value="PASIVOS">PASIVOS</option>
            <option value="PATRIMONIO">PATRIMONIO</option>
            <option value="INGRESOS">INGRESOS</option>
            <option value="COSTOS">COSTOS</option>
            <option value="GASTOS">GASTOS</option>
            <option value="OTROS_INGRESOS">OTROS_INGRESOS</option>
            <option value="OTROS_GASTOS">OTROS_GASTOS</option>
          </select>
        </div>

        {{-- Flags --}}
        <div class="grid grid-cols-2 gap-3 md:col-span-2">
          <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="cuenta_activa" class="rounded"><span class="text-sm">Activa</span></label>
          <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="titulo" class="rounded"><span class="text-sm">Título (no imputable)</span></label>
          <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="requiere_tercero" class="rounded"><span class="text-sm">Requiere tercero</span></label>
          <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.live="confidencial" class="rounded"><span class="text-sm">Confidencial</span></label>
        </div>

        {{-- Moneda / Saldo --}}
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Moneda</label>
          <input type="text" wire:model.defer="moneda"
                 class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                        shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Saldo inicial</label>
          <input type="number" step="0.01" min="0" wire:model.defer="saldo"
                 class="w-full h-10 text-right rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white
                        shadow-sm px-3 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
        </div>

        {{-- Dimensiones --}}
        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-4 gap-3">
          @for($d=1;$d<=4;$d++)
            <div>
              <label class="block text-[11px] font-medium text-gray-600 dark:text-gray-300 mb-1">Dimensión {{ $d }}</label>
              <input type="text" wire:model.defer="{{ 'dimension'.$d }}"
                     class="w-full h-10 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white px-3">
            </div>
          @endfor
        </div>

        {{-- Acciones modal --}}
        <div class="md:col-span-2 flex items-center justify-end gap-2 mt-2">
          <button type="button" @click="open=false"
                  class="px-4 h-10 rounded-xl bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600">
            Cancelar
          </button>
          <button type="submit"
                  class="px-4 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white">
            {{ $editingId ? 'Actualizar' : 'Guardar' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
