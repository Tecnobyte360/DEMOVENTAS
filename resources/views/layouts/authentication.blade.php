<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />
        <link rel="icon" type="image/png" href="{{ $empresaActual?->logo_url ?? asset('favicon.png') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles        

        <script>
            if (localStorage.getItem('dark-mode') === 'false' || !('dark-mode' in localStorage)) {
                document.querySelector('html').classList.remove('dark');
                document.querySelector('html').style.colorScheme = 'light';
            } else {
                document.querySelector('html').classList.add('dark');
                document.querySelector('html').style.colorScheme = 'dark';
            }
        </script>
    </head>
    <body class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400">

        <main class="bg-white dark:bg-gray-900">

            <div class="relative flex">

                <!-- Content -->
               <div class="w-full md:w-1/2">
    <div class="min-h-[100dvh] h-full flex flex-col after:flex-1">

        <!-- Header -->
       <div class="flex-1">
  <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">

    @php
        // Valores seguros (sin reventar si $empresaActual es null)
        $nombreEmpresa = $empresaActual->nombre ?? config('app.name', 'Mi Empresa');
        $logoUrl       = $empresaActual->logo_url ?? null;

        // Iniciales seguras (toma hasta 2 palabras)
        $iniciales = collect(preg_split('/\s+/', trim((string) $nombreEmpresa)))
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_substr($p, 0, 1))
            ->join('') ?: ''; // fallback
    @endphp

    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
      <!-- Wrapper con efecto glass y sombra -->
      <div class="relative rounded-2xl p-2 bg-white/60 dark:bg-white/10 backdrop-blur-md shadow-lg
                  ring-1 ring-black/5 dark:ring-white/5 transition-transform duration-300
                  group-hover:scale-105">
        <div class="absolute -inset-1 rounded-3xl bg-gradient-to-br from-indigo-500/20 via-purple-500/20 to-pink-500/20 -z-10"></div>

        @if ($logoUrl)
          <!-- Logo -->
          <img
            src="{{ $logoUrl }}"
            alt="{{ $nombreEmpresa }}"
            class="block h-12 sm:h-14 md:h-16 w-auto object-contain"
            loading="eager"
          />
        @else
          <!-- Fallback iniciales -->
          <div class="grid place-items-center h-12 sm:h-14 md:h-16 w-12 sm:w-14 md:w-16 rounded-xl 
                      bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 text-white font-bold text-lg">
            {{ $iniciales }}
          </div>
        @endif
      </div>
    </a>

  </div>
</div>


        <!-- Contenido dinámico -->
        <div class="max-w-sm mx-auto w-full px-4 py-8">
            {{ $slot }}
        </div>
    </div>
</div>


              
      <div class="hidden md:flex w-1/2 h-screen items-center justify-center bg-gradient-to-br from-purple-100 via-white to-violet-200 dark:from-gray-900 dark:via-gray-800 dark:to-black">
       @php
    
    $nombreEmpresa = $empresaActual->nombre ?? '';

  
    $iniciales = collect(explode(' ', trim($nombreEmpresa)))
        ->filter()
        ->take(2)
        ->map(fn($p) => mb_substr($p, 0, 1))
        ->join('') ?: 'ME';
@endphp

<div class="p-10 bg-white/70 dark:bg-gray-800/50 rounded-3xl shadow-2xl text-center 
            space-y-6 transform transition-all duration-500 hover:scale-[1.03] 
            relative overflow-hidden">

    <!-- Halo degradado animado detrás -->
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 via-purple-500/20 to-pink-500/20 
                blur-3xl opacity-60 animate-pulse"></div>

    <!-- Logo dinámico con fallback a iniciales (misma lógica que tu header) -->
    <div class="relative z-10 inline-block p-3 rounded-2xl bg-white/60 dark:bg-gray-900/50 
                backdrop-blur-lg shadow-lg ring-1 ring-black/5 dark:ring-white/10">
        @if ($empresaActual?->logo_url)
            <img
                src="{{ $empresaActual->logo_url }}"
                alt="{{ $nombreEmpresa }}"
                class="w-64 max-w-full h-auto object-contain mx-auto rounded-xl shadow-md"
                loading="eager"
            />
        @else
            <div class="grid place-items-center w-64 h-40 mx-auto rounded-xl 
                        bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500
                        text-white font-extrabold text-5xl tracking-wider">
                {{ $iniciales }}
            </div>
        @endif
    </div>

    <!-- Título y eslogan (también dinámicos si los tienes) -->
    <div class="relative z-10 space-y-1">
      <h2 class="text-3xl font-extrabold text-gray-800 dark:text-white tracking-tight">
  
</h2>

    </div>
</div>

        </div>

            </div>

        </main> 

        @livewireScriptConfig
    </body>
</html>
