<x-authentication-layout>

    <!-- ================= Header ================= -->
   <!-- ================= Header ================= -->
<div class="mb-8">

   
       
    

    <!-- Título principal -->
    <h1 class="mt-1 text-3xl sm:text-4xl font-extrabold leading-tight text-[#0B1E36]">
        Bienvenido a {{ $empresaActual?->nombre ?? 'Tecnobyte360' }}
        
    </h1>

    <!-- Subtítulo -->
    <p class="mt-3 text-sm text-slate-600 max-w-md">
        Inicia sesión para continuar y gestionar tu experiencia.
    </p>

</div>


    <!-- ================= Card ================= -->
    <div
        class="rounded-3xl
               border border-slate-200
               bg-white
               shadow-[0_18px_50px_-20px_rgba(2,6,23,.25)]
               p-6 sm:p-8"
    >
        <!-- Mensaje de estado -->
        @if (session('status'))
            <div class="mb-4 text-sm font-medium text-emerald-600">
                {{ session('status') }}
            </div>
        @endif

        <!-- ================= Form ================= -->
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Email -->
            <div>
                <x-label
                    for="email"
                    value="Correo electrónico"
                    class="text-sm font-semibold text-slate-700"
                />

                <div class="mt-2 relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                             viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16v16H4z" opacity=".15"></path>
                            <path d="M4 6l8 7 8-7"></path>
                        </svg>
                    </span>

                    <x-input
                        id="email"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        placeholder="tucorreo@empresa.com"
                        class="w-full pl-11 rounded-xl
                               border-slate-300
                               bg-white
                               text-slate-900
                               placeholder:text-slate-400
                               focus:border-[#0B1E36]
                               focus:ring-[#0B1E36]/25"
                    />
                </div>
            </div>

            <!-- Password -->
            <div>
                <x-label
                    for="password"
                    value="Contraseña"
                    class="text-sm font-semibold text-slate-700"
                />

                <div class="mt-2 relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                             viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <path d="M7 11V8a5 5 0 0 1 10 0v3" />
                            <path d="M6 11h12v10H6z" opacity=".15"></path>
                            <path d="M6 11h12v10H6z" />
                        </svg>
                    </span>

                    <x-input
                        id="password"
                        type="password"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="w-full pl-11 rounded-xl
                               border-slate-300
                               bg-white
                               text-slate-900
                               placeholder:text-slate-400
                               focus:border-[#0B1E36]
                               focus:ring-[#0B1E36]/25"
                    />
                </div>
            </div>

            <!-- Row -->
            <div class="flex items-center justify-between pt-1">
                <label for="remember_me" class="inline-flex items-center gap-2">
                    <input
                        id="remember_me"
                        type="checkbox"
                        name="remember"
                        class="rounded-md border-slate-300 text-[#0B1E36] focus:ring-[#0B1E36]/30"
                    />
                    <span class="text-sm text-slate-600">
                        Recordarme
                    </span>
                </label>

                @if (Route::has('password.request'))
                    <a
                        href="{{ route('password.request') }}"
                        class="text-sm font-medium text-[#0B1E36] hover:underline"
                    >
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>

            <!-- Button -->
            <button
                type="submit"
                class="w-full rounded-xl
                       bg-[#0B1E36]
                       px-4 py-3
                       text-white font-semibold
                       shadow-lg
                       transition
                       hover:bg-[#12345B]
                       focus:outline-none
                       focus:ring-4
                       focus:ring-[#0B1E36]/30"
            >
                Iniciar sesión →
            </button>

            <!-- Errors -->
            <x-validation-errors class="pt-2" />
        </form>
    </div>

    <!-- ================= Footer ================= -->
    <div class="pt-6 mt-6 text-center text-sm text-slate-500">
        <span>
            by
            <a href="https://tecnobyte360.com"
               target="_blank"
               class="font-semibold text-[#0B1E36] hover:underline">
                Tecnobyte 360
            </a>
            &copy; {{ date('Y') }}
        </span>
    </div>

</x-authentication-layout>
