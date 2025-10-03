@props(['align' => 'right'])

<div class="relative inline-flex" x-data="{ open:false }">
  <button
    type="button"
    @click.prevent="open=!open"
    :aria-expanded="open"
    aria-haspopup="true"
    class="inline-flex items-center gap-2 pl-1 pr-2 py-1 rounded-full
           bg-white/90 dark:bg-gray-900/80 backdrop-blur-md
           shadow-md ring-1 ring-black/10 dark:ring-white/10
           hover:bg-white hover:shadow-lg
           focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-400/70"
  >
    <img
      class="w-8 h-8 rounded-full ring-2 ring-white/90 dark:ring-gray-800/90"
      src="{{ Auth::user()->profile_photo_url }}"
      alt="{{ Auth::user()->name }}"
      width="32" height="32"
    />
    <span class="hidden sm:block truncate max-w-[10rem]
                 text-sm font-medium text-gray-900 dark:text-gray-100">
      {{ Auth::user()->name }}
    </span>
    <svg class="w-3 h-3 shrink-0 text-gray-600 dark:text-gray-300" viewBox="0 0 12 12" aria-hidden="true">
      <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
    </svg>
  </button>

  <div
    x-show="open" x-cloak
    @click.outside="open=false"
    @keydown.escape.window="open=false"
    x-transition:enter="transition ease-out duration-200 transform"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-out duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="origin-top-right z-50 absolute top-full mt-2 min-w-48
           {{ $align === 'right' ? 'right-0' : 'left-0' }}
           bg-white/95 dark:bg-gray-900/95 backdrop-blur-md
           border border-gray-200/70 dark:border-white/10
           rounded-xl shadow-lg overflow-hidden"
    role="menu"
  >
    <div class="pt-2 pb-3 px-3 border-b border-gray-200/70 dark:border-white/10">
      <div class="font-semibold text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</div>
      <div class="text-xs text-gray-500 dark:text-gray-400 italic">Administrador</div>
    </div>

    <ul class="py-1">
      <li>
        <a href="{{ route('profile.show') }}"
           @click="open=false"
           class="flex items-center justify-between px-3 py-2 text-sm
                  text-violet-700 dark:text-violet-400
                  hover:bg-violet-50 dark:hover:bg-white/5">
          Autogestión
        </a>
      </li>
      <li>
        <form method="POST" action="{{ route('logout') }}" x-data>
          @csrf
          <a href="{{ route('logout') }}"
             @click.prevent="$root.submit();"
             class="flex items-center justify-between px-3 py-2 text-sm
                    text-violet-700 dark:text-violet-400
                    hover:bg-violet-50 dark:hover:bg-white/5">
            {{ __('Cerrar Sesión') }}
          </a>
        </form>
      </li>
    </ul>
  </div>
</div>
