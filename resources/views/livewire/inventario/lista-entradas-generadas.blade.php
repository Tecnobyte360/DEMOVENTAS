<div class="space-y-6">
  {{-- ===== Filtros ===== --}}
  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 md:p-6 shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Desde</label>
        <input type="date" wire:model.defer="filtro_desde"
               class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hasta</label>
        <input type="date" wire:model.defer="filtro_hasta"
               class="mt-1 w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
      </div>
      <div class="flex gap-3 md:col-span-2">
        <button wire:click="aplicarFiltros"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold shadow hover:bg-indigo-700">
          <i class="fa-solid fa-magnifying-glass"></i> Buscar
        </button>
        <button wire:click="limpiarFiltros"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold border border-gray-200 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700">
          <i class="fa-solid fa-eraser"></i> Limpiar
        </button>
      </div>
    </div>
  </div>

  {{-- ===== Tabla ===== --}}
  <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800/50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Fecha</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Socio de negocio</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Observaciones</th>
            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
          @forelse ($entradas as $e)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
              <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">EM-{{ $e->id }}</td>
              <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                {{ optional($e->fecha_contabilizacion)->format('Y-m-d') ?? \Carbon\Carbon::parse($e->fecha_contabilizacion)->format('Y-m-d') }}
              </td>
              <td class="px-4 py-3 text-sm">
                <div class="text-gray-900 dark:text-gray-100 font-medium">
                  {{ $e->socioNegocio->razon_social ?? '—' }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ $e->socioNegocio->nit ?? '' }}
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[420px] truncate" title="{{ $e->observaciones }}">
                {{ $e->observaciones ?? '—' }}
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <button wire:click="toggleDetalleFila({{ $e->id }})"
                          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-200 border border-indigo-100 dark:border-gray-700 hover:bg-indigo-100 dark:hover:bg-gray-700">
                    <i class="fa-solid fa-eye"></i> Ver detalle
                  </button>
                  {{-- Si tienes impresión: --}}
                  {{-- <a href="{{ route('inventario.entradas.imprimir', $e) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 border hover:bg-gray-200 dark:hover:bg-gray-700">
                    <i class="fa-solid fa-print"></i> Imprimir
                  </a> --}}
                </div>
              </td>
            </tr>

            {{-- Fila expandible de detalle --}}
            @if ($filaAbiertaId === $e->id)
              <tr class="bg-gray-50/70 dark:bg-gray-800/40">
                <td colspan="5" class="px-4 py-4">
                  @php
                    $detalles = $detallesPorEntrada[$e->id] ?? collect();
                    $total = $detalles->reduce(fn($c, $d) => $c + (($d->cantidad ?? 0) * ($d->precio_unitario ?? 0)), 0);
                  @endphp

                  <div class="space-y-3">
                    <div class="flex items-center justify-between">
                      <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Detalle de EM-{{ $e->id }}
                      </p>
                      <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        Total: {{ number_format($total, 2, ',', '.') }}
                      </p>
                    </div>

                    <div class="overflow-x-auto">
                      <table class="min-w-full text-sm">
                        <thead>
                          <tr class="text-left text-xs uppercase text-gray-600 dark:text-gray-300">
                            <th class="px-3 py-2">Producto</th>
                            <th class="px-3 py-2">Bodega</th>
                            <th class="px-3 py-2">Descripción</th>
                            <th class="px-3 py-2 text-right">Cantidad</th>
                            <th class="px-3 py-2 text-right">Precio</th>
                            <th class="px-3 py-2 text-right">Subtotal</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                          @forelse($detalles as $d)
                            @php
                              $sub = ($d->cantidad ?? 0) * ($d->precio_unitario ?? 0);
                            @endphp
                            <tr>
                              <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                                {{ $d->producto->nombre ?? '—' }}
                              </td>
                              <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                {{ $d->bodega->nombre ?? '—' }}
                              </td>
                              <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                {{ $d->producto->descripcion ?? '—' }}
                              </td>
                              <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-100">
                                {{ number_format($d->cantidad ?? 0, 0, ',', '.') }}
                              </td>
                              <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-100">
                                {{ number_format($d->precio_unitario ?? 0, 2, ',', '.') }}
                              </td>
                              <td class="px-3 py-2 text-right text-gray-800 dark:text-gray-100 font-medium">
                                {{ number_format($sub, 2, ',', '.') }}
                              </td>
                            </tr>
                          @empty
                            <tr>
                              <td colspan="6" class="px-3 py-3 text-center text-gray-500 dark:text-gray-400">
                                Sin líneas de detalle.
                              </td>
                            </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>

                    <div class="flex justify-end">
                      <button wire:click="cerrarFila"
                              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <i class="fa-solid fa-xmark"></i> Cerrar detalle
                      </button>
                    </div>
                  </div>
                </td>
              </tr>
            @endif
          @empty
            <tr>
              <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                No hay entradas para el rango seleccionado.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800">
      {{ $entradas->links() }}
    </div>
  </div>
</div>
