<div
  x-data="{showAlert:true}"
  x-init="$watch(() => @js($mensaje), v => { if(v){ showAlert=true; setTimeout(()=>showAlert=false, 4000) } })"
  class="space-y-5"
>
  {{-- ALERTA --}}
  @if ($mensaje)
    <div
      x-show="showAlert"
      x-transition.opacity
      class="p-3 rounded-2xl flex justify-between items-center
        @if ($tipoMensaje === 'success') bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300/60
        @elseif ($tipoMensaje === 'error') bg-rose-100 text-rose-800 ring-1 ring-rose-300/60
        @elseif ($tipoMensaje === 'warning') bg-amber-100 text-amber-900 ring-1 ring-amber-300/60
        @else bg-gray-100 text-gray-800 ring-1 ring-gray-300/60 @endif"
      role="alert"
    >
      <div class="flex items-center gap-2">
        <i class="fa-solid
          @if ($tipoMensaje === 'success') fa-circle-check
          @elseif ($tipoMensaje === 'error') fa-triangle-exclamation
          @elseif ($tipoMensaje === 'warning') fa-circle-info
          @else fa-circle-info @endif"></i>
        <span class="font-medium">{{ $mensaje }}</span>
      </div>
      <button wire:click="$set('mensaje','')" class="font-bold ml-4 opacity-70 hover:opacity-100">✕</button>
    </div>
  @endif

  {{-- FORM --}}
  <form wire:submit.prevent="guardar" class="space-y-6">
    <div class="grid grid-cols-1 gap-5">
      {{-- Nombre --}}
      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">Nombre <span class="text-red-500">*</span></label>
        <input
          type="text"
          wire:model.defer="nombre"
          class="w-full h-12 px-4 rounded-2xl border-2 @error('nombre') border-red-500 focus:ring-red-300 @else border-gray-200 focus:border-indigo-500 focus:ring-indigo-300/60 @enderror
                 bg-white dark:bg-gray-800 dark:text-white"
          placeholder="Ej. Bodega Central"
        >
        @error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
      </div>

      {{-- Ubicación --}}
      <div>
        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-600 mb-2">Ubicación <span class="text-red-500">*</span></label>
        <input
          type="text"
          wire:model.defer="ubicacion"
          class="w-full h-12 px-4 rounded-2xl border-2 @error('ubicacion') border-red-500 focus:ring-red-300 @else border-gray-200 focus:border-indigo-500 focus:ring-indigo-300/60 @enderror
                 bg-white dark:bg-gray-800 dark:text-white"
          placeholder="Ej. Calle 123 #45-67"
        >
        @error('ubicacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
      </div>

      {{-- Activo (toggle lindo) --}}
      <div class="flex items-center justify-between rounded-2xl border-2 border-gray-200 px-4 py-3">
        <div>
          <div class="text-sm font-semibold text-gray-800">Bodega activa</div>
          <div class="text-xs text-gray-500">Controla si la bodega puede usarse en documentos.</div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer select-none">
          <input type="checkbox" class="sr-only peer" wire:model.defer="activo">
          <div class="w-12 h-7 bg-gray-300 rounded-full peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300/50 transition peer-checked:bg-emerald-600 relative">
            <span class="absolute top-0.5 left-0.5 h-6 w-6 bg-white rounded-full shadow transform transition peer-checked:translate-x-5"></span>
          </div>
        </label>
      </div>
    </div>

    {{-- Acciones --}}
    <div class="flex items-center justify-end gap-2 pt-2">
      {{-- Opción 1: cerrar por evento del front (Alpine/Livewire padre) --}}
      <button
        type="button"
        x-on:click="$dispatch('cerrarModal')"
        class="h-11 px-4 rounded-2xl border-2 border-gray-200 hover:bg-gray-100 text-gray-800"
      >
        Cancelar
      </button>

      {{-- Opción 2: cerrar desde este componente con método cancelar() --}}
      {{-- <button type="button" wire:click="cancelar" class="h-11 px-4 rounded-2xl border-2 border-gray-200 hover:bg-gray-100 text-gray-800">Cancelar</button> --}}

      <button type="submit" class="h-11 px-5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow">
        {{ $bodega_id ? 'Actualizar' : 'Guardar' }}
      </button>
    </div>
  </form>
</div>
