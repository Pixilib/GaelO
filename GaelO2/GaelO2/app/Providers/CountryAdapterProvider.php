<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CountryAdapterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\GetCountry\GetCountry::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\CountryRepository::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
