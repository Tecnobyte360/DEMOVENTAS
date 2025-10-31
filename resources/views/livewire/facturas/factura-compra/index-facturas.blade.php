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

     
    </div>
  </section>
</div>
