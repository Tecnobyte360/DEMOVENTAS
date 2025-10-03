{{-- En tu layout global incluye Alpine.js, @livewireScripts y <style>[x-cloak]{display:none!important}</style> --}}
<div x-data="{ tab:'lineas' }"
     class="p-4 md:p-6 bg-gradient-to-br from-white via-white to-gray-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950 rounded-2xl shadow-xl space-y-6 md:space-y-8">

    {{-- ENCABEZADO --}}
    <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 md:gap-4 border-b border-gray-200/70 dark:border-gray-800 pb-4">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-600">
                <i class="fas fa-file-signature"></i>
            </span>
            <div>
                <h1 class="text-xl md:text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">
                    @if($cotizacion) Editar Cotización #{{ $cotizacion->id }} @else Nueva Cotización @endif
                </h1>
                <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">Crea, envía y confirma cotizaciones al estilo Odoo.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" class="px-4 py-2 rounded-xl bg-purple-800 text-white hover:bg-purple-900" wire:click="guardar">
                <i class="fas fa-floppy-disk mr-2"></i> Guardar
            </button>
            <button type="button" class="px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700" wire:click="enviar">
                <i class="fas fa-paper-plane mr-2"></i> Enviar
            </button>
            <button type="button" class="px-4 py-2 rounded-xl bg-blue-700 text-white hover:bg-blue-800" wire:click="aprobarYGenerarPedido">
                <i class="fas fa-check mr-2"></i> Confirmar
            </button>
            <button type="button" class="px-4 py-2 rounded-xl bg-rose-600 text-white hover:bg-rose-700" wire:click="cancelar">
                <i class="fas fa-ban mr-2"></i> Cancelar
            </button>
        </div>
    </header>

    {{-- MENSAJES --}}
    @if(session()->has('ok'))
        <div class="p-3 md:p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl shadow-sm">
            {{ session('ok') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="p-3 md:p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl shadow-sm">
            Revise los campos obligatorios de la cotización.
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {{-- IZQUIERDA --}}
        <div class="lg:col-span-8 space-y-6">
            {{-- CABECERA --}}
            <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 p-4 md:p-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div>
                        <label class="text-xs md:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 block">Cliente</label>
                        <select wire:model="socio_negocio_id" class="w-full rounded-xl px-3 py-2 border dark:bg-gray-800 dark:text-white">
                            <option value="">— Seleccione —</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}">{{ $c->razon_social }} ({{ $c->nit }})</option>
                            @endforeach
                        </select>
                        @error('socio_negocio_id') <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4"
                         x-data="{fpVenc:null}"
                         x-init="
                            fpVenc = flatpickr($refs.venc, {
                              dateFormat:'Y-m-d', altInput:true, altFormat:'d/m/Y',
                              defaultDate: @js($vencimiento),
                              onChange: (_sel, iso) => $wire.set('vencimiento', iso)
                            });
                            Livewire.hook('message.processed', () => { fpVenc && fpVenc.setDate(@js($vencimiento), true); });
                         ">
                        <div>
                            <label class="text-xs md:text-sm font-medium">Vencimiento</label>
                            <input x-ref="venc" type="text" placeholder="dd/mm/aaaa"
                                   class="w-full rounded-xl px-3 py-2 border dark:bg-gray-800 dark:text-white">
                        </div>
                        <div>
                            <label class="text-xs md:text-sm font-medium">Fecha</label>
                            <input type="date" wire:model="fecha"
                                   class="w-full rounded-xl px-3 py-2 border dark:bg-gray-800 dark:text-white">
                        </div>
                    </div>

                    <div>
                        <label class="text-xs md:text-sm font-medium">Lista de precios</label>
                        <input type="text" wire:model.defer="lista_precio" class="w-full rounded-xl px-3 py-2 border dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="text-xs md:text-sm font-medium">Términos de pago</label>
                        <input type="text" wire:model.defer="terminos_pago" class="w-full rounded-xl px-3 py-2 border dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
            </section>

            {{-- TABS --}}
            <div class="border-b">
                <nav class="flex gap-2">
                    <button class="px-4 py-2 -mb-px border-b-2" :class="tab==='lineas' ? 'border-slate-800 font-medium' : 'border-transparent text-slate-500'" @click="tab='lineas'">Líneas</button>
                    <button class="px-4 py-2 -mb-px border-b-2" :class="tab==='opc' ? 'border-slate-800 font-medium' : 'border-transparent text-slate-500'" @click="tab='opc'">Opcionales</button>
                    <button class="px-4 py-2 -mb-px border-b-2" :class="tab==='info' ? 'border-slate-800 font-medium' : 'border-transparent text-slate-500'" @click="tab='info'">Otra info</button>
                </nav>
            </div>

            {{-- LÍNEAS --}}
            <section x-show="tab==='lineas'" class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b">
                            <th class="text-left p-3">Producto</th>
                            <th class="text-left p-3">Bodega</th>
                            <th class="text-right p-3">Cantidad</th>
                            <th class="text-right p-3">Precio</th>
                            <th class="text-right p-3">Desc %</th>
                            <th class="text-right p-3">Impuesto %</th>
                            <th class="text-right p-3">Importe</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lineas as $i => $l)
                            <tr class="border-b hover:bg-slate-50 dark:hover:bg-gray-800">
                                <td class="p-3 min-w-[220px]">
                                    <select class="w-full border rounded-xl p-2 dark:bg-gray-800 dark:text-white"
                                            wire:change="setProducto({{ $i }}, $event.target.value)">
                                        <option value="">— Seleccione —</option>
                                        @foreach($productos as $p)
                                            <option value="{{ $p->id }}" @selected($l['producto_id']==$p->id)>{{ $p->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error("lineas.$i.producto_id") <div class="text-rose-600 text-xs mt-1">{{ $message }}</div> @enderror
                                </td>

                                <td class="p-3 min-w-[180px]">
                                    <select class="w-full border rounded-xl p-2 dark:bg-gray-800 dark:text-white"
                                            wire:model.lazy="lineas.{{ $i }}.bodega_id">
                                        <option value="">— Seleccione —</option>
                                        @foreach($bodegas as $b)
                                            <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="p-3 text-right">
                                    <input type="number" min="0" step="0.001" class="w-24 border rounded-xl p-2 text-right dark:bg-gray-800 dark:text-white"
                                           value="{{ $l['cantidad'] }}" wire:change="setCantidad({{ $i }}, $event.target.value)">
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" step="0.01" class="w-24 border rounded-xl p-2 text-right dark:bg-gray-800 dark:text-white"
                                           value="{{ $l['precio_unitario'] }}" wire:change="setPrecio({{ $i }}, $event.target.value)">
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" step="0.001" class="w-20 border rounded-xl p-2 text-right dark:bg-gray-800 dark:text-white"
                                           value="{{ $l['descuento_pct'] }}" wire:change="setDescuento({{ $i }}, $event.target.value)">
                                </td>
                                <td class="p-3 text-right">
                                    <input type="number" min="0" step="0.001" class="w-20 border rounded-xl p-2 text-right dark:bg-gray-800 dark:text-white"
                                           value="{{ $l['impuesto_pct'] }}" wire:change="setImpuesto({{ $i }}, $event.target.value)">
                                </td>
                                <td class="p-3 text-right">${{ number_format($l['importe'], 2) }}</td>

                                <td class="p-3 text-right">
                                    <button class="text-rose-600" wire:click="removeLinea({{ $i }})">Quitar</button>
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <td colspan="8" class="p-3">
                                <div class="flex items-center gap-6 text-indigo-600">
                                    <button type="button" wire:click="addLinea" class="hover:underline">Agregar un producto</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="p-4 border-t text-slate-400">
                    <textarea wire:model.defer="notas" rows="3" placeholder="Términos y condiciones…"
                              class="w-full border rounded-xl p-3 dark:bg-gray-800 dark:text-white"></textarea>
                </div>
            </section>
        </div>

        {{-- DERECHA: totales --}}
        <div class="lg:col-span-4 space-y-4">
            @php
                $sub = collect($lineas)->sum('importe');
                $imp = collect($lineas)->sum(function($l){
                    $base = ($l['cantidad']*$l['precio_unitario'])*(1 - $l['descuento_pct']/100);
                    return $base * ($l['impuesto_pct']/100);
                });
            @endphp
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 overflow-hidden">
                <div class="px-4 py-3 flex justify-between border-b">
                    <span>Subtotal:</span><span>${{ number_format($sub,2) }}</span>
                </div>
                <div class="px-4 py-3 flex justify-between">
                    <span class="font-semibold">Total:</span><span class="font-semibold">${{ number_format($sub + $imp,2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- lista al pie (doble clic abre) --}}
    <livewire:cotizaciones.lista-cotizaciones />

 


</div>
