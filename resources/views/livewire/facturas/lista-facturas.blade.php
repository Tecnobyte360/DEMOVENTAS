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
               wire:model.live.debounce.400ms="q">
      </div>
    </div>
  </div>

  {{-- Tabla responsive --}}
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
            $badgeColors = [
              'borrador' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
              'emitida'  => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300',
              'parcialmente_pagada' => 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
              'pagada'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
              'anulada'  => 'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300',
            ];
            $badge = $badgeColors[$f->estado] ?? 'bg-slate-100 text-slate-700';
          @endphp

          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-colors duration-150">
            <td class="p-2 sm:p-3 font-medium whitespace-nowrap">{{ $f->prefijo ?? '—' }}</td>
            <td class="p-2 sm:p-3 font-semibold text-indigo-700 dark:text-indigo-400 whitespace-nowrap">{{ $num }}</td>
            <td class="p-2 sm:p-3 whitespace-nowrap">
              {{ \Illuminate\Support\Carbon::parse($f->fecha)->format('d/m/Y') }}
            </td>
            <td class="p-2 sm:p-3">
              <div class="truncate max-w-[220px] sm:max-w-[340px]">
                {{ $f->cliente->razon_social ?? '—' }}
                @if(!empty($f->cliente?->nit))
                  <span class="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500">
                    ({{ $f->cliente->nit }})
                  </span>
                @endif
              </div>
            </td>
            <td class="p-2 sm:p-3 text-right whitespace-nowrap">${{ number_format($f->subtotal, 2) }}</td>
            <td class="p-2 sm:p-3 text-right whitespace-nowrap">${{ number_format($f->impuestos, 2) }}</td>
            <td class="p-2 sm:p-3 text-right font-semibold whitespace-nowrap">${{ number_format($f->total, 2) }}</td>
            <td class="p-2 sm:p-3 whitespace-nowrap">
              <span class="px-2.5 sm:px-3 py-1 rounded-full text-[10px] sm:text-[11px] font-semibold {{ $badge }} shadow-sm">
                {{ ucwords(str_replace('_', ' ', $f->estado)) }}
              </span>
            </td>

            {{-- Acciones --}}
            <td class="p-2 sm:p-3 whitespace-nowrap">
              <div class="flex items-center gap-1.5 sm:gap-2">
                {{-- Editar --}}
                <button type="button" 
                        wire:click="abrir({{ $f->id }})"
                        title="Editar"
                        class="group relative px-2.5 py-1.5 rounded-lg bg-gray-900 dark:bg-gray-700 text-white text-xs hover:bg-black dark:hover:bg-gray-600 transition-colors duration-150">
                  <i class="fa-solid fa-pen-to-square"></i>
                  <span class="hidden sm:block pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 bg-gray-900 text-white text-[11px] px-2 py-1 rounded-md whitespace-nowrap transition-opacity duration-200 shadow-lg z-10">
                    Editar
                  </span>
                </button>

                {{-- Enviar --}}
                <button type="button"
                        wire:click="enviarPorCorreo({{ $f->id }})"
                        title="Enviar por correo"
                        class="group relative px-2.5 py-1.5 rounded-lg bg-indigo-600 dark:bg-indigo-700 text-white text-xs hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-150">
                  <i class="fa-solid fa-envelope"></i>
                  <span class="hidden sm:block pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 bg-indigo-700 text-white text-[11px] px-2 py-1 rounded-md whitespace-nowrap transition-opacity duration-200 shadow-lg z-10">
                    Enviar por correo
                  </span>
                </button>

                {{-- Previsualizar --}}
                <button type="button"
                        wire:click="preview({{ $f->id }})"
                        title="Previsualizar PDF"
                        class="group relative px-2.5 py-1.5 rounded-lg bg-slate-700 dark:bg-slate-600 text-white text-xs hover:bg-slate-800 dark:hover:bg-slate-500 transition-colors duration-150">
                  <i class="fa-solid fa-file-pdf"></i>
                  <span class="hidden sm:block pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 bg-slate-800 text-white text-[11px] px-2 py-1 rounded-md whitespace-nowrap transition-opacity duration-200 shadow-lg z-10">
                    Previsualizar
                  </span>
                </button>

                {{-- Imprimir --}}
                <button type="button"
                        onclick="imprimirPOS({{ $f->id }})"
                        title="Imprimir ticket"
                        class="group relative px-2.5 py-1.5 rounded-lg bg-emerald-600 dark:bg-emerald-700 text-white text-xs hover:bg-emerald-700 dark:hover:bg-emerald-600 transition-colors duration-150">
                  <i class="fa-solid fa-receipt"></i>
                  <span class="hidden sm:block pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 bg-emerald-700 text-white text-[11px] px-2 py-1 rounded-md whitespace-nowrap transition-opacity duration-200 shadow-lg z-10">
                    Imprimir
                  </span>
                </button>

                {{-- Mapa de relaciones --}}
                <button type="button"
                        wire:click="abrirMapa({{ $f->id }})"
                        title="Ver mapa de relaciones"
                        class="group relative px-2.5 py-1.5 rounded-lg bg-amber-500 dark:bg-amber-600 text-white text-xs hover:bg-amber-600 dark:hover:bg-amber-500 transition-colors duration-150">
                  <i class="fa-solid fa-diagram-project"></i>
                  <span class="hidden sm:block pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 bg-amber-600 text-white text-[11px] px-2 py-1 rounded-md whitespace-nowrap transition-opacity duration-200 shadow-lg z-10">
                    Mapa
                  </span>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="p-8 text-center text-gray-500 dark:text-gray-400">
              <i class="fa-solid fa-inbox text-3xl mb-2 opacity-50"></i>
              <p class="text-sm">No se encontraron facturas</p>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginación --}}
  <div class="mt-4">
    {{ $items->links() }}
  </div>

  {{-- Componentes de Livewire --}}
  <livewire:facturas.enviar-factura />
  <livewire:mapa-relacion.mapa-relaciones />

  {{-- Modal de Previsualización --}}
