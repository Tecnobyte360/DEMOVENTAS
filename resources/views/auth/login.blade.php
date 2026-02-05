<x-authentication-layout>

   

  
    <div class="relative overflow-hidden rounded-[2rem] border border-slate-200/70 bg-white
                shadow-[0_40px_90px_-30px_rgba(15,23,42,.18)]">

        {{-- Top brand line --}}
        <div class="h-[3px] bg-gradient-to-r from-[#132742] via-sky-400 to-transparent"></div>

        <div class="p-6 sm:p-8">
            {{-- Mensaje de estado --}}
            @if (session('status'))
                <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <x-label for="email" value="Correo electrónico" class="text-sm font-semibold text-slate-700" />

                    <div class="mt-2 relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16v16H4z" opacity=".12"></path>
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
                                   border-slate-200
                                   bg-white
                                   text-slate-900
                                   placeholder:text-slate-400
                                   shadow-sm
                                   focus:border-[#132742]/40
                                   focus:ring-4
                                   focus:ring-[#132742]/15"
                        />
                    </div>
                </div>

                {{-- Password --}}
                <div>
                    <x-label for="password" value="Contraseña" class="text-sm font-semibold text-slate-700" />

                    <div class="mt-2 relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path d="M7 11V8a5 5 0 0 1 10 0v3" />
                                <path d="M6 11h12v10H6z" opacity=".12"></path>
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
                                   border-slate-200
                                   bg-white
                                   text-slate-900
                                   placeholder:text-slate-400
                                   shadow-sm
                                   focus:border-[#132742]/40
                                   focus:ring-4
                                   focus:ring-[#132742]/15"
                        />
                    </div>
                </div>

                {{-- Row --}}
                <div class="flex items-center justify-between pt-1">
                    <label for="remember_me" class="inline-flex items-center gap-2">
                        <input
                            id="remember_me"
                            type="checkbox"
                            name="remember"
                            class="h-4 w-4 rounded-md border-slate-300 text-[#132742] focus:ring-4 focus:ring-[#132742]/15"
                        />
                        <span class="text-sm text-slate-600">Recordarme</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="text-sm font-semibold text-[#132742] hover:text-sky-700 hover:underline">
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif
                </div>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="group relative w-full overflow-hidden rounded-xl bg-[#132742]
                           px-4 py-3 text-white font-semibold
                           shadow-lg shadow-[#132742]/20
                           transition
                           hover:-translate-y-[1px]
                           hover:shadow-xl hover:shadow-[#132742]/25
                           focus:outline-none focus:ring-4 focus:ring-[#132742]/20">

                    <span class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                          style="background: radial-gradient(1200px circle at 50% 0%, rgba(255,255,255,.22), transparent 55%);">
                    </span>

                    <span class="relative inline-flex items-center justify-center gap-2">
                        Iniciar sesión
                        <svg class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h13"></path>
                            <path d="M12 5l7 7-7 7"></path>
                        </svg>
                    </span>
                </button>

                <x-validation-errors class="pt-2" />
            </form>
        </div>
    </div>

    {{-- Footer --}}
    <div class="pt-6 mt-6 text-center text-xs text-slate-400">
        <span>
            by
            <a href="https://tecnobyte360.com" target="_blank"
               class="font-semibold text-[#132742] hover:text-sky-700 hover:underline">
                Tecnobyte 360
            </a>
            &copy; {{ date('Y') }}
        </span>
    </div>

</x-authentication-layout>
