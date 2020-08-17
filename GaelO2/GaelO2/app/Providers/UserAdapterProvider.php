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

        $this->app->bind('GetUser', function ($app) {
            return new \App\GaelO\UseCases\GetUser\GetUser (new \App\GaelO\Repositories\UserRepository());
        });

        $this->app->bind('GetUserRequest', function ($app) {
            return new \App\GaelO\UseCases\GetUser\GetUserRequest();
        });

        $this->app->bind('GetUserResponse', function ($app) {
            return new \App\GaelO\UseCases\GetUser\GetUserResponse();
        });

        $this->app->bind('DeleteUser', function ($app) {
            return new \App\GaelO\UseCases\DeleteUser\DeleteUser (new \App\GaelO\Repositories\UserRepository());
        });

        $this->app->bind('DeleteUserRequest', function ($app) {
            return new \App\GaelO\UseCases\DeleteUser\DeleteUserRequest();
        });

        $this->app->bind('DeleteUserResponse', function ($app) {
            return new \App\GaelO\UseCases\DeleteUser\DeleteUserResponse();
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
