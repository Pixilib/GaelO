<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TrackerRepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\GetTrackerAdmin\GetTrackerAdmin::class,
            \App\GaelO\UseCases\GetTrackerUser\GetTrackerUser::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\TrackerRepository::class);
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
