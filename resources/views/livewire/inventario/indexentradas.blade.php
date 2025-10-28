@once
  @push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
      [x-cloak] { display: none !important; }
    </style>
  @endpush
@endonce

@once
  @push('scripts')
    <script>
      // Alpine espera a Livewire (evita race conditions)
      window.deferLoadingAlpine = (alpineInit) => {
        document.addEventListener('livewire:init', alpineInit)
      }
    </script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  @endpush
@endonce

<div x-data="{ tab: 'form' }" class="space-y-8">
  {{-- ===== Barra de navegación ===== --}}
  <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">
    <nav class="flex flex-wrap gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <!-- Nueva entrada -->
      <button
        @click="tab = 'form'"
        :class="tab === 'form' ? 'bg-indigo-600 text-white shadow' : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-dolly"></i> Nueva entrada
      </button>

      <!-- Entradas registradas -->
      <button
        @click="tab = 'list'"
        :class="tab === 'list' ? 'bg-indigo-600 text-white shadow' : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-list"></i> Entradas registradas
      </button>

      <!-- Devolución a proveedor -->
      <button
        @click="tab = 'ret_form'"
        :class="tab === 'ret_form' ? 'bg-indigo-600 text-white shadow' : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-rotate-left"></i> Devolución a proveedor
      </button>

      <!-- Devoluciones registradas -->
      <button
        @click="tab = 'ret_list'"
        :class="tab === 'ret_list' ? 'bg-indigo-600 text-white shadow' : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-rectangle-list"></i> Devoluciones registradas
      </button>

      <!-- Movimientos / Kardex -->
      <button
        @click="tab = 'kardex'"
        :class="tab === 'kardex' ? 'bg-indigo-600 text-white shadow' : 'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
        class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all duration-200">
        <i class="fa-solid fa-diagram-project"></i> Movimientos (Kardex)
      </button>
    </nav>

    {{-- ===== Contenido de pestañas ===== --}}
    <div class="p-6 md:p-8">
      {{-- TAB: Nueva entrada (tu componente) --}}
      <div x-show="tab === 'form'" x-cloak>
        <livewire:inventario.entradas-mercancia key="entradas-mercancia-form" />
      </div>

      {{-- TAB: Entradas registradas (si ya las muestra el propio componente, puedes dejar un placeholder o mover aquí solo la lista) --}}
      <div x-show="tab === 'list'" x-cloak class="space-y-4">
        <livewire:inventario.lista-entradas-generadas key="entradas-list" />
        </div>


      {{-- TAB: Devolución a proveedor (pendiente / placeholder) --}}
      <div x-show="tab === 'ret_form'" x-cloak class="text-sm text-gray-600 dark:text-gray-300">
        {{-- Aquí podrías incluir otro componente Livewire, ej:
           <livewire:inventario.devoluciones-proveedor key="dev-proveedor-form" />
        --}}
        <div class="rounded-xl border border-dashed p-6 dark:border-gray-700">
          <p class="font-semibold">Formulario de Devolución a Proveedor</p>
          <p class="mt-1">Próximamente…</p>
        </div>
      </div>

      {{-- TAB: Devoluciones registradas (pendiente / placeholder) --}}
      <div x-show="tab === 'ret_list'" x-cloak class="text-sm text-gray-600 dark:text-gray-300">
        {{-- <livewire:inventario.devoluciones-proveedor-list key="dev-proveedor-list" /> --}}
        <div class="rounded-xl border border-dashed p-6 dark:border-gray-700">
          <p class="font-semibold">Devoluciones registradas</p>
          <p class="mt-1">Próximamente…</p>
        </div>
      </div>

      {{-- TAB: Movimientos / Kardex (pendiente / placeholder) --}}
      <div x-show="tab === 'kardex'" x-cloak class="text-sm text-gray-600 dark:text-gray-300">
        {{-- <livewire:inventario.kardex key="kardex" /> --}}
        <div class="rounded-xl border border-dashed p-6 dark:border-gray-700">
          <p class="font-semibold">Movimientos (Kardex)</p>
          <p class="mt-1">Próximamente…</p>
        </div>
      </div>
    </div>
  </section>
</div>
