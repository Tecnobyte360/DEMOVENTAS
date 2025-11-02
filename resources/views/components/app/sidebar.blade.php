<div class="min-w-fit">
    <!-- Sidebar backdrop (mobile only) -->
    <div
        class="fixed inset-0 bg-gray-900/30 z-40 lg:hidden lg:z-auto transition-opacity duration-200"
        :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        aria-hidden="true"
        x-cloak
    ></div>

    <!-- Sidebar -->
    <div
      id="sidebar"
      x-data="{
        fg:'#FFFFFF',
        fgMuted:'rgba(255,255,255,.75)',
        isLight:false,
        hexToRgb(hex){
          const h = (hex||'').trim();
          if(!h) return {r:255,g:255,b:255};
          const s = h.replace('#','');
          const n = s.length===3 ? s.split('').map(c=>c+c).join('') : s;
          const r = parseInt(n.substring(0,2),16)||255, g = parseInt(n.substring(2,4),16)||255, b = parseInt(n.substring(4,6),16)||255;
          return {r,g,b};
        },
        luminance({r,g,b}){
          const srgb = [r,g,b].map(v=>v/255).map(v=>v<=0.03928 ? v/12.92 : Math.pow((v+0.055)/1.055,2.4));
          return 0.2126*srgb[0]+0.7152*srgb[1]+0.0722*srgb[2];
        },
        setup(bg){
          const rgb = this.hexToRgb(bg);
          const L = this.luminance(rgb);
          this.isLight = L > 0.65; // umbral para fondos muy claros (blanco)
          if(this.isLight){
            this.fg = '#111827';           // slate-900
            this.fgMuted = 'rgba(17,24,39,.72)';
          }else{
            this.fg = '#FFFFFF';
            this.fgMuted = 'rgba(255,255,255,.78)';
          }
        }
      }"
      x-init="setup('{{ $empresaActual?->color_primario }}')"
      :style="`
        background-color: {{ $empresaActual?->color_primario }};
        --sidebar-fg: ${fg};
        --sidebar-fg-muted: ${fgMuted};
      `"
      class="flex lg:flex! flex-col absolute z-40 left-0 top-0 lg:static lg:left-auto lg:top-auto lg:translate-x-0 h-[100dvh] overflow-y-scroll lg:overflow-y-auto no-scrollbar w-64 lg:w-20 lg:sidebar-expanded:!w-64 2xl:w-64! shrink-0 transition-all duration-200 ease-in-out {{ $variant === 'v2' ? 'border-r border-gray-200 dark:border-gray-700/60' : 'rounded-r-2xl shadow-xs' }}"
      :class="sidebarOpen ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-64'"
      @click.outside="sidebarOpen = false"
      @keydown.escape.window="sidebarOpen = false"
    >

        <!-- Sidebar header -->
        <div class="flex justify-between mb-10 pr-3 sm:px-2">
            <!-- Close button -->
            <button class="lg:hidden text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)]" @click.stop="sidebarOpen = !sidebarOpen" aria-controls="sidebar" :aria-expanded="sidebarOpen">
                <span class="sr-only">Close sidebar</span>
                <svg class="w-6 h-6" :class="isLight ? 'fill-slate-700' : 'fill-white'" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.7 18.7l1.4-1.4L7.8 13H20v-2H7.8l4.3-4.3-1.4-1.4L4 12z" />
                </svg>
            </button>
            <!-- Logo -->
            <div class="flex justify-center w-full py-6">
              <a href="{{ route('dashboard') }}">
                  <div class="p-3 rounded-2xl bg-white shadow-lg" :class="isLight ? 'ring-1 ring-gray-200 shadow-gray-300/60' : 'ring-1 ring-white/20 shadow-black/20'">
                      <img 
                          src="{{ $empresaActual?->logo_url }}" 
                          alt="{{ $empresaActual?->nombre }}"
                          class="h-20 w-auto object-contain"
                      />
                  </div>
              </a>
            </div>
        </div>

        <!-- Links -->
        <div class="space-y-8">
            <!-- Pages group -->
            <div>
                <h3 class="text-xs uppercase font-semibold pl-3 text-[color:var(--sidebar-fg-muted)]">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Pages</span>
                </h3>
                <ul class="mt-3">
                    <!-- Dashboard -->
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-linear-to-r @if(in_array(Request::segment(1), ['dashboard'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['dashboard']) ? 1 : 0 }} }">
                        <a class="block truncate transition hover:text-[color:var(--sidebar-fg)] text-[color:var(--sidebar-fg)]" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0" :class="@json(in_array(Request::segment(1), ['dashboard'])) ? 'fill-violet-400' : ''" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="!(@json(in_array(Request::segment(1), ['dashboard']))) ? 'fill: var(--sidebar-fg-muted)' : ''">
                                        <path d="M5.936.278A7.983 7.983 0 0 1 8 0a8 8 0 1 1-8 8c0-.722.104-1.413.278-2.064a1 1 0 1 1 1.932.516A5.99 5.99 0 0 0 2 8a6 6 0 1 0 6-6c-.53 0-1.045.076-1.548.21A1 1 0 1 1 5.936.278Z" />
                                        <path d="M6.068 7.482A2.003 2.003 0 0 0 8 10a2 2 0 1 0-.518-3.932L3.707 2.293a1 1 0 0 0-1.414 1.414l3.775 3.775Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 text-[color:var(--sidebar-fg)]">Dashboard</span>
                                </div>
                            </div>
                        </a>
                    </li>

                    {{-- Ingresos --}}

                    <!-- E-Commerce (Inventario) -->
                    @if(auth()->user()->hasRole('administrador'))
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-linear-to-r @if(in_array(Request::segment(1), ['community'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['community']) ? 1 : 0 }} }">
                        <a class="block truncate transition hover:text-[color:var(--sidebar-fg)] text-[color:var(--sidebar-fg)]" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0" :class="@json(in_array(Request::segment(1), ['community'])) ? 'fill-violet-400' : ''" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="!(@json(in_array(Request::segment(1), ['community']))) ? 'fill: var(--sidebar-fg-muted)' : ''">
                                        <path d="M12 1a1 1 0 1 0-2 0v2a3 3 0 0 0 3 3h2a1 1 0 1 0 0-2h-2a1 1 0 0 1-1-1V1ZM1 10a1 1 0 1 0 0 2h2a1 1 0 0 1 1 1v2a1 1 0 1 0 2 0v-2a3 3 0 0 0-3-3H1ZM5 0a1 1 0 0 1 1 1v2a3 3 0 0 1-3 3H1a1 1 0 0 1 0-2h2a1 1 0 0 0 1-1V1a1 1 0 0 1 1-1ZM12 13a1 1 0 0 1 1-1h2a1 1 0 1 0 0-2h-2a3 3 0 0 0-3 3v2a1 1 0 1 0 2 0v-2Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 text-[color:var(--sidebar-fg)]">Ventas</span>
                                </div>
                                <div class="flex shrink-0 ml-2">
                                    <svg class="w-3 h-3 shrink-0 ml-1" :style="`fill: ${open ? 'var(--sidebar-fg)' : 'var(--sidebar-fg-muted)'}`" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['community'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('SociosNegocio')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('SociosNegocio') }}">
                                        <span class="text-sm font-medium ml-4">Cotizaciones</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['community'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('SociosNegocio')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('SociosNegocio') }}">
                                        <span class="text-sm font-medium ml-4">Ordenes de venta</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['community'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Facturacion')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('Facturacion') }}">
                                        <span class="text-sm font-medium ml-4">Factura de venta</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                         <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['community'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('notascreditoclientes')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('notascreditoclientes') }}">
                                        <span class="text-sm font-medium ml-4">Notas credito de ventas</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                          <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['community'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('notascreditoclientes')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('notascreditoclientes') }}">
                                        <span class="text-sm font-medium ml-4">Listas de precios</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        
                      
                    </li>
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-linear-to-r @if(in_array(Request::segment(1), ['community'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['community']) ? 1 : 0 }} }">
                        <a class="block truncate transition hover:text-[color:var(--sidebar-fg)] text-[color:var(--sidebar-fg)]" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0" :class="@json(in_array(Request::segment(1), ['community'])) ? 'fill-violet-400' : ''" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="!(@json(in_array(Request::segment(1), ['community']))) ? 'fill: var(--sidebar-fg-muted)' : ''">
                                        <path d="M12 1a1 1 0 1 0-2 0v2a3 3 0 0 0 3 3h2a1 1 0 1 0 0-2h-2a1 1 0 0 1-1-1V1ZM1 10a1 1 0 1 0 0 2h2a1 1 0 0 1 1 1v2a1 1 0 1 0 2 0v-2a3 3 0 0 0-3-3H1ZM5 0a1 1 0 0 1 1 1v2a3 3 0 0 1-3 3H1a1 1 0 0 1 0-2h2a1 1 0 0 0 1-1V1a1 1 0 0 1 1-1ZM12 13a1 1 0 0 1 1-1h2a1 1 0 1 0 0-2h-2a3 3 0 0 0-3 3v2a1 1 0 1 0 2 0v-2Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 text-[color:var(--sidebar-fg)]">Compras</span>
                                </div>
                                <div class="flex shrink-0 ml-2">
                                    <svg class="w-3 h-3 shrink-0 ml-1" :style="`fill: ${open ? 'var(--sidebar-fg)' : 'var(--sidebar-fg-muted)'}`" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['community'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Factura-compras')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('Factura-compras') }}">
                                        <span class="text-sm font-medium ml-4">Factura de compra</span>
                                    </a>
                                </li>
                                  <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Notascreditocompra')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('Notascreditocompra') }}">
                                        <span class="text-sm font-medium ml-4">Nota credito de compras</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                       
                    </li>
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-linear-to-r @if(in_array(Request::segment(1), ['ecommerce'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['ecommerce']) ? 1 : 0 }} }">
                        <a class="block truncate transition hover:text-[color:var(--sidebar-fg)] text-[color:var(--sidebar-fg)]" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0" :class="@json(in_array(Request::segment(1), ['ecommerce'])) ? 'fill-violet-400' : ''" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="!(@json(in_array(Request::segment(1), ['ecommerce']))) ? 'fill: var(--sidebar-fg-muted)' : ''">
                                        <path d="M9 6.855A3.502 3.502 0 0 0 8 0a3.5 3.5 0 0 0-1 6.855v1.656L5.534 9.65a3.5 3.5 0 1 0 1.229 1.578L8 10.267l1.238.962a3.5 3.5 0 1 0 1.229-1.578L9 8.511V6.855ZM6.5 3.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm4.803 8.095c.005-.005.01-.01.013-.016l.012-.016a1.5 1.5 0 1 1-.025.032ZM3.5 11c.474 0 .897.22 1.171.563l.013.016.013.017A1.5 1.5 0 1 1 3.5 11Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 text-[color:var(--sidebar-fg)]">Inventario</span>
                                </div>
                                <!-- Icon -->
                                <div class="flex shrink-0 ml-2">
                                    <svg class="w-3 h-3 shrink-0 ml-1" :style="`fill: ${open ? 'var(--sidebar-fg)' : 'var(--sidebar-fg-muted)'}`" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>

                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['ecommerce'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                              <li class="mb-1 last:mb-0" x-data="{ open: false }">
                                <a @click="open = !open" class="flex items-center justify-between transition truncate cursor-pointer text-[color:var(--sidebar-fg)] hover:opacity-90">
                                  <span class="text-sm font-medium ml-4">Operaciones de stock</span>
                                  <svg class="w-4 h-4 transition-transform" :style="`stroke: ${fg}`" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                  </svg>
                                </a>
                                <ul x-show="open" x-collapse class="mt-2 space-y-1 pl-4 border-l" :style="`border-color: ${isLight ? '#CBD5E1' : 'rgba(255,255,255,.2)'}`">
                                  <li class="space-y-1">
                                    <a href="{{ route('Entradas') }}" class="block text-sm font-medium ml-4 text-[color:var(--sidebar-fg)] hover:opacity-90">Entradas de stock</a>
                                    <a href="{{ route('SalidaMercancia') }}" class="block text-sm font-medium ml-4 text-[color:var(--sidebar-fg)] hover:opacity-90">Salida mercancia</a>
                                    <a href="{{ route('DevolucionMercancia') }}" class="block text-sm font-medium ml-4 text-[color:var(--sidebar-fg)] hover:opacity-90">Devolucion de mercancia</a>
                                  </li>
                                </ul>
                              </li>

                              <li class="mb-1 last:mb-0">
                                <a class="block transition truncate @if(Route::is('Bodegas')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('Bodegas') }}">
                                  <span class="text-sm font-medium ml-4">Gestión Bodegas</span>
                                </a>
                              </li>
                              <li class="mb-1 last:mb-0">
                                <a class="block transition truncate @if(Route::is('indexcategorias')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('indexcategorias') }}">
                                  <span class="text-sm font-medium ml-4">Categorías Artículos</span>
                                </a>
                              </li>
                               <li class="mb-1 last:mb-0">
                                <a class="block transition truncate @if(Route::is('subcategorias.index')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('subcategorias.index') }}">
                                  <span class="text-sm font-medium ml-4">SubCategorias de articulos</span>
                                </a>
                              </li>
                              <li class="mb-1 last:mb-0">
                                <a class="block transition truncate @if(Route::is('productos.index')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('productos.index') }}">
                                  <span class="text-sm font-medium ml-4">productos inventariables</span>
                                </a>
                              </li>
                                <li class="mb-1 last:mb-0">
                                <a class="block transition truncate @if(Route::is('cardexinventario')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('cardexinventario') }}">
                                  <span class="text-sm font-medium ml-4">Kardex</span>
                                </a>
                              </li>

                              
                            </ul>
                        </div>
                    </li>
                    @endif

                    
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-linear-to-r @if(in_array(Request::segment(1), ['community'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['community']) ? 1 : 0 }} }">
                        <a class="block truncate transition hover:text-[color:var(--sidebar-fg)] text-[color:var(--sidebar-fg)]" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0" :class="@json(in_array(Request::segment(1), ['community'])) ? 'fill-violet-400' : ''" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="!(@json(in_array(Request::segment(1), ['community']))) ? 'fill: var(--sidebar-fg-muted)' : ''">
                                        <path d="M12 1a1 1 0 1 0-2 0v2a3 3 0 0 0 3 3h2a1 1 0 1 0 0-2h-2a1 1 0 0 1-1-1V1ZM1 10a1 1 0 1 0 0 2h2a1 1 0 0 1 1 1v2a1 1 0 1 0 2 0v-2a3 3 0 0 0-3-3H1ZM5 0a1 1 0 0 1 1 1v2a3 3 0 0 1-3 3H1a1 1 0 0 1 0-2h2a1 1 0 0 0 1-1V1a1 1 0 0 1 1-1ZM12 13a1 1 0 0 1 1-1h2a1 1 0 1 0 0-2h-2a3 3 0 0 0-3 3v2a1 1 0 1 0 2 0v-2Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 text-[color:var(--sidebar-fg)]">Terceros</span>
                                </div>
                                <div class="flex shrink-0 ml-2">
                                    <svg class="w-3 h-3 shrink-0 ml-1" :style="`fill: ${open ? 'var(--sidebar-fg)' : 'var(--sidebar-fg-muted)'}`" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['community'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('SociosNegocio')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('SociosNegocio') }}">
                                        <span class="text-sm font-medium ml-4">Maestro de terceros</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                  
                    @if(auth()->user()->hasRole('administrador')  || auth()->user()->hasRole('rutas') )
                    <!-- Rutas Disponibles -->
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-linear-to-r @if(in_array(Request::segment(1), ['finance'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['finance']) ? 1 : 0 }} }">
                        <a class="block truncate transition hover:text-[color:var(--sidebar-fg)] text-[color:var(--sidebar-fg)]" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0" :class="@json(in_array(Request::segment(1), ['finance'])) ? 'fill-violet-400' : ''" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="!(@json(in_array(Request::segment(1), ['finance']))) ? 'fill: var(--sidebar-fg-muted)' : ''">
                                        <path d="M6 0a6 6 0 0 0-6 6c0 1.077.304 2.062.78 2.912a1 1 0 1 0 1.745-.976A3.945 3.945 0 0 1 2 6a4 4 0 0 1 4-4c.693 0 1.344.194 1.936.525A1 1 0 1 0 8.912.779 5.944 5.944 0 0 0 6 0Z" />
                                        <path d="M10 4a6 6 0 1 0 0 12 6 6 0 0 0 0-12Zm-4 6a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 text-[color:var(--sidebar-fg)]">Logistica</span>
                                </div>
                                <div class="flex shrink-0 ml-2">
                                    <svg class="w-3 h-3 shrink-0 ml-1" :style="`fill: ${open ? 'var(--sidebar-fg)' : 'var(--sidebar-fg-muted)'}`" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['finance'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('RutasDisponibles')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('RutasDisponibles') }}">
                                        <span class="text-sm font-medium ml-4">Ruta del dia</span>
                                    </a>
                                </li>
                                 <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Maestro-Rutas')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('Maestro-Rutas') }}">
                                        <span class="text-sm font-medium ml-4">Control de ruta</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Vehiculos')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('Vehiculos') }}">
                                        <span class="text-sm font-medium ml-4">Vehiculos</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    @endif

                    @if(auth()->user()->hasRole('administrador') )
                    <!-- Finanzas -->
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-linear-to-r @if(in_array(Request::segment(1), ['finance'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['finance']) ? 1 : 0 }} }">
                        <a class="block truncate transition hover:text-[color:var(--sidebar-fg)] text-[color:var(--sidebar-fg)]" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0" :class="@json(in_array(Request::segment(1), ['finance'])) ? 'fill-violet-400' : ''" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="!(@json(in_array(Request::segment(1), ['finance']))) ? 'fill: var(--sidebar-fg-muted)' : ''">
                                        <path d="M10.5 1a3.502 3.502 0 0 1 3.355 2.5H15a1 1 0 1 1 0 2h-1.145a3.502 3.502 0 0 1-6.71 0H1a1 1 0 0 1 0-2h6.145A3.502 3.502 0 0 1 10.5 1ZM9 4.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM5.5 9a3.502 3.502 0 0 1 3.355 2.5H15a1 1 0 1 1 0 2H8.855a3.502 3.502 0 0 1-6.71 0H1a1 1 0 1 1 0-2h1.145A3.502 3.502 0 0 1 5.5 9ZM4 12.5a1.5 1.5 0 1 0 3 0 1.5 1.5 0 0 0-3 0Z" fill-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 text-[color:var(--sidebar-fg)]">Finanzas</span>
                                </div>
                                <div class="flex shrink-0 ml-2">
                                    <svg class="w-3 h-3 shrink-0 ml-1" :style="`fill: ${open ? 'var(--sidebar-fg)' : 'var(--sidebar-fg-muted)'}`" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['finance'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Finanzas')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('Finanzas') }}">
                                        <span class="text-sm font-medium ml-4">Finanzas</span>
                                    </a>
                                </li>
                            </ul>
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['finance'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                               
                                 <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('PagosRecibidos')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('PagosRecibidos') }}">
                                        <span class="text-sm font-medium ml-4">Recibos de caja</span>
                                    </a>
                                </li>
                               
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Gastos')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('Gastos') }}">
                                        <span class="text-sm font-medium ml-4">Gastos</span>
                                    </a>
                                </li>
                              
                             
                               
                            </ul>
                        </div>
                    </li>
                     <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-linear-to-r @if(in_array(Request::segment(1), ['finance'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['finance']) ? 1 : 0 }} }">
                        <a class="block truncate transition hover:text-[color:var(--sidebar-fg)] text-[color:var(--sidebar-fg)]" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0" :class="@json(in_array(Request::segment(1), ['finance'])) ? 'fill-violet-400' : ''" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="!(@json(in_array(Request::segment(1), ['finance']))) ? 'fill: var(--sidebar-fg-muted)' : ''">
                                        <path d="M6 0a6 6 0 0 0-6 6c0 1.077.304 2.062.78 2.912a1 1 0 1 0 1.745-.976A3.945 3.945 0 0 1 2 6a4 4 0 0 1 4-4c.693 0 1.344.194 1.936.525A1 1 0 1 0 8.912.779 5.944 5.944 0 0 0 6 0Z" />
                                        <path d="M10 4a6 6 0 1 0 0 12 6 6 0 0 0 0-12Zm-4 6a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 text-[color:var(--sidebar-fg)]">Informes</span>
                                </div>
                                <div class="flex shrink-0 ml-2">
                                    <svg class="w-3 h-3 shrink-0 ml-1" :style="`fill: ${open ? 'var(--sidebar-fg)' : 'var(--sidebar-fg-muted)'}`" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['finance'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('asientos.index')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('asientos.index') }}">
                                        <span class="text-sm font-medium ml-4">Informe diario contable</span>
                                    </a>
                                </li>
                                  <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('asientos.index')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('asientos.index') }}">
                                        <span class="text-sm font-medium ml-4">Informe de venta</span>
                                    </a>
                                </li>
                               
                            </ul>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>

            <!-- More group -->
            <div>
                <h3 class="text-xs uppercase font-semibold pl-3 text-[color:var(--sidebar-fg-muted)]">
                    <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6" aria-hidden="true">•••</span>
                    <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">More</span>
                </h3>
                <ul class="mt-3">
                    <!-- Authentication / Configuración -->
                    @if(auth()->user()->hasRole('administrador') )
                    <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0 bg-linear-to-r @if(in_array(Request::segment(1), ['settings'])){{ 'from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04]' }}@endif" x-data="{ open: {{ in_array(Request::segment(1), ['settings']) ? 1 : 0 }} }">
                        <a class="block truncate transition hover:text-[color:var(--sidebar-fg)] text-[color:var(--sidebar-fg)]" href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="shrink-0" :class="@json(in_array(Request::segment(1), ['settings'])) ? 'fill-violet-400' : ''" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="!(@json(in_array(Request::segment(1), ['settings']))) ? 'fill: var(--sidebar-fg-muted)' : ''">
                                        <path d="M10.5 1a3.502 3.502 0 0 1 3.355 2.5H15a1 1 0 1 1 0 2h-1.145a3.502 3.502 0 0 1-6.71 0H1a1 1 0 0 1 0-2h6.145A3.502 3.502 0 0 1 10.5 1ZM9 4.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM5.5 9a3.502 3.502 0 0 1 3.355 2.5H15a1 1 0 1 1 0 2H8.855a3.502 3.502 0 0 1-6.71 0H1a1 1 0 1 1 0-2h1.145A3.502 3.502 0 0 1 5.5 9ZM4 12.5a1.5 1.5 0 1 0 3 0 1.5 1.5 0 0 0-3 0Z" fill-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm font-medium ml-4 text-[color:var(--sidebar-fg)]">Configuración</span>
                                </div>
                                <div class="flex shrink-0 ml-2">
                                    <svg class="w-3 h-3 shrink-0 ml-1" :style="`fill: ${open ? 'var(--sidebar-fg)' : 'var(--sidebar-fg-muted)'}`" :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                                        <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                        <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                            <ul class="pl-8 mt-1 @if(!in_array(Request::segment(1), ['settings'])){{ 'hidden' }}@endif" :class="open ? 'block!' : 'hidden'">
                                <li class="mb-1 last:mb-0"></li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Usuarios')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('Usuarios') }}">
                                        <span class="text-sm font-medium ml-4">Usuarios</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('roles.index')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('roles.index') }}">
                                        <span class="text-sm font-medium ml-4">Roles</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('roles.index2')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('roles.index2') }}">
                                        <span class="text-sm font-medium ml-4">Permisos</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Empresas')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('Empresas') }}">
                                        <span class="text-sm font-medium ml-4">Empresas</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('SeriesDocumentos')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('SeriesDocumentos') }}">
                                        <span class="text-sm font-medium ml-4">Series Documentos</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('normas-reparto.index')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('normas-reparto.index') }}">
                                        <span class="text-sm font-medium ml-4">Normas de Reparto</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Cuentas-contables')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('Cuentas-contables') }}">
                                        <span class="text-sm font-medium ml-4">Plan de cuentas</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Impuestos')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('Impuestos') }}">
                                        <span class="text-sm font-medium ml-4">Tablas de impuestos</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('condicionespago')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('condicionespago') }}">
                                        <span class="text-sm font-medium ml-4">Condiciones Pago</span>
                                    </a>
                                </li>
                                <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('Mediospagos')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('Mediospagos') }}">
                                        <span class="text-sm font-medium ml-4">Medios de pago</span>
                                    </a>
                                </li>
                                  <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('TipoDocumentos')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" 
                                    href="{{ route('TipoDocumentos') }}">
                                        <span class="text-sm font-medium ml-4">Tipo Documentos</span>
                                    </a>
                                </li>
                                  <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('tiposGastos')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('tiposGastos') }}">
                                        <span class="text-sm font-medium ml-4">Tipos Gastos</span>
                                    </a>
                                </li>
 <li class="mb-1 last:mb-0">
                                    <a class="block transition truncate @if(Route::is('ConceptoDocumentos')) text-violet-400 @else text-[color:var(--sidebar-fg-muted)] hover:text-[color:var(--sidebar-fg)] @endif" href="{{ route('ConceptoDocumentos') }}">
                                        <span class="text-sm font-medium ml-4">Conceptos Documentos</span>
                                    </a>
                                </li>



                                
                            </ul>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
            
        </div>

        <!-- Expand / collapse button -->
        <div class="pt-3 hidden lg:inline-flex 2xl:hidden justify-end mt-auto">
            <div class="w-12 pl-4 pr-3 py-2">
                <button class="transition-colors" :style="`color:${fgMuted}`" @click="sidebarExpanded = !sidebarExpanded">
                    <span class="sr-only">Expand / collapse sidebar</span>
                    <svg class="shrink-0 sidebar-expanded:rotate-180" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" :style="`fill:${fgMuted}`">
                        <path d="M15 16a1 1 0 0 1-1-1V1a1 1 0 1 1 2 0v14a1 1 0 0 1-1 1ZM8.586 7H1a1 1 0 1 0 0 2h7.586l-2.793 2.793a1 1 0 1 0 1.414 1.414l4.5-4.5A.997.997 0 0 0 12 8.01M11.924 7.617a.997.997 0 0 0-.217-.324l-4.5-4.5a1 1 0 0 0-1.414 1.414L8.586 7M12 7.99a.996.996 0 0 0-.076-.373Z" />
                    </svg>
                </button>
            </div>
        </div>

    </div>
</div>
