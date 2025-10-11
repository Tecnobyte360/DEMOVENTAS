{{-- resources/views/livewire/facturas/lista-facturas.blade.php --}}

@assets
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
@endassets

<div class="mt-8 space-y-4">

  {{-- Filtros / Buscador --}}
  <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60 backdrop-blur p-3 md:p-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

      {{-- Selects --}}
      <div class="flex items-center gap-2">
        <label class="sr-only">Estado</label>
        <select wire:model.live="estado"
                class="px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600">
          <option value="todas">Todos</option>
          <option value="borrador">Borrador</option>
          <option value="emitida">Emitida</option>
          <option value="parcialmente_pagada">Parcial</option>
          <option value="pagada">Pagada</option>
          <option value="anulada">Anulada</option>
        </select>

        <label class="sr-only">Por página</label>
        <select wire:model.live="perPage"
                class="px-3 py-2 rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600">
          <option value="10">10</option>
          <option value="12">12</option>
          <option value="25">25</option>
          <option value="50">50</option>
        </select>
      </div>

      {{-- Buscador --}}
      <div class="relative w-full md:w-80">
        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
        <input type="text"
               placeholder="Buscar #, prefijo, cliente, NIT, estado…"
               class="pl-9 pr-3 py-2 w-full rounded-xl border border-gray-200/70 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70 text-sm text-gray-700 dark:text-gray-100 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
               wire:model.debounce.400ms="q">
      </div>
    </div>
  </div>


 {{-- Tabla responsive en todos los tamaños --}}
<div class="overflow-x-auto rounded-3xl border border-gray-200/70 dark:border-gray-800 bg-white/60 dark:bg-gray-900/60 backdrop-blur-md shadow-xl">
  <table class="min-w-full text-xs sm:text-sm text-gray-700 dark:text-gray-100">
    <thead class="bg-gradient-to-r from-gray-100 via-gray-50 to-gray-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 text-[11px] sm:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300">
      <tr>
        <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Prefijo</th>
        <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Número</th>
        <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Fecha</th>
        <th class="p-2 sm:p-3 text-left font-semibold">Cliente</th>
        <th class="p-2 sm:p-3 text-right font-semibold whitespace-nowrap">Subtotal</th>
        <th class="p-2 sm:p-3 text-right font-semibold whitespace-nowrap">Impuestos</th>
        <th class="p-2 sm:p-3 text-right font-semibold whitespace-nowrap">Total</th>
        <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Estado</th>
        <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Acciones</th>
      </tr>
    </thead>

    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
      @forelse($items as $f)
        @php
          $len = $f->serie->longitud ?? 6;
          $num = $f->numero !== null ? str_pad((string)$f->numero, $len, '0', STR_PAD_LEFT) : '—';
          $badge = [
            'borrador' => 'bg-slate-100 text-slate-700',
            'emitida'  => 'bg-indigo-100 text-indigo-700',
            'parcialmente_pagada' => 'bg-amber-100 text-amber-700',
            'pagada'   => 'bg-emerald-100 text-emerald-700',
            'anulada'  => 'bg-rose-100 text-rose-700',
          ][$f->estado] ?? 'bg-slate-100 text-slate-700';
        @endphp

        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all duration-200 ease-in-out">
          <td class="p-2 sm:p-3 font-medium whitespace-nowrap">{{ $f->prefijo ?? '—' }}</td>
          <td class="p-2 sm:p-3 font-semibold text-indigo-700 dark:text-indigo-300 whitespace-nowrap">{{ $num }}</td>
          <td class="p-2 sm:p-3 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($f->fecha)->format('d/m/Y') }}</td>
          <td class="p-2 sm:p-3">
            <div class="truncate max-w-[220px] sm:max-w-[340px]">
              {{ $f->cliente->razon_social ?? '—' }}
              @if(!empty($f->cliente?->nit))
                <span class="text-[10px] sm:text-xs text-gray-400">({{ $f->cliente->nit }})</span>
              @endif
            </div>
          </td>
          <td class="p-2 sm:p-3 text-right whitespace-nowrap">${{ number_format($f->subtotal,2) }}</td>
          <td class="p-2 sm:p-3 text-right whitespace-nowrap">${{ number_format($f->impuestos,2) }}</td>
          <td class="p-2 sm:p-3 text-right font-semibold whitespace-nowrap">${{ number_format($f->total,2) }}</td>
          <td class="p-2 sm:p-3 whitespace-nowrap">
            <span class="px-2.5 sm:px-3 py-1 rounded-full text-[10px] sm:text-[11px] font-semibold {{ $badge }} shadow-sm">
              {{ ucwords(str_replace('_',' ',$f->estado)) }}
            </span>
          </td>

          {{-- Acciones con tooltip en desktop y títulos en móvil --}}
          <td class="p-2 sm:p-3 whitespace-nowrap">
            <div class="flex items-center gap-1.5 sm:gap-2">
              {{-- Editar --}}
              <button type="button" title="Editar"
                      wire:click="abrir({{ $f->id }})"
                      class="group relative px-2.5 py-1.5 rounded-lg bg-gray-900 text-white text-xs hover:bg-black/80 transition">
                <i class="fa-solid fa-pen-to-square"></i>
                <span class="hidden sm:block pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 bg-gray-900 text-white text-[11px] px-2 py-1 rounded-md whitespace-nowrap transition-all duration-200 shadow-lg">Editar</span>
              </button>

              {{-- Enviar --}}
              <button type="button" title="Enviar por correo"
                      wire:click="enviarPorCorreo({{ $f->id }})"
                      class="group relative px-2.5 py-1.5 rounded-lg bg-indigo-600 text-white text-xs hover:bg-indigo-700 transition">
                <i class="fa-solid fa-envelope"></i>
                <span class="hidden sm:block pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 bg-indigo-700 text-white text-[11px] px-2 py-1 rounded-md whitespace-nowrap transition-all duration-200 shadow-lg">Enviar por correo</span>
              </button>

              {{-- Vista previa --}}
             <button type="button" title="Vista previa"
        wire:click="preview({{ $f->id }})"
        class="group relative px-2.5 py-1.5 rounded-lg text-white text-xs 
               bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-500 
               hover:from-amber-500 hover:to-yellow-600
               shadow-md hover:shadow-lg transition-all duration-200">
  <i class="fa-solid fa-eye"></i>
  <span class="hidden sm:block pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 
               opacity-0 group-hover:opacity-100 bg-amber-600 text-white text-[11px] px-2 py-1 
               rounded-md whitespace-nowrap transition-all duration-200 shadow-lg">
    Vista previa
  </span>
