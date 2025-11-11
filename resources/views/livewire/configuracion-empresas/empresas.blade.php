{{-- resources/views/livewire/configuracion-empresas/empresas.blade.php --}}
<div class="relative p-8 md:p-10 rounded-3xl shadow-2xl
            bg-gradient-to-br from-violet-50 via-white to-indigo-50
            dark:from-gray-900 dark:via-gray-900 dark:to-gray-800
            border border-violet-100/50 dark:border-gray-700"
     x-data="{ ...logoUploader(), theme: @entangle('theme'), usarGrad: @entangle('usar_gradiente'), gradAngle: @entangle('grad_angle') }">

  {{-- Fondos decorativos --}}
  <div aria-hidden="true" class="pointer-events-none absolute -top-24 -right-24 w-72 h-72 rounded-full bg-indigo-400/20 blur-3xl"></div>
  <div aria-hidden="true" class="pointer-events-none absolute -bottom-20 -left-20 w-96 h-96 rounded-full bg-violet-400/20 blur-3xl"></div>

  {{-- Header --}}
  <div class="relative z-10 mb-8">
    <div class="flex items-start justify-between gap-4">
      <div>
        <h2 class="text-2xl md:text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white flex items-center gap-3">
          <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400">
            <i class="fas fa-building"></i>
          </span>
          Configuración de Empresas
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Gestiona nombre, logos y datos básicos. Personaliza colores, tema PDF y estado.</p>
      </div>
    </div>
  </div>

  {{-- Alertas de éxito en propiedad --}}
  @if ($ok)
    <div class="relative z-10 mb-6 rounded-2xl border border-emerald-300/60 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm
                dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-700/60">
      <div class="flex items-start gap-3">
        <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-300">
          <i class="fa-solid fa-check"></i>
        </span>
        <div>{{ $ok }}</div>
      </div>
    </div>
  @endif

  {{-- Alertas de éxito flash (opcional) --}}
  @if (session('ok'))
    <div class="relative z-10 mb-6 rounded-2xl border border-emerald-300/60 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm
                dark:bg-emerald-900/30 dark:text-emerald-200 dark:border-emerald-700/60">
      <div class="flex items-start gap-3">
        <span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-300">
          <i class="fa-solid fa-check"></i>
        </span>
        <div>{{ session('ok') }}</div>
      </div>
    </div>
  @endif

  {{-- Formulario principal --}}
  <form wire:submit.prevent="save" class="relative z-10 grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Columna 1 (datos + tema PDF) --}}
    <div class="lg:col-span-2 space-y-7 max-w-6xl mx-auto w-full">

      {{-- Datos generales --}}
      <div class="bg-white/80 dark:bg-white/10 backdrop-blur-md rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
        <div class="mb-4 flex items-center gap-2">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400">
            <i class="fa-solid fa-id-badge"></i>
          </span>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Datos generales</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nombre *</label>
            <input type="text" wire:model.defer="nombre"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                   placeholder="Mi Empresa S.A.S.">
            @error('nombre') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">NIT</label>
            <input type="text" wire:model.defer="nit"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                   placeholder="900.123.456-7">
            @error('nit') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Email</label>
            <input type="email" wire:model.defer="email"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                   placeholder="contacto@empresa.com">
            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Teléfono</label>
            <input type="text" wire:model.defer="telefono"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                   placeholder="+57 300 000 0000">
            @error('telefono') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Sitio Web</label>
            <input type="url" wire:model.defer="sitio_web"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                   placeholder="https://empresa.com">
            @error('sitio_web') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Dirección</label>
            <input type="text" wire:model.defer="direccion"
                   class="mt-1 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                   placeholder="Calle 123 #45-67">
            @error('direccion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
          </div>
        </div>
      </div>

      {{-- Marca & Estado --}}
      <div class="bg-white/80 dark:bg-white/10 backdrop-blur-md rounded-2xl 
                  border border-gray-200 dark:border-gray-700 p-8">
        <div class="mb-6 flex items-center gap-3">
          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl 
                       bg-violet-600/10 text-violet-600 dark:text-violet-400">
            <i class="fa-solid fa-palette"></i>
          </span>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Marca & Estado</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Color primario</label>
            <div class="mt-2 flex items-center gap-2">
              <input type="color" class="h-10 w-10 rounded-lg cursor-pointer border border-gray-300 dark:border-gray-700"
                     x-model="theme.primary" @input="$wire.set('theme.primary', theme.primary)">
              <input type="text" wire:model.defer="color_primario"
                     x-model.lazy="theme.primary"
                     class="flex-1 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                     placeholder="#223361">
            </div>
            @error('color_primario') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Color secundario</label>
            <div class="mt-2 flex items-center gap-2">
              <input type="color" class="h-10 w-10 rounded-lg cursor-pointer border border-gray-300 dark:border-gray-700"
                     x-model="theme.theadBg" @input="$wire.set('theme.theadBg', theme.theadBg)">
              <input type="text" wire:model.defer="color_secundario"
                     class="flex-1 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                     placeholder="#eef2f8">
            </div>
            @error('color_secundario') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
          </div>

          <div class="md:col-span-2 flex items-center justify-start">
            <label class="inline-flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200 select-none">
              <input type="checkbox" wire:model.defer="is_activa"
                     class="rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900">
              Empresa activa
            </label>
          </div>
        </div>
      </div>

      {{-- 🎨 Tema PDF (Factura) --}}
      <div class="bg-white/80 dark:bg-white/10 backdrop-blur-md rounded-2xl border border-gray-200 dark:border-gray-700 p-8">
        <div class="mb-6 flex items-center gap-3">
          <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400">
            <i class="fa-solid fa-file-invoice"></i>
          </span>
          <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Tema PDF (Factura)</h3>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {{-- Colores 1 --}}
          <div class="space-y-4">
            @php
              $keys1 = [
                ['primary','Primario'],
                ['base','Base (fondo)'],
                ['ink','Texto principal'],
                ['muted','Texto secundario'],
              ];
            @endphp
            @foreach ($keys1 as [$key,$label])
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
                <div class="mt-2 flex items-center gap-2">
                  <input type="color" class="h-10 w-10 rounded-lg cursor-pointer border border-gray-300 dark:border-gray-700"
                         x-model="theme.{{ $key }}" @input="$wire.set('theme.{{ $key }}', theme.{{ $key }})">
                  <input type="text" class="flex-1 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                         x-model.lazy="theme.{{ $key }}" placeholder="#000000">
                </div>
              </div>
            @endforeach
          </div>

          {{-- Colores 2 --}}
          <div class="space-y-4">
            @php
              $keys2 = [
                ['border','Borde'],
                ['theadBg','Encabezado tabla (bg)'],
                ['theadText','Encabezado tabla (texto)'],
                ['stripe','Fila alterna'],
              ];
            @endphp
            @foreach ($keys2 as [$key,$label])
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
                <div class="mt-2 flex items-center gap-2">
                  <input type="color" class="h-10 w-10 rounded-lg cursor-pointer border border-gray-300 dark:border-gray-700"
                         x-model="theme.{{ $key }}" @input="$wire.set('theme.{{ $key }}', theme.{{ $key }})">
                  <input type="text" class="flex-1 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                         x-model.lazy="theme.{{ $key }}" placeholder="#000000">
                </div>
              </div>
            @endforeach
          </div>

          {{-- Colores 3 + Gradiente --}}
          <div class="space-y-4">
            @php
              $keys3 = [
                ['grandBg','Total (bg)'],
                ['grandTx','Total (texto)'],
              ];
            @endphp
            @foreach ($keys3 as [$key,$label])
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
                <div class="mt-2 flex items-center gap-2">
                  <input type="color" class="h-10 w-10 rounded-lg cursor-pointer border border-gray-300 dark:border-gray-700"
                         x-model="theme.{{ $key }}" @input="$wire.set('theme.{{ $key }}', theme.{{ $key }})">
                  <input type="text" class="flex-1 rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                         x-model.lazy="theme.{{ $key }}" placeholder="#000000">
                </div>
              </div>
            @endforeach

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Watermark (rgba)</label>
              <input type="text" class="mt-2 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                     x-model.lazy="theme.wmColor" placeholder="rgba(34, 51, 97, .06)">
            </div>

            <div class="pt-2 space-y-2">
              <label class="inline-flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200 select-none">
                <input type="checkbox"
                       class="rounded-lg border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900"
                       x-model="usarGrad">
                Usar gradiente de encabezado
              </label>
              <div class="flex items-center gap-3" x-show="usarGrad">
                <input type="range" min="0" max="360" step="1"
                       class="w-full"
                       x-model.number="gradAngle"
                       @input="$wire.set('grad_angle', gradAngle)">
                <span class="text-xs text-gray-600 dark:text-gray-300 w-12 text-right" x-text="gradAngle + '°'"></span>
              </div>
            </div>
          </div>
        </div>

        {{-- Vista previa mini --}}
        <div class="mt-8">
          <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3">Previsualización</h4>
          <div class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow">
            {{-- Header --}}
            <div class="p-4"
                 :style="usarGrad
                          ? `background: linear-gradient(${gradAngle}deg, ${theme.primary}, ${theme.theadBg});`
                          : `background: ${theme.primary};`">
              <div class="text-white font-bold">FACTURA</div>
              <div class="text-white/80 text-xs">N.º DEMO-000001</div>
            </div>

            {{-- Tabla --}}
            <div class="bg-white dark:bg-gray-900">
              <table class="w-full text-sm">
                <thead>
                  <tr :style="`background:${theme.theadBg}; color:${theme.theadText}; border-bottom:1px solid ${theme.border}`">
                    <th class="text-left px-4 py-2">Producto</th>
                    <th class="text-right px-4 py-2">Cant.</th>
                    <th class="text-right px-4 py-2">Precio</th>
                    <th class="text-right px-4 py-2">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr :style="`border-bottom:1px solid ${theme.border}`">
                    <td class="px-4 py-2">Artículo A</td>
                    <td class="px-4 py-2 text-right">2</td>
                    <td class="px-4 py-2 text-right">$10.00</td>
                    <td class="px-4 py-2 text-right">$20.00</td>
                  </tr>
                  <tr :style="`background:${theme.stripe}; border-bottom:1px solid ${theme.border}`">
                    <td class="px-4 py-2">Artículo B</td>
                    <td class="px-4 py-2 text-right">1</td>
                    <td class="px-4 py-2 text-right">$5.00</td>
                    <td class="px-4 py-2 text-right">$5.00</td>
                  </tr>
                </tbody>
              </table>
            </div>

            {{-- Totales --}}
            <div class="p-4 flex items-center justify-end gap-6"
                 :style="`color:${theme.ink}; background:${theme.base}`">
              <div class="text-right text-sm">
                <div :style="`color:${theme.muted}`">Subtotal</div>
                <div :style="`color:${theme.muted}`">Impuestos</div>
                <div class="font-bold px-3 py-1 rounded-lg"
                     :style="`background:${theme.grandBg}; color:${theme.grandTx}`">Total</div>
              </div>
              <div class="text-right text-sm">
                <div>$25.00</div>
                <div>$4.75</div>
                <div class="font-bold px-3 py-1 rounded-lg"
                     :style="`background:${theme.grandBg}; color:${theme.grandTx}`">$29.75</div>
              </div>
            </div>
          </div>

          <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
            Esta es una vista previa aproximada. El PDF final usa estos mismos colores.
          </p>
        </div>
      </div>
    </div>

    {{-- Columna 2 (archivos + diagnóstico) --}}
    <div class="space-y-6">

      <div class="bg-white/80 dark:bg-white/10 backdrop-blur-md rounded-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-5">
        <div class="mb-1 flex items-center gap-2">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-600 dark:text-indigo-400">
            <i class="fa-solid fa-image"></i>
          </span>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Identidad visual</h3>
        </div>

        {{-- Logo claro --}}
        <div class="group">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Logo (claro)</label>
          <input type="file" accept="image/*"
                 @change="toBase64($event, 'logoPreview', 'logo_b64')"
                 class="mt-1 block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-200">
          @error('logo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

          <div class="mt-3 flex items-center gap-3">
            <template x-if="logoPreview">
              <img :src="logoPreview" class="h-12 rounded-xl border border-gray-200 dark:border-gray-700" alt="preview logo">
            </template>

            @if (!empty($logo_actual))
              <img src="{{ $logo_actual }}" class="h-12 rounded-xl border border-gray-200 dark:border-gray-700" alt="logo actual">
            @endif
          </div>
        </div>

        {{-- Logo oscuro --}}
        <div class="group">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Logo (oscuro)</label>
          <input type="file" accept="image/*"
                 @change="toBase64($event, 'logoDarkPreview', 'logo_dark_b64')"
                 class="mt-1 block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-200">
          @error('logo_dark') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

          <div class="mt-3 flex items-center gap-3">
            <template x-if="logoDarkPreview">
              <img :src="logoDarkPreview" class="h-12 rounded-xl border border-gray-200 dark:border-gray-700" alt="preview logo dark">
            </template>

            @if (!empty($logo_dark_actual))
              <img src="{{ $logo_dark_actual }}" class="h-12 rounded-xl border border-gray-200 dark:border-gray-700" alt="logo oscuro actual">
            @endif
          </div>
        </div>

        {{-- Favicon --}}
        <div class="group">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Favicon</label>
          <input type="file" accept="image/*,.ico"
                 @change="toBase64($event, 'faviconPreview', 'favicon_b64')"
                 class="mt-1 block w-full text-sm file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950 dark:file:text-indigo-200">
          @error('favicon') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

          <div class="mt-3 flex items-center gap-3">
            <template x-if="faviconPreview">
              <img :src="faviconPreview" class="h-10 w-10 rounded-lg border border-gray-200 dark:border-gray-700" alt="preview favicon">
            </template>

            @if (!empty($favicon_actual))
              <img src="{{ $favicon_actual }}" class="h-10 w-10 rounded-lg border border-gray-200 dark:border-gray-700" alt="favicon actual">
            @endif
          </div>
        </div>
      </div>

      {{-- Barra de acciones --}}
      <div class="flex items-center justify-end gap-3">
        <button type="button" wire:click="cancel"
                class="px-5 py-3 rounded-2xl bg-white text-gray-700 border border-gray-200 shadow-sm hover:shadow transition dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700">
          Cancelar
        </button>
        <button type="submit"
                class="px-5 py-3 rounded-2xl bg-indigo-600 text-white font-semibold shadow hover:shadow-lg hover:bg-indigo-700 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-900">
          <span wire:loading.remove wire:target="save" class="inline-flex items-center gap-2">
            <i class="fa-solid fa-floppy-disk"></i>
            Guardar configuración
          </span>
          <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
            <i class="fa-solid fa-spinner animate-spin"></i>
            Guardando...
          </span>
        </button>
      </div>
    </div>
  </form>

  {{-- Listado --}}
  <div class="relative z-10 mt-10 bg-white/80 dark:bg-white/10 backdrop-blur-md rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
        <i class="fas fa-list-ul text-indigo-600"></i>
        Empresas registradas
      </h3>

      <button type="button" wire:click="createNew"
              class="px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold shadow hover:bg-indigo-700">
        Nueva empresa
      </button>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50/70 dark:bg-gray-800/40">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Logo</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Nombre</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">NIT</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Email</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Teléfono</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Estado</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
          @forelse ($rows as $row)
            <tr class="hover:bg-indigo-50/30 dark:hover:bg-gray-800/40 transition">
              <td class="px-4 py-3">
                @if ($row->logo_url)
                  <img src="{{ $row->logo_url }}" class="h-8 w-auto rounded-md border border-gray-200 dark:border-gray-700" alt="logo">
                @else
                  <div class="h-8 w-8 rounded-md bg-gray-200 dark:bg-gray-700 grid place-content-center text-xs text-gray-500">—</div>
                @endif
              </td>
              <td class="px-4 py-3">
                <div class="font-medium text-gray-900 dark:text-gray-100">{{ $row->nombre }}</div>
                <div class="text-xs text-indigo-600 dark:text-indigo-300 truncate max-w-[220px]">{{ $row->sitio_web }}</div>
              </td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row->nit }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row->email }}</td>
              <td class="px-4 py-3 text-gray-700 dark:text-gray-200">{{ $row->telefono }}</td>
              <td class="px-4 py-3">
                @if ($row->is_activa)
                  <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Activa
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800/40 dark:text-gray-300">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-500"></span>
                    Inactiva
                  </span>
                @endif
              </td>
              <td class="px-4 py-3 text-right">
                <div class="inline-flex gap-2">
                  <button type="button" wire:click="edit({{ $row->id }})"
                          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white text-gray-700 border border-gray-200 shadow-sm hover:shadow hover:bg-gray-50 transition dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700">
                    <i class="fa-solid fa-pen"></i>
                    Editar
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-4 py-12 text-center">
                <div class="mx-auto w-full max-w-md">
                  <div class="h-28 rounded-2xl bg-gradient-to-r from-indigo-500/10 via-violet-500/10 to-indigo-500/10 border border-dashed border-indigo-300/40 dark:border-indigo-700/40 grid place-content-center mb-4">
                    <i class="fa-solid fa-building-circle-check text-3xl text-indigo-500/70"></i>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-300">No hay empresas registradas.</p>
                  <button type="button" wire:click="createNew"
                          class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold shadow hover:bg-indigo-700">
                    <i class="fa-solid fa-circle-plus"></i>
                    Crear la primera empresa
                  </button>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
  </div>
</div>

{{-- Alpine helper: lee archivo y envía Base64 al componente Livewire --}}
<script>
  function logoUploader() {
    return {
      logoPreview: null,
      logoDarkPreview: null,
      faviconPreview: null,

      toBase64(event, previewKey, livewireProp) {
        const file = event.target.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = e => {
          const dataUrl = e.target.result; // data:image/*;base64,...
          this[previewKey] = dataUrl;

          // Livewire v3
          if (typeof $wire !== 'undefined') {
            $wire.set(livewireProp, dataUrl);
          }
        };
        reader.readAsDataURL(file);
      }
    }
  }
</script>
