@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>[x-cloak]{display:none!important}</style>
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Evita conflictos entre Alpine y Livewire
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit);
      };
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div
  x-data="{
    tab: localStorage.getItem('tab-facturas-compra') || 'form',
    setTab(v){ this.tab=v; localStorage.setItem('tab-facturas-compra', v); }
  }"
  x-init="
    // Cuando el formulario guarde/emmita y dispare este evento, saltamos a la lista
    window.addEventListener('refrescar-lista-facturas', () => setTab('list'));
  "
  class="space-y-8"
>

  {{-- ======= Barra de navegación ======= --}}
  <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">
    <nav class="flex flex-wrap gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700" aria-label="Tabs de compras">
      {{-- Nueva factura de compra --}}
      <button
        @click="setTab('form')"
        :class="tab === 'form'
          ? 'bg-indigo-600 text-white shadow'
          : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-file-invoice-dollar"></i> Nueva factura de compra
      </button>

      {{-- Facturas de compra generadas --}}
      <button
        @click="setTab('list')"
        :class="tab === 'list'
          ? 'bg-indigo-600 text-white shadow'
          : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-list"></i> Facturas registradas
      </button>

      {{-- Devoluciones a proveedor (placeholder) --}}
      <button
        @click="setTab('ret_form')"
        :class="tab === 'ret_form'
          ? 'bg-indigo-600 text-white shadow'
          : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-rotate-left"></i> Devoluciones a proveedor
      </button>
    </nav>

    {{-- ======= Contenido de pestañas ======= --}}
    <div class="p-6 md:p-8">
      {{-- TAB: Nueva factura de compra --}}
      <div x-show="tab === 'form'" x-cloak>
        <livewire:facturas.factura-compra.factura-compra
          :modo="'compra'"
          :key="'factura-compra-form'"
        />
      </div>

      {{-- TAB: Listado de facturas de compra --}}
      <div x-show="tab === 'list'" x-cloak>
        <livewire:facturas.factura-compra.lista-facturas-compra
          :key="'factura-compra-list'"
        />
      </div>

      {{-- TAB: Devoluciones a proveedor (puedes reemplazar por tu componente real) --}}
      <div x-show="tab === 'ret_form'" x-cloak>
        {{-- Reemplaza por tu componente real cuando lo tengas --}}
        {{-- <livewire:compras.devoluciones.devolucion-proveedor :key="'devoluciones-proveedor-form'" /> --}}
        <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-6 text-sm text-gray-600 dark:text-gray-300">
          <p class="font-semibold mb-2">Devoluciones a proveedor</p>
          <p>Placeholder del módulo. Aquí puedes montar tu componente de devoluciones.</p>
        </div>
      </div>
    </div>
  </section>
</div>