</button>


              {{-- Imprimir --}}
              <button type="button" title="Imprimir"
                      onclick="imprimirPOS({{ $f->id }})"
                      class="group relative px-2.5 py-1.5 rounded-lg bg-emerald-600 text-white text-xs hover:bg-emerald-700 transition">
                <i class="fa-solid fa-receipt"></i>
                <span class="hidden sm:block pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 bg-emerald-700 text-white text-[11px] px-2 py-1 rounded-md whitespace-nowrap transition-all duration-200 shadow-lg">Imprimir</span>
              </button>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="9" class="p-6 text-center text-gray-500 dark:text-gray-400">Sin resultados…</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>


  {{-- Lista móvil (cards) --}}
  <div class="md:hidden space-y-3">
    @forelse($items as $f)
      @php
        $len = $f->serie->longitud ?? 6;
        $num = $f->numero !== null ? str_pad((string)$f->numero, $len, '0', STR_PAD_LEFT) : '—';
        $badge = [
          'borrador' => 'bg-slate-200 text-slate-700',
          'emitida'  => 'bg-indigo-100 text-indigo-700',
          'parcialmente_pagada' => 'bg-amber-100 text-amber-700',
          'pagada'   => 'bg-emerald-100 text-emerald-700',
          'anulada'  => 'bg-rose-100 text-rose-700',
        ][$f->estado] ?? 'bg-slate-100 text-slate-700';
      @endphp

      <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/70 dark:bg-gray-900/60 backdrop-blur p-3">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-xs text-gray-500">Prefijo</div>
            <div class="font-semibold text-gray-800 dark:text-gray-100">{{ $f->prefijo ?? '—' }}</div>
          </div>
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $badge }}">
            {{ ucwords(str_replace('_',' ',$f->estado)) }}
          </span>
        </div>

        <div class="mt-2">
          <div class="text-xs text-gray-500">Factura</div>
          <div class="font-semibold text-indigo-700 dark:text-indigo-300">{{ $num }}</div>
        </div>

        <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
          <div>
            <div class="text-xs text-gray-500">Fecha</div>
            <div>{{ \Illuminate\Support\Carbon::parse($f->fecha)->format('d/m/Y') }}</div>
          </div>
          <div class="text-right">
            <div class="text-xs text-gray-500">Total</div>
            <div class="font-semibold">${{ number_format($f->total,2) }}</div>
          </div>
        </div>

        <div class="mt-2">
          <div class="text-xs text-gray-500">Cliente</div>
          <div class="truncate">
            {{ $f->cliente->razon_social ?? '—' }}
            @if(!empty($f->cliente?->nit))
              <span class="text-xs text-gray-400">({{ $f->cliente->nit }})</span>
            @endif
          </div>
        </div>

        <div class="mt-3 grid grid-cols-4 gap-2">
          <button class="px-2 py-2 rounded-xl bg-gray-900 text-white text-xs hover:bg-black/90 dark:bg-gray-700 dark:hover:bg-gray-600"
                  wire:click="abrir({{ $f->id }})" title="Editar">
            <i class="fa-solid fa-pen-to-square"></i>
          </button>
          <button class="px-2 py-2 rounded-xl bg-indigo-600 text-white text-xs hover:bg-indigo-700"
                  wire:click="enviarPorCorreo({{ $f->id }})" title="Enviar">
            <i class="fa-solid fa-envelope"></i>
          </button>
          <button class="px-2 py-2 rounded-xl bg-amber-500 text-white text-xs hover:bg-amber-600"
                  wire:click="preview({{ $f->id }})" title="Vista previa">
            <i class="fa-solid fa-eye"></i>
          </button>
          <button class="px-2 py-2 rounded-xl bg-emerald-600 text-white text-xs hover:bg-emerald-700"
                  onclick="imprimirPOS({{ $f->id }})" title="Imprimir">
            <i class="fa-solid fa-receipt"></i>
          </button>
        </div>
      </div>
    @empty
      <div class="text-center text-gray-500 dark:text-gray-400">Sin resultados…</div>
    @endforelse
  </div>

  {{-- Modal de Vista Previa (tu versión actual) --}}
  @if($showPreview)
    <div class="fixed inset-0 z-[100]">
      <div class="absolute inset-0 bg-black/50" wire:click="closePreview"></div>
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-[95vw] max-w-4xl h-[85vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-800">
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-800">
            <div class="font-semibold text-gray-800 dark:text-gray-100">Vista previa ticket POS</div>
            <div class="flex items-center gap-2">
              <a href="{{ route('facturas.ticket', $previewId) }}" target="_blank"
                 class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-slate-200 text-slate-800 text-sm font-medium hover:bg-slate-300 dark:bg-slate-700 dark:text-white">
                <i class="fa-solid fa-up-right-from-square"></i> Abrir
              </a>
              <button type="button"
                      class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700"
                      onclick="(function(){var f=document.getElementById('ticketFrame'); if(f&&f.contentWindow){f.contentWindow.focus(); f.contentWindow.print();}})()">
                <i class="fa-solid fa-print"></i> Imprimir
              </button>
              <button type="button"
                      class="inline-flex items-center px-3 py-1.5 rounded-md bg-slate-200 text-slate-800 text-sm font-medium hover:bg-slate-300 dark:bg-slate-700 dark:text-white"
                      wire:click="closePreview">
                Cerrar
              </button>
            </div>
          </div>
          <div class="w-full h-[calc(85vh-56px)]">
            @if($previewId)
              <iframe id="ticketFrame" src="{{ route('facturas.ticket', [$previewId, 'preview' => 1]) }}" class="w-full h-full" frameborder="0"></iframe>
            @else
              <div class="w-full h-full grid place-items-center text-sm text-slate-500">Cargando vista previa…</div>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif

  {{-- Paginación --}}
  <div class="mt-2">
    {{ $items->links() }}
  </div>

  {{-- Modal de Envío --}}
  <livewire:facturas.enviar-factura />
</div>

@once
  <script>
    async function imprimirPOS(id) {
      try {
        const res = await fetch(`{{ url('/facturas') }}/${id}/print-pos`, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        if(!res.ok) throw new Error('Error al imprimir');
        alert('Ticket enviado a la impresora.');
      } catch (e) {
        alert('No se pudo imprimir. Revisa conexión de la impresora.');
        console.error(e);
      }
    }
  </script>
@endonce
