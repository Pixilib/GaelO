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

        $this->app->bind('CreateUser', \App\GaelO\UseCases\CreateUser\CreateUser::class);
        $this->app->bind('GetUser', \App\GaelO\UseCases\GetUser\GetUser::class);
        //$this->app->bind('User', \App\User::class);

        $this->app->when(
            [\App\GaelO\UseCases\CreateUser\CreateUser::class,
            \App\GaelO\UseCases\GetUser\GetUser::class])
          ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
          ->give(\App\GaelO\Repositories\UserRepository::class);

        $this->app->bind('CreateUserRequest', \App\GaelO\UseCases\CreateUser\CreateUserRequest::class);
        $this->app->bind('CreateUserResponse', \App\GaelO\UseCases\CreateUser\CreateUserResponse::class);

        $this->app->bind('ModifyUserRequest', \App\GaelO\UseCases\ModifyUser\ModifyUserRequest::class);
        $this->app->bind('ModifyUserResponse', \App\GaelO\UseCases\ModifyUser\ModifyUserResponse::class);

        $this->app->bind('GetUserRequest', \App\GaelO\UseCases\GetUser\GetUserRequest::class);
        $this->app->bind('GetUserResponse', \App\GaelO\UseCases\GetUser\GetUserResponse::class);

        $this->app->bind('DeleteUserRequest', \App\GaelO\UseCases\DeleteUser\DeleteUserRequest::class);
        $this->app->bind('DeleteUserResponse', \App\GaelO\UseCases\DeleteUser\DeleteUserResponse::class);

        $this->app->bind('ChangePasswordRequest', \App\GaelO\UseCases\ChangePassword\ChangePasswordRequest::class);
        $this->app->bind('ChangePasswordResponse', \App\GaelO\UseCases\ChangePassword\ChangePasswordResponse::class);
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
