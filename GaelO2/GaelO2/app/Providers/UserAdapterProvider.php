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

        $this->app->when(
            [\App\GaelO\UseCases\CreateUser\CreateUser::class,
            \App\GaelO\UseCases\GetUser\GetUser::class,
            \App\GaelO\UseCases\DeleteUser\DeleteUser::class,
            \App\GaelO\UseCases\ChangePassword\ChangePassword::class,
            \App\GaelO\UseCases\ResetPassword\ResetPassword::class,
            \App\GaelO\UseCases\ModifyUser\ModifyUser::class,
            \App\GaelO\UseCases\Login\Login::class,
            \App\GaelO\UseCases\GetUserRoles\GetUserRoles::class])
        ->needs(\App\GaelO\Interfaces\PersistenceInterface::class)
        ->give(\App\GaelO\Repositories\UserRepository::class);
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
