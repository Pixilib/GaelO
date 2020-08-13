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

        $this->app->bind('ModifyUser', function ($app) {
            return new \App\GaelO\ModifyUser\ModifyUser (new \App\GaelO\Adapters\Model\UserAdapter);
        });

        $this->app->bind('ModifyUserRequest', function ($app) {
            return new \App\GaelO\ModifyUser\ModifyUserRequest();
        });

        $this->app->bind('ModifyUserResponse', function ($app) {
            return new \App\GaelO\ModifyUser\ModifyUserResponse();
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
