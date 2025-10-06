{{-- resources/views/livewire/facturas/enviar-factura.blade.php --}}
<div>
  @push('styles')
    <style>
      @keyframes grow { from { transform: scaleX(0); } 50% { transform: scaleX(0.6); } to { transform: scaleX(1); } }
    </style>
  @endpush

  @if ($show)
    {{-- Backdrop --}}
    <div
      class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm transition-opacity"
      wire:click="cerrar"
      aria-hidden="true">
    </div>

    {{-- Modal --}}
    <div
      class="fixed z-50 top-1/2 left-1/2 w-[520px] max-w-[95vw] -translate-x-1/2 -translate-y-1/2
             rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/5
             dark:bg-gray-900 dark:text-white dark:ring-white/10"
      role="dialog" aria-modal="true" aria-labelledby="envioFacturaTitulo">

      <div class="flex items-start justify-between gap-4 mb-5">
        <h3 id="envioFacturaTitulo" class="text-xl font-bold tracking-tight">
          Enviar factura por correo
        </h3>
        <button
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg
                 hover:bg-gray-100 dark:hover:bg-gray-800"
          wire:click="cerrar" aria-label="Cerrar">
          <svg viewBox="0 0 24 24" class="h-5 w-5">
            <path fill="currentColor" d="M18.3 5.71L12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.29 19.7 2.88 18.3 9.17 12 2.88 5.71 4.29 4.29 10.59 10.6l6.3-6.3z"/>
          </svg>
        </button>
      </div>

      {{-- Form --}}
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Para <span class="text-rose-600">*</span>
          </label>
          <input type="email"
                 class="w-full rounded-xl border-2 border-gray-200 px-3 py-2.5
                        focus:outline-none focus:ring-4 focus:ring-indigo-300/50
                        dark:bg-gray-800 dark:border-gray-700"
                 placeholder="cliente@correo.com"
                 wire:model.defer="para">
          @error('para') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CC</label>
          <input type="text"
                 class="w-full rounded-xl border-2 border-gray-200 px-3 py-2.5
                        focus:outline-none focus:ring-4 focus:ring-indigo-300/50
                        dark:bg-gray-800 dark:border-gray-700"
                 placeholder="separa con coma, ; o espacios"
                 wire:model.defer="cc">
          @error('cc') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Asunto</label>
          <input type="text"
                 class="w-full rounded-xl border-2 border-gray-200 px-3 py-2.5
                        focus:outline-none focus:ring-4 focus:ring-indigo-300/50
                        dark:bg-gray-800 dark:border-gray-700"
                 placeholder="Factura FVE-000123"
                 wire:model.defer="asunto">
          @error('asunto') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
      </div>

      {{-- Actions --}}
      <div class="mt-6 flex items-center justify-end gap-2">
        <button
          class="px-4 py-2 h-11 rounded-xl border-2 border-gray-300 text-gray-700
                 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
          wire:click="cerrar">
          Cerrar
        </button>

        <button
          class="px-5 py-2 h-11 inline-flex items-center gap-2 rounded-xl
                 bg-indigo-600 text-white font-medium shadow
                 hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300/50
                 disabled:opacity-60 disabled:cursor-not-allowed"
          wire:click="enviar"
          wire:loading.attr="disabled"
          wire:target="enviar">

          {{-- Spinner en botón --}}
          <svg wire:loading wire:target="enviar" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
          </svg>

          <span>
            <span wire:loading.remove wire:target="enviar">Enviar</span>
            <span wire:loading wire:target="enviar">Enviando…</span>
          </span>
        </button>
      </div>

      {{-- Barra de progreso superior mientras envía --}}
      <div class="absolute left-0 right-0 top-0 h-1 overflow-hidden rounded-t-2xl" aria-hidden="true">
        <div wire:loading wire:target="enviar"
             class="h-1 w-full origin-left scale-x-0 animate-[grow_1.2s_ease-in-out_infinite] bg-indigo-600"></div>
      </div>
    </div>

    {{-- Overlay de carga global (bloquea clics) --}}
    <div wire:loading wire:target="enviar"
         class="fixed inset-0 z-[60] grid place-items-center bg-black/10">
      <div class="flex items-center gap-3 rounded-xl bg-white/90 px-4 py-3 shadow dark:bg-gray-800/90">
        <svg class="h-6 w-6 animate-spin" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
        </svg>
        <span class="text-sm font-medium dark:text-white">Enviando correo…</span>
      </div>
    </div>
  @endif
</div>
