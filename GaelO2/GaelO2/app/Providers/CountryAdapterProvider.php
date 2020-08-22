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

        $this->app->bind('GetCountry', \App\GaelO\UseCases\GetCountry\GetCountry::class);
        $this->app->bind('GetCountryRequest', \App\GaelO\UseCases\GetCountry\GetCountryRequest::class);
        $this->app->bind('GetCountryResponse', \App\GaelO\UseCases\GetCountry\GetCountryResponse::class);
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
