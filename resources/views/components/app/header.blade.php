@php
    // Header siempre blanco (independiente del color secundario de la empresa)
    $headerHex = 'FFFFFF';
    $headerBg  = '#FFFFFF';
    $hr = 255; $hg = 255; $hb = 255;
@endphp

<header
    x-data="{
        bgColor: '{{ $headerBg }}',
        textColor: '#111827',
        isLight: true,

        hexToRgb(hex) {
            const h = (hex || '').trim().replace('#', '');
            const s = h.length === 3 ? h.split('').map(c => c + c).join('') : h;
            return {
                r: parseInt(s.substring(0, 2), 16) || 255,
                g: parseInt(s.substring(2, 4), 16) || 255,
                b: parseInt(s.substring(4, 6), 16) || 255
            };
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
    class="sticky top-0 z-30 backdrop-blur-lg transition-all duration-300
           bg-white dark:bg-gray-900
           border-b border-gray-200 dark:border-gray-800
           text-gray-900 dark:text-gray-100 shadow-sm"
>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">

            <!-- Left -->
            <div class="flex items-center gap-3 min-w-[44px]">
                <button
                    type="button"
                    class="inline-flex lg:hidden items-center justify-center w-10 h-10 rounded-xl border transition-all duration-200 shadow-sm"
                    :style="`
                        color: ${textColor};
                        border-color: ${isLight ? 'rgba(0,0,0,0.08)' : 'rgba(255,255,255,0.12)'};
                        background: ${isLight ? 'rgba(255,255,255,0.70)' : 'rgba(255,255,255,0.08)'};
                    `"
                    @click.stop="sidebarOpen = !sidebarOpen"
                    aria-label="Abrir menú"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none">
                        <path d="M4 6H20" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                        <path d="M4 12H20" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                        <path d="M4 18H20" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <!-- Right -->
            <div class="flex items-center gap-1">
                <x-modal-search />
                <x-dropdown-notifications align="right" />
                <x-dropdown-help align="right" />
                <x-theme-toggle />
                <div class="w-px h-6 mx-2" :style="`background:${isLight ? 'rgba(0,0,0,0.10)' : 'rgba(255,255,255,0.20)'}`"></div>
                <x-dropdown-profile align="right" />
            </div>
        </div>
    </div>
</header>