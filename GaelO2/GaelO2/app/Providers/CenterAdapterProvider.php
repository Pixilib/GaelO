<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CenterAdapterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('GetCenter', function ($app) {
            return new \App\GaelO\UseCases\GetCenter\GetCenter (new \App\GaelO\Repositories\CenterRepository());
        });

        $this->app->bind('GetCenterRequest', function ($app) {
            return new \App\GaelO\UseCases\GetCenter\GetCenterRequest();
        });

        $this->app->bind('GetCenterResponse', function ($app) {
            return new \App\GaelO\UseCases\GetCenter\GetCenterResponse();
        });
        $this->app->bind('CreateCenter', function ($app) {
            return new \App\GaelO\UseCases\CreateCenter\CreateCenter (new \App\GaelO\Repositories\CountryRepository());
        });

        $this->app->bind('CreateCenterRequest', function ($app) {
            return new \App\GaelO\UseCases\CreateCenter\CreateCenterRequest();
        });

        $this->app->bind('CreateCenterResponse', function ($app) {
            return new \App\GaelO\UseCases\CreateCenter\CreateCenterResponse();
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
