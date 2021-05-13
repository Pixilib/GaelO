<?php

namespace App\Providers;

use App\GaelO\Adapters\DatabaseDumperAdapter;
use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Interfaces\Adapters\DatabaseDumperInterface;
use App\GaelO\Interfaces\Adapters\HttpClientInterface;
use Illuminate\Support\ServiceProvider;

class AdapterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(DatabaseDumperInterface::class, DatabaseDumperAdapter::class);
        $this->app->bind(HttpClientInterface::class, HttpClientAdapter::class);
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
