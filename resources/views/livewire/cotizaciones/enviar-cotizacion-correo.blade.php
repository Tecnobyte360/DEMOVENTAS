<div x-cloak x-data x-show="@entangle('show').live" style="display:none" class="fixed inset-0 z-[100]">

    <div class="absolute inset-0 bg-black/40" @click="$wire.cerrar()"></div>

    <div class="absolute left-1/2 top-10 -translate-x-1/2 w-[95%] md:w-[880px] rounded-2xl bg-white dark:bg-gray-900 shadow-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Enviar</h3>
            <button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" @click="$wire.cerrar()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">
            <div>
                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Para</label>
                <input type="email" wire:model.defer="email_to"
                       class="mt-1 w-full rounded-xl border px-3 py-2 dark:bg-gray-800 dark:text-white"
                       placeholder="cliente@correo.com">
                @error('email_to') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">CC (opcional)</label>
                <input type="text" wire:model.defer="email_cc"
                       class="mt-1 w-full rounded-xl border px-3 py-2 dark:bg-gray-800 dark:text-white"
                       placeholder="separar con coma: a@b.com, c@d.com">
            </div>

            <div>
                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Asunto</label>
                <input type="text" wire:model.defer="email_subject"
                       class="mt-1 w-full rounded-xl border px-3 py-2 dark:bg-gray-800 dark:text-white">
                @error('email_subject') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Mensaje</label>
                <textarea rows="8" wire:model.defer="email_body"
                          class="mt-1 w-full rounded-xl border px-3 py-2 dark:bg-gray-800 dark:text-white"></textarea>
                @error('email_body') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="pt-2">
                <div class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-2">Adjuntos</div>
                @if($email_attachment_path && $email_attachment_name)
                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-100 dark:bg-gray-800">
                        <i class="fa-solid fa-paperclip"></i>
                        <span class="text-sm">{{ $email_attachment_name }}</span>
                        <button class="text-rose-600 hover:underline" wire:click="quitarAdjunto" type="button">&times;</button>
                    </div>
                @else
                    <button type="button" wire:click="regenerarAdjunto"
                            class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700">
                        <i class="fa-solid fa-file-pdf"></i> Generar PDF
                    </button>
                @endif
            </div>
        </div>

        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-3">
            <button class="px-4 py-2 rounded-xl border" @click="$wire.cerrar()" type="button">Cancelar</button>
            <button class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700"
                    wire:click="enviarCorreo" type="button">
                Enviar
            </button>
        </div>
    </div>
</div>
