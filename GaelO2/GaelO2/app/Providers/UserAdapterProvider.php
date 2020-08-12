<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class UserAdapterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('CreateUser', function ($app) {
            return new \App\GaelO\CreateUser\CreateUser (new \App\GaelO\Adapters\Model\UserAdapter);
        });

        $this->app->bind('CreateUserRequest', function ($app) {
            return new \App\GaelO\CreateUser\CreateUserRequest();
        });

        $this->app->bind('CreateUserResponse', function ($app) {
            return new \App\GaelO\CreateUser\CreateUserResponse();
        });
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
