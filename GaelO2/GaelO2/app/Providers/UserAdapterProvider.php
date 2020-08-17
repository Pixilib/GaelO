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
            return new \App\GaelO\UseCases\CreateUser\CreateUser (new \App\GaelO\Repositories\UserRepository());
        });

        $this->app->bind('CreateUserRequest', function ($app) {
            return new \App\GaelO\UseCases\CreateUser\CreateUserRequest();
        });

        $this->app->bind('CreateUserResponse', function ($app) {
            return new \App\GaelO\UseCases\CreateUser\CreateUserResponse();
        });

        $this->app->bind('ModifyUser', function ($app) {
            return new \App\GaelO\UseCases\ModifyUser\ModifyUser (new \App\GaelO\Repositories\UserRepository());
        });

        $this->app->bind('ModifyUserRequest', function ($app) {
            return new \App\GaelO\UseCases\ModifyUser\ModifyUserRequest();
        });

        $this->app->bind('ModifyUserResponse', function ($app) {
            return new \App\GaelO\UseCases\ModifyUser\ModifyUserResponse();
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
