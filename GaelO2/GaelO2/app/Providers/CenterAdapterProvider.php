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
        $this->app->bind('ModifyCenter', function ($app) {
            return new \App\GaelO\UseCases\ModifyCenter\ModifyCenter (new \App\GaelO\Repositories\CountryRepository());
        });

        $this->app->bind('ModifyCenterRequest', function ($app) {
            return new \App\GaelO\UseCases\ModifyCenter\ModifyCenterRequest();
        });

        $this->app->bind('ModifyCenterResponse', function ($app) {
            return new \App\GaelO\UseCases\ModifyCenter\ModifyCenterResponse();
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
