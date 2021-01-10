<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CountryAdapterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app
        ->bind(\App\GaelO\Interfaces\CountryRepositoryInterface::class,
        \App\GaelO\Repositories\CountryRepository::class);
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
