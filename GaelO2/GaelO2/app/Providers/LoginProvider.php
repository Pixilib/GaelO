<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LoginProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('Login', \App\GaelO\UseCases\Login\Login::class);
        $this->app->bind('LoginRequest', \App\GaelO\UseCases\Login\LoginRequest::class);
        $this->app->bind('LoginResponse', \App\GaelO\UseCases\Login\LoginResponse::class);
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
