<div x-data="{ tab: 'form' }" class="space-y-8">

  {{-- Barra de navegación --}}
  <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">
    <nav class="flex flex-wrap gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <button
        @click="tab = 'form'"
        :class="tab === 'form'
          ? 'bg-indigo-600 text-white shadow'
          : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition">
        <i class="fa-solid fa-file-invoice"></i> Nueva factura
      </button>

      <button
        @click="tab = 'list'"
        :class="tab === 'list'
          ? 'bg-green-600 text-white shadow'
          : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition">
        <i class="fa-solid fa-list"></i> Facturas generadas
      </button>

      <!-- ✅ NUEVA pestaña: crear Nota Crédito -->
      <button
        @click="tab = 'nc_form'"
        :class="tab === 'nc_form'
          ? 'bg-violet-600 text-white shadow'
          : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition">
        <i class="fa-solid fa-arrow-rotate-left"></i> Nueva nota crédito
      </button>

      <!-- ✅ NUEVA pestaña: listar Notas Crédito -->
      <button
        @click="tab = 'nc_list'"
        :class="tab === 'nc_list'
          ? 'bg-fuchsia-600 text-white shadow'
          : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition">
        <i class="fa-solid fa-rectangle-list"></i> Notas crédito
      </button>
    </nav>

    <div class="p-6 md:p-8">
      {{-- ====== Facturas: crear ====== --}}
      <section x-show="tab === 'form'" x-transition class="space-y-4">
        <livewire:facturas.factura-form />
      </section>

      {{-- ====== Facturas: lista ====== --}}
      <section x-show="tab === 'list'" x-transition class="space-y-4">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-list text-green-500"></i> Facturas generadas
        </h2>
        <livewire:facturas.lista-facturas />
      </section>

      {{-- ✅ ====== Notas Crédito: crear ====== --}}
      <section x-show="tab === 'nc_form'" x-transition class="space-y-4">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-arrow-rotate-left text-violet-600"></i> Nueva nota crédito
        </h2>
        <livewire:facturas.nota-credito-form />
      </section>

     
    </div>
  </section>
</div>
