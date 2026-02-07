<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

       

        {{-- Grid --}}
        <div class="grid grid-cols-12 gap-6 items-stretch">
            <div class="col-span-12 xl:col-span-12">
                <div class="h-[560px]">
                    @livewire('indicadores.ventas-por-mes')
                </div>
            </div>
            {{-- Card 1 --}}
            <div class="col-span-12 xl:col-span-6">
                <div class="h-[560px]">
                    @livewire('indicadores.indicadores')
                </div>
            </div>

            {{-- Card 2 --}}
            <div class="col-span-12 xl:col-span-6">
                <div class="h-[560px]">
                    @livewire('indicadores.ingresos-vs-egresos')
                </div>
            </div>

            {{-- Card 3 --}}


        </div>

    </div>
</x-app-layout>
