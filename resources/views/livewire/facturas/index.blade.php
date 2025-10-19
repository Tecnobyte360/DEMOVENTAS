<div x-data="{ tab: 'form' }" class="space-y-8">

  {{-- ===== Barra de navegación ===== --}}
  <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">
    <nav class="flex flex-wrap gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">

      <!-- Nueva factura -->
      <button
        @click="tab = 'form'"
        :class="tab === 'form'
          ? 'bg-indigo-600 text-white shadow'
          : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-file-invoice"></i> Nueva factura
      </button>

      <!-- Facturas generadas -->
      <button
        @click="tab = 'list'"
        :class="tab === 'list'
          ? 'bg-indigo-600 text-white shadow'
          : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-list"></i> Facturas generadas
      </button>

      <!-- Nueva nota crédito -->
      <button
        @click="tab = 'nc_form'"
        :class="tab === 'nc_form'
          ? 'bg-indigo-600 text-white shadow'
          : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-arrow-rotate-left"></i> Nueva nota crédito
      </button>

      <!-- Listado notas crédito -->
      <button
        @click="tab = 'nc_list'"
        :class="tab === 'nc_list'
          ? 'bg-indigo-600 text-white shadow'
          : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-rectangle-list"></i> Notas crédito
      </button>

      <!-- 💰 Turno de Caja -->
      <button
        @click="tab = 'caja'"
        :class="tab === 'caja'
          ? 'bg-indigo-600 text-white shadow'
          : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-cash-register"></i> Turno de Caja
      </button>

    </nav>

    {{-- ===== Contenido dinámico de pestañas ===== --}}
    <div class="p-6 md:p-8">

      {{-- ====== Facturas: crear ====== --}}
      <section x-show="tab === 'form'" x-transition class="space-y-4">
        <livewire:facturas.factura-form />
      </section>

      {{-- ====== Facturas: lista ====== --}}
      <section x-show="tab === 'list'" x-transition class="space-y-4">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-list text-indigo-500"></i> Facturas generadas
        </h2>
        <livewire:facturas.lista-facturas />
      </section>

      {{-- ====== Notas Crédito: crear ====== --}}
      <section x-show="tab === 'nc_form'" x-transition class="space-y-4">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">

        </h2>
        <livewire:facturas.nota-credito-form />
      </section>

      {{-- ====== Notas Crédito: lista ====== --}}
     <section 
    x-show="tab === 'nc_list'" 
    x-transition 
    class="space-y-4"
>
  <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
    <i class="fa-solid fa-rectangle-list text-indigo-500"></i> 
    Notas crédito generadas
  </h2>

  {{-- Lista de notas crédito --}}
  <livewire:facturas.lista-notas-credito />
</section>


      {{-- ====== 💰 Turno de Caja ====== --}}
      <section x-show="tab === 'caja'" x-transition class="space-y-4">
        <h2 class="text-xl font-bold text-gray-800 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-cash-register text-indigo-500"></i> Turno de Caja
        </h2>

        {{-- Componente de caja Livewire --}}
        <livewire:turnos-caja.turno-caja />
      </section>

    </div>
  </section>
</div>
