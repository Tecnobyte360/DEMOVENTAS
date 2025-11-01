<div class="p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-xl space-y-6">
  {{-- Filtros --}}
  <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
    <div>
      <label class="text-sm text-gray-600 dark:text-gray-300">Producto</label>
      <select wire:model.live="producto_id" class="w-full mt-1 rounded-xl border dark:bg-gray-800 dark:text-white">
        <option value="">— Selecciona —</option>
        @foreach($this->productos as $p)
          <option value="{{ $p->id }}">{{ $p->nombre }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-sm text-gray-600 dark:text-gray-300">Bodega</label>
      <select wire:model.live="bodega_id" class="w-full mt-1 rounded-xl border dark:bg-gray-800 dark:text-white">
        <option value="">— Todas —</option>
        @foreach($this->bodegas as $b)
          <option value="{{ $b->id }}">{{ $b->nombre }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-sm text-gray-600 dark:text-gray-300">Desde</label>
      <input type="date" wire:model.live="desde" class="w-full mt-1 rounded-xl border dark:bg-gray-800 dark:text-white">
    </div>
    <div>
      <label class="text-sm text-gray-600 dark:text-gray-300">Hasta</label>
      <input type="date" wire:model.live="hasta" class="w-full mt-1 rounded-xl border dark:bg-gray-800 dark:text-white">
    </div>
    <div>
      <label class="text-sm text-gray-600 dark:text-gray-300">Documento / Ref</label>
      <input type="text" wire:model.defer="buscarDoc" placeholder="OC, Remisión, Ref..."
             class="w-full mt-1 rounded-xl border dark:bg-gray-800 dark:text-white">
    </div>
  </div>

  <div class="flex items-center justify-between gap-3">
    <div class="text-sm text-gray-600 dark:text-gray-300">
      <span class="font-semibold">Saldo inicial</span> —
      Cant: <strong>{{ number_format($this->saldoInicialCant, 6) }}</strong>,
      Valor: <strong>${{ number_format($this->saldoInicialVal, 2) }}</strong>
    </div>
    <div class="flex items-center gap-2">
      <label class="text-sm">Filas</label>
      <select wire:model.live="perPage" class="rounded-lg border dark:bg-gray-800 dark:text-white">
        <option>10</option><option>25</option><option>50</option><option>100</option>
      </select>
      <button wire:click="$refresh" class="px-3 py-1.5 rounded-lg bg-violet-600 text-white">Aplicar</button>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="overflow-x-auto">
    <table class="min-w-[1200px] w-full text-sm border dark:border-gray-700">
      <thead class="bg-violet-600 text-white">
        <tr>
          <th class="px-3 py-2 text-left">Fecha</th>
          <th class="px-3 py-2 text-left">Bodega</th>
          <th class="px-3 py-2 text-left">Doc / Ref</th>
          <th class="px-3 py-2 text-center">Tipo</th>
          <th class="px-3 py-2 text-right">Entrada</th>
          <th class="px-3 py-2 text-right">Salida</th>
          <th class="px-3 py-2 text-right">Costo Unit.</th>
          <th class="px-3 py-2 text-right">Saldo Cant.</th>
          <th class="px-3 py-2 text-right">Saldo CPU</th>
          <th class="px-3 py-2 text-right">Saldo Valor</th>
        </tr>
      </thead>
      <tbody class="divide-y dark:divide-gray-700">
        @forelse($filas as $r)
          <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
            <td class="px-3 py-2">{{ $r['fecha'] }}</td>
            <td class="px-3 py-2">{{ $r['bodega'] ?? '—' }}</td>
            <td class="px-3 py-2">{{ $r['doc'] }}</td>
            <td class="px-3 py-2 text-center">
              <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                {{ $r['tipo']==='ENTRADA' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $r['tipo'] }}
              </span>
            </td>
            <td class="px-3 py-2 text-right">{{ $r['entrada'] !== null ? number_format($r['entrada'], 6) : '—' }}</td>
            <td class="px-3 py-2 text-right">{{ $r['salida']  !== null ? number_format($r['salida'],  6) : '—' }}</td>
            <td class="px-3 py-2 text-right">{{ $r['costo_unit'] !== null ? number_format($r['costo_unit'], 6) : '—' }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($r['saldo_cant'], 6) }}</td>
            <td class="px-3 py-2 text-right">{{ $r['saldo_cpu']  !== null ? number_format($r['saldo_cpu'], 6) : '—' }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($r['saldo_val'], 2) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="10" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">
              Selecciona un producto para ver su kardex.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Paginación + Saldos finales --}}
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      {{ $filas->links() }}
    </div>
    <div class="text-sm text-gray-600 dark:text-gray-300">
      <span class="font-semibold">Saldo final</span> —
      Cant: <strong>{{ number_format($this->saldoFinalCant, 6) }}</strong>,
      Valor: <strong>${{ number_format($this->saldoFinalVal, 2) }}</strong>
    </div>
  </div>
</div>
