<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;


//use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        date_default_timezone_set('UTC');
        //Passport::routes();
        // https forzato solo se l'app è pubblicata in https (APP_URL): con nginx in http
        // gli URL generati (form dei pannelli, redirect) punterebbero a https e non funzionerebbero
        if (in_array(config('app.env'), ['production', 'staging'], true)
            && str_starts_with((string) config('app.url'), 'https://')) {
            \URL::forceScheme('https');
        }
    }
}
