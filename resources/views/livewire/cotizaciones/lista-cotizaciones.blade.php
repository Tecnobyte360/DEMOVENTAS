@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  @endpush

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.5/qz-tray.js"></script>
  @endpush
@endonce

<div class="space-y-4">

    {{-- ====== Título ====== --}}
    <div class="flex items-center gap-2">
        <i class="fa-solid fa-list text-indigo-600"></i>
        <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">
            Cotizaciones generadas
        </h2>

        <div wire:loading class="ml-2 text-xs text-slate-500 dark:text-slate-400 inline-flex items-center gap-2">
            <span class="h-2 w-2 rounded-full bg-indigo-600 animate-pulse"></span>
            Cargando…
        </div>
    </div>

    {{-- ====== Card contenedor ====== --}}
    <section
        class="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">

        {{-- ====== Barra filtros ====== --}}
        <div class="p-4 border-b border-slate-200/70 dark:border-slate-800">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Estado --}}
                    <div class="relative">
                        <select wire:model.live="estado"
                            class="h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800
                                   text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:focus:ring-indigo-900/40">
                            <option value="todas">Todos</option>
                            <option value="borrador">Borrador</option>
                            <option value="enviada">Enviada</option>
                            <option value="confirmada">Confirmada</option>
                            <option value="convertida">Orden de venta</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>

                    {{-- Per page --}}
                    <div class="relative">
                        <select wire:model.live="perPage"
                            class="h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800
                                   text-sm text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:focus:ring-indigo-900/40">
                            <option value="10">10</option>
                            <option value="12">12</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>

                {{-- Buscador --}}
                <div class="relative w-full lg:w-[360px]">
                    <i
                        class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>

                    <input wire:model.debounce.400ms="search" type="text"
                        placeholder="Buscar #, cliente, NIT, estado…"
                        class="w-full h-10 pl-10 pr-10 rounded-lg border border-slate-200 dark:border-slate-700
                               bg-white dark:bg-slate-800 text-sm text-slate-800 dark:text-slate-100
                               focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:focus:ring-indigo-900/40" />

                    @if (!empty($search))
                        <button type="button" wire:click="$set('search','')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 h-7 w-7 rounded-md
                                   bg-slate-100 hover:bg-slate-200 text-slate-600
                                   dark:bg-slate-700 dark:hover:bg-slate-600 dark:text-slate-100"
                            title="Limpiar">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    @endif
                </div>

            </div>
        </div>

        {{-- ====== Tabla ====== --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800/60">
                    <tr class="text-slate-500 dark:text-slate-300 text-[11px] uppercase tracking-wider">
                        <th class="px-4 py-3 text-left font-bold">Número</th>
                        <th class="px-4 py-3 text-left font-bold">Fecha</th>
                        <th class="px-4 py-3 text-left font-bold">Cliente</th>
                        <th class="px-4 py-3 text-right font-bold">Subtotal</th>
                        <th class="px-4 py-3 text-right font-bold">Impuestos</th>
                        <th class="px-4 py-3 text-right font-bold">Total</th>
                        <th class="px-4 py-3 text-left font-bold">Estado</th>
                        <th class="px-4 py-3 text-right font-bold">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($items as $c)
                        @php
                            $numero = $c->numero ?? 'S' . str_pad($c->id, 5, '0', STR_PAD_LEFT);
                            $fecha = \Illuminate\Support\Carbon::parse($c->fecha ?? $c->created_at)->format('d/m/Y');

                            $estado = $c->estado ?? 'borrador';
                            $estadoText = $estado === 'convertida' ? 'Orden de venta' : ucfirst($estado);

                            $badge = match ($estado) {
                                'borrador' => 'bg-slate-100 text-slate-700 dark:bg-slate-700/40 dark:text-slate-200',
                                'enviada' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-200',
                                'confirmada' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200',
                                'convertida'
                                    => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200',
                                'cancelada' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200',
                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-700/40 dark:text-slate-200',
                            };
                        @endphp

                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition cursor-pointer"
                            wire:dblclick="abrir({{ $c->id }})">

                            {{-- Número --}}
                            <td class="px-4 py-3 font-semibold text-indigo-700 dark:text-indigo-300 whitespace-nowrap">
                                {{ $numero }}
                            </td>

                            {{-- Fecha --}}
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200 whitespace-nowrap">
                                {{ $fecha }}
                            </td>

                            {{-- Cliente --}}
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900 dark:text-white truncate max-w-[520px]">
                                    {{ $c->cliente->razon_social ?? '—' }}
                                    @if (!empty($c->cliente?->nit))
                                        <span class="text-xs text-slate-400">
                                            ({{ $c->cliente->nit }})
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Subtotal --}}
                            <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-200 whitespace-nowrap">
                                ${{ number_format((float) ($c->subtotal ?? 0), 2) }}
                            </td>

                            {{-- Impuestos --}}
                            <td class="px-4 py-3 text-right text-slate-700 dark:text-slate-200 whitespace-nowrap">
                                ${{ number_format((float) ($c->impuestos ?? 0), 2) }}
                            </td>

                            {{-- Total --}}
                            <td
                                class="px-4 py-3 text-right font-semibold text-slate-900 dark:text-white whitespace-nowrap">
                                ${{ number_format((float) ($c->total ?? 0), 2) }}
                            </td>

                            {{-- Estado --}}
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                                    {{ $estadoText }}
                                </span>
                            </td>

                            {{-- Acciones --}}
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">

                                    {{-- Editar --}}
                                    <button type="button" wire:click.stop="abrir({{ $c->id }})"
                                        class="h-9 w-9 rounded-lg bg-slate-900 hover:bg-black text-white inline-flex items-center justify-center"
                                        title="Editar">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>

                                    {{-- Enviar correo --}}
                                    <button type="button" wire:click.stop="enviar({{ $c->id }})"
                                        class="h-9 w-9 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center justify-center"
                                        title="Enviar">
                                        <i class="fa-solid fa-envelope text-xs"></i>
                                    </button>

                                    {{-- PDF --}}
                                    <button type="button" wire:click.stop="pdf({{ $c->id }})"
                                        class="h-9 w-9 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white inline-flex items-center justify-center"
                                        title="PDF">
                                        <i class="fa-solid fa-file-pdf text-xs"></i>
                                    </button>
                                    <button type="button" wire:click.stop="imprimir({{ $c->id }})"
                                        class="h-9 w-9 rounded-lg bg-amber-500 hover:bg-amber-600 text-white inline-flex items-center justify-center"
                                        title="Imprimir">
                                        <i class="fa-solid fa-print text-xs"></i>
                                    </button>

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                Sin resultados…
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="p-4 border-t border-slate-200/70 dark:border-slate-800 bg-white dark:bg-slate-900">
            {{ $items->links() }}
        </div>

    </section>

    {{-- Modal envío (hijo) --}}
    <livewire:cotizaciones.enviar-cotizacion-correo :key="'enviar-global'" />

    {{-- ===== Modal preview (si tienes $showPreview y $previewId en el componente) ===== --}}
    @if (!empty($showPreview) && !empty($previewId))
        <div x-data x-on:keydown.escape.window="$wire.closePreview()" class="fixed inset-0 z-[100]">
            <div class="absolute inset-0 bg-black/50" wire:click="closePreview"></div>

            <div
                class="relative mx-auto max-w-[98vw] w-[98vw] h-[98vh] mt-[1vh] bg-white dark:bg-slate-900 rounded-lg shadow-2xl overflow-hidden border border-slate-200 dark:border-slate-800">
                <div
                    class="flex items-center justify-between px-4 py-3 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-sm md:text-base font-semibold text-slate-900 dark:text-white">
                        Previsualización - Cotización #{{ $previewId }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('cotizaciones.preview', $previewId) }}" target="_blank"
                            class="px-3 py-1.5 text-xs rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                            Abrir pestaña
                        </a>
                        <button type="button" wire:click="closePreview"
                            class="px-3 py-1.5 text-xs rounded-lg bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-200 hover:bg-slate-300 dark:hover:bg-slate-700">
                            Cerrar
                        </button>
                    </div>
                </div>

                <div class="h-[calc(98vh-3.5rem)]">
                    <iframe src="{{ route('cotizaciones.preview', $previewId) }}?t={{ now()->timestamp }}"
                        class="w-full h-full" style="border:0" title="Previsualización cotización"></iframe>
                </div>
            </div>
        </div>
    @endif


