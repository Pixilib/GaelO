<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class VisitAdapterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /*$this->app->bind('CreateVisit', function ($app) {
            return new \App\GaelO\UseCases\CreateVisit\CreateVisit (new \App\GaelO\Repositories\VisitRepository());
        });

        $this->app->bind('CreateVisitRequest', function ($app) {
            return new \App\GaelO\UseCases\CreateVisit\CreateVisitRequest();
        });

        $this->app->bind('CreateVisitResponse', function ($app) {
            return new \App\GaelO\UseCases\CreateVisit\CreateVisitResponse();
        });

        $this->app->bind('ModifyVisit', function ($app) {
            return new \App\GaelO\UseCases\ModifyVisit\ModifyVisit (new \App\GaelO\Repositories\VisitRepository());
        });

        $this->app->bind('ModifyVisitRequest', function ($app) {
            return new \App\GaelO\UseCases\ModifyVisit\ModifyVisitRequest();
        });

        $this->app->bind('ModifyVisitResponse', function ($app) {
            return new \App\GaelO\UseCases\ModifyVisit\ModifyVisitResponse();
        });

        $this->app->bind('GetVisit', function ($app) {
            return new \App\GaelO\UseCases\GetVisit\GetVisit (new \App\GaelO\Repositories\VisitRepository());
        });

        $this->app->bind('GetVisitRequest', function ($app) {
            return new \App\GaelO\UseCases\GetVisit\GetVisitRequest();
        });

        $this->app->bind('GetVisitResponse', function ($app) {
            return new \App\GaelO\UseCases\GetVisit\GetVisitResponse();
        });

        $this->app->bind('DeleteVisit', function ($app) {
            return new \App\GaelO\UseCases\DeleteVisit\DeleteVisit (new \App\GaelO\Repositories\VisitRepository());
        });

        $this->app->bind('DeleteVisitRequest', function ($app) {
            return new \App\GaelO\UseCases\DeleteVisit\DeleteVisitRequest();
        });

        $this->app->bind('DeleteVisitResponse', function ($app) {
            return new \App\GaelO\UseCases\DeleteVisit\DeleteVisitResponse();
        });*/
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
