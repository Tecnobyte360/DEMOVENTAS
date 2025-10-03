<div class="w-full px-4 sm:px-6 lg:px-8">
  <div class="mx-auto max-w-md md:max-w-lg lg:max-w-3xl xl:max-w-5xl bg-white/90 dark:bg-gray-800/90 border border-gray-200 dark:border-gray-700 p-6 md:p-8 rounded-2xl shadow-xl">

    {{-- ALERTAS --}}
    <div x-data="{show:true}" x-init="setTimeout(()=>show=false,4000)">
      @if (session()->has('message'))
        <div x-show="show" role="alert"
             class="mb-4 rounded-xl border border-emerald-300/60 bg-emerald-50 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-100 px-4 py-3 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 1 0 10 10A10.012 10.012 0 0 0 12 2Zm1 15h-2v-2h2Zm0-4h-2V7h2Z"/></svg>
            <span class="font-medium">{{ session('message') }}</span>
          </div>
          <button type="button" wire:click="$set('message','')" class="opacity-70 hover:opacity-100">✕</button>
        </div>
      @endif

      @if (session()->has('error'))
        <div x-show="show" role="alert"
             class="mb-4 rounded-xl border border-red-300/60 bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-100 px-4 py-3 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2A10 10 0 1 0 22 12 10.012 10.012 0 0 0 12 2Zm1 15h-2v-2h2Zm0-4h-2V7h2Z"/></svg>
            <span class="font-medium">{{ session('error') }}</span>
          </div>
          <button type="button" wire:click="$set('error','')" class="opacity-70 hover:opacity-100">✕</button>
        </div>
      @endif
    </div>

    {{-- HEADER --}}
    <header class="mb-6">
      <div class="flex items-center gap-3">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-600">
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11V3H4a1 1 0 0 0-1 1v15a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-9ZM6 7h8v2H6Zm0 4h8v2H6Zm0 4h6v2H6Zm12 4h-1v-8h3Z"/></svg>
        </span>
        <div>
          <h2 class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white">Nuevo socio de negocio</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">Registra los datos generales, fiscales y direcciones de entrega.</p>
        </div>
      </div>
    </header>

    <form wire:submit.prevent="save" class="space-y-10">

      {{-- =================== Datos generales =================== --}}
      <section>
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100">Datos generales</h3>
          <span class="text-[11px] px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">Obligatorios *</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          {{-- Razón Social --}}
          <div class="sm:col-span-2 lg:col-span-1">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Razón Social *</label>
            <input type="text" wire:model.debounce.500ms="razon_social"
                   class="mt-1 w-full rounded-lg border @error('razon_social') border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 @enderror shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                   placeholder="Ej: Industrias XYZ S.A.S.">
            @error('razon_social') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          {{-- NIT --}}
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">NIT/Cédula *</label>
            <input type="text" wire:model.debounce.500ms="nit" oninput="this.value=this.value.replace(/[^\d]/g,'')"
                   class="mt-1 w-full rounded-lg border @error('nit') border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 @enderror shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                   pattern="^[0-9]{6,20}$" placeholder="Solo números">
            @error('nit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          {{-- Tipo --}}
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Tipo *</label>
            <select wire:model.debounce.500ms="Tipo"
                    class="mt-1 w-full rounded-lg border @error('Tipo') border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 @enderror shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600">
              <option value="">Selecciona</option>
              <option value="C">Cliente</option>
              <option value="P">Proveedor</option>
            </select>
            @error('Tipo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          {{-- Teléfono Fijo --}}
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Teléfono Fijo</label>
            <input type="text" wire:model.debounce.500ms="telefono_fijo" oninput="this.value=this.value.replace(/[^\d]/g,'')"
                   class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                   pattern="^\d{7,15}$" placeholder="Ej: 6041234567">
            @error('telefono_fijo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          {{-- Teléfono Móvil --}}
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Teléfono Móvil</label>
            <input type="text" wire:model.debounce.500ms="telefono_movil" oninput="this.value=this.value.replace(/[^\d]/g,'')"
                   class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                   pattern="^\d{7,15}$" placeholder="Ej: 3001234567">
            @error('telefono_movil') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          {{-- Correo --}}
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Correo</label>
            <input type="email" wire:model.debounce.500ms="correo"
                   class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                   placeholder="nombre@empresa.com">
            @error('correo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          {{-- Dirección principal --}}
          <div class="sm:col-span-2 lg:col-span-1">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Dirección</label>
            <input type="text" wire:model.debounce.500ms="direccion"
                   class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                   placeholder="Calle 10 # 20-30">
            @error('direccion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          {{-- Barrio / complemento --}}
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Municipio/Barrio</label>
            <input type="text" wire:model.debounce.500ms="municipio_barrio"
                   class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                   placeholder="Barrio / complemento">
            @error('municipio_barrio') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          {{-- Saldo pendiente --}}
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Saldo Pendiente</label>
            <input type="number" step="0.01" min="0" wire:model.debounce.500ms="saldo_pendiente"
                   oninput="this.value=this.value.replace(/[^0-9\.]/g,'')"
                   class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                   placeholder="0.00">
            @error('saldo_pendiente') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          {{-- ========== Condición de pago (NUEVO) ========== --}}
          @php
            $condSel = $condicionesPago->firstWhere('id', (int)($condicion_pago_id ?? 0));
          @endphp
          <div class="sm:col-span-2 lg:col-span-3">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Condición de pago (opcional)</label>
            <select
              wire:model.live="condicion_pago_id"
              class="mt-1 w-full rounded-lg border @error('condicion_pago_id') border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 @enderror shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600">
              <option value="">— Seleccione —</option>
              @foreach($condicionesPago as $c)
                <option value="{{ $c->id }}">
                  {{ $c->nombre }} — {{ strtoupper($c->tipo) }}@if($c->plazo_dias) ({{ $c->plazo_dias }}d) @endif
                </option>
              @endforeach
            </select>
            @error('condicion_pago_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

            {{-- Vista previa --}}
            @if($condSel)
              <div class="mt-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-900/70 p-3 text-sm">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="font-semibold">{{ $condSel->nombre }}</div>
                    <div class="mt-1 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-600 dark:text-gray-300">
                      <div>Tipo: <span class="font-medium">{{ strtoupper($condSel->tipo) }}</span></div>
                      <div>Plazo: <span class="font-medium">{{ $condSel->plazo_dias ?? '—' }}</span></div>
                      <div>Mora %: <span class="font-medium">{{ $condSel->interes_mora_pct ?? '—' }}</span></div>
                      <div>Límite:
                        <span class="font-medium">
                          {{ $condSel->limite_credito !== null ? '$'.number_format($condSel->limite_credito,2,',','.') : '—' }}
                        </span>
                      </div>
                      <div>Tolerancia: <span class="font-medium">{{ $condSel->tolerancia_mora_dias ?? '—' }}</span></div>
                      <div>Día corte: <span class="font-medium">{{ $condSel->dia_corte ?? '—' }}</span></div>
                    </div>
                  </div>
                  <span class="inline-flex items-center text-[11px] px-2 py-0.5 rounded-full
                    {{ $condSel->activo ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' }}">
                    {{ $condSel->activo ? 'Activa' : 'Inactiva' }}
                  </span>
                </div>
              </div>
            @endif
          </div>
          {{-- ========== /Condición de pago ========== --}}

        </div>
      </section>

      {{-- =================== Datos fiscales =================== --}}
      <section>
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100">Datos fiscales</h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Tipo de persona *</label>
            <select wire:model.defer="tipo_persona"
                    class="mt-1 w-full rounded-lg border @error('tipo_persona') border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 @enderror shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600">
              <option value="N">Natural</option>
              <option value="J">Jurídica</option>
            </select>
            @error('tipo_persona') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Régimen IVA *</label>
            <select wire:model.defer="regimen_iva"
                    class="mt-1 w-full rounded-lg border @error('regimen_iva') border-red-500 focus:ring-red-500 @else border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 @enderror shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600">
              <option value="no_responsable">No responsable</option>
              <option value="responsable">Responsable</option>
            </select>
            @error('regimen_iva') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          <div class="flex items-center gap-3">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Régimen Simple</label>
            <input type="checkbox" wire:model.defer="regimen_simple" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Municipio (principal)</label>
            <select wire:model.defer="municipio_id"
                    class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600">
              <option value="">— Seleccione —</option>
              @foreach($municipios as $m)
                <option value="{{ $m['id'] }}">{{ $m['nombre'] }}</option>
              @endforeach
            </select>
            @error('municipio_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Actividad económica (CIIU)</label>
            <input type="text" wire:model.defer="actividad_economica" placeholder="Ej: 4711"
                   class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600">
            @error('actividad_economica') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>

          <div class="sm:col-span-2 lg:col-span-3">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Dirección medios magnéticos</label>
            <input type="text" wire:model.defer="direccion_medios_magneticos"
                   class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                   placeholder="Dirección fiscal para información exógena">
            @error('direccion_medios_magneticos') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
          </div>
        </div>
      </section>

      {{-- =================== Direcciones de entrega (repeater) =================== --}}
      <section>
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base md:text-lg font-bold text-gray-800 dark:text-gray-100">Direcciones de entrega</h3>
          <button type="button" wire:click="addDireccion"
                  class="inline-flex items-center gap-2 px-3 py-1.5 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2Z"/></svg>
            Agregar dirección
          </button>
        </div>

        <div class="space-y-4">
          @foreach($direcciones as $i => $dir)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/60 p-4">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">#{{ $i+1 }}</span>
                  @if($dir['es_principal'])
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Principal</span>
                  @endif
                </div>
                <button type="button" wire:click="removeDireccion({{ $i }})"
                        class="text-sm px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                  Eliminar
                </button>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Nombre / Punto</label>
                  <input type="text" wire:model.defer="direcciones.{{ $i }}.nombre"
                         class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-800 dark:border-gray-600"
                         placeholder="Ej: Bodega, Casa, Oficina">
                  @error('direcciones.'.$i.'.nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="sm:col-span-2">
                  <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Dirección</label>
                  <input type="text" wire:model.defer="direcciones.{{ $i }}.direccion"
                         class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-800 dark:border-gray-600"
                         placeholder="Calle 10 # 20-30, apto 201">
                  @error('direcciones.'.$i.'.direccion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                  <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Municipio</label>
                  <select wire:model.defer="direcciones.{{ $i }}.municipio_id"
                          class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-800 dark:border-gray-600">
                    <option value="">—</option>
                    @foreach($municipios as $m)
                      <option value="{{ $m['id'] }}">{{ $m['nombre'] }}</option>
                    @endforeach
                  </select>
                  @error('direcciones.'.$i.'.municipio_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="sm:col-span-2 lg:col-span-3">
                  <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300">Referencia</label>
                  <input type="text" wire:model.defer="direcciones.{{ $i }}.referencia"
                         class="mt-1 w-full rounded-lg border border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm px-3 py-2 dark:bg-gray-800 dark:border-gray-600"
                         placeholder="Punto de referencia, piso, portería, etc.">
                  @error('direcciones.'.$i.'.referencia') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center gap-2">
                  <input id="principal-{{ $i }}" type="checkbox"
                         wire:model="direcciones.{{ $i }}.es_principal"
                         wire:change="setPrincipal({{ $i }})"
                         class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                  <label for="principal-{{ $i }}" class="text-sm text-gray-700 dark:text-gray-200">Marcar como principal</label>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </section>

      {{-- =================== Acciones =================== --}}
      <div class="sticky bottom-0 pt-4 -mx-6 md:-mx-8 px-6 md:px-8 bg-gradient-to-t from-white/90 dark:from-gray-800/90">
        <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
          <button type="button"
                  class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200"
                  x-on:click="$dispatch('cerrarModal')">
            Cancelar
          </button>
          <button type="submit"
                  class="px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow">
            Guardar
          </button>
        </div>
      </div>

    </form>
  </div>
</div>
