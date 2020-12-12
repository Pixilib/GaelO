<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class OrthancStudyProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\GetKnownOrthancID\GetKnownOrthancID::class,
            \App\GaelO\UseCases\GetDicoms\GetDicoms::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\OrthancStudyRepository::class);
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
