<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class VisitProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\CreateVisit\CreateVisit::class,
            \App\GaelO\UseCases\GetVisit\GetVisit::class,
            \App\GaelO\Services\VisitService::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\VisitRepository::class);
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
