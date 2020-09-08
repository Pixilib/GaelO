<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class StudyRepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\CreateStudy\CreateStudy::class,
            \App\GaelO\UseCases\GetStudy\GetStudy::class,
            \App\GaelO\UseCases\DeleteStudy\DeleteStudy::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\StudyRepository::class);
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