</div>
@push('scripts')
    <script>
        // =============================================
        // QZ Tray — impresión directa a impresora de red
        // =============================================

        // 1️⃣  Configura aquí el nombre EXACTO de la impresora de red
        const NOMBRE_IMPRESORA = 'HP LaserJet 400'; // ← cámbialo

        // 2️⃣  Escucha el evento de Livewire
        document.addEventListener('abrir-impresion', async (e) => {
            const pdfUrl = e.detail.url;
            await imprimirConQZ(pdfUrl);
        });

        async function imprimirConQZ(pdfUrl) {
            try {
                // Conectar a QZ Tray
                if (!qz.websocket.isActive()) {
                    await qz.websocket.connect();
                }

                // Buscar impresora
                const impresora = await qz.printers.find(NOMBRE_IMPRESORA);

                // Configurar trabajo de impresión
                const config = qz.configs.create(impresora, {
                    copies: 1,
                    colorType: 'blackWhite', // o 'color'
                    orientation: 'portrait', // o 'landscape'
                });

                // Datos: URL del PDF (QZ Tray lo descarga y lo manda a imprimir)
                const data = [{
                    type: 'pixel',
                    format: 'pdf',
                    flavor: 'file',
                    data: pdfUrl,
                }];

                await qz.print(config, data);

                // Notificación de éxito
                mostrarToast('✅ Enviado a impresora: ' + NOMBRE_IMPRESORA, 'success');

            } catch (err) {
                console.error('QZ Tray error:', err);

                if (err.message && err.message.includes('Unable to establish')) {
                    mostrarToast('❌ QZ Tray no está activo. Por favor ábrelo en tu PC.', 'error');
                } else {
                    mostrarToast('❌ Error al imprimir: ' + err.message, 'error');
                }
            }
        }

        // Toast simple de notificación
        function mostrarToast(msg, tipo) {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-5 right-5 z-[9999] px-4 py-3 rounded-xl shadow-lg text-white text-sm font-medium transition-all
        ${tipo === 'success' ? 'bg-emerald-600' : 'bg-rose-600'}`;
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    </script>
@endpush
