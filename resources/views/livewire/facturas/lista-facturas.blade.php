{{-- resources/views/livewire/facturas/lista-facturas.blade.php --}}

@assets
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
@endassets

<div class="mt-8">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
    <div class="flex items-center gap-2">
      <select wire:model.live="estado" class="px-3 py-2 rounded-md border text-sm dark:bg-gray-800 dark:text-white">
          <option value="todas">Todos</option>
          <option value="borrador">Borrador</option>
          <option value="emitida">Emitida</option>
          <option value="parcialmente_pagada">Parcial</option>
          <option value="pagada">Pagada</option>
          <option value="anulada">Anulada</option>
      </select>

      <select wire:model.live="perPage" class="px-3 py-2 rounded-md border text-sm dark:bg-gray-800 dark:text-white">
          <option value="10">10</option>
          <option value="12">12</option>
          <option value="25">25</option>
          <option value="50">50</option>
      </select>
    </div>

    <div class="relative">
      <input type="text" placeholder="Buscar #, prefijo, cliente, NIT, estado…"
             class="pl-9 pr-3 py-2 border rounded-md text-sm w-80 dark:bg-gray-800 dark:text-white"
             wire:model.debounce.400ms="q">
      <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
    </div>
  </div>

  <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
    <table class="w-full border-collapse">
      <thead class="bg-slate-50 dark:bg-gray-800/60">
        <tr>
          <th class="text-left px-3 py-2">Prefijo</th>
          <th class="text-left px-3 py-2">Número</th>
          <th class="text-left px-3 py-2">Fecha</th>
          <th class="text-left px-3 py-2">Cliente</th>
          <th class="text-right px-3 py-2">Subtotal</th>
          <th class="text-right px-3 py-2">Impuestos</th>
          <th class="text-right px-3 py-2">Total</th>
          <th class="text-left px-3 py-2">Estado</th>
          <th class="text-left px-3 py-2">Acciones</th> {{-- 👈 NUEVA --}}
        </tr>
      </thead>
      <tbody>
        @forelse($items as $f)
          @php
            $len = $f->serie->longitud ?? 6;
            $num = $f->numero !== null
              ? str_pad((string)$f->numero, $len, '0', STR_PAD_LEFT)
              : '—';

            $badge = [
              'borrador' => 'bg-slate-200 text-slate-700',
              'emitida'  => 'bg-indigo-100 text-indigo-700',
              'parcialmente_pagada' => 'bg-amber-100 text-amber-700',
              'pagada'   => 'bg-emerald-100 text-emerald-700',
              'anulada'  => 'bg-rose-100 text-rose-700',
            ][$f->estado] ?? 'bg-slate-100 text-slate-700';
          @endphp

          <tr class="border-t hover:bg-slate-50 dark:hover:bg-gray-800">
            <td class="px-3 py-2 font-semibold text-slate-700 whitespace-nowrap">{{ $f->prefijo ?? '—' }}</td>
            <td class="px-3 py-2 font-semibold text-indigo-700 whitespace-nowrap">{{ $num }}</td>
            <td class="px-3 py-2 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($f->fecha)->format('d/m/Y') }}</td>
            <td class="px-3 py-2">
              <div class="truncate max-w-[360px]">
                {{ $f->cliente->razon_social ?? '—' }}
                @if(!empty($f->cliente?->nit))
                  <span class="text-xs text-slate-400">({{ $f->cliente->nit }})</span>
                @endif
              </div>
            </td>
            <td class="px-3 py-2 text-right whitespace-nowrap">${{ number_format($f->subtotal,2) }}</td>
            <td class="px-3 py-2 text-right whitespace-nowrap">${{ number_format($f->impuestos,2) }}</td>
            <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">${{ number_format($f->total,2) }}</td>
            <td class="px-3 py-2">
              <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                {{ ucwords(str_replace('_',' ',$f->estado)) }}
              </span>
            </td>

            {{-- 👇 Acciones --}}
            {{-- 👇 Acciones --}}
