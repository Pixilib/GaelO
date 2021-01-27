<?php

namespace App\Providers;

use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Repositories\TrackerRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(TrackerRepositoryInterface::class, TrackerRepository::class);
        //
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
