{{-- resources/views/livewire/facturas/lista-notas-credito.blade.php --}}

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
          <option value="aplicada">Aplicada</option>
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
               wire:model.debounce.400ms="q">
      </div>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="overflow-x-auto rounded-3xl border border-gray-200/70 dark:border-gray-800 bg-white/60 dark:bg-gray-900/60 backdrop-blur-md shadow-xl">
    <table class="min-w-full text-xs sm:text-sm text-gray-700 dark:text-gray-100">
      <thead class="bg-gradient-to-r from-gray-100 via-gray-50 to-gray-100 dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 text-[11px] sm:text-xs uppercase tracking-wider text-gray-600 dark:text-gray-300">
        <tr>
          <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Prefijo</th>
          <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Número</th>
          <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Fecha</th>
          <th class="p-2 sm:p-3 text-left font-semibold">Cliente</th>
          <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Empleado</th>
          <th class="p-2 sm:p-3 text-right font-semibold whitespace-nowrap">Subtotal</th>
          <th class="p-2 sm:p-3 text-right font-semibold whitespace-nowrap">Impuestos</th>
          <th class="p-2 sm:p-3 text-right font-semibold whitespace-nowrap">Total</th>
          <th class="p-2 sm:p-3 text-left font-semibold whitespace-nowrap">Estado</th>
        </tr>
      </thead>

      <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse($items as $n)
          @php
            $len = $n->serie->longitud ?? 6;
            $num = $n->numero !== null ? str_pad((string) $n->numero, $len, '0', STR_PAD_LEFT) : '—';

            $badge = [
              'borrador' => 'bg-slate-100 text-slate-700',
              'emitida'  => 'bg-indigo-100 text-indigo-700',
              'aplicada' => 'bg-emerald-100 text-emerald-700',
              'anulada'  => 'bg-rose-100 text-rose-700',
            ][$n->estado] ?? 'bg-slate-100 text-slate-700';

            $empleado = $n->creadoPor->name ?? '—';
            $inicial = $empleado !== '—' ? strtoupper(mb_substr($empleado, 0, 1)) : '—';
          @endphp

          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition-all duration-200 ease-in-out">
            <td class="p-2 sm:p-3 font-medium whitespace-nowrap">
              {{ $n->prefijo ?? '—' }}
            </td>

            <td class="p-2 sm:p-3 font-semibold text-indigo-700 dark:text-indigo-300 whitespace-nowrap">
              {{ $num }}
            </td>

            <td class="p-2 sm:p-3 whitespace-nowrap">
              {{ \Illuminate\Support\Carbon::parse($n->fecha)->format('d/m/Y') }}
            </td>

            <td class="p-2 sm:p-3">
              <div class="truncate max-w-[220px] sm:max-w-[340px]">
                {{ $n->cliente->razon_social ?? '—' }}
                @if(!empty($n->cliente?->nit))
                  <span class="text-[10px] sm:text-xs text-gray-400">
                    ({{ $n->cliente->nit }})
                  </span>
                @endif
              </div>
            </td>

            {{-- Empleado --}}
            <td class="p-2 sm:p-3 whitespace-nowrap">
              <div class="flex items-center gap-2 min-w-[160px]">
                <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-xs font-bold">
                  {{ $inicial }}
                </div>
                <div class="truncate max-w-[130px] sm:max-w-[180px]">
                  {{ $empleado }}
                </div>
              </div>
            </td>

            <td class="p-2 sm:p-3 text-right whitespace-nowrap">
              ${{ number_format($n->subtotal, 2) }}
            </td>

            <td class="p-2 sm:p-3 text-right whitespace-nowrap">
              ${{ number_format($n->impuestos, 2) }}
            </td>

            <td class="p-2 sm:p-3 text-right font-semibold whitespace-nowrap">
              ${{ number_format($n->total, 2) }}
            </td>

            <td class="p-2 sm:p-3 whitespace-nowrap">
              <span class="px-2.5 sm:px-3 py-1 rounded-full text-[10px] sm:text-[11px] font-semibold {{ $badge }} shadow-sm">
                {{ ucwords(str_replace('_', ' ', $n->estado)) }}
              </span>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="p-6 text-center text-gray-500 dark:text-gray-400">
              Sin resultados…
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-2">
    {{ $items->links() }}
  </div>

</div>