@assets
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
@endassets

@php
    // ========= Color primario de la empresa (igual que el sidebar) =========
    $rawColor = $empresaActual?->color_primario ?: '1F2937';
    $hex = ltrim(trim($rawColor), '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (strlen($hex) !== 6) { $hex = '1F2937'; }

    $primary = '#'.$hex;
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Variante más oscura para el gradiente (mezcla con negro ~15%)
    $darkR = max(0, (int) round($r * 0.75));
    $darkG = max(0, (int) round($g * 0.75));
    $darkB = max(0, (int) round($b * 0.75));

    $primaryDark = sprintf('#%02X%02X%02X', $darkR, $darkG, $darkB);
@endphp

<div
    x-data="{
        isVisibleCreateRolesModal: $wire.entangle('isVisibleCreateRolesModal').live,
        isVisibleEditRolesModal: $wire.entangle('isVisibleEditRolesModal').live,
        isVisibleAssignPermissionModal: $wire.entangle('isVisibleAssignPermissionModal').live,
        permSearch: '',
    }"
    class="p-4 md:p-6 space-y-6"
    style="--brand:{{ $primary }}; --brand-dark:{{ $primaryDark }}; --brand-rgb:{{ $r }},{{ $g }},{{ $b }};"
>
    {{-- ============================================================
        BREADCRUMB
    ============================================================= --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" aria-label="Breadcrumb">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
        <i class="fas fa-chevron-right text-xs"></i>
        <i class="fas fa-user-tag" style="color: var(--brand);"></i>
        <span class="font-semibold text-gray-700 dark:text-gray-200">Roles y permisos</span>
    </nav>

    {{-- ============================================================
        HEADER
    ============================================================= --}}
    <div class="relative overflow-hidden rounded-2xl text-white shadow-xl"
        style="background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);">
        <div class="absolute -top-10 -right-10 w-56 h-56 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-16 -left-16 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative p-6 md:p-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm shadow-lg">
                    <i class="fas fa-user-shield text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold tracking-tight">Gestión de Roles</h1>
                    <p class="text-sm md:text-base text-white/80 mt-0.5">
                        Define quién puede hacer qué en el sistema
                    </p>
                </div>
            </div>

            <button
                @click="openModal($event)"
                wire:click="OpenCreateRolesModal"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white font-semibold shadow-lg hover:shadow-xl hover:scale-105 transition-all"
                style="color: var(--brand);"
            >
                <i class="fas fa-plus"></i>
                <span>Nuevo rol</span>
                <svg wire:loading wire:target="OpenCreateRolesModal" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" style="color: var(--brand);">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </button>
        </div>
    </div>

    {{-- ============================================================
        STATS + BUSCADOR
    ============================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-2 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm p-4">
            <label class="block text-xs uppercase tracking-wider font-semibold text-gray-500 dark:text-gray-400 mb-2">
                <i class="fas fa-search mr-1"></i> Buscar rol
            </label>
            <div class="relative">
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="Escribe el nombre del rol..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white outline-none focus:ring-2"
                    style="--tw-ring-color: var(--brand);">
                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
            </div>
        </div>

        <div class="rounded-2xl text-white shadow-sm p-4 flex items-center gap-3"
            style="background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);">
            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                <i class="fas fa-user-tag text-xl"></i>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-white/80">Roles</div>
                <div class="text-2xl font-extrabold">{{ $roles->total() }}</div>
            </div>
        </div>

        <div class="rounded-2xl shadow-sm p-4 flex items-center gap-3 border-2"
            style="background: rgba(var(--brand-rgb), 0.08); border-color: rgba(var(--brand-rgb), 0.25);">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                style="background: rgba(var(--brand-rgb), 0.15); color: var(--brand);">
                <i class="fas fa-key text-xl"></i>
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider" style="color: var(--brand);">Permisos</div>
                <div class="text-2xl font-extrabold" style="color: var(--brand-dark);">{{ $todosLosPermisos->count() }}</div>
            </div>
        </div>
    </div>

    {{-- ============================================================
        GRID DE ROLES
    ============================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse ($roles as $role)
            @php
                $totalPermisos = $role->permissions->count();
                $porcentaje    = $todosLosPermisos->count() > 0 ? round(($totalPermisos / $todosLosPermisos->count()) * 100) : 0;
                $esAdmin       = strtolower($role->name) === 'administrador';

                // Estilo del header de la card según el tipo de rol
                if ($esAdmin) {
                    // Admin: usa el color primario fuerte
                    $cardHeaderStyle = 'background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);';
                } elseif ($totalPermisos > 0) {
                    // Rol con permisos: variante suave del primario
                    $cardHeaderStyle = 'background: linear-gradient(135deg, rgba(var(--brand-rgb), 0.85) 0%, var(--brand) 100%);';
                } else {
                    // Rol sin permisos: gris neutro
                    $cardHeaderStyle = 'background: linear-gradient(135deg, #6B7280 0%, #4B5563 100%);';
                }
            @endphp

            <div class="group rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-xl transition-all overflow-hidden flex flex-col">
                {{-- Encabezado de la card --}}
                <div class="relative p-5 text-white" style="{{ $cardHeaderStyle }}">
                    <div class="absolute -top-6 -right-6 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                    <div class="relative flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-lg">
                                <i class="fas {{ $esAdmin ? 'fa-crown' : 'fa-user-tag' }} text-xl"></i>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-wider text-white/80">Rol</div>
                                <h3 class="text-lg font-extrabold capitalize">{{ $role->name }}</h3>
                            </div>
                        </div>

                        @if ($esAdmin)
                            <span class="px-2 py-0.5 rounded-full bg-white/25 text-xs font-semibold backdrop-blur-sm">
                                <i class="fas fa-shield-halved mr-1"></i> System
                            </span>
                        @endif
                    </div>

                    {{-- Barra de progreso de permisos --}}
                    <div class="mt-4">
                        <div class="flex justify-between text-xs text-white/80 mb-1">
                            <span>{{ $totalPermisos }} permisos asignados</span>
                            <span>{{ $porcentaje }}%</span>
                        </div>
                        <div class="w-full h-2 bg-white/20 rounded-full overflow-hidden">
                            <div class="h-full bg-white rounded-full transition-all" style="width: {{ $porcentaje }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Cuerpo de la card --}}
                <div class="flex-1 p-5 space-y-3">
                    @if (filled($role->description))
                        <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                            {{ $role->description }}
                        </p>
                    @else
                        <p class="text-sm italic text-gray-400">Sin descripción</p>
                    @endif

                    @if ($totalPermisos > 0)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($role->permissions->take(6) as $p)
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-medium"
                                    style="background: rgba(var(--brand-rgb), 0.1); color: var(--brand-dark);">
                                    {{ $p->name }}
                                </span>
                            @endforeach
                            @if ($totalPermisos > 6)
                                <span class="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-[11px] font-semibold">
                                    +{{ $totalPermisos - 6 }} más
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="text-xs text-rose-600 font-medium flex items-center gap-1">
                            <i class="fas fa-triangle-exclamation"></i>
                            Este rol no tiene permisos asignados
                        </div>
                    @endif
                </div>

                {{-- Acciones --}}
                <div class="p-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-end gap-1">
                    <button
                        @click="openModal($event)"
                        wire:click="OpenAssignPermissionToRolesModal({{ $role->id }})"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium text-emerald-700 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/30 transition"
                        title="Asignar permisos">
                        <i class="fas fa-key"></i>
                        <span>Permisos</span>
                    </button>

                    <button
                        @click="openModal($event)"
                        wire:click="OpenEditRolesModal({{ $role->id }})"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sky-600 hover:bg-sky-50 dark:text-sky-400 dark:hover:bg-sky-900/30 transition"
                        title="Editar">
                        <i class="fas fa-pen"></i>
                    </button>

                    <button
                        wire:click="DuplicatRol({{ $role->id }})"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-violet-600 hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-900/30 transition"
                        title="Duplicar">
                        <i class="fas fa-copy"></i>
                    </button>

                    <button
                        wire:click="OpenDeleteEditRolesModal({{ $role->id }})"
                        onclick="return confirm('¿Seguro quieres eliminar el rol {{ $role->name }}?')"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/30 transition"
                        title="Eliminar"
                        @disabled($esAdmin)>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="md:col-span-2 xl:col-span-3 rounded-2xl bg-white dark:bg-gray-900 border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center">
                <i class="fas fa-user-slash text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                <p class="text-gray-500 dark:text-gray-400">No se encontraron roles.</p>
            </div>
        @endforelse
    </div>

    <div>{{ $roles->links() }}</div>

    {{-- ============================================================
        MODAL: CREAR ROL
    ============================================================= --}}
    <template x-teleport="body">
        <div x-show="isVisibleCreateRolesModal" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="isVisibleCreateRolesModal=false"></div>

            <div x-show="isVisibleCreateRolesModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden">

                <div class="px-6 py-5 text-white flex items-center gap-3"
                    style="background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">Nuevo rol</h3>
                        <p class="text-xs text-white/80">Define un nuevo perfil de acceso</p>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nombre del rol
                        </label>
                        <input type="text" wire:model="name"
                            oninput="this.value = this.value.toLowerCase();"
                            wire:keydown.enter="store"
                            placeholder="ej. vendedor, cajero, bodega"
                            class="w-full px-3 py-2 rounded-lg border @error('name') border-rose-500 bg-rose-50 @else border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white @enderror focus:ring-2 outline-none"
                            style="--tw-ring-color: var(--brand);">
                        @error('name') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Descripción
                        </label>
                        <textarea wire:model="description" rows="3"
                            placeholder="Describe para qué sirve este rol..."
                            class="w-full px-3 py-2 rounded-lg border @error('description') border-rose-500 bg-rose-50 @else border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white @enderror focus:ring-2 outline-none"
                            style="--tw-ring-color: var(--brand);"></textarea>
                        @error('description') <p class="text-xs text-rose-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 flex justify-end gap-2">
                    <button @click="isVisibleCreateRolesModal=false" wire:click="CloseModalClick('isVisibleCreateRolesModal')"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        Cancelar
                    </button>
                    <button wire:click="store" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white font-semibold shadow-md transition hover:opacity-90"
                        style="background: var(--brand);">
                        <i class="fas fa-save"></i> Guardar
                        <svg wire:loading wire:target="store" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ============================================================
        MODAL: EDITAR ROL
    ============================================================= --}}
    <template x-teleport="body">
        <div x-show="isVisibleEditRolesModal" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="isVisibleEditRolesModal=false"></div>

            <div x-show="isVisibleEditRolesModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden">

                <div class="px-6 py-5 text-white flex items-center gap-3"
                    style="background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                        <i class="fas fa-pen"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg">Editar rol</h3>
                        <p class="text-xs text-white/80">Actualiza la información del rol</p>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre del rol</label>
                        <input type="text" wire:model="name"
                            oninput="this.value = this.value.toLowerCase();"
                            wire:keydown.enter="update"
                            class="w-full px-3 py-2 rounded-lg border @error('name') border-rose-500 bg-rose-50 @else border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white @enderror focus:ring-2 outline-none"
                            style="--tw-ring-color: var(--brand);">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                        <textarea wire:model="description" rows="3"
                            class="w-full px-3 py-2 rounded-lg border @error('description') border-rose-500 bg-rose-50 @else border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white @enderror focus:ring-2 outline-none"
                            style="--tw-ring-color: var(--brand);"></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 flex justify-end gap-2">
                    <button @click="isVisibleEditRolesModal=false" wire:click="$set('isVisibleEditRolesModal', false)"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        Cancelar
                    </button>
                    <button wire:click="update" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white font-semibold shadow-md transition hover:opacity-90"
                        style="background: var(--brand);">
                        <i class="fas fa-save"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ============================================================
        MODAL: ASIGNAR PERMISOS (agrupados por módulo)
    ============================================================= --}}
    <template x-teleport="body">
        <div x-show="isVisibleAssignPermissionModal" x-cloak class="fixed inset-0 z-[90] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" @click="isVisibleAssignPermissionModal=false"></div>

            <div x-show="isVisibleAssignPermissionModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden flex flex-col">

                {{-- Header --}}
                <div class="px-6 py-5 text-white flex items-center justify-between gap-3"
                    style="background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                            <i class="fas fa-key"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Permisos del rol: <span class="capitalize">{{ $selectedRole['name'] ?? '' }}</span></h3>
                            <p class="text-xs text-white/80">Marca las acciones que este rol podrá realizar</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-white/80">Seleccionados</div>
                        <div class="text-2xl font-extrabold">
                            <span x-text="$wire.selectedPermissions.length"></span> / {{ $todosLosPermisos->count() }}
                        </div>
                    </div>
                </div>

                {{-- Toolbar --}}
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="relative flex-1">
                        <input type="text" x-model="permSearch"
                            placeholder="Buscar permiso o módulo..."
                            class="w-full pl-9 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-2 outline-none text-sm"
                            style="--tw-ring-color: var(--brand);">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-xs"></i>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button"
                            @click="$wire.set('selectedPermissions', {{ $todosLosPermisos->pluck('id')->toJson() }})"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition hover:opacity-90"
                            style="background: var(--brand);">
                            <i class="fas fa-check-double"></i> Todos
                        </button>
                        <button type="button"
                            @click="$wire.set('selectedPermissions', [])"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-100 hover:bg-rose-200 text-rose-800 transition">
                            <i class="fas fa-xmark"></i> Ninguno
                        </button>
                    </div>
                </div>

                {{-- Cuerpo con módulos --}}
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    @foreach ($grupos as $grupo)
                        @php
                            $idsGrupo = collect($grupo['permisos'])->pluck('id')->values()->all();
                            $idsJson  = json_encode($idsGrupo);
                        @endphp
                        <div
                            x-data="{ show: true }"
                            x-show="
                                permSearch === '' ||
                                '{{ strtolower($grupo['titulo']) }}'.includes(permSearch.toLowerCase()) ||
                                @js(collect($grupo['permisos'])->pluck('name')->implode('|')).toLowerCase().includes(permSearch.toLowerCase())
                            "
                            class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/60 overflow-hidden">

                            {{-- Header del módulo --}}
                            @php
                                $totalGrupo = count($grupo['permisos']);
                            @endphp
                            <div
                                x-data="{
                                    get sel() {
                                        const sel = ($wire.selectedPermissions || []).map(v => String(v));
                                        const ids = {{ $idsJson }}.map(v => String(v));
                                        return ids.filter(id => sel.includes(id)).length;
                                    }
                                }"
                                class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 border-b border-gray-100 dark:border-gray-800">
                                <button type="button" @click="show = !show"
                                    class="flex items-center gap-3 flex-1 text-left">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                        style="background: rgba(var(--brand-rgb), 0.12); color: var(--brand);">
                                        <i class="fas {{ $grupo['icono'] }}"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-gray-800 dark:text-gray-100 text-sm">
                                            {{ $grupo['titulo'] }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400">
                                            <span x-text="sel"></span> de {{ $totalGrupo }} {{ $totalGrupo === 1 ? 'permiso' : 'permisos' }}
                                        </div>
                                    </div>
                                </button>

                                <div class="flex items-center gap-2 ml-3 shrink-0">
                                    <button type="button"
                                        title="Seleccionar todos"
                                        @click="
                                            let sel = ($wire.selectedPermissions || []).map(v => String(v));
                                            let ids = {{ $idsJson }}.map(v => String(v));
                                            const all = ids.every(id => sel.includes(id));
                                            if (all) {
                                                $wire.set('selectedPermissions', sel.filter(v => !ids.includes(v)));
                                            } else {
                                                $wire.set('selectedPermissions', [...new Set([...sel, ...ids])]);
                                            }
                                        "
                                        class="text-[11px] px-3 h-8 rounded-lg font-semibold transition flex items-center gap-1.5"
                                        :class="sel === {{ $totalGrupo }}
                                            ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'
                                            : 'text-white hover:opacity-90 border border-transparent'"
                                        :style="sel === {{ $totalGrupo }} ? '' : 'background: var(--brand);'">
                                        <i class="fas" :class="sel === {{ $totalGrupo }} ? 'fa-times' : 'fa-check-double'"></i>
                                        <span x-text="sel === {{ $totalGrupo }} ? 'Quitar' : 'Marcar todos'"></span>
                                    </button>
                                    <button type="button" @click="show = !show"
                                        class="h-8 w-8 grid place-items-center rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-chevron-down text-xs transition" :class="show ? 'rotate-180' : ''"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Permisos del módulo --}}
                            <div x-show="show" x-collapse class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach ($grupo['permisos'] as $perm)
                                    <label class="perm-item flex items-center gap-3 px-3 py-2.5 rounded-xl border-2 border-gray-200 dark:border-gray-800 cursor-pointer">
                                        <input type="checkbox"
                                            wire:model.live="selectedPermissions"
                                            value="{{ $perm['id'] }}"
                                            class="border-gray-300 focus:ring-2"
                                            style="accent-color: var(--brand); --tw-ring-color: var(--brand);">
                                        <div class="flex-1 min-w-0">
                                            <div class="perm-name text-sm font-medium text-gray-800 dark:text-gray-100 truncate">
                                                {{ $perm['label'] }}
                                            </div>
                                            <div class="perm-code text-[10px] text-gray-500 font-mono truncate">
                                                {{ $perm['name'] }}
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-2">
                    <button @click="isVisibleAssignPermissionModal=false" wire:click="$set('isVisibleAssignPermissionModal', false)"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        Cancelar
                    </button>
                    <button wire:click="updateRolePermissions" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg text-white font-semibold shadow-md transition hover:opacity-90"
                        style="background: var(--brand);">
                        <i class="fas fa-save"></i> Guardar permisos
                        <svg wire:loading wire:target="updateRolePermissions" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <style>
        .perm-item {
            position: relative;
            transition: all .15s ease;
            background: white;
        }
        .perm-item:hover {
            border-color: var(--brand) !important;
            background: rgba(var(--brand-rgb), 0.06);
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0,0,0,.04);
        }
        .perm-item:has(:checked) {
            background: linear-gradient(135deg, rgba(var(--brand-rgb), 0.10), rgba(var(--brand-rgb), 0.18));
            border-color: var(--brand) !important;
            box-shadow: inset 0 0 0 1px rgba(var(--brand-rgb), 0.30);
        }
        .perm-item:has(:checked) .perm-name {
            color: var(--brand);
            font-weight: 600;
        }
        .perm-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border-radius: 6px;
        }
        .perm-item .perm-code {
            opacity: .55;
        }
    </style>
</div>
