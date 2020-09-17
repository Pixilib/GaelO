<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class VisitTypeProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\CreateVisitType\CreateVisitType::class,
            \App\GaelO\UseCases\GetVisitType\GetVisitType::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\VisitTypeRepository::class);
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
