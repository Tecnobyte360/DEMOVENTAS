<div class="w-full" x-data x-on:socioCreado.window="$dispatch('cerrarModal')">

  <div class="mx-auto max-w-7xl">
    <div class="relative overflow-hidden rounded-3xl border border-gray-200/70 dark:border-gray-700/70 bg-white/75 dark:bg-gray-900/55 shadow-[0_30px_90px_-35px_rgba(15,23,42,.35)] backdrop-blur-xl">
      <div class="pointer-events-none absolute -top-28 -right-28 h-64 w-64 rounded-full bg-indigo-500/15 blur-3xl"></div>
      <div class="pointer-events-none absolute -bottom-28 -left-28 h-64 w-64 rounded-full bg-fuchsia-500/10 blur-3xl"></div>

      <div class="relative p-6 md:p-8">

        {{-- HEADER --}}
        <header class="mb-7">
          <div class="flex items-start gap-4">
            <div class="h-11 w-11 rounded-2xl bg-gradient-to-br from-indigo-600 to-indigo-500 text-white grid place-items-center shadow-lg shadow-indigo-600/25">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 11V3H4a1 1 0 0 0-1 1v15a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-9ZM6 7h8v2H6Zm0 4h8v2H6Zm0 4h6v2H6Zm12 4h-1v-8h3Z"/>
              </svg>
            </div>

            <div class="flex-1">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <h2 class="text-xl md:text-2xl font-extrabold text-gray-900 dark:text-white">
                    Nuevo socio de negocio
                  </h2>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Datos generales, fiscales y direcciones de entrega.
                  </p>
                </div>

                <span class="hidden sm:inline-flex text-[11px] font-semibold px-3 py-1 rounded-full
                             bg-indigo-600/10 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200">
                  Campos obligatorios *
                </span>
              </div>
            </div>
          </div>
        </header>

        @php
          $inBase = "w-full rounded-2xl border bg-white/70 dark:bg-gray-950/40 px-3.5 py-2.5 shadow-sm outline-none transition
                     focus:ring-4 focus:ring-indigo-500/15 focus:border-indigo-500 dark:focus:ring-indigo-400/20";
          $inOk   = "border-gray-200/80 dark:border-gray-700/70";
          $inErr  = "border-red-500/70 focus:border-red-500 focus:ring-red-500/15 dark:focus:ring-red-400/20";
          $lab    = "block text-[11px] font-semibold tracking-wide text-gray-600 dark:text-gray-300";
          $errTxt = "mt-1 text-[11px] text-red-500";
          $fmtMoney = fn($v) => '$'.number_format((float)($v ?? 0), 2, ',', '.');

          /** @var \Illuminate\Support\Collection|\App\Models\CondicionPago\CondicionPago[] $condicionesPago */
          $condSel = $condicionesPago->firstWhere('id', (int)($condicion_pago_id ?? 0));
        @endphp

        {{-- GRID HORIZONTAL --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

          {{-- IZQUIERDA --}}
          <div class="xl:col-span-8">
            <form wire:submit.prevent="save" class="space-y-10">

              {{-- DATOS GENERALES --}}
              <section class="space-y-4">
                <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-gray-100">Datos generales</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

                  <div class="sm:col-span-2 lg:col-span-1">
                    <label class="{{ $lab }}">Razón Social <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.debounce.500ms="razon_social"
                           class="{{ $inBase }} @error('razon_social') {{ $inErr }} @else {{ $inOk }} @enderror"
                           placeholder="Ej: Industrias XYZ S.A.S.">
                    @error('razon_social') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div>
                    <label class="{{ $lab }}">NIT/Cédula <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.debounce.500ms="nit" oninput="this.value=this.value.replace(/[^\d]/g,'')"
                           class="{{ $inBase }} @error('nit') {{ $inErr }} @else {{ $inOk }} @enderror"
                           placeholder="Solo números">
                    @error('nit') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div>
                    <label class="{{ $lab }}">Tipo <span class="text-red-500">*</span></label>
                    <select wire:model.debounce.500ms="Tipo"
                            class="{{ $inBase }} @error('Tipo') {{ $inErr }} @else {{ $inOk }} @enderror">
                      <option value="">Selecciona</option>
                      <option value="C">Cliente</option>
                      <option value="P">Proveedor</option>
                    </select>
                    @error('Tipo') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div>
                    <label class="{{ $lab }}">Teléfono fijo <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.debounce.500ms="telefono_fijo" oninput="this.value=this.value.replace(/[^\d]/g,'')"
                           class="{{ $inBase }} @error('telefono_fijo') {{ $inErr }} @else {{ $inOk }} @enderror">
                    @error('telefono_fijo') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div>
                    <label class="{{ $lab }}">Teléfono móvil <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.debounce.500ms="telefono_movil" oninput="this.value=this.value.replace(/[^\d]/g,'')"
                           class="{{ $inBase }} @error('telefono_movil') {{ $inErr }} @else {{ $inOk }} @enderror">
                    @error('telefono_movil') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div>
                    <label class="{{ $lab }}">Correo <span class="text-red-500">*</span></label>
                    <input type="email" wire:model.debounce.500ms="correo"
                           class="{{ $inBase }} @error('correo') {{ $inErr }} @else {{ $inOk }} @enderror">
                    @error('correo') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div class="sm:col-span-2 lg:col-span-1">
                    <label class="{{ $lab }}">Dirección <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.debounce.500ms="direccion"
                           class="{{ $inBase }} @error('direccion') {{ $inErr }} @else {{ $inOk }} @enderror">
                    @error('direccion') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div>
                    <label class="{{ $lab }}">Municipio/Barrio</label>
                    <input type="text" wire:model.debounce.500ms="municipio_barrio"
                           class="{{ $inBase }} @error('municipio_barrio') {{ $inErr }} @else {{ $inOk }} @enderror">
                    @error('municipio_barrio') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div>
                    <label class="{{ $lab }}">Saldo pendiente</label>
                    <input type="number" step="0.01" min="0" wire:model.debounce.500ms="saldo_pendiente"
                           class="{{ $inBase }} @error('saldo_pendiente') {{ $inErr }} @else {{ $inOk }} @enderror">
                    @error('saldo_pendiente') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  {{-- Condición de pago (OBLIGATORIA) --}}
                  <div class="sm:col-span-2 lg:col-span-3">
                    <label class="{{ $lab }}">Condición de pago <span class="text-red-500">*</span></label>
                    <select wire:model.live="condicion_pago_id"
                            class="{{ $inBase }} @error('condicion_pago_id') {{ $inErr }} @else {{ $inOk }} @enderror">
                      <option value="">— Seleccione —</option>
                      @foreach($condicionesPago as $c)
                        <option value="{{ $c->id }}">
                          {{ $c->nombre }} — {{ strtoupper($c->tipo) }}@if($c->plazo_dias) ({{ $c->plazo_dias }}d) @endif
                        </option>
                      @endforeach
                    </select>
                    @error('condicion_pago_id') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                </div>
              </section>

              {{-- DATOS FISCALES --}}
              <section class="space-y-4">
                <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-gray-100">Datos fiscales</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                  <div>
                    <label class="{{ $lab }}">Tipo de persona <span class="text-red-500">*</span></label>
                    <select wire:model.defer="tipo_persona"
                            class="{{ $inBase }} @error('tipo_persona') {{ $inErr }} @else {{ $inOk }} @enderror">
                      <option value="N">Natural</option>
                      <option value="J">Jurídica</option>
                    </select>
                    @error('tipo_persona') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div>
                    <label class="{{ $lab }}">Régimen IVA <span class="text-red-500">*</span></label>
                    <select wire:model.defer="regimen_iva"
                            class="{{ $inBase }} @error('regimen_iva') {{ $inErr }} @else {{ $inOk }} @enderror">
                      <option value="no_responsable">No responsable</option>
                      <option value="responsable">Responsable</option>
                    </select>
                    @error('regimen_iva') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                  </div>

                  <div class="flex items-center gap-3 pt-6">
                    <input type="checkbox" wire:model.defer="regimen_simple"
                           class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">Régimen simple</label>
                  </div>

                  <div>
                    <label class="{{ $lab }}">Municipio (principal)</label>
                    <select wire:model.defer="municipio_id" class="{{ $inBase }} {{ $inOk }}">
                      <option value="">— Seleccione —</option>
                      @foreach($municipios as $m)
                        <option value="{{ $m['id'] }}">{{ $m['nombre'] }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div>
                    <label class="{{ $lab }}">Actividad económica (CIIU)</label>
                    <input type="text" wire:model.defer="actividad_economica" class="{{ $inBase }} {{ $inOk }}" placeholder="Ej: 4711">
                  </div>

                  <div class="sm:col-span-2 lg:col-span-3">
                    <label class="{{ $lab }}">Dirección medios magnéticos</label>
                    <input type="text" wire:model.defer="direccion_medios_magneticos" class="{{ $inBase }} {{ $inOk }}">
                  </div>
                </div>
              </section>

              {{-- DIRECCIONES (REPEATER) --}}
              <section class="space-y-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-base md:text-lg font-bold text-gray-900 dark:text-gray-100">Direcciones de entrega</h3>
                  <button type="button" wire:click="addDireccion"
                          class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700 font-semibold shadow-lg shadow-indigo-600/20">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2Z"/></svg>
                    Agregar
                  </button>
                </div>

                <div class="space-y-4">
                  @foreach($direcciones as $i => $dir)
                    <div class="rounded-3xl border border-gray-200/70 dark:border-gray-700/70 bg-white/60 dark:bg-gray-950/30 p-4">
                      <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                          <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                            #{{ $i+1 }}
                          </span>
                          @if($dir['es_principal'])
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                              Principal
                            </span>
                          @endif
                        </div>

                        @if(count($direcciones) > 1)
                          <button type="button" wire:click="removeDireccion({{ $i }})"
                                  class="px-3 py-1.5 rounded-2xl border border-gray-300/70 dark:border-gray-700/70 hover:bg-gray-50 dark:hover:bg-gray-900 text-sm font-semibold">
                            Eliminar
                          </button>
                        @endif
                      </div>

                      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                          <label class="{{ $lab }}">Nombre / Punto</label>
                          <input type="text" wire:model.defer="direcciones.{{ $i }}.nombre"
                                 class="{{ $inBase }} {{ $inOk }}" placeholder="Ej: Oficina, Bodega, Casa">
                          @error('direcciones.'.$i.'.nombre') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                        </div>

                        <div class="sm:col-span-2">
                          <label class="{{ $lab }}">Dirección</label>
                          <input type="text" wire:model.defer="direcciones.{{ $i }}.direccion"
                                 class="{{ $inBase }} {{ $inOk }}" placeholder="Calle 10 # 20-30, apto 201">
                          @error('direcciones.'.$i.'.direccion') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                        </div>

                        <div>
                          <label class="{{ $lab }}">Municipio</label>
                          <select wire:model.defer="direcciones.{{ $i }}.municipio_id" class="{{ $inBase }} {{ $inOk }}">
                            <option value="">—</option>
                            @foreach($municipios as $m)
                              <option value="{{ $m['id'] }}">{{ $m['nombre'] }}</option>
                            @endforeach
                          </select>
                          @error('direcciones.'.$i.'.municipio_id') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3">
                          <label class="{{ $lab }}">Referencia</label>
                          <input type="text" wire:model.defer="direcciones.{{ $i }}.referencia"
                                 class="{{ $inBase }} {{ $inOk }}" placeholder="Piso, portería, punto de referencia...">
                          @error('direcciones.'.$i.'.referencia') <div class="{{ $errTxt }}">{{ $message }}</div> @enderror
                        </div>

                        <div class="flex items-center gap-2 pt-6">
                          <input id="principal-{{ $i }}" type="checkbox"
                                 wire:model="direcciones.{{ $i }}.es_principal"
                                 wire:change="setPrincipal({{ $i }})"
                                 class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                          <label for="principal-{{ $i }}" class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                            Principal
                          </label>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </section>

              {{-- ACCIONES --}}
              <div class="pt-2">
                <div class="flex items-center justify-end gap-3">
                  <button type="button"
                          class="px-4 py-2.5 rounded-2xl border border-gray-300/70 dark:border-gray-700/70 hover:bg-gray-50 dark:hover:bg-gray-900 text-gray-700 dark:text-gray-200 font-semibold"
                          x-on:click="$dispatch('cerrarModal')">
                    Cancelar
                  </button>

                  <button type="submit"
                          class="px-5 py-2.5 rounded-2xl bg-indigo-600 text-white hover:bg-indigo-700 font-semibold shadow-lg shadow-indigo-600/25">
                    Guardar
                  </button>
                </div>
              </div>

            </form>
          </div>

          {{-- DERECHA (RESUMEN) --}}
          <aside class="xl:col-span-4">
            <div class="space-y-4 xl:sticky xl:top-6">

              {{-- Resumen --}}
              <div class="rounded-3xl border border-gray-200/70 dark:border-gray-700/70 bg-white/70 dark:bg-gray-950/35 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Resumen</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Vista en vivo de lo que estás registrando.</div>
                  </div>
                  <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-indigo-600/10 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-200">
                    Live
                  </span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                  <div class="rounded-2xl border border-gray-200/70 dark:border-gray-700/70 p-3">
                    <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Razón social</div>
                    <div class="font-bold text-gray-900 dark:text-gray-100 truncate">{{ $razon_social ?: '—' }}</div>
                  </div>
                  <div class="rounded-2xl border border-gray-200/70 dark:border-gray-700/70 p-3">
                    <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">NIT/Cédula</div>
                    <div class="font-bold text-gray-900 dark:text-gray-100 truncate">{{ $nit ?: '—' }}</div>
                  </div>

                  <div class="rounded-2xl border border-gray-200/70 dark:border-gray-700/70 p-3">
                    <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Tipo</div>
                    <div class="font-bold text-gray-900 dark:text-gray-100">
                      {{ $Tipo === 'P' ? 'Proveedor' : 'Cliente' }}
                    </div>
                  </div>
                  <div class="rounded-2xl border border-gray-200/70 dark:border-gray-700/70 p-3">
                    <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Saldo</div>
                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ $fmtMoney($saldo_pendiente) }}</div>
                  </div>

                  <div class="col-span-2 rounded-2xl border border-gray-200/70 dark:border-gray-700/70 p-3">
                    <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Contacto</div>
                    <div class="font-bold text-gray-900 dark:text-gray-100 truncate">
                      {{ $telefono_movil ?: '—' }} @if($correo) · {{ $correo }} @endif
                    </div>
                  </div>

                  <div class="col-span-2 rounded-2xl border border-gray-200/70 dark:border-gray-700/70 p-3">
                    <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Dirección</div>
                    <div class="font-bold text-gray-900 dark:text-gray-100 truncate">{{ $direccion ?: '—' }}</div>
                  </div>
                </div>
              </div>

              {{-- Condición de pago --}}
              <div class="rounded-3xl border border-gray-200/70 dark:border-gray-700/70 bg-white/70 dark:bg-gray-950/35 p-5 shadow-sm">
                <div class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Condición de pago</div>
                <div class="mt-2 text-sm text-gray-700 dark:text-gray-200">
                  @if($condSel)
                    <div class="font-bold">{{ $condSel->nombre }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      {{ strtoupper($condSel->tipo) }}
                      @if($condSel->plazo_dias) · {{ $condSel->plazo_dias }} días @endif
                      @if($condSel->limite_credito !== null) · Límite {{ $fmtMoney($condSel->limite_credito) }} @endif
                    </div>
                  @else
                    <span class="text-gray-500 dark:text-gray-400">— Aún sin seleccionar —</span>
                  @endif
                </div>
              </div>

              {{-- Fiscales --}}
              <div class="rounded-3xl border border-gray-200/70 dark:border-gray-700/70 bg-white/70 dark:bg-gray-950/35 p-5 shadow-sm">
                <div class="text-sm font-extrabold text-gray-900 dark:text-gray-100">Fiscales</div>
                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                  <div class="rounded-2xl border border-gray-200/70 dark:border-gray-700/70 p-3">
                    <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Persona</div>
                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ $tipo_persona === 'J' ? 'Jurídica' : 'Natural' }}</div>
                  </div>
                  <div class="rounded-2xl border border-gray-200/70 dark:border-gray-700/70 p-3">
                    <div class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">IVA</div>
                    <div class="font-bold text-gray-900 dark:text-gray-100">{{ $regimen_iva === 'responsable' ? 'Responsable' : 'No responsable' }}</div>
                  </div>
                  <div class="col-span-2 text-xs text-gray-500 dark:text-gray-400">
                    Régimen simple: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $regimen_simple ? 'Sí' : 'No' }}</span>
                  </div>
                </div>
              </div>

            </div>
          </aside>

        </div>
      </div>
    </div>
  </div>
</div>
