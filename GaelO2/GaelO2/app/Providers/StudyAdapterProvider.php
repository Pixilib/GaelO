<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class StudyAdapterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /*$this->app->bind('CreateStudy', function ($app) {
            return new \App\GaelO\UseCases\CreateStudy\CreateStudy (new \App\GaelO\Repositories\StudyRepository());
        });

        $this->app->bind('CreateStudyRequest', function ($app) {
            return new \App\GaelO\UseCases\CreateStudy\CreateStudyRequest();
        });

        $this->app->bind('CreateStudyResponse', function ($app) {
            return new \App\GaelO\UseCases\CreateStudy\CreateStudyResponse();
        });

        $this->app->bind('ModifyStudy', function ($app) {
            return new \App\GaelO\UseCases\ModifyStudy\ModifyStudy (new \App\GaelO\Repositories\StudyRepository());
        });

        $this->app->bind('ModifyStudyRequest', function ($app) {
            return new \App\GaelO\UseCases\ModifyStudy\ModifyStudyRequest();
        });

        $this->app->bind('ModifyStudyResponse', function ($app) {
            return new \App\GaelO\UseCases\ModifyStudy\ModifyStudyResponse();
        });

        $this->app->bind('GetStudy', function ($app) {
            return new \App\GaelO\UseCases\GetStudy\GetStudy (new \App\GaelO\Repositories\StudyRepository());
        });

        $this->app->bind('GetStudyRequest', function ($app) {
            return new \App\GaelO\UseCases\GetStudy\GetStudyRequest();
        });

        $this->app->bind('GetStudyResponse', function ($app) {
            return new \App\GaelO\UseCases\GetStudy\GetStudyResponse();
        });

        $this->app->bind('DeleteStudy', function ($app) {
            return new \App\GaelO\UseCases\DeleteStudy\DeleteStudy (new \App\GaelO\Repositories\StudyRepository());
        });

        $this->app->bind('DeleteStudyRequest', function ($app) {
            return new \App\GaelO\UseCases\DeleteStudy\DeleteStudyRequest();
        });

        $this->app->bind('DeleteStudyResponse', function ($app) {
            return new \App\GaelO\UseCases\DeleteStudy\DeleteStudyResponse();
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
