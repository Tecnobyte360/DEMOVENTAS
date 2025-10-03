@assets
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
@endassets

<div class="p-4 md:p-6 bg-gradient-to-br from-white via-white to-gray-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-950 rounded-2xl shadow-xl">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl md:text-2xl font-extrabold text-gray-800 dark:text-white tracking-tight">Cotizaciones</h1>
            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400">Lista al estilo Odoo.</p>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <select wire:model.live="estado" class="px-3 py-2 rounded-md border text-sm dark:bg-gray-800 dark:text-white">
                <option value="todas">Todos los estados</option>
                <option value="borrador">Borrador</option>
                <option value="enviada">Enviada</option>
                <option value="confirmada">Confirmada</option>
                <option value="convertida">Orden de venta</option>
                <option value="cancelada">Cancelada</option>
            </select>
            <select wire:model.live="perPage" class="px-3 py-2 rounded-md border text-sm dark:bg-gray-800 dark:text-white">
                <option value="10">10 por página</option>
                <option value="12">12 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
            </select>
        </div>

        <div class="relative">
            <input type="text" placeholder="Buscar por #, cliente, NIT o estado…"
                   class="pl-9 pr-3 py-2 border rounded-md text-sm w-80 dark:bg-gray-800 dark:text-white"
                   wire:model.debounce.400ms="search">
            <i class="fas fa-search absolute left-3 top-2.5 text-slate-400"></i>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full border-collapse">
            <thead class="bg-slate-50 dark:bg-gray-800/60">
                <tr>
                    <th class="text-left px-3 py-2">Número</th>
                    <th class="text-left px-3 py-2">Fecha</th>
                    <th class="text-left px-3 py-2">Cliente</th>
                    <th class="text-right px-3 py-2">Subtotal</th>
                    <th class="text-right px-3 py-2">Impuestos</th>
                    <th class="text-right px-3 py-2">Total</th>
                    <th class="text-left px-3 py-2">Estado</th>
                    <th class="text-left px-3 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $c)
                    @php
                        $numero = $c->numero ?? ('S'.str_pad($c->id,5,'0',STR_PAD_LEFT));
                        $badge = [
                          'borrador'   => 'bg-slate-200 text-slate-700',
                          'enviada'    => 'bg-indigo-100 text-indigo-700',
                          'confirmada' => 'bg-blue-100 text-blue-700',
                          'convertida' => 'bg-emerald-100 text-emerald-700',
                          'cancelada'  => 'bg-rose-100 text-rose-700',
                        ][$c->estado] ?? 'bg-slate-100 text-slate-700';
                        $estadoText = $c->estado === 'convertida' ? 'Orden de venta' : ucfirst($c->estado);
                    @endphp
                    <tr class="border-t hover:bg-slate-50 dark:hover:bg-gray-800 cursor-pointer"
                        wire:dblclick="abrir({{ $c->id }})">
                        <td class="px-3 py-2 font-semibold text-indigo-700 whitespace-nowrap">{{ $numero }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ \Illuminate\Support\Carbon::parse($c->fecha ?? $c->created_at)->format('d/m/Y') }}
                        </td>
                        <td class="px-3 py-2">
                            <div class="truncate max-w-[360px]">
                                {{ $c->cliente->razon_social ?? '—' }}
                                @if(!empty($c->cliente?->nit))
                                    <span class="text-xs text-slate-400">({{ $c->cliente->nit }})</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">${{ number_format($c->subtotal, 2) }}</td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">${{ number_format($c->impuestos, 2) }}</td>
                        <td class="px-3 py-2 text-right font-semibold whitespace-nowrap">${{ number_format($c->total, 2) }}</td>
                        <td class="px-3 py-2">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badge }}">{{ $estadoText }}</span>
                        </td>
                        <td class="px-3 py-2">
                            <button wire:click="enviar({{ $c->id }})"
                                    class="px-3 py-1 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-xs">
                                <i class="fa-solid fa-paper-plane mr-1"></i> Enviar
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-6 text-center text-slate-500">Sin resultados…</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
<livewire:cotizaciones.enviar-cotizacion-correo :key="'enviar-global'" />
    <div class="mt-4">
        {{ $items->links() }}
    </div>
</div>
