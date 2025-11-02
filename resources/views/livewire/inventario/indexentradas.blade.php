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

    </div>
  </section>
</div>
