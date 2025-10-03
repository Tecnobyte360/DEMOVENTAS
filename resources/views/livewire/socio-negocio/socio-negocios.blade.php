{{-- resources/views/livewire/socio-negocio/socio-negocios.blade.php --}}
@once
  @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Igual que en factura-form: evita choque Alpine ↔ Livewire
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" defer></script>
  @endpush
@endonce

<div
  x-data="{
    showCreateModal:false,
    showEditModal:false,
    showImportModal:false,
    showPedidosModal:false,
    showDireccionesModal: @entangle('showDireccionesModal').live,
    tab:'list'
  }"
  x-init="
    window.addEventListener('abrir-modal-pedidos', () => { showPedidosModal = true; tab = 'list' });
  "
  wire:init="loadListas"
  class="p-6 md:p-8 space-y-6"
>

  {{-- ================= HERO ================= --}}
  @php $sociosTotal = collect($clientes)->concat($proveedores)->count(); @endphp
  <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-violet-600 via-indigo-600 to-blue-600 text-white shadow-2xl">
    <div class="px-6 md:px-8 py-8 md:py-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div class="space-y-1">
        <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight flex items-center gap-3">
          <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-white/15 backdrop-blur">
            <i class="fas fa-users text-2xl"></i>
          </span>
          Socios de Negocio
        </h1>
        <p class="text-sm text-white/80">Clientes y Proveedores en una sola lista.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/20 font-semibold text-xs md:text-sm">
          <i class="fa-solid fa-list"></i> Total: {{ $sociosTotal }}
        </span>
        <button @click="showCreateModal=true" class="h-11 px-4 rounded-2xl bg-white/90 hover:bg-white text-violet-700 font-semibold shadow">
          <i class="fas fa-plus mr-2"></i> Nuevo socio
        </button>
       <button @click="showImportModal=true" class="h-11 px-4 rounded-2xl bg-white/20 hover:bg-white/25 text-white font-semibold shadow ring-1 ring-white/30">
          <i class="fas fa-file-import mr-2"></i> Importar
        </button>
      </div>
    </div>
  </section>

  {{-- ================= FILTROS ================= --}}
  <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 shadow-2xl overflow-hidden">
    <header class="px-6 md:px-8 py-4 border-b border-gray-100 dark:border-gray-800">
      <h2 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
        <span class="inline-grid place-items-center w-8 h-8 md:w-9 md:h-9 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">
          <i class="fa-solid fa-magnifying-glass text-[13px]"></i>
        </span>
        Filtros
      </h2>
    </header>

    <div class="px-6 md:px-8 py-6 grid grid-cols-1 lg:grid-cols-12 gap-4">
      {{-- Selector puntual (TomSelect) --}}
      <div class="lg:col-span-6">
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Ir a un socio puntual</label>
        <div wire:ignore x-data x-init="
          const t = new TomSelect($refs.selSocio,{
            placeholder:'Selecciona un socio…',
            allowEmptyOption:true, create:false,
            onChange:v => @this.set('socioNegocioId', v)
          });
          Livewire.hook('message.processed',()=>t.refreshOptions(false));
        ">
          <select x-ref="selSocio" class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
            <option value=''>— Todos —</option>
            @foreach($clientesFiltrados as $c)
              <option value="{{ $c['id'] }}">{{ $c['razon_social'] }} ({{ $c['nit'] }})</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- Buscador libre --}}
      <div class="lg:col-span-6">
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 mb-2">Buscar (razón social, NIT, dirección)</label>
        <div class="flex gap-2">
          <input type="text" wire:model.debounce.500ms="buscador"
                 placeholder="Escribe para filtrar…"
                 class="w-full h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white">
          @if($socioNegocioId || $buscador)
            <button type="button"
                    wire:click="$set('socioNegocioId', null); $set('buscador',''); loadListas();"
                    class="h-12 px-4 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
              Limpiar
            </button>
          @endif
        </div>
      </div>
    </div>
  </section>

  {{-- ================= LISTA (Cards móvil / Tabla desktop) ================= --}}
  @php
    $socios = collect($clientes)->concat($proveedores);
  @endphp

  <section class="rounded-3xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 shadow-2xl overflow-hidden">
    <header class="px-6 md:px-8 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <span class="inline-grid place-items-center w-9 h-9 rounded-xl bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">
          <i class="fas fa-rectangle-list"></i>
        </span>
        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Todos los socios</h3>
      </div>
      <span class="text-xs px-2 py-1 rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300">
        {{ $socios->count() }}
      </span>
    </header>

    <div class="p-4 md:p-6">
      {{-- ===== Móvil (cards) ===== --}}
      <div class="md:hidden space-y-3">
        @forelse($socios as $s)
          @php
            $esCliente = strtoupper(trim($s->tipo ?? '')) === 'C';
            // Soporta ambos esquemas: 'condiciones_pago_efectivas' (nuevo) o 'condiciones_pago' (legado)
            $cp = is_array($s->condiciones_pago_efectivas ?? null)
                  ? $s->condiciones_pago_efectivas
                  : (is_array($s->condiciones_pago ?? null) ? $s->condiciones_pago : []);
          @endphp
          <article class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 bg-white dark:bg-gray-900">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center justify-center text-[10px] px-2 py-0.5 rounded-full
                               {{ $esCliente ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700' }}">
                    {{ $esCliente ? 'Cliente' : 'Proveedor' }}
                  </span>
                  <h4 class="text-base font-bold">{{ ucwords(strtolower($s->razon_social)) }}</h4>
                </div>
                <div class="text-xs text-gray-500">NIT: {{ $s->nit ?? '—' }}</div>
                <div class="text-xs text-gray-500">{{ ucwords(strtolower($s->direccion ?? '')) }}</div>

                <div class="mt-2 flex flex-wrap gap-1">
                  @if(data_get($s,'cuentas.cuentaCxc.codigo'))
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                      CxC: {{ data_get($s,'cuentas.cuentaCxc.codigo') }}
                    </span>
                  @endif
                  @if(data_get($s,'cuentas.cuentaIva.codigo'))
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                      IVA: {{ data_get($s,'cuentas.cuentaIva.codigo') }}
                    </span>
                  @endif

                  {{-- Condiciones de pago (compact) --}}
                  @if(!empty($cp))
                    @php
                      $tipo = strtoupper((string)($cp['tipo'] ?? $cp['tipo_credito'] ?? ''));
                    @endphp
                    @if($tipo)
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">{{ $tipo }}</span>
                    @endif
                    @if(!empty($cp['plazo_dias']))
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ $cp['plazo_dias'] }} días</span>
                    @endif
                    @if(array_key_exists('interes_mora_pct',$cp) && $cp['interes_mora_pct'] !== null && $cp['interes_mora_pct']!=='')
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">
                        Mora: {{ number_format((float)$cp['interes_mora_pct'],2) }}%
                      </span>
                    @endif
                    @if(!empty($cp['lista_precios']))
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-teal-100 text-teal-700">
                        Lista: {{ $cp['lista_precios'] }}
                      </span>
                    @endif
                  @endif

                  @if(optional($s->dirEntregaPrincipal)->direccion)
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">
                      Entrega: {{ \Illuminate\Support\Str::limit(optional($s->dirEntregaPrincipal)->direccion, 30) }}
                    </span>
                  @endif
                </div>
              </div>

              <div class="text-right">
                <div class="text-[11px] text-gray-500">Saldo</div>
                <div class="text-sm font-extrabold {{ $esCliente ? 'text-emerald-600' : 'text-sky-600' }}">
                  ${{ number_format($s->saldoPendiente ?? 0, 2, ',', '.') }}
                </div>
              </div>
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
              <button wire:click="editsocio({{ $s->id }})" @click="showEditModal=true" class="text-blue-600 hover:text-blue-800 text-sm">
                <i class="fas fa-pen-to-square mr-1"></i> Editar
              </button>
              <button wire:click="abrirCuentasModal({{ $s->id }})" class="text-violet-600 hover:text-violet-800 text-sm">
                <i class="fa-solid fa-book mr-1"></i> Cuentas
              </button>
              @if($esCliente)
                <button wire:click="mostrarPedidos({{ $s->id }})" @click="showPedidosModal=true" class="text-amber-600 hover:text-amber-800 text-sm">
                  <i class="fas fa-file-invoice mr-1"></i> Pedidos
                </button>
              @endif
              <button wire:click="abrirDirecciones({{ $s->id }})" class="text-sky-600 hover:text-sky-800 text-sm">
                <i class="fa-solid fa-location-dot mr-1"></i> Direcciones
              </button>
            </div>
          </article>
        @empty
          <div class="rounded-xl border border-dashed p-6 text-center text-gray-500">Sin resultados</div>
        @endforelse
      </div>

      {{-- ===== Desktop (tabla) ===== --}}
      <div class="hidden md:block overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
            <tr>
              <th class="p-3 text-left">ID</th>
              <th class="p-3 text-left">Tipo</th>
              <th class="p-3 text-left">Razón Social</th>
              <th class="p-3 text-left">NIT</th>
              <th class="p-3 text-left">Dirección</th>
              <th class="p-3 text-center">Saldo</th>
              <th class="p-3 text-left">Condiciones de pago</th>
              <th class="p-3 text-center">Cuentas</th>
              <th class="p-3 text-center">Pedidos</th>
              <th class="p-3 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($socios as $socio)
              @php
                $esCliente = strtoupper(trim($socio->tipo ?? '')) === 'C';
                $cp = is_array($socio->condiciones_pago_efectivas ?? null)
                      ? $socio->condiciones_pago_efectivas
                      : (is_array($socio->condiciones_pago ?? null) ? $socio->condiciones_pago : []);
              @endphp
              <tr class="hover:bg-violet-50/50 dark:hover:bg-gray-800 transition">
                <td class="p-3">{{ $socio->id }}</td>
                <td class="p-3">
                  <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full
                               {{ $esCliente ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700' }}">
                    <i class="fa-solid {{ $esCliente ? 'fa-handshake' : 'fa-truck-field' }}"></i>
                    {{ $esCliente ? 'Cliente' : 'Proveedor' }}
                  </span>
                </td>
                <td class="p-3">
                  <div class="font-semibold">{{ ucwords(strtolower($socio->razon_social)) }}</div>
                  <div class="mt-1 flex flex-wrap gap-1">
                    @if(data_get($socio, 'cuentas.cuentaCxc.codigo'))
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                        CxC: {{ data_get($socio, 'cuentas.cuentaCxc.codigo') }}
                      </span>
                    @endif
                    @if(data_get($socio, 'cuentas.cuentaIva.codigo'))
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                        IVA: {{ data_get($socio, 'cuentas.cuentaIva.codigo') }}
                      </span>
                    @endif
                    @if(optional($socio->dirEntregaPrincipal)->direccion)
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">
                        Entrega: {{ \Illuminate\Support\Str::limit(optional($socio->dirEntregaPrincipal)->direccion, 30) }}
                      </span>
                    @endif
                  </div>
                </td>
                <td class="p-3">{{ $socio->nit }}</td>
                <td class="p-3">{{ ucwords(strtolower($socio->direccion ?? '')) }}</td>
                <td class="p-3 text-center font-bold {{ $esCliente ? 'text-emerald-600' : 'text-sky-600' }}">
                  ${{ number_format($socio->saldoPendiente ?? 0, 2, ',', '.') }}
                </td>

                {{-- Condiciones de pago --}}
                <td class="p-3">
                  <div class="flex flex-wrap gap-1">
                    @php $tipo = strtoupper((string)($cp['tipo'] ?? $cp['tipo_credito'] ?? '')); @endphp
                    @if($tipo)
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">{{ $tipo }}</span>
                    @endif
                    @if(!empty($cp['plazo_dias']))
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ $cp['plazo_dias'] }} días</span>
                    @endif
                    @if(array_key_exists('interes_mora_pct',$cp) && $cp['interes_mora_pct'] !== null && $cp['interes_mora_pct']!=='')
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-rose-100 text-rose-700">
                        Mora: {{ number_format((float)$cp['interes_mora_pct'],2) }}%
                      </span>
                    @endif
                    @if(!empty($cp['lista_precios']))
                      <span class="text-[10px] px-2 py-0.5 rounded-full bg-teal-100 text-teal-700">
                        Lista: {{ $cp['lista_precios'] }}
                      </span>
                    @endif
                  </div>
                </td>

                <td class="p-3 text-center">
                  <button wire:click="abrirCuentasModal({{ $socio->id }})"
                          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <i class="fa-solid fa-book"></i> Editar
                  </button>
                </td>

                <td class="p-3 text-center">
                  @if($esCliente)
                    <button wire:click="mostrarPedidos({{ $socio->id }})" @click="showPedidosModal=true"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border-2 border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                      <i class="fas fa-file-invoice"></i> Ver
                    </button>
                  @else
                    <span class="text-[11px] text-gray-400">—</span>
                  @endif
                </td>

                <td class="p-3 text-center space-x-2">
                  <button wire:click="editsocio({{ $socio->id }})" @click="showEditModal=true" class="text-blue-600 hover:text-blue-800" title="Editar">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button wire:click="delete({{ $socio->id }})" class="text-red-600 hover:text-red-800" title="Eliminar">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="10" class="p-6 text-center text-gray-500 dark:text-gray-400">Sin resultados</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </section>

  {{-- ================= MODAL: Cuentas contables ================= --}}
  <div class="fixed inset-0 z-50 grid place-items-center bg-black/50 backdrop-blur-sm"
       x-show="$wire.showCuentasModal" x-cloak
       @keydown.escape.window="$wire.cerrarCuentasModal()"
       @click.self="$wire.cerrarCuentasModal()">
    <div class="w-full max-w-3xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between sticky top-0 bg-inherit">
        <h3 class="text-lg font-bold flex items-center gap-2">
          <i class="fa-solid fa-book text-violet-600"></i> Asociar cuentas contables
        </h3>
        <button class="text-red-600" @click="$wire.cerrarCuentasModal()"><i class="fas fa-times-circle"></i></button>
      </div>

      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          @php
            $campos = [
              ['prop'=>'cuenta_cxc_id',        'label'=>'CxC (Clientes)'],
              ['prop'=>'cuenta_anticipos_id',  'label'=>'Anticipos'],
              ['prop'=>'cuenta_descuentos_id', 'label'=>'Descuentos'],
              ['prop'=>'cuenta_ret_fuente_id', 'label'=>'Retención en la fuente'],
              ['prop'=>'cuenta_ret_ica_id',    'label'=>'Retención ICA'],
              ['prop'=>'cuenta_iva_id',        'label'=>'IVA'],
            ];
          @endphp

          @foreach($campos as $c)
            <div wire:ignore x-data x-init="
              const opts = @js($cuentasOptions);
              const inst = new TomSelect($refs.sel{{ $c['prop'] }},{
                valueField:'id', labelField:'label', searchField:'label',
                options:opts, allowEmptyOption:true, create:false, placeholder:'Seleccione...',
                onChange:v => $wire.set('{{ $c['prop'] }}', v || null)
              });
              $watch('$wire.{{ $c['prop'] }}', (v)=>{ if(!v){ inst.clear(); } else { inst.setValue(v, true); }});
            ">
              <label class="text-xs text-gray-500 mb-1 block">{{ $c['label'] }}</label>
              <select x-ref="sel{{ $c['prop'] }}">
                <option value="">— Ninguna —</option>
              </select>
              @error($c['prop']) <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>
          @endforeach
        </div>
      </div>

      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/50 flex justify-end gap-3 sticky bottom-0">
        <button class="px-4 py-2 rounded-2xl border-2 border-gray-200 dark:border-gray-700"
                @click="$wire.cerrarCuentasModal()">Cancelar</button>
        <button class="px-4 py-2 rounded-2xl bg-violet-600 text-white hover:bg-violet-700"
                wire:click="guardarCuentas">Guardar</button>
      </div>
    </div>
  </div>

  {{-- ================= MODAL: Crear ================= --}}
  <div class="fixed inset-0 z-50 grid place-items-center bg-black/50 backdrop-blur-sm" x-show="showCreateModal" x-cloak @keydown.escape.window="showCreateModal=false">
    <div class="w-full max-w-4xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-auto max-h-[90vh]">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="text-lg font-bold">Crear socio</h3>
        <button class="text-red-600" @click="showCreateModal=false"><i class="fas fa-times-circle"></i></button>
      </div>
      <div class="p-6">@livewire('socio-negocio.create')</div>
    </div>
  </div>

  {{-- ================= MODAL: Importar ================= --}}
  <div class="fixed inset-0 z-50 grid place-items-center bg-black/50 backdrop-blur-sm" x-show="showImportModal" x-cloak @keydown.escape.window="showImportModal=false">
    <div class="w-full max-w-3xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-auto max-h-[85vh]">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="text-lg font-bold">Importar socios de negocio</h3>
        <button class="text-red-600" @click="showImportModal=false"><i class="fas fa-times-circle"></i></button>
      </div>
      <div class="p-6">@livewire('socio-negocio.importar-excel')</div>
    </div>
  </div>

  {{-- ================= MODAL: Editar ================= --}}
  <div class="fixed inset-0 z-50 grid place-items-center bg-black/50 backdrop-blur-sm" x-show="showEditModal" x-cloak @keydown.escape.window="showEditModal=false">
    <div class="w-full max-w-4xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-auto max-h-[90vh]">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="text-lg font-bold">Editar socio</h3>
        <button class="text-red-600" @click="showEditModal=false"><i class="fas fa-times-circle"></i></button>
      </div>
      <div class="p-6">@livewire('socio-negocio.edit')</div>
    </div>
  </div>

  {{-- ================= MODAL: Pedidos ================= --}}
  <div class="fixed inset-0 z-50 grid place-items-center bg-black/60 backdrop-blur-sm"
       x-show="showPedidosModal" x-cloak
       @keydown.escape.window="showPedidosModal=false; @this.cerrarPedidosModal()"
       @click.self="showPedidosModal=false; @this.cerrarPedidosModal()">
    <div class="w-full max-w-4xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-auto max-h-[85vh]">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
        <h3 class="text-lg font-bold flex items-center gap-2">
          <i class="fas fa-clipboard-list text-violet-500"></i> Pedidos del socio
        </h3>
        <button class="text-red-600" @click="showPedidosModal=false; @this.cerrarPedidosModal()">
          <i class="fas fa-times-circle"></i>
        </button>
      </div>

      <div class="p-6">
        <div x-show="tab==='list'">
          @if(empty($pedidosSocio))
            <p class="text-gray-500 dark:text-gray-400 italic">No hay pedidos a crédito con saldo pendiente.</p>
          @else
            <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
                  <tr>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Fecha</th>
                    <th class="px-4 py-2">Ruta</th>
                    <th class="px-4 py-2">Conductor</th>
                    <th class="px-4 py-2 text-right">Total pendiente</th>
                    <th class="px-4 py-2 text-center">Tipo de pago</th>
                    <th class="px-4 py-2 text-center">Detalle</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  @foreach($pedidosSocio as $p)
                    <tr>
                      <td class="px-4 py-2">{{ $p['id'] }}</td>
                      <td class="px-4 py-2">{{ $p['fecha'] }}</td>
                      <td class="px-4 py-2">{{ $p['ruta'] }}</td>
                      <td class="px-4 py-2">{{ $p['usuario'] }}</td>
                      <td class="px-4 py-2 text-right font-semibold dark:text-emerald-400">${{ $p['total'] }}</td>
                      <td class="px-4 py-2 text-center capitalize">{{ $p['tipo_pago'] }}</td>
                      <td class="px-4 py-2 text-center">
                        <button wire:click="mostrarDetallePedido({{ $p['id'] }})" @click="tab='detail'"
                                class="text-xs px-2 py-1 rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200">
                          Ver detalle
                        </button>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="7" class="px-4 py-2 text-right text-sm">
                      <b>Total general crédito:</b>
                      <span class="text-emerald-600 dark:text-emerald-400">
                        ${{ number_format(collect($pedidosSocio)->sum('total_raw'),2,',','.') }}
                      </span>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          @endif
        </div>

        <div x-show="tab==='detail'" x-cloak class="space-y-4">
          @if(!$mostrarDetalleModal)
            <p class="text-gray-500 dark:text-gray-400 italic">Selecciona un pedido para ver el detalle.</p>
          @else
            <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-700">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs tracking-wider">
                  <tr>
                    <th class="px-3 py-2 text-left">Producto</th>
                    <th class="px-3 py-2 text-center">Cantidad</th>
                    <th class="px-3 py-2 text-right">Precio</th>
                    <th class="px-3 py-2 text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  @foreach($detallesPedido as $d)
                    <tr>
                      <td class="px-3 py-2">{{ $d['producto'] }}</td>
                      <td class="px-3 py-2 text-center">{{ $d['cantidad'] }}</td>
                      <td class="px-3 py-2 text-right">${{ $d['precio_unitario'] }}</td>
                      <td class="px-3 py-2 text-right">${{ $d['subtotal'] }}</td>
                    </tr>
                  @endforeach
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4" class="px-3 py-2 text-right font-bold">
                      @php
                        $totalDetalle = collect($detallesPedido)->sum(function($x){
                          $n = preg_replace('/[^0-9,.-]/','', $x['subtotal'] ?? '0');
                          $n = str_replace(['.','.'], ['',''], $n);
                          $n = str_replace([','], ['.'], $n);
                          return (float)$n;
                        });
                      @endphp
                      Total: ${{ number_format($totalDetalle, 2, ',', '.') }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div class="flex justify-between">
              <button class="px-3 py-2 rounded-2xl border-2 border-gray-200 dark:border-gray-700" @click="tab='list'">← Volver</button>
              <button class="px-3 py-2 rounded-2xl bg-violet-600 text-white hover:bg-violet-700"
                      @click="showPedidosModal=false; @this.cerrarPedidosModal()">Cerrar</button>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
