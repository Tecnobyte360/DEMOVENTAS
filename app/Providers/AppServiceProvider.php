<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Masmerise\Toaster\Http\Livewire\Toaster;
use Masmerise\Toaster\Toaster as ToasterToaster;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\ConfiguracionEmpresas\Empresa;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191); 

        Livewire::component('toaster', ToasterToaster::class);

     
        View::composer('*', function ($view) {
            $empresa = cache()->remember('empresa_activa', now()->addMinutes(10), function () {
                return Empresa::where('is_activa', true)->latest('id')->first();
            });

            $view->with('empresaActual', $empresa);
        });
    }
}
