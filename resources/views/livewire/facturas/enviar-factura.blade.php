<div>
  @if($show)
    <div class="fixed inset-0 bg-black/40 z-40" wire:click="cerrar"></div>

    <div class="fixed z-50 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2
                w-[520px] max-w-[95vw] rounded-2xl bg-white p-5 shadow-xl
                dark:bg-gray-900 dark:text-white">
      <h3 class="text-lg font-semibold mb-4">Enviar factura por correo</h3>

      <div class="space-y-3">
        <div>
          <label class="text-sm font-medium">Para *</label>
          <input type="email" class="w-full border rounded-md px-3 py-2 dark:bg-gray-800"
                 wire:model.defer="para">
          @error('para') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-medium">CC</label>
          <input type="text" class="w-full border rounded-md px-3 py-2 dark:bg-gray-800"
                 placeholder="separa con coma, ; o espacios"
                 wire:model.defer="cc">
          @error('cc') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
        </div>

        <div>
          <label class="text-sm font-medium">Asunto</label>
          <input type="text" class="w-full border rounded-md px-3 py-2 dark:bg-gray-800"
                 wire:model.defer="asunto">
          @error('asunto') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="mt-5 flex justify-end gap-2">
        <button class="px-4 py-2 rounded-md border" wire:click="cerrar">Cerrar</button>
        <button class="px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700"
                wire:click="enviar">Enviar</button>
      </div>
    </div>
  @endif
</div>