<td class="px-3 py-2 whitespace-nowrap">
  <div class="flex items-center gap-2">
    {{-- Editar --}}
    <button type="button"
            class="px-2.5 py-1.5 rounded-md bg-slate-800 text-white text-xs hover:bg-slate-900"
            wire:click="abrir({{ $f->id }})"
            title="Editar">
      <i class="fa-solid fa-pen-to-square mr-1"></i> Editar
    </button>

    {{-- Enviar por correo --}}
    <button type="button"
            class="px-2.5 py-1.5 rounded-md bg-indigo-600 text-white text-xs hover:bg-indigo-700"
            wire:click="enviarPorCorreo({{ $f->id }})"
            title="Enviar por correo">
      <i class="fa-solid fa-envelope mr-1"></i> Enviar
    </button>

    {{-- 👇 NUEVO: Vista previa POS dentro de un modal --}}
    <button type="button"
            class="px-2.5 py-1.5 rounded-md bg-amber-600 text-white text-xs hover:bg-amber-700"
            wire:click="preview({{ $f->id }})"
            title="Vista previa POS">
      <i class="fa-solid fa-eye mr-1"></i> Vista previa
    </button>

    {{-- Imprimir POS en nueva pestaña (dejas tu botón actual) --}}
    <a href="{{ route('facturas.ticket', $f->id) }}"
       target="_blank"
       class="px-2.5 py-1.5 rounded-md bg-emerald-600 text-white text-xs hover:bg-emerald-700"
       title="Imprimir ticket (POS)">
      <i class="fa-solid fa-print mr-1"></i> POS
    </a>
    <button type="button"
        class="px-2.5 py-1.5 rounded-md bg-emerald-700 text-white text-xs hover:bg-emerald-800"
        onclick="imprimirPOS({{ $f->id }})"
        title="Imprimir directo en POS (ESC/POS)">
  <i class="fa-solid fa-receipt mr-1"></i> Imprimir POS
</button>

@once
  <script>
    async function imprimirPOS(id) {
      try {
        const res = await fetch(`{{ url('/facturas') }}/${id}/print-pos`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          }
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

  </div>
</td>

          </tr>
        @empty
          <tr>
            <td colspan="9" class="p-6 text-center text-slate-500">Sin resultados…</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
{{-- ===================== --}}
{{-- Modal de Vista Previa --}}
{{-- ===================== --}}
@if($showPreview)
  <div class="fixed inset-0 z-[100]">
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" wire:click="closePreview"></div>

    {{-- Contenedor modal --}}
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="relative w-[95vw] max-w-4xl h-[85vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-800">
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-800">
          <div class="font-semibold text-gray-800 dark:text-gray-100">
            Vista previa ticket POS
          </div>
          <div class="flex items-center gap-2">
            <a
              href="{{ route('facturas.ticket', $previewId) }}"
              target="_blank"
              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-slate-200 text-slate-800 text-sm font-medium hover:bg-slate-300 dark:bg-slate-700 dark:text-white"
              title="Abrir en nueva pestaña (plan B)"
            >
              <i class="fa-solid fa-up-right-from-square"></i> Abrir
            </a>
            <button
              type="button"
              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700"
              onclick="(function(){
                  var f = document.getElementById('ticketFrame');
                  if (f && f.contentWindow) { f.contentWindow.focus(); f.contentWindow.print(); }
              })()"
            >
              <i class="fa-solid fa-print"></i> Imprimir
            </button>
            <button
              type="button"
              class="inline-flex items-center px-3 py-1.5 rounded-md bg-slate-200 text-slate-800 text-sm font-medium hover:bg-slate-300 dark:bg-slate-700 dark:text-white"
              wire:click="closePreview"
            >
              Cerrar
            </button>
          </div>
        </div>

        {{-- Cuerpo con iframe (siempre que exista previewId) --}}
        <div class="w-full h-[calc(85vh-56px)]">
          @if($previewId)
            <iframe
              id="ticketFrame"
              src="{{ route('facturas.ticket', [$previewId, 'preview' => 1]) }}"
              class="w-full h-full"
              frameborder="0"
            ></iframe>
          @else
            <div class="w-full h-full grid place-items-center text-sm text-slate-500">
              Cargando vista previa…
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endif

  <div class="mt-4">
    {{ $items->links() }}
  </div>

  {{-- Modal de Envío (se abre con el evento) --}}
  <livewire:facturas.enviar-factura />
</div>
