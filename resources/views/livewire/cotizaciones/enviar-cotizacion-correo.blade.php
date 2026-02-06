<div
  x-data="{ open: @entangle('show') }"
  x-cloak
>
  <div
    x-show="open"
    x-transition.opacity
    class="fixed inset-0 z-[300] flex items-center justify-center"
    @keydown.escape.window="open = false"
  >
    <div class="absolute inset-0 bg-black/60" @click="open=false"></div>

    <div class="relative z-10 w-[95vw] max-w-2xl rounded-2xl bg-white dark:bg-slate-900 shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white">Enviar cotización por correo</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Se adjunta PDF automáticamente</p>
        </div>

        <button type="button" class="h-9 w-9 rounded-xl bg-slate-100 dark:bg-slate-800"
                wire:click="cerrar">
          ✕
        </button>
      </div>

      <div class="p-5 space-y-4">
        <div>
          <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">Para</label>
          <input type="email" wire:model.defer="email_to"
                 class="mt-1 w-full h-11 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-white">
          @error('email_to') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">CC (separado por comas)</label>
          <input type="text" wire:model.defer="email_cc"
                 class="mt-1 w-full h-11 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-white">
        </div>

        <div>
          <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">Asunto</label>
          <input type="text" wire:model.defer="email_subject"
                 class="mt-1 w-full h-11 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-white">
          @error('email_subject') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
          <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">Mensaje</label>
          <textarea rows="6" wire:model.defer="email_body"
                    class="mt-1 w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-white"></textarea>
          @error('email_body') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
          <div>
            Adjunto: <span class="font-semibold">{{ $email_attachment_name ?? 'Sin adjunto' }}</span>
          </div>
          <div class="flex gap-2">
            <button type="button" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800"
                    wire:click="regenerarAdjunto">Regenerar</button>

            <button type="button" class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800"
                    wire:click="quitarAdjunto">Quitar</button>
          </div>
        </div>
      </div>

      <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 flex items-center justify-end gap-2">
        <button type="button" class="h-11 px-4 rounded-xl bg-slate-200 dark:bg-slate-800"
                wire:click="cerrar">Cerrar</button>

        <button type="button" class="h-11 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white"
                wire:click="enviarCorreo"
                wire:loading.attr="disabled"
                wire:target="enviarCorreo">
          <span wire:loading.remove wire:target="enviarCorreo">Enviar</span>
          <span wire:loading wire:target="enviarCorreo">Enviando…</span>
        </button>
      </div>
    </div>
  </div>
</div>
