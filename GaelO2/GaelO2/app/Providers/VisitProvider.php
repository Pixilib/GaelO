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
            \App\GaelO\Services\VisitService::class,
            \App\GaelO\UseCases\GetPatientVisit\GetPatientVisit::class,
            \App\GaelO\UseCases\DeleteVisit\DeleteVisit::class,
            \App\GaelO\UseCases\ModifyQualityControl\ModifyQualityControl::class,
            \App\GaelO\UseCases\DeleteSeries\DeleteSeries::class,
            \App\GaelO\UseCases\ReactivateDicomSeries\ReactivateDicomSeries::class])
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
