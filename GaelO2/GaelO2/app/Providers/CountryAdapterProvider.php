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
        $this->app->bind('GetCountry', function ($app) {
            return new \App\GaelO\UseCases\GetCountry\GetCountry (new \App\GaelO\Repositories\CountryRepository());
        });

        $this->app->bind('GetCountryRequest', function ($app) {
            return new \App\GaelO\UseCases\GetCountry\GetCountryRequest();
        });

        $this->app->bind('GetCountryResponse', function ($app) {
            return new \App\GaelO\UseCases\GetCountry\GetCountryResponse();
        });
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
