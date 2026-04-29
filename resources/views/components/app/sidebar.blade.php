@php
    // =========================
    //  NORMALIZAR COLOR EMPRESA
    // =========================
    $rawColor = $empresaActual?->color_primario ?: '1F2937';
    $hexColor = ltrim(trim($rawColor), '#');

    // soporta #RGB
    if (strlen($hexColor) === 3) {
        $hexColor = $hexColor[0] . $hexColor[0] . $hexColor[1] . $hexColor[1] . $hexColor[2] . $hexColor[2];
    }

    // si viene raro, fallback
    if (strlen($hexColor) !== 6) {
        $hexColor = '1F2937';
    }

    $primary = '#' . $hexColor;

    // RGB para rgba()
    $r = hexdec(substr($hexColor, 0, 2));
    $g = hexdec(substr($hexColor, 2, 2));
    $b = hexdec(substr($hexColor, 4, 2));
@endphp

<div class="min-w-fit">
    <!-- Sidebar backdrop (mobile only) -->
    <div class="fixed inset-0 bg-white z-40 lg:hidden transition-opacity duration-300"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'" aria-hidden="true" x-cloak></div>

    <!-- Sidebar -->
    <div id="sidebar" x-data="{
        fg: '#FFFFFF',
        fgMuted: 'rgba(255,255,255,0.92)',
        fgHover: '#FFFFFF',
        isLight: false,
    
        hexToRgb(hex) {
            const h = (hex || '').trim();
            if (!h) return { r: 31, g: 41, b: 55 };
            const s = h.replace('#', '');
            const n = s.length === 3 ? s.split('').map(c => c + c).join('') : s;
            const r = parseInt(n.substring(0, 2), 16) || 31;
            const g = parseInt(n.substring(2, 4), 16) || 41;
            const b = parseInt(n.substring(4, 6), 16) || 55;
            return { r, g, b };
        },
        luminance({ r, g, b }) {
            const srgb = [r, g, b]
                .map(v => v / 255)
                .map(v => v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4));
            return 0.2126 * srgb[0] + 0.7152 * srgb[1] + 0.0722 * srgb[2];
        },
        setup(bg) {
            const rgb = this.hexToRgb(bg);
            const L = this.luminance(rgb);
            this.isLight = L > 0.65;
    
            if (this.isLight) {
                this.fg = '#111827';
                this.fgMuted = 'rgba(17,24,39,.70)';
                this.fgHover = '#111827';
            } else {
                this.fg = '#FFFFFF';
                this.fgMuted = 'rgba(255,255,255,0.92)';
                this.fgHover = '#FFFFFF';
            }
        }
    }" x-init="setup('{{ $primary }}')"
        :style="`
                    background: linear-gradient(180deg,
                        {{ $primary }} 0%,
                        rgba({{ $r }}, {{ $g }}, {{ $b }}, 0.96) 100%
                    );
                    --sidebar-fg: ${fg};
                    --sidebar-fg-muted: ${fgMuted};
                    --sidebar-fg-hover: ${fgHover};
                `"
        class="flex flex-col fixed z-50 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0
               h-screen w-72 lg:w-20 lg:sidebar-expanded:!w-72 2xl:w-72
               shrink-0 transition-all duration-300 ease-in-out
               {{ $variant === 'v2' ? 'border-r border-white/10' : 'shadow-2xl' }}"
        :class="sidebarOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-72'" @click.outside="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false" x-cloak>
        <!-- Header fijo -->
        <div class="flex-none px-4 pt-6 pb-4">
            <!-- Close button (mobile) -->
            <button
                class="lg:hidden absolute top-4 right-4 p-2 rounded-lg bg-white/10 hover:bg-white/30
                       transition-all duration-200 hover:scale-110 hover:rotate-90"
                @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar" :aria-expanded="sidebarOpen">
                <svg class="w-5 h-5" :style="`fill: ${fg}`" viewBox="0 0 24 24">
                    <path
                        d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                </svg>
            </button>

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="flex justify-center mb-6 group">
                <div
                    class="p-3 rounded-xl bg-white shadow-lg group-hover:shadow-2xl
                            transition-all duration-300 group-hover:scale-105">
                    <img src="{{ $empresaActual?->logo_url }}" alt="{{ $empresaActual?->nombre }}"
                        class="h-14 w-auto object-contain" />
                </div>
            </a>

            <!-- System title -->
            <div class="text-center mb-4 lg:hidden lg:sidebar-expanded:block 2xl:block">
                <h1 class="font-bold text-base" :style="`color: ${fg}`">{{ $empresaActual?->nombre }}</h1>
                <p class="text-xs mt-1" :style="`color: ${fgMuted}`">Panel del cliente</p>
            </div>

            <!-- Buscador del sidebar -->
            <div x-data="{ q: '' }" x-init="$watch('q', v => {
                    const term = v.trim().toLowerCase();
                    const nav = document.querySelector('#sidebar nav');
                    if (!nav) return;
                    // Filtra solo items de PRIMER NIVEL (no submenús, para no romper x-show de Alpine)
                    const items = nav.querySelectorAll(':scope > .space-y-1 > a, :scope > .space-y-1 > div');
                    items.forEach(el => {
                        if (!term) { el.style.display = ''; return; }
                        const txt = (el.textContent || '').trim().toLowerCase();
                        el.style.display = txt.includes(term) ? '' : 'none';
                    });
                })"
                class="relative mb-4 lg:hidden lg:sidebar-expanded:block 2xl:block">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-sm pointer-events-none"
                   :style="`color: ${fgMuted}`"></i>
                <input type="text" x-model="q" placeholder="Buscar..."
                    class="sidebar-search w-full h-10 pl-10 pr-3 rounded-xl text-sm border-0 focus:outline-none focus:ring-2 focus:ring-white/40"
                    :style="`background: ${isLight ? 'rgba(0,0,0,.08)' : 'rgba(255,255,255,.15)'}; color: ${fg};`">
                <button type="button" x-show="q !== ''" x-cloak
                    @click="q = ''"
                    class="absolute right-2 top-1/2 -translate-y-1/2 h-6 w-6 grid place-items-center rounded-md hover:bg-white/20"
                    :style="`color: ${fgMuted}`">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Section label -->
            <div class="mb-3 flex items-center gap-2" data-menu-section>
                <span class="h-px flex-1" :style="`background: ${isLight ? 'rgba(0,0,0,.12)' : 'rgba(255,255,255,.18)'}`"></span>
                <span class="text-[10px] font-bold uppercase tracking-[0.18em]" :style="`color: ${fgMuted}`">
                    Principal
                </span>
                <span class="h-px flex-1" :style="`background: ${isLight ? 'rgba(0,0,0,.12)' : 'rgba(255,255,255,.18)'}`"></span>
            </div>
        </div>

        <!-- Scrollable navigation -->
        <nav class="flex-1 px-3 pb-4 overflow-y-auto sidebar-scrollbar">
            <div class="space-y-1">

                <!-- Dashboard -->
                @php
                    $dashboardPermisoExiste = \Spatie\Permission\Models\Permission::where('name', 'dashboard.ver')->exists();
                    $puedeVerDashboard = !$dashboardPermisoExiste || (auth()->user()?->can('dashboard.ver') ?? false);
                @endphp
                @if($puedeVerDashboard)
                <a href="{{ route('dashboard') }}"
                    class="group relative flex items-center gap-3 px-3 py-2.5 rounded-lg
                          transition-all duration-200 overflow-hidden
                          @if (Route::is('dashboard')) bg-white/20 shadow-md
                          @else
                              hover:bg-white/25 hover:shadow-md hover:translate-x-1 @endif">
                    <div
                        class="absolute left-0 top-0 bottom-0 w-1 bg-white/0 group-hover:bg-white transition-all duration-300 rounded-r-full">
                    </div>

                    <svg class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-125 group-hover:rotate-12"
                        :style="`fill: ${@json(Route::is('dashboard')) ? fg : fgMuted}`" viewBox="0 0 24 24">
                        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                    </svg>

                    <span
                        class="text-sm font-bold lg:hidden lg:sidebar-expanded:block 2xl:block transition-all duration-200"
                        :style="`color: ${@json(Route::is('dashboard')) ? fg : fgMuted}`"
                        :class="'group-hover:font-bold'">
                        Dashboard
                    </span>
                </a>
                @endif

                <!-- Section: OPERACIONES -->
                @canany(['ventas.ver', 'facturas.ver', 'cotizaciones.ver', 'notas_credito.ver', 'caja.ver',
                         'compras.ver', 'notas_credito_compra.ver',
                         'inventario.ver', 'bodegas.ver', 'productos.ver', 'categorias.ver', 'kardex.ver', 'transferencias.ver',
                         'terceros.ver', 'finanzas.ver', 'pagos.ver', 'gastos.ver'])
                    <div class="pt-3 pb-1 px-3 flex items-center gap-2">
                        <span class="h-px flex-1" :style="`background: ${isLight ? 'rgba(0,0,0,.10)' : 'rgba(255,255,255,.14)'}`"></span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.18em]" :style="`color: ${fgMuted}`">Operaciones</span>
                        <span class="h-px flex-1" :style="`background: ${isLight ? 'rgba(0,0,0,.10)' : 'rgba(255,255,255,.14)'}`"></span>
                    </div>
                @endcanany

                <!-- Ventas -->
                @canany(['ventas.ver', 'facturas.ver', 'cotizaciones.ver', 'notas_credito.ver', 'caja.ver'])
                    <div x-data="{ open: {{ in_array(Request::segment(1), ['ventas']) ? 'true' : 'false' }} }">
                        <button @click="open = !open; sidebarExpanded = true"
                            class="group relative flex items-center justify-between w-full gap-3 px-3 py-2.5 rounded-lg
                                   transition-all duration-200 overflow-hidden
                                   hover:bg-white/25 hover:shadow-md hover:translate-x-1"
                            :class="open ? 'bg-white/20 shadow-sm' : ''">
                            <div
                                class="absolute left-0 top-0 bottom-0 w-1 bg-white/0 group-hover:bg-white transition-all duration-300 rounded-r-full">
                            </div>

                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-125"
                                    :style="`fill: ${fgMuted}`" viewBox="0 0 24 24">
                                    <path
                                        d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" />
                                </svg>
                                <span
                                    class="text-sm font-bold lg:hidden lg:sidebar-expanded:block 2xl:block transition-all duration-200"
                                    :style="`color: ${fgMuted}`" :class="'group-hover:font-bold'">
                                    Ventas
                                </span>
                            </div>

                            <svg class="w-4 h-4 lg:hidden lg:sidebar-expanded:block 2xl:block transition-transform duration-300"
                                :class="open ? 'rotate-180' : ''" :style="`fill: ${fgMuted}`" viewBox="0 0 12 12">
                                <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                            </svg>
                        </button>

                        <div x-show="open" x-collapse
                            class="mt-1 ml-8 space-y-1 lg:hidden lg:sidebar-expanded:block 2xl:block">
                            @can('facturas.ver')
                                <a href="{{ route('Facturacion') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('Facturacion')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Factura de venta</span>
                                </a>

                                <a href="{{ route('pos.factura') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('pos.factura')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">POS Venta rápida</span>
                                </a>
                            @endcan

                            @can('cotizaciones.ver')
                                <a href="{{ route('Cotizaciones') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('Cotizaciones')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Cotizaciones</span>
                                </a>
                            @endcan

                            @can('notas_credito.ver')
                                <a href="{{ route('notascreditoclientes') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('notascreditoclientes')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Nota crédito de
                                        ventas</span>
                                </a>
                            @endcan

                            @can('caja.ver')
                                <a href="{{ route('abrircaja') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('abrircaja')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Apertura de caja</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanany

                <!-- Compras -->
                @canany(['compras.ver', 'notas_credito_compra.ver'])
                    <div x-data="{ open: false }">
                        <button @click="open = !open; sidebarExpanded = true"
                            class="group relative flex items-center justify-between w-full gap-3 px-3 py-2.5 rounded-lg
                                   transition-all duration-200 overflow-hidden
                                   hover:bg-white/25 hover:shadow-md hover:translate-x-1"
                            :class="open ? 'bg-white/20 shadow-sm' : ''">
                            <div
                                class="absolute left-0 top-0 bottom-0 w-1 bg-white/0 group-hover:bg-white transition-all duration-300 rounded-r-full">
                            </div>

                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-125"
                                    :style="`fill: ${fgMuted}`" viewBox="0 0 24 24">
                                    <path
                                        d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z" />
                                </svg>
                                <span
                                    class="text-sm font-bold lg:hidden lg:sidebar-expanded:block 2xl:block transition-all duration-200"
                                    :style="`color: ${fgMuted}`" :class="'group-hover:font-bold'">
                                    Compras
                                </span>
                            </div>

                            <svg class="w-4 h-4 lg:hidden lg:sidebar-expanded:block 2xl:block transition-transform duration-300"
                                :class="open ? 'rotate-180' : ''" :style="`fill: ${fgMuted}`" viewBox="0 0 12 12">
                                <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                            </svg>
                        </button>

                        <div x-show="open" x-collapse
                            class="mt-1 ml-8 space-y-1 lg:hidden lg:sidebar-expanded:block 2xl:block">
                            @can('compras.ver')
                                <a href="{{ route('Factura-compras') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('Factura-compras')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Factura de compra</span>
                                </a>
                            @endcan

                            @can('notas_credito_compra.ver')
                                <a href="{{ route('Notascreditocompra') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('Notascreditocompra')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Nota crédito de
                                        compras</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanany

                <!-- Inventario -->
                @canany(['inventario.ver', 'bodegas.ver', 'productos.ver', 'categorias.ver', 'kardex.ver', 'transferencias.ver'])
                    <div x-data="{ open: {{ in_array(Request::segment(1), ['ecommerce']) ? 'true' : 'false' }} }">
                        <button @click="open = !open; sidebarExpanded = true"
                            class="group relative flex items-center justify-between w-full gap-3 px-3 py-2.5 rounded-lg
                                   transition-all duration-200 overflow-hidden
                                   hover:bg-white/25 hover:shadow-md hover:translate-x-1"
                            :class="open ? 'bg-white/20 shadow-sm' : ''">
                            <div
                                class="absolute left-0 top-0 bottom-0 w-1 bg-white/0 group-hover:bg-white transition-all duration-300 rounded-r-full">
                            </div>

                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-125"
                                    :style="`fill: ${fgMuted}`" viewBox="0 0 24 24">
                                    <path
                                        d="M20 2H4c-1 0-2 .9-2 2v3.01c0 .72.43 1.34 1 1.69V20c0 1.1 1.1 2 2 2h14c.9 0 2-.9 2-2V8.7c.57-.35 1-.97 1-1.69V4c0-1.1-1-2-2-2zm-5 12H9v-2h6v2zm5-7H4V4h16v3z" />
                                </svg>
                                <span
                                    class="text-sm font-bold lg:hidden lg:sidebar-expanded:block 2xl:block transition-all duration-200"
                                    :style="`color: ${fgMuted}`" :class="'group-hover:font-bold'">
                                    Inventario
                                </span>
                            </div>

                            <svg class="w-4 h-4 lg:hidden lg:sidebar-expanded:block 2xl:block transition-transform duration-300"
                                :class="open ? 'rotate-180' : ''" :style="`fill: ${fgMuted}`" viewBox="0 0 12 12">
                                <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                            </svg>
                        </button>

                        <div x-show="open" x-collapse
                            class="mt-1 ml-8 space-y-1 lg:hidden lg:sidebar-expanded:block 2xl:block">
                            @can('inventario.entradas')
                            <!-- Nested: Operaciones de stock -->
                            <div x-data="{ openStock: false }">
                                <button @click="openStock = !openStock"
                                    class="group flex items-center justify-between w-full px-3 py-2 rounded-lg text-sm font-medium
                                           transition-all duration-200 hover:bg-white/25"
                                    :style="`color: ${fgMuted}`">
                                    <span class="transition-all duration-200 group-hover:font-bold">Operaciones de
                                        stock</span>
                                    <svg class="w-3 h-3 transition-transform duration-200"
                                        :class="openStock ? 'rotate-180' : ''" :style="`fill: ${fgMuted}`"
                                        viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </button>

                                <div x-show="openStock" x-collapse class="mt-1 ml-4 space-y-1">
                                    <a href="{{ route('Entradas') }}"
                                        class="group relative block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 overflow-hidden
                                          hover:bg-white/25 hover:pl-5"
                                        :style="`color: ${fgMuted}`">
                                        <div
                                            class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                        </div>
                                        <span class="transition-all duration-200 group-hover:font-bold">Entradas de
                                            stock</span>
                                    </a>

                                    {{-- <a href="{{ route('SalidaMercancia') }}"
                                        class="group relative block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 overflow-hidden
                                          hover:bg-white/25 hover:pl-5"
                                        :style="`color: ${fgMuted}`">
                                        <div
                                            class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                        </div>
                                        <span class="transition-all duration-200 group-hover:font-bold">Salida
                                            mercancía</span>
                                    </a> --}}

                                    {{-- <a href="{{ route('DevolucionMercancia') }}"
                                        class="group relative block px-3 py-2 rounded-lg text-xs font-medium transition-all duration-200 overflow-hidden
                                          hover:bg-white/25 hover:pl-5"
                                        :style="`color: ${fgMuted}`">
                                        <div
                                            class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                        </div>
                                        <span class="transition-all duration-200 group-hover:font-bold">Devolución de
                                            mercancía</span>
                                    </a> --}}



                                </div>
                            </div>
                            @endcan

                            @can('bodegas.ver')
                                <a href="{{ route('Bodegas') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('Bodegas')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Gestión Bodegas</span>
                                </a>

                                <a href="{{ route('inventario.por-bodega') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('inventario.por-bodega')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Inventario por Bodega</span>
                                </a>
                            @endcan

                            @can('categorias.ver')
                                <a href="{{ route('indexcategorias') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('indexcategorias')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Categorías
                                        Artículos</span>
                                </a>

                                <a href="{{ route('subcategorias.index') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('subcategorias.index')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">SubCategorías de
                                        artículos</span>
                                </a>
                            @endcan

                            @can('productos.ver')
                                <a href="{{ route('productos.index') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('productos.index')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Productos
                                        inventariables</span>
                                </a>
                            @endcan

                            @can('kardex.ver')
                                <a href="{{ route('cardexinventario') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('cardexinventario')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Kardex</span>
                                </a>
                            @endcan

                            @can('transferencias.ver')
                                <a href="{{ route('transferencias.stock') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('cardexinventario')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Transferencia de
                                        stock</span>
                                </a>
                            @endcan

                        </div>
                    </div>
                @endcanany

                <!-- Terceros -->
                @can('terceros.ver')
                <a href="{{ route('SociosNegocio') }}"
                    class="group relative flex items-center gap-3 px-3 py-2.5 rounded-lg
                          transition-all duration-200 overflow-hidden
                          @if (Route::is('SociosNegocio')) bg-white/20 shadow-md
                          @else
                              hover:bg-white/25 hover:shadow-md hover:translate-x-1 @endif">
                    <div
                        class="absolute left-0 top-0 bottom-0 w-1 bg-white/0 group-hover:bg-white transition-all duration-300 rounded-r-full">
                    </div>

                    <svg class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-125"
                        :style="`fill: ${@json(Route::is('SociosNegocio')) ? fg : fgMuted}`" viewBox="0 0 24 24">
                        <path
                            d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z" />
                    </svg>

                    <span
                        class="text-sm font-bold lg:hidden lg:sidebar-expanded:block 2xl:block transition-all duration-200"
                        :style="`color: ${@json(Route::is('SociosNegocio')) ? fg : fgMuted}`">
                        Terceros
                    </span>
                </a>
                @endcan

                <!-- Finanzas -->
                @canany(['finanzas.ver', 'pagos.ver', 'gastos.ver'])
                    <div x-data="{ open: false }">
                        <button @click="open = !open; sidebarExpanded = true"
                            class="group relative flex items-center justify-between w-full gap-3 px-3 py-2.5 rounded-lg
                                   transition-all duration-200 overflow-hidden
                                   hover:bg-white/25 hover:shadow-md hover:translate-x-1"
                            :class="open ? 'bg-white/20 shadow-sm' : ''">
                            <div
                                class="absolute left-0 top-0 bottom-0 w-1 bg-white/0 group-hover:bg-white transition-all duration-300 rounded-r-full">
                            </div>

                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-125"
                                    :style="`fill: ${fgMuted}`" viewBox="0 0 24 24">
                                    <path
                                        d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z" />
                                </svg>

                                <span
                                    class="text-sm font-bold lg:hidden lg:sidebar-expanded:block 2xl:block transition-all duration-200"
                                    :style="`color: ${fgMuted}`">
                                    Finanzas
                                </span>
                            </div>

                            <svg class="w-4 h-4 lg:hidden lg:sidebar-expanded:block 2xl:block transition-transform duration-300"
                                :class="open ? 'rotate-180' : ''" :style="`fill: ${fgMuted}`" viewBox="0 0 12 12">
                                <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                            </svg>
                        </button>

                        <div x-show="open" x-collapse
                            class="mt-1 ml-8 space-y-1 lg:hidden lg:sidebar-expanded:block 2xl:block">
                            @can('pagos.ver')
                                <a href="{{ route('PagosRecibidos') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('PagosRecibidos')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Recibos de caja</span>
                                </a>
                            @endcan

                            @can('gastos.ver')
                                <a href="{{ route('Gastos') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('Gastos')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Gastos</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanany

                <!-- Section: REPORTES -->
                @canany(['informes.ver', 'informes.ventas', 'informes.asientos'])
                    <div class="pt-3 pb-1 px-3 flex items-center gap-2">
                        <span class="h-px flex-1" :style="`background: ${isLight ? 'rgba(0,0,0,.10)' : 'rgba(255,255,255,.14)'}`"></span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.18em]" :style="`color: ${fgMuted}`">Reportes</span>
                        <span class="h-px flex-1" :style="`background: ${isLight ? 'rgba(0,0,0,.10)' : 'rgba(255,255,255,.14)'}`"></span>
                    </div>
                @endcanany

                <!-- Informes -->
                @canany(['informes.ver', 'informes.ventas', 'informes.asientos'])
                    <div x-data="{ open: {{ in_array(Request::segment(1), ['reportes']) ? 'true' : 'false' }} }">
                        <button @click="open = !open; sidebarExpanded = true"
                            class="group relative flex items-center justify-between w-full gap-3 px-3 py-2.5 rounded-lg
                                   transition-all duration-200 overflow-hidden
                                   hover:bg-white/25 hover:shadow-md hover:translate-x-1"
                            :class="open ? 'bg-white/20 shadow-sm' : ''">
                            <div
                                class="absolute left-0 top-0 bottom-0 w-1 bg-white/0 group-hover:bg-white transition-all duration-300 rounded-r-full">
                            </div>

                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-125"
                                    :style="`fill: ${fgMuted}`" viewBox="0 0 24 24">
                                    <path
                                        d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z" />
                                </svg>

                                <span
                                    class="text-sm font-bold lg:hidden lg:sidebar-expanded:block 2xl:block transition-all duration-200"
                                    :style="`color: ${fgMuted}`">
                                    Informes
                                </span>
                            </div>

                            <svg class="w-4 h-4 lg:hidden lg:sidebar-expanded:block 2xl:block transition-transform duration-300"
                                :class="open ? 'rotate-180' : ''" :style="`fill: ${fgMuted}`" viewBox="0 0 12 12">
                                <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                            </svg>
                        </button>

                        <div x-show="open" x-collapse
                            class="mt-1 ml-8 space-y-1 lg:hidden lg:sidebar-expanded:block 2xl:block">
                            @can('informes.asientos')
                                <a href="{{ route('asientos.index') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm
                                      @if (Route::is('asientos.index')) bg-white/20 pl-5 @endif"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Informe diario
                                        contable</span>
                                </a>
                            @endcan

                            @can('informes.ventas')
                                <a href="{{ route('reportes.ventas') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden
                                      hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div
                                        class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300">
                                    </div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Informe de venta</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanany

                <!-- Section label: ADMINISTRACION -->
                <div class="pt-3 pb-1 px-3 flex items-center gap-2">
                    <span class="h-px flex-1" :style="`background: ${isLight ? 'rgba(0,0,0,.10)' : 'rgba(255,255,255,.14)'}`"></span>
                    <span class="text-[10px] font-bold uppercase tracking-[0.18em]" :style="`color: ${fgMuted}`">Administración</span>
                    <span class="h-px flex-1" :style="`background: ${isLight ? 'rgba(0,0,0,.10)' : 'rgba(255,255,255,.14)'}`"></span>
                </div>

                <!-- Configuración -->
                @canany([
                    'configuracion.ver',
                    'usuarios.gestionar', 'roles.gestionar', 'empresas.gestionar',
                    'series.gestionar', 'normas_reparto.gestionar', 'cuentas_contables.gestionar',
                    'impuestos.gestionar', 'condiciones_pago.gestionar', 'medios_pago.gestionar',
                    'tipo_documentos.gestionar', 'conceptos_documentos.gestionar',
                ])
                    <div x-data="{ open: {{ in_array(Request::segment(1), ['settings']) ? 'true' : 'false' }} }">
                        <button @click="open = !open; sidebarExpanded = true"
                            class="group relative flex items-center justify-between w-full gap-3 px-3 py-2.5 rounded-lg
                                   transition-all duration-200 overflow-hidden
                                   hover:bg-white/25 hover:shadow-md hover:translate-x-1"
                            :class="open ? 'bg-white/20 shadow-sm' : ''">
                            <div
                                class="absolute left-0 top-0 bottom-0 w-1 bg-white/0 group-hover:bg-white transition-all duration-300 rounded-r-full">
                            </div>

                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-125 group-hover:rotate-180"
                                    :style="`fill: ${fgMuted}`" viewBox="0 0 24 24">
                                    <path
                                        d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94L14.4 2.81c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z" />
                                </svg>

                                <span
                                    class="text-sm font-bold lg:hidden lg:sidebar-expanded:block 2xl:block transition-all duration-200"
                                    :style="`color: ${fgMuted}`">
                                    Configuración
                                </span>
                            </div>

                            <svg class="w-4 h-4 lg:hidden lg:sidebar-expanded:block 2xl:block transition-transform duration-300"
                                :class="open ? 'rotate-180' : ''" :style="`fill: ${fgMuted}`" viewBox="0 0 12 12">
                                <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                            </svg>
                        </button>

                        <div x-show="open" x-collapse
                            class="mt-1 ml-8 space-y-1 lg:hidden lg:sidebar-expanded:block 2xl:block">
                            @can('usuarios.gestionar')
                                <a href="{{ route('Usuarios') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Usuarios</span>
                                </a>
                            @endcan
                            @can('roles.gestionar')
                                <a href="{{ route('roles.index') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Roles</span>
                                </a>
                                <a href="{{ route('roles.index2') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Permisos</span>
                                </a>
                            @endcan
                            @can('empresas.gestionar')
                                <a href="{{ route('Empresas') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Empresas</span>
                                </a>
                            @endcan
                            @can('series.gestionar')
                                <a href="{{ route('SeriesDocumentos') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Series Documentos</span>
                                </a>
                            @endcan
                            @can('normas_reparto.gestionar')
                                <a href="{{ route('normas-reparto.index') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Normas de Reparto</span>
                                </a>
                            @endcan
                            @can('cuentas_contables.gestionar')
                                <a href="{{ route('Cuentas-contables') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Plan de cuentas</span>
                                </a>
                            @endcan
                            @can('impuestos.gestionar')
                                <a href="{{ route('Impuestos') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Tablas de impuestos</span>
                                </a>
                            @endcan
                            @can('condiciones_pago.gestionar')
                                <a href="{{ route('condicionespago') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Condiciones Pago</span>
                                </a>
                            @endcan
                            @can('medios_pago.gestionar')
                                <a href="{{ route('Mediospagos') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Medios de pago</span>
                                </a>
                            @endcan
                            @can('tipo_documentos.gestionar')
                                <a href="{{ route('TipoDocumentos') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Tipo Documentos</span>
                                </a>
                            @endcan
                            @can('conceptos_documentos.gestionar')
                                <a href="{{ route('ConceptoDocumentos') }}"
                                    class="group relative block px-3 py-2 rounded-lg text-sm font-medium transition-all duration-200 overflow-hidden hover:bg-white/25 hover:pl-5 hover:shadow-sm"
                                    :style="`color: ${fgMuted}`">
                                    <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-white/0 group-hover:bg-white transition-all duration-300"></div>
                                    <span class="transition-all duration-200 group-hover:font-bold">Conceptos Documentos</span>
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanany

            </div>
        </nav>

        <!-- Footer fijo -->
        <div class="flex-none p-4 border-t" :style="`border-color: ${isLight ? '#cbd5e1' : 'rgba(255,255,255,.15)'}`">
            <!-- User info -->
            <div class="mb-3 lg:hidden lg:sidebar-expanded:block 2xl:block">
                <div
                    class="group flex items-center gap-3 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 transition-all duration-200 cursor-pointer">
                    <div
                        class="flex items-center justify-center w-9 h-9 rounded-full bg-violet-500 text-white font-semibold text-sm group-hover:scale-110 transition-transform duration-200">
                        {{ substr(auth()->user()->name ?? 'S', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate" :style="`color: ${fg}`">
                            {{ auth()->user()->name ?? 'Usuario' }}</p>
                        <p class="text-xs truncate" :style="`color: ${fgMuted}`">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            <!-- Collapse button -->
            <button @click="sidebarExpanded = !sidebarExpanded"
                class="hidden lg:flex 2xl:hidden w-full items-center justify-center gap-2 px-3 py-2.5 rounded-lg
                       bg-white/10 hover:bg-white/20 transition-all duration-200 hover:scale-105">
                <svg class="w-4 h-4 transition-transform duration-300 sidebar-expanded:rotate-180"
                    :style="`fill: ${fgMuted}`" viewBox="0 0 16 16">
                    <path
                        d="M15 16a1 1 0 0 1-1-1V1a1 1 0 1 1 2 0v14a1 1 0 0 1-1 1ZM8.586 7H1a1 1 0 1 0 0 2h7.586l-2.793 2.793a1 1 0 1 0 1.414 1.414l4.5-4.5A.997.997 0 0 0 12 8.01M11.924 7.617a.997.997 0 0 0-.217-.324l-4.5-4.5a1 1 0 0 0-1.414 1.414L8.586 7M12 7.99a.996.996 0 0 0-.076-.373Z" />
                </svg>
            </button>

            <!-- Cerrar sesión -->
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit"
                    class="group relative flex items-center gap-3 w-full px-3 py-2.5 rounded-lg overflow-hidden
                               transition-all duration-200 hover:bg-red-500/20 hover:shadow-sm">
                    <div
                        class="absolute left-0 top-0 bottom-0 w-1 bg-red-500/0 group-hover:bg-red-500 transition-all duration-300 rounded-r-full">
                    </div>

                    <svg class="w-5 h-5 shrink-0 transition-all duration-300 group-hover:scale-125 group-hover:-translate-x-1"
                        :style="`fill: ${fgMuted}`" viewBox="0 0 24 24">
                        <path
                            d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
                    </svg>

                    <span
                        class="text-sm font-bold lg:hidden lg:sidebar-expanded:block 2xl:block transition-all duration-200 group-hover:text-red-400"
                        :style="`color: ${fgMuted}`">
                        Cerrar Sesión
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* Scrollbar personalizado elegante */
    .sidebar-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, .45) transparent;
    }

    .sidebar-scrollbar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, .45);
        border-radius: 10px;
        transition: background-color 0.2s;
    }

    .sidebar-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(255, 255, 255, .65);
    }

    /* Previene flash */
    [x-cloak] {
        display: none !important;
    }

    /* Item activo: fondo blanco translúcido más fuerte + barra izquierda + ligera elevación */
    #sidebar nav a.bg-white\/20,
    #sidebar nav button.bg-white\/20 {
        background-color: rgba(255, 255, 255, .22) !important;
        position: relative;
    }
    #sidebar nav a.bg-white\/20::before,
    #sidebar nav button.bg-white\/20::before {
        content: "";
        position: absolute;
        left: 0; top: 8px; bottom: 8px;
        width: 4px;
        border-radius: 0 4px 4px 0;
        background: #ffffff;
        box-shadow: 0 0 8px rgba(255,255,255,.5);
    }

    /* Hover suave consistente */
    #sidebar nav a:hover,
    #sidebar nav button:hover {
        background-color: rgba(255, 255, 255, .14) !important;
    }

    /* Sub-items (más sutiles) */
    #sidebar nav .ml-8 a:hover {
        background-color: rgba(255, 255, 255, .10) !important;
    }

    /* Buscador del sidebar */
    .sidebar-search::placeholder {
        color: rgba(255, 255, 255, .65);
        font-weight: 400;
    }
    .sidebar-search:focus {
        background-color: rgba(255, 255, 255, .22) !important;
    }

    /* === Tipografía limpia tipo "Alimentos La Hacienda" === */

    /* Esconde las líneas divisorias en los headers de sección */
    #sidebar .h-px.flex-1 {
        display: none !important;
    }

    /* Headers de sección: pequeñas, capitalizadas, sin tracking exagerado */
    #sidebar [class*="text-[10px]"][class*="uppercase"] {
        font-size: 12px !important;
        font-weight: 500 !important;
        letter-spacing: 0 !important;
        text-transform: capitalize !important;
        opacity: .55 !important;
    }

    /* Quitar espacio extra de los headers de sección */
    #sidebar nav .pt-3.pb-1.px-3,
    #sidebar .mb-3.flex.items-center.gap-2 {
        justify-content: flex-start !important;
        padding-left: 0.75rem !important;
        margin-top: 1rem !important;
        margin-bottom: 0.25rem !important;
    }

    /* Items: texto blanco más limpio */
    #sidebar nav > .space-y-1 > a span,
    #sidebar nav > .space-y-1 > div > button span {
        font-weight: 500 !important;
        font-size: 14px !important;
        opacity: .92;
    }

    /* Item activo: bold + opacidad 1 + flecha sutil al final */
    #sidebar nav a.bg-white\/20 span,
    #sidebar nav button.bg-white\/20 span,
    #sidebar nav a[class*="bg-white/20"] span {
        font-weight: 700 !important;
        opacity: 1 !important;
    }
    #sidebar nav a.bg-white\/20::after,
    #sidebar nav button.bg-white\/20::after {
        content: "›";
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #fff;
        font-weight: 700;
        font-size: 18px;
        opacity: .85;
    }

    /* Iconos: tamaño y opacidad uniformes */
    #sidebar nav a svg,
    #sidebar nav button svg,
    #sidebar nav a i,
    #sidebar nav button i {
        opacity: .85;
    }
    #sidebar nav a.bg-white\/20 svg,
    #sidebar nav a.bg-white\/20 i,
    #sidebar nav button.bg-white\/20 svg,
    #sidebar nav button.bg-white\/20 i {
        opacity: 1 !important;
    }
</style>
