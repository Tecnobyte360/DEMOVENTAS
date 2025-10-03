<div x-data="{ showForm: @entangle('showForm') }" class="space-y-6">

    {{-- flashes --}}
    @if (session('message'))
        <div class="flex items-center gap-2 px-4 py-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium">{{ session('message') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-2 px-4 py-3 rounded-xl bg-red-50 text-red-700 border border-red-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M12 9v2m0 4h.01M12 5a7 7 0 100 14 7 7 0 000-14z"/></svg>
            <span class="text-sm font-medium">{{ session('error') }}</span>
        </div>
    @endif

    {{-- header / acciones --}}
    <section class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
        <header class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="inline-flex size-10 items-center justify-center rounded-xl bg-indigo-600/10 text-indigo-600">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 00-7 7v4.5l-1.447 2.894A1 1 0 004.447 18H7a5 5 0 0010 0h2.553a1 1 0 00.894-1.606L19 13V9a7 7 0 00-7-7z"/></svg>
                </span>
                <div>
                    <h2 class="text-xl font-extrabold text-gray-800 dark:text-white">Municipios</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Catálogo maestro para relaciones (FK)</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <span class="absolute inset-y-0 left-3 grid place-items-center text-gray-400">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M10 18a8 8 0 118-8 8 8 0 01-8 8zm11 3-6-6"/></svg>
                    </span>
                    <input type="text"
                           wire:model.debounce.400ms="search"
                           placeholder="Buscar por nombre, DANE, dpto…"
                           class="w-72 pl-9 pr-9 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @if($search)
                        <button type="button" wire:click="$set('search','')"
                                class="absolute inset-y-0 right-2 px-2 text-gray-400 hover:text-gray-600">✕</button>
                    @endif
                </div>

                <select wire:model="perPage"
                        class="px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:outline-none">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>

                <button wire:click="openCreate"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11 11V5h2v6h6v2h-6v6h-2v-6H5v-2z"/></svg>
                    Nuevo
                </button>
            </div>
        </header>

        {{-- tabla + loading --}}
        <div class="relative">
            <div wire:loading.delay class="absolute inset-0 z-10 grid place-items-center bg-white/60 dark:bg-gray-900/60 backdrop-blur-sm">
                <div class="animate-spin size-8 rounded-full border-4 border-indigo-200 border-t-indigo-600"></div>
            </div>

            <div class="overflow-x-auto rounded-2xl m-4 border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 sticky top-0 z-0">
                        <tr>
                            <th class="p-3 text-left">ID</th>
                            <th class="p-3 text-left">Nombre</th>
                            <th class="p-3 text-left">Departamento</th>
                            <th class="p-3 text-left">Código DANE</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($rows as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition" wire:key="mun-{{ $r->id }}">
                                <td class="p-3 text-gray-500">{{ $r->id }}</td>
                                <td class="p-3 font-semibold text-gray-900 dark:text-gray-100">{{ $r->nombre }}</td>
                                <td class="p-3 text-gray-700 dark:text-gray-300">{{ $r->departamento ?: '—' }}</td>
                                <td class="p-3 text-gray-700 dark:text-gray-300">{{ $r->codigo_dane ?: '—' }}</td>
                                <td class="p-3 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <button wire:click="openEdit({{ $r->id }})"
                                                class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                                            Editar
                                        </button>
                                        <button
                                            x-on:click.prevent="if(confirm('¿Eliminar municipio «{{ $r->nombre }}»?')) $wire.delete({{ $r->id }})"
                                            class="px-3 py-1.5 rounded-lg border border-red-300 text-red-600 hover:bg-red-50">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center">
                                    <div class="mx-auto max-w-md">
                                        <div class="mx-auto mb-3 size-12 rounded-full bg-gray-100 dark:bg-gray-800 grid place-items-center">
                                            <svg class="w-6 h-6 text-gray-400" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5h18v2H3zm0 6h12v2H3zm0 6h18v2H3z"/></svg>
                                        </div>
                                        <p class="text-gray-500 dark:text-gray-400">No hay municipios que coincidan con la búsqueda.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-6 pb-6 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <div>
                    Mostrando <span class="font-semibold">{{ $rows->firstItem() }}</span>–
                    <span class="font-semibold">{{ $rows->lastItem() }}</span> de
                    <span class="font-semibold">{{ $rows->total() }}</span>
                </div>
                <div>{{ $rows->links() }}</div>
            </div>
        </div>
    </section>

    {{-- MODAL: crear/editar --}}
    <div
        x-show="showForm"
        x-cloak
        @keydown.escape.window="showForm=false; $wire.closeForm()"
        class="fixed inset-0 z-50 grid place-items-center bg-black/50 backdrop-blur-sm"
        x-transition.opacity>
        <div
            class="w-full max-w-xl mx-4 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-2xl overflow-hidden"
            x-transition.scale.origin.center>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <h3 class="text-lg font-bold">
                    {{ $editingId ? 'Editar municipio' : 'Nuevo municipio' }}
                </h3>
                <button class="text-red-600 hover:text-red-700"
                        @click="showForm=false"
                        wire:click="closeForm">✕</button>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="text-xs text-gray-600 dark:text-gray-300">Nombre *</label>
                    <input type="text" wire:model.defer="nombre"
                           class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @error('nombre') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-600 dark:text-gray-300">Departamento</label>
                        <input type="text" wire:model.defer="departamento"
                               class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-white focus:outline-none">
                        @error('departamento') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="text-xs text-gray-600 dark:text-gray-300">Código DANE</label>
                        <input type="text" wire:model.defer="codigo_dane"
                               class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 px-3 py-2 dark:bg-gray-800 dark:text-white focus:outline-none">
                        @error('codigo_dane') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50 flex justify-end gap-2">
                <button class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-700"
                        @click="showForm=false"
                        wire:click="closeForm">Cancelar</button>
                <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700"
                        wire:click="save">Guardar</button>
            </div>
        </div>
    </div>
</div>
