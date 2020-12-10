<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class OrthancSeriesProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\DeleteSeries\DeleteSeries::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\OrthancSeriesRepository::class);
    }

    public function boot()
    {
        //
    }
}
