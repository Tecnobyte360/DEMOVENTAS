@assets
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
@endassets

@once
    @push('scripts')
        <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    @endpush
@endonce

<div x-data="{ tab: 'form' }" x-on:cotizacion-opened.window="tab = 'form'" class="space-y-8">

    {{-- =================== NAV =================== --}}
    <section class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">

        <nav class="flex flex-wrap gap-3 px-6 py-4 border-b border-gray-200 dark:border-gray-700">

            {{-- Nueva cotización --}}
            <button @click="tab = 'form'"
                :class="tab === 'form'
                    ?
                    'bg-indigo-600 text-white shadow' :
                    'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
                class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition">
                <i class="fa-solid fa-file-invoice"></i>
                Nueva cotización
            </button>

            {{-- Cotizaciones generadas --}}
            <button @click="tab = 'list'"
                :class="tab === 'list'
                    ?
                    'bg-indigo-600 text-white shadow' :
                    'bg-indigo-50 dark:bg-gray-800 text-indigo-700 dark:text-gray-300 hover:bg-indigo-100 dark:hover:bg-gray-700'"
                class="px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center gap-2 transition">
                <i class="fa-solid fa-list"></i>
                Cotizaciones generadas
            </button>



        </nav>

        {{-- =================== CONTENIDO =================== --}}
        <div class="p-6 md:p-8">

            {{-- Formulario --}}
            <section x-show="tab === 'form'" x-transition>
                <livewire:cotizaciones.cotizacion />
            </section>

            {{-- Lista --}}
            <section x-show="tab === 'list'" x-transition>
                <livewire:cotizaciones.lista-cotizaciones />
            </section>

        </div>
    </section>
</div>
