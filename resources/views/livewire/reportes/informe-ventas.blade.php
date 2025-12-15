@assets
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
@endassets

<div class="p-4 md:p-6 space-y-6">

  {{-- ===================== HEADER ===================== --}}
  @php
    $codigo   = strtoupper($serieSeleccionada?->tipo?->codigo ?? '');
    $esCompra = in_array($codigo, ['FACTURACOMPRA','NOTA_CREDITO_COMPRA','NOTACREDITOCOMPRA'], true);
  @endphp

  <section class="rounded-3xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-xl p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
          Informe de {{ $esCompra ? 'Compras' : 'Ventas' }}
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">
          Selecciona una <b>serie</b> para consultar <b>facturas de venta</b> o <b>facturas de compra</b>.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold
          {{ $esCompra ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700' }}">
          <i class="fas {{ $esCompra ? 'fa-truck' : 'fa-store' }}"></i>
          {{ $esCompra ? 'FACTURAS DE COMPRA' : 'FACTURAS DE VENTA' }}
        </span>

        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-xs font-semibold">
          <i class="fas fa-layer-group"></i>
          {{ $serieSeleccionada?->nombre ?? 'VENTAS (por defecto)' }}
        </span>
      </div>
    </div>
  </section>

  {{-- ===================== KPIs (SIN x-kpi) ===================== --}}
  <section class="grid grid-cols-2 md:grid-cols-6 gap-4">
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow">
      <div class="text-xs text-gray-500">Documentos</div>
      <div class="text-xl font-extrabold text-gray-900 dark:text-white">{{ number_format($totalFacturas ?? 0) }}</div>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow">
      <div class="text-xs text-gray-500">Facturado</div>
      <div class="text-xl font-extrabold text-gray-900 dark:text-white">${{ number_format($totalFacturado ?? 0,0,',','.') }}</div>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow">
      <div class="text-xs text-gray-500">Pagado</div>
      <div class="text-xl font-extrabold text-emerald-600">${{ number_format($totalPagado ?? 0,0,',','.') }}</div>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow">
      <div class="text-xs text-gray-500">Saldo</div>
      <div class="text-xl font-extrabold {{ ($totalSaldo ?? 0) > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
        ${{ number_format($totalSaldo ?? 0,0,',','.') }}
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow">
      <div class="text-xs text-gray-500">Contado</div>
      <div class="text-xl font-extrabold text-gray-900 dark:text-white">${{ number_format($totalContado ?? 0,0,',','.') }}</div>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 shadow">
      <div class="text-xs text-gray-500">Crédito</div>
      <div class="text-xl font-extrabold text-gray-900 dark:text-white">${{ number_format($totalCredito ?? 0,0,',','.') }}</div>
    </div>
  </section>

  {{-- ===================== FILTROS ===================== --}}
  <section class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow p-5">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">

      {{-- ✅ SELECT: define ventas/compras por serie --}}
      <div>
        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
          Consultar por Serie (Ventas / Compras)
        </label>

        <select wire:model.live="serieId"
                class="w-full h-10 px-3 rounded-xl border bg-white dark:bg-gray-800 dark:text-white dark:border-gray-700 focus:ring-2 focus:ring-indigo-600">
          <option value="">— FACTURAS DE VENTA (por defecto) —</option>

          <optgroup label="FACTURAS DE VENTA">
            @foreach(($seriesVentas ?? collect()) as $s)
              <option value="{{ $s->id }}">
                {{ $s->nombre }} · {{ $s->prefijo }} ({{ $s->tipo->codigo ?? '' }})
              </option>
            @endforeach
          </optgroup>

          <optgroup label="FACTURAS DE COMPRA">
            @foreach(($seriesCompras ?? collect()) as $s)
              <option value="{{ $s->id }}">
                {{ $s->nombre }} · {{ $s->prefijo }} ({{ $s->tipo->codigo ?? '' }})
              </option>
            @endforeach
          </optgroup>
        </select>

        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
          Si eliges una serie de <b>COMPRA</b> el informe cambia automáticamente.
        </p>
      </div>

      {{-- Estado --}}
      <div>
        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Estado</label>
        <select wire:model.live="estadoFiltro" class="w-full h-10 px-3 rounded-xl border dark:bg-gray-800 dark:text-white">
          <option value="todos">Todos</option>
          <option value="borrador">Borrador</option>
          <option value="emitida">Emitida</option>
          <option value="pagada">Pagada</option>
          <option value="parcialmente_pagada">Parcial</option>
          <option value="anulada">Anulada</option>
          <option value="vencida">Vencida</option>
        </select>
      </div>

      {{-- Tipo pago --}}
      <div>
        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Tipo de pago</label>
        <select wire:model.live="tipoPagoFiltro" class="w-full h-10 px-3 rounded-xl border dark:bg-gray-800 dark:text-white">
          <option value="todos">Todos</option>
          <option value="contado">Contado</option>
          <option value="credito">Crédito</option>
          <option value="transferencia">Transferencia</option>
        </select>
      </div>

      {{-- Cliente / Proveedor (con sugerencias strings) --}}
      <div class="flex flex-col">
        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
          {{ $esCompra ? 'Proveedor' : 'Cliente' }}
        </label>

        <div class="relative">
          <input type="text"
                 list="terceros_list"
                 wire:model.debounce.500ms="filtroCliente"
                 placeholder="Buscar por nombre..."
                 class="w-full h-10 rounded-xl text-sm pl-9 pr-8 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-indigo-500">

          <datalist id="terceros_list">
            @foreach(($terceros ?? []) as $t)
              <option value="{{ $t }}"></option>
            @endforeach
          </datalist>

          <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>

          <button type="button" wire:click="$set('filtroCliente','')"
                  class="absolute right-2 top-2 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times-circle"></i>
          </button>
        </div>

        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
          Escribe para filtrar (sugerencias: {{ $esCompra ? 'proveedores' : 'clientes' }}).
        </p>
      </div>

      {{-- Fechas --}}
      <div>
        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Desde</label>
        <input type="date" wire:model.live="fechaInicio"
               class="w-full h-10 px-3 rounded-xl border dark:bg-gray-800 dark:text-white dark:border-gray-700">
      </div>

      <div>
        <label class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">Hasta</label>
        <input type="date" wire:model.live="fechaFin"
               class="w-full h-10 px-3 rounded-xl border dark:bg-gray-800 dark:text-white dark:border-gray-700">
      </div>

    </div>

    {{-- Botones --}}
    <div class="flex flex-wrap justify-end gap-2 mt-4">
      <button type="button" wire:click="cargarVentas"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow">
        <i class="fas fa-search"></i> Buscar
      </button>

      <button type="button" wire:click="limpiarFiltros"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-sm">
        <i class="fas fa-rotate"></i> Limpiar
      </button>
    </div>
  </section>

  {{-- ===================== TABLA ===================== --}}
  <section class="rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow overflow-hidden">

    {{-- Loading --}}
    <div wire:loading class="p-6">
      <div class="animate-pulse h-10 rounded-xl bg-gray-100 dark:bg-gray-800 mb-3"></div>
      <div class="animate-pulse h-10 rounded-xl bg-gray-100 dark:bg-gray-800 mb-3"></div>
      <div class="animate-pulse h-10 rounded-xl bg-gray-100 dark:bg-gray-800"></div>
    </div>

    <div wire:loading.remove class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase text-xs">
          <tr>
            <th class="px-4 py-3 text-left">Documento</th>
            <th class="px-4 py-3 text-left">Fecha</th>
            <th class="px-4 py-3 text-left">{{ $esCompra ? 'Proveedor' : 'Cliente' }}</th>
            <th class="px-4 py-3 text-center">Estado</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-right">Pagado</th>
            <th class="px-4 py-3 text-right">Saldo</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
          @forelse($facturas as $f)
            @php
              $total  = (float) ($f->total ?? 0);
              $pagado = (float) ($f->pagado ?? 0);
              $saldo  = (float) ($f->saldo ?? 0);
              $estado = (string) ($f->estado ?? '');
            @endphp

            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
              <td class="px-4 py-3 font-mono font-semibold">
                {{ $f->numero_formateado ?? ('#'.$f->id) }}
              </td>

              <td class="px-4 py-3">
                {{ $f->fecha?->format('d/m/Y') ?? '—' }}
              </td>

              <td class="px-4 py-3">
                @if($esCompra)
                  {{ $f->proveedor->razon_social ?? ($f->proveedor_nombre ?? '—') }}
                @else
                  {{ $f->cliente->razon_social ?? ($f->cliente_nombre ?? '—') }}
                @endif
              </td>

              <td class="px-4 py-3 text-center">
                <span class="px-2 py-1 rounded-full text-xs font-semibold
                  {{ $estado === 'pagada'
                      ? 'bg-emerald-100 text-emerald-700'
                      : ($estado === 'anulada'
                          ? 'bg-gray-200 text-gray-700'
                          : 'bg-amber-100 text-amber-700') }}">
                  {{ $estado ? ucfirst(str_replace('_',' ',$estado)) : '—' }}
                </span>
              </td>

              <td class="px-4 py-3 text-right font-semibold">
                ${{ number_format($total,0,',','.') }}
              </td>

              <td class="px-4 py-3 text-right text-emerald-600">
                ${{ number_format($pagado,0,',','.') }}
              </td>

              <td class="px-4 py-3 text-right {{ $saldo > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                ${{ number_format($saldo,0,',','.') }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                No hay documentos con los filtros actuales.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>

      <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $facturas->links() }}
      </div>
    </div>
  </section>

</div>
