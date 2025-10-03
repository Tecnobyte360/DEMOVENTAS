<x-form-section submit="updateProfileInformation">
    <x-slot name="title">
        {{ __('Información del Perfil') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Actualiza la información de tu perfil y la dirección de correo electrónico de tu cuenta.') }}
    </x-slot>

    <x-slot name="form">
        <!-- Sección de Foto de Perfil -->
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
            <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6 sm:col-span-4">
                <x-label for="photo" value="{{ __('Foto') }}" />
                
                <input type="file" id="photo" class="hidden"
                       wire:model.live="photo"
                       x-ref="photo"
                       x-on:change="
                            photoName = $refs.photo.files[0].name;
                            const reader = new FileReader();
                            reader.onload = (e) => { photoPreview = e.target.result; };
                            reader.readAsDataURL($refs.photo.files[0]);
                        " />
                
                <div class="mt-2 flex items-center space-x-4">
                    <!-- Foto de Perfil Actual -->
                    <img src="{{ $this->user->profile_photo_url }}" 
                         alt="{{ $this->user->name }}" 
                         class="rounded-full h-20 w-20 object-cover" x-show="!photoPreview">
                    
                    <!-- Vista Previa de Nueva Foto de Perfil -->
                    <span class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                          x-bind:style="'background-image: url(' + photoPreview + ');'"
                          x-show="photoPreview" style="display: none;"></span>
                </div>
                
                <div class="mt-2 flex space-x-2">
                    <x-secondary-button type="button" x-on:click.prevent="$refs.photo.click()">
                        {{ __('Seleccionar una Nueva Foto') }}
                    </x-secondary-button>
                    @if ($this->user->profile_photo_path)
                        <x-secondary-button type="button" wire:click="deleteProfilePhoto">
                            {{ __('Eliminar Foto') }}
                        </x-secondary-button>
                    @endif
                </div>
                
                <x-input-error for="photo" class="mt-2" />
            </div>
        @endif

        <!-- Sección de Nombre -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="name" value="{{ __('Nombre') }}" />
            <x-input id="name" type="text" class="mt-1 block w-full" wire:model.live="state.name" required autocomplete="name" />
            <x-input-error for="name" class="mt-2" />
        </div>

        <!-- Sección de Correo Electrónico -->
        <div class="col-span-6 sm:col-span-4">
            <x-label for="email" value="{{ __('Correo Electrónico') }}" />
            <x-input id="email" type="email" class="mt-1 block w-full" wire:model.live="state.email" required autocomplete="username" />
            <x-input-error for="email" class="mt-2" />
            
            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
                <p class="text-sm mt-2 dark:text-white">
                    {{ __('Tu dirección de correo electrónico no está verificada.') }}
                    <button type="button" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 dark:focus:ring-offset-gray-800" wire:click.prevent="sendEmailVerification">
                        {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                    </button>
                </p>
                @if ($this->verificationLinkSent)
                    <p class="mt-2 font-medium text-sm text-green-700">
                        {{ __('Se ha enviado un nuevo enlace de verificación a tu dirección de correo electrónico.') }}
                    </p>
                @endif
            @endif
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            {{ __('Guardado.') }}
        </x-action-message>
        
        <x-button wire:loading.attr="disabled" wire:target="photo">
            {{ __('Guardar') }}
        </x-button>
    </x-slot>
</x-form-section>
