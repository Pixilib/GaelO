<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RequestProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('SendRequest', App\GaelO\UseCases\Request\SendRequest::class);
        $this->app->bind('RequestRequest', App\GaelO\UseCases\Request\RequestRequest::class);
        $this->app->bind('RequestResponse', \App\GaelO\UseCases\Request\RequestResponse::class);
    }
}
