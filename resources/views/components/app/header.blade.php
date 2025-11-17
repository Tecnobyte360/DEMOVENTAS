<header 
    x-data="{
        bgColor: '#{{ $empresaActual?->color_secundario ?? 'FFFFFF' }}',
        textColor: '#000000',
        isLight: false,
        
        hexToRgb(hex) {
            const h = (hex || '').trim().replace('#', '');
            const s = h.length === 3 ? h.split('').map(c => c + c).join('') : h;
            const r = parseInt(s.substring(0, 2), 16) || 255;
            const g = parseInt(s.substring(2, 4), 16) || 255;
            const b = parseInt(s.substring(4, 6), 16) || 255;
            return { r, g, b };
        },
        
        luminance({ r, g, b }) {
            const srgb = [r, g, b].map(v => v / 255).map(v => 
                v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4)
            );
            return 0.2126 * srgb[0] + 0.7152 * srgb[1] + 0.0722 * srgb[2];
        },
        
        init() {
            const rgb = this.hexToRgb(this.bgColor);
            const L = this.luminance(rgb);
            this.isLight = L > 0.5;
            this.textColor = this.isLight ? '#111827' : '#FFFFFF';
        }
    }"
    x-init="init()"
    class="sticky top-0 z-30 backdrop-blur-lg transition-all duration-300"
    :style="`
        background: ${bgColor}f8;
        color: ${textColor};
        border-bottom: 1px solid ${isLight ? 'rgba(0,0,0,0.06)' : 'rgba(255,255,255,0.08)'};
    `"
>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            
            <!-- Left -->
            <div class="flex items-center gap-3">
                <button
                    class="p-2 rounded-lg lg:hidden hover:bg-white/10 transition-all"
                    :style="`color: ${textColor};`"
                    @click.stop="sidebarOpen = !sidebarOpen"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <!-- Right -->
            <div class="flex items-center gap-1">
                <x-modal-search />
                <x-dropdown-notifications align="right" />
                <x-dropdown-help align="right" />
                <x-theme-toggle />
                <div class="w-px h-6 mx-2 bg-white/20"></div>
                <x-dropdown-profile align="right" />
            </div>
        </div>
    </div>
</header>