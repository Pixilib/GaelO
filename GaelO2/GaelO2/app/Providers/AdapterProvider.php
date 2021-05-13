<?php

namespace App\Providers;

use App\GaelO\Adapters\DatabaseDumperAdapter;
use App\GaelO\Adapters\FrameworkAdapter;
use App\GaelO\Adapters\HashAdapter;
use App\GaelO\Adapters\HttpClientAdapter;
use App\GaelO\Interfaces\Adapters\DatabaseDumperInterface;
use App\GaelO\Interfaces\Adapters\FrameworkInterface;
use App\GaelO\Interfaces\Adapters\HashInterface;
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
        $this->app->bind(HashInterface::class, HashAdapter::class);
        $this->app->bind(FrameworkInterface::class, FrameworkAdapter::class);
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
