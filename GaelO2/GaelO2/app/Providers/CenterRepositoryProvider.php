<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CenterRepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\GetCenter\GetCenter::class,
            \App\GaelO\UseCases\ModifyCenter\ModifyCenter::class,
            \App\GaelO\UseCases\CreateCenter\CreateCenter::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\CenterRepository::class);
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
