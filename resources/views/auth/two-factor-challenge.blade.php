<x-authentication-layout>
    <h1 class="text-3xl text-gray-800 dark:text-gray-100 font-bold mb-6">{{ __('Confirmar acceso') }}</h1>
    <div x-data="{ recovery: false }">
        <div class="mb-4" x-show="! recovery">
            {{ __('Por favor, confirma el acceso a tu cuenta ingresando el código de autenticación proporcionado por tu aplicación de autenticación.') }}
        </div>

        <div class="mb-4" x-show="recovery">
            {{ __('Por favor, confirma el acceso a tu cuenta ingresando uno de tus códigos de recuperación de emergencia.') }}
        </div>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('two-factor.login') }}">
            @csrf
            <div class="space-y-4">
                <div x-show="! recovery">
                    <x-label for="code" value="{{ __('Código') }}" />
                    <x-input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code" />
                </div>
                <div x-show="recovery">
                    <x-label for="recovery_code" value="{{ __('Código de recuperación') }}" />
                    <x-input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code" />
                </div>
            </div>
            <div class="flex items-center justify-end mt-6">
                <button type="button" class="text-sm underline hover:no-underline"
                    x-show="! recovery"
                    x-on:click="
                        recovery = true;
                        $nextTick(() => { $refs.recovery_code.focus() })
                    ">
                    {{ __('Usar un código de recuperación') }}
                </button>

                <button type="button" class="text-sm text-gray-600 hover:text-gray-900 underline cursor-pointer"
                    x-show="recovery"
                    x-on:click="
                        recovery = false;
                        $nextTick(() => { $refs.code.focus() })
                    ">
                    {{ __('Usar un código de autenticación') }}
                </button>

                <x-button class="ml-4">
                    {{ __('Iniciar sesión') }}
                </x-button>
            </div>
        </form>
    </div>
</x-authentication-layout>