@if($this->showPreview && $this->previewId)
  <div x-data="{ show: @entangle('showPreview') }"
       x-show="show"
       x-on:keydown.escape.window="$wire.closePreview()"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-0"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-[100] flex items-start justify-center p-4 overflow-y-auto"
       style="display: none;">
      
      {{-- Backdrop --}}
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"
           wire:click="closePreview"></div>

      {{-- Modal Container --}}
      <div x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="transition ease-in duration-200"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="relative w-full max-w-6xl my-8 bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        
        {{-- Header --}}
        <div class="flex items-center justify-between px-4 md:px-6 py-3 md:py-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
          <h3 class="text-sm md:text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-file-pdf text-red-600"></i>
            Previsualización - Factura #{{ $previewId }}
          </h3>
          <div class="flex items-center gap-2">
            <a href="{{ route('facturas.preview', $previewId) }}" 
               target="_blank"
               class="px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors duration-150 flex items-center gap-1.5">
              <i class="fa-solid fa-external-link-alt"></i>
              <span class="hidden sm:inline">Abrir pestaña</span>
            </a>
            <button type="button" 
                    wire:click="closePreview"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors duration-150 flex items-center gap-1.5">
              <i class="fa-solid fa-times"></i>
              Cerrar
            </button>
          </div>
        </div>

        {{-- Iframe Content --}}
        <div class="bg-gray-100 dark:bg-gray-950">
          <div class="h-[75vh] md:h-[80vh]">
            <iframe
              src="{{ route('facturas.preview', $previewId) }}?t={{ now()->timestamp }}"
              class="w-full h-full border-0"
              title="Previsualización de factura"
              loading="lazy">
            </iframe>
          </div>
        </div>
      </div>
    </div>
  @endif

</div>

{{-- Scripts --}}
@script
<script>
  async function imprimirPOS(id) {
    try {
      const response = await fetch(`{{ url('/facturas') }}/${id}/print-pos`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`Error HTTP: ${response.status}`);
      }

      const data = await response.json();
      
      // Notificación de éxito
      window.dispatchEvent(new CustomEvent('notificacion', {
        detail: {
          tipo: 'success',
          mensaje: 'Ticket enviado a la impresora correctamente'
        }
      }));

    } catch (error) {
      console.error('Error al imprimir:', error);
      
      // Notificación de error
      window.dispatchEvent(new CustomEvent('notificacion', {
        detail: {
          tipo: 'error',
          mensaje: 'No se pudo imprimir. Verifica la conexión de la impresora.'
        }
      }));
    }
  }
</script>
@endscript