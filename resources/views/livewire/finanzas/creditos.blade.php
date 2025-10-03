@assets
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
@endassets

<div x-data="{ openFilters:false }"
     class="p-4 md:p-6 bg-gradient-to-br from-white via-white to-gray-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950 rounded-2xl shadow-xl space-y-6 md:space-y-8">

    <!-- ENCABEZADO -->
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4 border-b border-gray-200/70 dark:border-gray-800 pb-4">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-600">
                <i class="fas fa-file-invoice-dollar"></i>
            </span>
            <div>
                <h1 class="text-xl md:text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Órdenes de Venta</h1>
                <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">Filtra, revisa y gestiona pagos rápidamente.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button"
                    class="md:hidden inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 bg-white/70 dark:bg-gray-800/70"
                    @click="openFilters=!openFilters">
                <i class="fas fa-sliders-h"></i> Filtros
            </button>
        </div>
    </header>

    <!-- KPIs opcionales -->
    @if(isset($totalOrdenes))
    <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4">
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Órdenes</div>
            <div class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalOrdenes) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Contado</div>
            <div class="text-lg md:text-xl font-bold">{{ number_format($totalContado ?? 0) }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Facturado</div>
            <div class="text-lg md:text-xl font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($totalFacturado ?? 0,0,',','.') }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Crédito</div>
            <div class="text-lg md:text-xl font-bold text-indigo-600 dark:text-indigo-400">${{ number_format($totalValorCredito ?? 0,0,',','.') }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Gastos</div>
            <div class="text-lg md:text-xl font-bold text-rose-600 dark:text-rose-400">${{ number_format($totalGastos ?? 0,0,',','.') }}</div>
        </div>
        <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-3 md:p-4">
            <div class="text-xs text-gray-500 dark:text-gray-400">Adm.</div>
            <div class="text-lg md:text-xl font-bold">${{ number_format($gastosAdministrativos ?? 0,0,',','.') }}</div>
        </div>
    </section>
    @endif

    <!-- FILTROS (con Flatpickr dd/mm/aaaa) -->
    <section
        class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-4 md:p-5 space-y-4"
        :class="{'block': openFilters, 'hidden md:block': !openFilters}">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 md:gap-4">

            <!-- Estado -->
            <div class="flex flex-col">
                <label for="estadoFiltro" class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estado</label>
                <select wire:model="estadoFiltro" id="estadoFiltro"
                        class="rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="todos">Todos</option>
                    <option value="pendiente">Pendientes</option>
                    <option value="parcial">Parciales</option>
                    <option value="pagado">Pagados</option>
                </select>
            </div>

            <!-- Cliente -->
            <div class="flex flex-col">
                <label for="filtroCliente" class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Cliente</label>
                <div class="relative">
                    <input type="text" wire:model.debounce.500ms="filtroCliente" id="filtroCliente"
                           placeholder="Buscar por nombre..."
                           class="w-full rounded-xl text-sm pl-9 pr-8 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                    <button type="button" wire:click="$set('filtroCliente','')"
                            class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>

            <!-- Tipo pago -->
            <div class="flex flex-col">
                <label for="tipoPagoFiltro" class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de Pago</label>
                <select wire:model="tipoPagoFiltro" id="tipoPagoFiltro"
                        class="rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="todos">Todos</option>
                    <option value="credito">Crédito</option>
                    <option value="contado">Contado</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>

            <!-- Conductor -->
            <div class="flex flex-col">
                <label for="conductorFiltro" class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Conductor</label>
                <select wire:model="conductorFiltro" id="conductorFiltro"
                        class="rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="todos">Todos</option>
                    @foreach($conductores as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Fechas (dd/mm/aaaa con altInput) -->
            <div class="grid grid-cols-2 gap-3"
                 x-data="{fpDesde:null, fpHasta:null}"
                 x-init="
                    fpDesde = flatpickr($refs.desde, {
                      dateFormat: 'Y-m-d',
                      altInput: true,
                      altFormat: 'd/m/Y',
                      defaultDate: @js($fechaInicio),
                      onChange: (_sel, iso) => $wire.set('fechaInicio', iso)
                    });
                    fpHasta = flatpickr($refs.hasta, {
                      dateFormat: 'Y-m-d',
                      altInput: true,
                      altFormat: 'd/m/Y',
                      defaultDate: @js($fechaFin),
                      onChange: (_sel, iso) => $wire.set('fechaFin', iso)
                    });
                    Livewire.hook('message.processed', () => {
                      fpDesde && fpDesde.setDate(@js($fechaInicio), true);
                      fpHasta && fpHasta.setDate(@js($fechaFin), true);
                    });
                 ">
                <div class="flex flex-col" wire:ignore>
                    <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Desde</label>
                    <input x-ref="desde" type="text" placeholder="dd/mm/aaaa"
                           class="w-full rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex flex-col" wire:ignore>
                    <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hasta</label>
                    <input x-ref="hasta" type="text" placeholder="dd/mm/aaaa"
                           class="w-full rounded-xl text-sm px-3 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <!-- Botones -->
        <div class="flex flex-col sm:flex-row justify-end gap-2 pt-1">
            <button type="button" wire:click="cargarPedidosCredito"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow">
                <i class="fas fa-search"></i> Buscar
            </button>
            <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
                <i class="fas fa-sync-alt"></i> Limpiar
            </button>
        </div>

        <!-- Chips -->
        <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
            @if(($estadoFiltro ?? 'todos') !== 'todos')
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800">
                    Estado: <b class="ml-1">{{ ucfirst($estadoFiltro) }}</b>
                </span>
            @endif
            @if(($tipoPagoFiltro ?? 'todos') !== 'todos')
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800">
                    Pago: <b class="ml-1">{{ ucfirst($tipoPagoFiltro) }}</b>
                </span>
            @endif
            @if(($conductorFiltro ?? 'todos') !== 'todos')
                @php $cSel = collect($conductores)->firstWhere('id', (int)$conductorFiltro); @endphp
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800">
                    Conductor: <b class="ml-1">{{ $cSel->name ?? '—' }}</b>
                </span>
            @endif
            @if(!empty($fechaInicio) || !empty($fechaFin))
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800">
                    Rango:
                    <b class="ml-1">
                        {{ $fechaInicio ? \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') : '—' }}
                    </b> — <b>
                        {{ $fechaFin ? \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') : '—' }}
                    </b>
                </span>
            @endif
            @if(!empty($filtroCliente))
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-800">
                    Cliente: <b class="ml-1">{{ $filtroCliente }}</b>
                    <button type="button" class="ml-1 text-gray-500 hover:text-gray-700"
                            wire:click="$set('filtroCliente','')"><i class="fas fa-times"></i></button>
                </span>
            @endif
        </div>
    </section>

    <!-- MENSAJES -->
    @if (session()->has('message'))
        <div class="p-3 md:p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl shadow-sm">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="p-3 md:p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- LISTA / TABLA -->
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 overflow-hidden">

        <!-- Skeleton -->
        <div wire:loading.flex class="p-4 md:p-6 gap-3 md:gap-4 flex flex-col">
            @for($i=0; $i<4; $i++)
                <div class="animate-pulse h-16 rounded-xl bg-gray-100 dark:bg-gray-800"></div>
            @endfor
        </div>

        <div wire:loading.remove>
            <!-- Cards móviles -->
            <div class="md:hidden divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($pedidosCredito as $pedido)
                    @php
                        $totalAplicado = $pedido->detalles->sum(fn($d) => $d->cantidad * ($d->precio_unitario));
                        $pagado = $pedido->pagos->sum('monto');
                        $saldo  = $totalAplicado - $pagado;
                        $estado = $pagado == 0 ? 'Pendiente' : ($pagado < $totalAplicado ? 'Parcial' : 'Pagado');
                    @endphp
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">#{{ $pedido->id }} · {{ $pedido->socioNegocio->razon_social }}</div>
                                <div class="text-xs text-gray-500">
                                    <i class="far fa-calendar-alt mr-1"></i>{{ $pedido->fecha->format('d/m/Y') }} ·
                                    <i class="fas fa-route mx-1"></i>{{ $pedido->ruta->ruta ?? 'NO DEFINIDA' }}
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold
                                {{ $estado === 'Pagado' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' :
                                   ($estado === 'Parcial' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' :
                                   'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300') }}">
                                <i class="fas {{ $estado==='Pagado'?'fa-check-circle':($estado==='Parcial'?'fa-hourglass-half':'fa-clock') }}"></i>
                                {{ $estado }}
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                            <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-2">
                                <div class="text-gray-500">Aplicado</div>
                                <div class="font-bold text-right text-gray-900 dark:text-white">
                                    ${{ number_format($totalAplicado,0,',','.') }}
                                </div>
                            </div>
                            <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-2">
                                <div class="text-gray-500">Saldo</div>
                                <div class="font-bold text-right {{ $saldo>0?'text-rose-600 dark:text-rose-400':'text-emerald-600 dark:text-emerald-400' }}">
                                    ${{ number_format($saldo,0,',','.') }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                            <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full
                                {{ $pedido->tipo_pago === 'credito'
                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                    : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">
                                <i class="fas {{ $pedido->tipo_pago==='credito'?'fa-credit-card':'fa-money-bill-wave' }}"></i>
                                {{ ucfirst($pedido->tipo_pago) }}
                            </div>
                            <div class="truncate"><i class="fas fa-user mr-1"></i>{{ $pedido->conductor->name ?? 'Sin asignar' }}</div>
                        </div>

                        <div class="mt-3 flex gap-2">
                            @if($estado !== 'Pagado')
                                <button wire:click="abrirModalPago({{ $pedido->id }})"
                                        class="flex-1 px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">
                                   Pagar
                                </button>
                            @endif
                            <button wire:click="verDetalle({{ $pedido->id }})"
                                    class="flex-1 px-3 py-2 rounded-xl border border-amber-300 text-amber-700 dark:border-amber-700 dark:text-amber-300 text-xs font-semibold hover:bg-amber-50 dark:hover:bg-amber-900/20">
                                Ver Detalle
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-inbox mb-2 text-2xl"></i>
                        <p>No hay resultados con los filtros actuales.</p>
                    </div>
                @endforelse

                <div class="px-4 pb-4">
                    {{ $pedidosCredito->links() }}
                </div>
            </div>

            <!-- Tabla desktop -->
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100/80 dark:bg-gray-800/80 text-gray-800 dark:text-gray-200">
                        <tr class="uppercase text-xs tracking-wider">
                            <th class="px-4 py-3 text-left">#Pedido</th>
                            <th class="px-4 py-3 text-left">Ruta</th>
                            <th class="px-4 py-3 text-left">Cliente</th>
                            <th class="px-4 py-3 text-left">Conductor</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-right">Aplicado</th>
                            <th class="px-4 py-3 text-right">Pagado</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3 text-center">Tipo Pago</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($pedidosCredito as $pedido)
                            @php
                                $totalAplicado = $pedido->detalles->sum(fn($d) => $d->cantidad * ($d->precio_unitario));
                                $pagado = $pedido->pagos->sum('monto');
                                $saldo  = $totalAplicado - $pagado;
                                $estado = $pagado == 0 ? 'Pendiente' : ($pagado < $totalAplicado ? 'Parcial' : 'Pagado');
                            @endphp
                            <tr class="hover:bg-indigo-50/50 dark:hover:bg-gray-800 transition">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">#{{ $pedido->id }}</td>
                                <td class="px-4 py-3">{{ $pedido->ruta->ruta ?? 'NO DEFINIDA' }}</td>
                                <td class="px-4 py-3">{{ $pedido->socioNegocio->razon_social }}</td>
                                <td class="px-4 py-3">{{ $pedido->conductor->name ?? 'Sin asignar' }}</td>
                                <td class="px-4 py-3">{{ $pedido->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($totalAplicado, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400">${{ number_format($pagado, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right {{ $saldo>0?'text-rose-600 dark:text-rose-400':'text-emerald-600 dark:text-emerald-400' }}">
                                    ${{ number_format($saldo, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                                        {{ $pedido->tipo_pago === 'credito'
                                            ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                                            : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' }}">
                                        <i class="fas {{ $pedido->tipo_pago==='credito'?'fa-credit-card':'fa-money-bill-wave' }}"></i>
                                        {{ ucfirst($pedido->tipo_pago) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold
                                        {{ $estado === 'Pagado' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' :
                                           ($estado === 'Parcial' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' :
                                           'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300') }}">
                                        <i class="fas {{ $estado==='Pagado'?'fa-check-circle':($estado==='Parcial'?'fa-hourglass-half':'fa-clock') }}"></i>
                                        {{ $estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if($estado !== 'Pagado')
                                            <button wire:click="abrirModalPago({{ $pedido->id }})"
                                                    class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold">
                                                Pagar
                                            </button>
                                        @endif
                                        <button wire:click="verDetalle({{ $pedido->id }})"
                                                class="px-3 py-1.5 rounded-lg border border-amber-300 text-amber-700 dark:border-amber-700 dark:text-amber-300 text-xs font-semibold hover:bg-amber-50 dark:hover:bg-amber-900/20">
                                            Ver Detalle
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-6 py-10 text-center text-gray-500">
                                    <i class="fas fa-inbox text-2xl mb-2"></i>
                                    <div>No hay resultados con los filtros actuales.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4 px-4 pb-4">
                    {{ $pedidosCredito->links() }}
                </div>
            </div>
        </div>
    </section>

    <!-- MODAL REGISTRO DE PAGO -->
    @if($showPagoModal && $pedidoSeleccionado)
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="relative w-full sm:max-w-md bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-2xl shadow-2xl p-5 sm:p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base md:text-lg font-bold text-gray-800 dark:text-white">
                    Registrar Pago · #{{ $pedidoSeleccionado->id }}
                </h2>
                <button wire:click="$set('showPagoModal', false)" class="text-gray-500 hover:text-rose-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form wire:submit.prevent="registrarPago"
                  x-data="{
                      monto: @entangle('montoPago').defer,
                      get montoFormateado() {
                          if (this.monto === '' || isNaN(this.monto)) return '';
                          return new Intl.NumberFormat('es-CO',{style:'currency',currency:'COP',minimumFractionDigits:0}).format(this.monto);
                      }
                  }"
                  class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">Monto a pagar</label>
                    <input type="number" x-model="monto" step="0.01" min="0"
                           class="w-full px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none text-sm">
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">💲 <span x-text="montoFormateado"></span></div>
                    @error('montoPago') <span class="text-rose-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showPagoModal', false)"
                            class="px-4 py-2 text-sm rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                        Cancelar
                    </button>
                    <button type="button"
                            x-on:click="$wire.set('montoPago', monto).then(() => $wire.registrarPago())"
                            class="px-4 py-2 text-sm rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow">
                        Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- MODAL DETALLE -->
    @if($mostrarDetalle && $pedidoDetalleSeleccionado)
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
        <div class="relative w-full sm:max-w-5xl bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-2xl shadow-2xl p-5 sm:p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h2 class="text-base md:text-lg font-bold text-gray-800 dark:text-white">
                    Detalle del Pedido #{{ $pedidoDetalleSeleccionado->id }}
                </h2>
                <button wire:click="$set('mostrarDetalle', false)" class="text-gray-500 hover:text-rose-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700 dark:text-gray-300">
                <div><strong>Cliente:</strong> {{ $pedidoDetalleSeleccionado->socioNegocio->razon_social }}</div>
                <div><strong>Ruta:</strong> {{ $pedidoDetalleSeleccionado->ruta->ruta ?? 'NO DEFINIDA' }}</div>
                <div><strong>Conductor:</strong> {{ $pedidoDetalleSeleccionado->conductor->name ?? 'Sin asignar' }}</div>
                <div><strong>Fecha:</strong> {{ $pedidoDetalleSeleccionado->fecha->format('d/m/Y') }}</div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-4 py-2 text-center">Cantidad</th>
                            <th class="px-4 py-2 text-right">Precio</th>
                            <th class="px-4 py-2 text-left">Lista Aplicada</th>
                            <th class="px-4 py-2 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($pedidoDetalleSeleccionado->detalles as $detalle)
                            <tr>
                                <td class="px-4 py-2">{{ $detalle->producto->nombre }}</td>
                                <td class="px-4 py-2 text-center">{{ $detalle->cantidad }}</td>
                                <td class="px-4 py-2 text-right">${{ number_format($detalle->precio_unitario,0,',','.') }}</td>
                                <td class="px-4 py-2">{{ optional($detalle->precioLista)->nombre ?? 'Precio Base' }}</td>
                                <td class="px-4 py-2 text-right">
                                    ${{ number_format($detalle->cantidad * $detalle->precio_unitario,0,',','.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button wire:click="$set('mostrarDetalle', false)"
                        class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-sm shadow">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
@endassets
