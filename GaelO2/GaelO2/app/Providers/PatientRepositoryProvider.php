<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class PatientRepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\CreatePatient\CreatePatient::class,
            \App\GaelO\UseCases\GetPatient\GetPatient::class,
            \App\GaelO\UseCases\GetPatientFromStudy\GetPatientFromStudy::class,
            \App\GaelO\UseCases\DeletePatient\DeletePatient::class,
            \App\GaelO\Services\ImportPatientsService::class,
            \App\GaelO\UseCases\ModifyPatient\ModifyPatient::class,
            \App\GaelO\UseCases\ModifyPatientWithdraw\ModifyPatientWithdraw::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\PatientRepository::class);
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
