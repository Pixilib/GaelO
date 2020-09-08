<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(
            \App\GaelO\Interfaces\MailInterface::class,
            \App\GaelO\Adapters\SendEmailAdapter::class
        );

        $this->app->when(
            [\App\GaelO\Services\TrackerService::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\TrackerRepository::class);

        $this->app->bind('TrackerService', \App\GaelO\Services\TrackerService::class);

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
