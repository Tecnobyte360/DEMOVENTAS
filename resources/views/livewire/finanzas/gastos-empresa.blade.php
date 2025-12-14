<form wire:submit.prevent="guardarGasto" class="space-y-10">

    {{-- HEADER --}}
    <section class="bg-gradient-to-br from-white to-gray-100 dark:from-gray-900 dark:to-gray-800 p-8 rounded-3xl shadow-2xl space-y-8">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b pb-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-2xl bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-700 dark:text-violet-300">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Gastos de la Empresa</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Registra gastos por ruta o administrativos y visualiza el historial filtrado.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Filtro de vista</span>
                <select wire:model="filtroTipo"
                        class="px-3 py-1.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-800 dark:text-gray-100 focus:ring-2 focus:ring-violet-500">
                    <option value="todos">Todos los gastos</option>
                    <option value="ruta">Solo con ruta</option>
                    <option value="admin">Solo administrativos</option>
                </select>
            </div>
        </div>

        {{-- CARDS RESUMEN --}}
        @php
            $total   = $gastosFiltrados->sum('monto');
            $conRuta = $gastosFiltrados->whereNotNull('ruta_id')->sum('monto');
            $admin   = $gastosFiltrados->whereNull('ruta_id')->sum('monto');
        @endphp

        <div class="grid md:grid-cols-3 gap-4">
            <div class="bg-white/80 dark:bg-gray-900/80 rounded-2xl p-5 shadow border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">Total gastos</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${{ number_format($total, 0, ',', '.') }}
                    </p>
                </div>
                <div class="h-10 w-10 rounded-2xl bg-violet-100 dark:bg-violet-900/40 flex items-center justify-center text-violet-700 dark:text-violet-300">
                    <i class="fas fa-coins"></i>
                </div>
            </div>

            <div class="bg-white/80 dark:bg-gray-900/80 rounded-2xl p-5 shadow border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">Con ruta</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${{ number_format($conRuta, 0, ',', '.') }}
                    </p>
                </div>
                <div class="h-10 w-10 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-300">
                    <i class="fas fa-route"></i>
                </div>
            </div>

            <div class="bg-white/80 dark:bg-gray-900/80 rounded-2xl p-5 shadow border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">Administrativos</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        ${{ number_format($admin, 0, ',', '.') }}
                    </p>
                </div>
                <div class="h-10 w-10 rounded-2xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-amber-700 dark:text-amber-300">
                    <i class="fas fa-building"></i>
                </div>
            </div>
        </div>

        {{-- MENSAJES (además del toaster) --}}
        @if (session()->has('message'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-3 rounded-2xl shadow-sm flex items-center gap-2 mt-4">
                <i class="fas fa-check-circle"></i> <span>{{ session('message') }}</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-3 rounded-2xl shadow-sm flex items-center gap-2 mt-4">
                <i class="fas fa-times-circle"></i> <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- FORMULARIO --}}
        <div class="space-y-4 mt-4">

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

                {{-- Serie (NUEVO) --}}
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">Serie *</label>

                    <select wire:model="serie_id"
                            class="w-full px-4 py-2 rounded-xl border @error('serie_id') border-red-500 @else border-gray-300 @enderror
                                   dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500">
                        <option value="">-- Selecciona serie --</option>
                        @foreach ($series as $s)
                            <option value="{{ $s['id'] }}">
                                {{ $s['nombre'] }}{{ $s['prefijo'] ? ' ('.$s['prefijo'].')' : '' }}
                            </option>
                        @endforeach
                    </select>

                    @error('serie_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Siguiente: <span class="font-semibold">{{ $this->previewSiguiente() ?: '—' }}</span>
                    </p>
                </div>

                {{-- Concepto contable --}}
                <div class="space-y-1 md:col-span-2">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">Concepto contable *</label>
                    <select wire:model="concepto_documento_id"
                            class="w-full px-4 py-2 rounded-xl border @error('concepto_documento_id') border-red-500 @else border-violet-400 @enderror dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500">
                        <option value="">-- Selecciona concepto --</option>
                        @foreach ($conceptosContables as $c)
                            <option value="{{ $c->id }}">{{ $c->codigo }} — {{ $c->nombre }}</option>
                        @endforeach
                    </select>
                    @error('concepto_documento_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Tipo de gasto --}}
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">Tipo de gasto *</label>
                    <select wire:model="tipo_gasto_id"
                            class="w-full px-4 py-2 rounded-xl border @error('tipo_gasto_id') border-red-500 @else border-gray-300 @enderror dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500">
                        <option value="">-- Selecciona tipo --</option>
                        @foreach ($tiposGasto as $tg)
                            <option value="{{ $tg->id }}">{{ $tg->nombre }}</option>
                        @endforeach
                    </select>
                    @error('tipo_gasto_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Monto --}}
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">Monto *</label>
                    <input type="number" wire:model="monto" step="0.01"
                           class="w-full px-4 py-2 rounded-xl border @error('monto') border-red-500 @else border-gray-300 @enderror dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                           placeholder="0.00">
                    @error('monto') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Ruta (opcional) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">Ruta (opcional)</label>
                    <select wire:model="ruta_id"
                            class="w-full px-4 py-2 rounded-xl border @error('ruta_id') border-red-500 @else border-gray-300 @enderror dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500">
                        <option value="">Administración</option>
                        @foreach ($rutas as $r)
                            <option value="{{ $r->id }}">{{ $r->ruta }}</option>
                        @endforeach
                    </select>
                    @error('ruta_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Observación --}}
                <div class="space-y-1 md:col-span-2">
                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-300">Observación</label>
                    <textarea wire:model="observacion" rows="3"
                              class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500"
                              placeholder="Detalles del gasto..."></textarea>
                    @error('observacion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Botones --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pt-3">
                <button type="button" wire:click="$reset"
                        class="px-5 py-2 rounded-full bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-medium shadow-sm">
                    <i class="fas fa-eraser mr-1"></i> Limpiar formulario
                </button>

                <button type="submit"
                        class="px-6 py-2 rounded-full bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold shadow-md flex items-center gap-2"
                        wire:loading.attr="disabled">
                    <i class="fas fa-save"></i>
                    <span wire:loading.remove>Guardar gasto</span>
                    <span wire:loading>Guardando...</span>
                </button>
            </div>
        </div>

        {{-- HISTORIAL --}}
        <div class="mt-10">
            <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3 flex items-center gap-2">
                <i class="fas fa-list text-violet-500"></i> Historial de gastos
            </h3>

            <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
                <table class="min-w-full text-sm text-gray-700 dark:text-gray-200">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Documento</th>
                        <th class="px-4 py-3 text-left">Ruta / área</th>
                        <th class="px-4 py-3 text-left">Tipo gasto</th>
                        <th class="px-4 py-3 text-left">Concepto</th>
                        <th class="px-4 py-3 text-right">Monto</th>
                        <th class="px-4 py-3 text-left">Observación</th>
                        <th class="px-4 py-3 text-center">Caja</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse ($gastosFiltrados as $gasto)
                        @php
                            // Documento: PREFIJO-000123
                            $pref = $gasto->prefijo ?? '';
                            $num  = $gasto->numero ?? null;

                            // Si no tienes longitud en gasto, dejamos padding simple
                            $doc = '—';
                            if ($num !== null) {
                                $doc = ($pref ? $pref.'-' : '') . str_pad((string)$num, 6, '0', STR_PAD_LEFT);
                            }

                            $tieneCaja = !empty($gasto->caja_movimiento_id);
                        @endphp

                        <tr class="border-t border-gray-100 dark:border-gray-700 hover:bg-violet-50/60 dark:hover:bg-gray-800 transition">
                            <td class="px-4 py-3">
                                {{ optional($gasto->created_at)->format('Y-m-d') }}
                            </td>

                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">
                                {{ $doc }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $gasto->ruta->ruta ?? 'Administración' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $gasto->tipoGasto->nombre ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $gasto->conceptoDocumento->codigo ?? '—' }}
                                {{ $gasto->conceptoDocumento ? ' — '.$gasto->conceptoDocumento->nombre : '' }}
                            </td>

                            <td class="px-4 py-3 text-right font-semibold">
                                ${{ number_format((float)$gasto->monto, 2, ',', '.') }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $gasto->observacion }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                @if ($tieneCaja)
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                                        <i class="fas fa-check-circle"></i> Sí
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        <i class="fas fa-minus-circle"></i> No
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400 text-sm italic">
                                <i class="fas fa-info-circle mr-1"></i>
                                No hay gastos registrados con el filtro seleccionado.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </section>
</form>
