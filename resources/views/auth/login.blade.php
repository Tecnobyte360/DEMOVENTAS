<x-authentication-layout>
    <!-- Título -->
 <h1 class="text-4xl font-extrabold text-gray-800 dark:text-gray-100 mb-6">
    {{ $empresaActual?->nombre ?? '' }} <span class="text-violet-500">!</span>
</h1>
<link rel="icon" type="image/png" href="{{ $empresaActual?->logo_url ?? asset('favicon.png') }}">


    <!-- Mensaje de estado -->
    @if (session('status'))
        <div class="mb-4 text-sm font-medium text-green-600">
            {{ session('status') }}
        </div>
    @endif   

    <!-- Formulario de Inicio de Sesión -->
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="space-y-5">
            <div>
                <x-label for="email" value="{{ __('Usuario') }}" class="text-sm font-semibold" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autofocus class="w-full mt-1 rounded-lg border-gray-300 focus:border-violet-500 focus:ring-violet-500" />                
            </div>
            <div>
                <x-label for="password" value="{{ __('Clave') }}" class="text-sm font-semibold" />
                <x-input id="password" type="password" name="password" required autocomplete="current-password" class="w-full mt-1 rounded-lg border-gray-300 focus:border-violet-500 focus:ring-violet-500" />                
            </div>
        </div>

        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <a class="text-sm text-violet-500 hover:text-violet-600 underline" href="{{ route('password.request') }}">
                    {{ __('Olvide mi contraseña') }}
                </a>
            @endif            
            <x-button class="ml-3 px-6 py-2 bg-violet-600 hover:bg-violet-700 text-white font-semibold rounded-lg">
                {{ __('Iniciar sesión') }}
            </x-button>            
        </div>
    </form>

    <!-- Validaciones -->
    <x-validation-errors class="mt-4" />   

    <!-- Footer -->
    <div class="pt-5 mt-6 border-t border-gray-200 dark:border-gray-700/60 text-center">
       

   
     <div class="mt-5">
  <div class="bg-violet-500/20 text-white-800 px-4 py-3 rounded-lg flex items-center space-x-2">

        <svg class="w-4 h-4 fill-current" viewBox="0 0 12 12">
            <path d="M10.28 1.28L3.989 7.575 1.695 5.28A1 1 0 00.28 6.695l3 3a1 1 0 001.414 0l7-7A1 1 0 0010.28 1.28z" />
        </svg>
       <span class="text-sm">
    <b>by</b> <a href="https://tecnobyte360.com/" class="text-900 hover:underline"><b>Tecnobyte 360</b></a> &copy; Copyright
</span>

    </div>
</div>


    </div>
</x-authentication-layout>
