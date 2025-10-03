<header 
   class="sticky top-0 before:absolute before:inset-0 before:backdrop-blur-md before:bg-transparent
         before:-z-10 z-30
         {{ $variant === 'v2' || $variant === 'v3'
              ? 'after:absolute after:h-px after:inset-x-0 after:top-full after:bg-gray-200 dark:after:bg-gray-700/60 after:-z-10'
              : 'max-lg:shadow-xs' }}"
 class="sticky top-0 z-30 shadow-md rounded-b-3xl"
    style="background-color: {{ $empresaActual?->color_secundario }};"
>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 
                    {{ $variant === 'v2' || $variant === 'v3' 
                        ? '' 
                        : 'lg:border-b border-gray-200 dark:border-gray-700/60' }}">
            
            <!-- Header: Left side -->
            <div class="flex">
                <button
                    class="text-white hover:text-gray-200 lg:hidden"
                    @click.stop="sidebarOpen = !sidebarOpen"
                    aria-controls="sidebar"
                    :aria-expanded="sidebarOpen"
                >
                    <span class="sr-only">Open sidebar</span>
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                        <rect x="4" y="5" width="16" height="2" />
                        <rect x="4" y="11" width="16" height="2" />
                        <rect x="4" y="17" width="16" height="2" />
                    </svg>
                </button>
            </div>

            <!-- Header: Right side -->
            <div class="flex items-center space-x-3">
                <x-modal-search />
                <x-dropdown-notifications align="right" />
                <x-dropdown-help align="right" />
                <x-theme-toggle />
                <hr class="w-px h-6 bg-white/40 border-none" />
                <x-dropdown-profile align="right" />
            </div>
        </div>
    </div>
</header>
