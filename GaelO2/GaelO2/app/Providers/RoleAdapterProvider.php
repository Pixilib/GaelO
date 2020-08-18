<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RoleAdapterProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        /*$this->app->bind('CreateRole', function ($app) {
            return new \App\GaelO\UseCases\CreateRole\CreateRole (new \App\GaelO\Repositories\RoleRepository());
        });

        $this->app->bind('CreateRoleRequest', function ($app) {
            return new \App\GaelO\UseCases\CreateRole\CreateRoleRequest();
        });

        $this->app->bind('CreateRoleResponse', function ($app) {
            return new \App\GaelO\UseCases\CreateRole\CreateRoleResponse();
        });

        $this->app->bind('ModifyRole', function ($app) {
            return new \App\GaelO\UseCases\ModifyRole\ModifyRole (new \App\GaelO\Repositories\RoleRepository());
        });

        $this->app->bind('ModifyRoleRequest', function ($app) {
            return new \App\GaelO\UseCases\ModifyRole\ModifyRoleRequest();
        });

        $this->app->bind('ModifyRoleResponse', function ($app) {
            return new \App\GaelO\UseCases\ModifyRole\ModifyRoleResponse();
        });

        $this->app->bind('GetRole', function ($app) {
            return new \App\GaelO\UseCases\GetRole\GetRole (new \App\GaelO\Repositories\RoleRepository());
        });

        $this->app->bind('GetRoleRequest', function ($app) {
            return new \App\GaelO\UseCases\GetRole\GetRoleRequest();
        });

        $this->app->bind('GetRoleResponse', function ($app) {
            return new \App\GaelO\UseCases\GetRole\GetRoleResponse();
        });

        $this->app->bind('DeleteRole', function ($app) {
            return new \App\GaelO\UseCases\DeleteRole\DeleteRole (new \App\GaelO\Repositories\RoleRepository());
        });

        $this->app->bind('DeleteRoleRequest', function ($app) {
            return new \App\GaelO\UseCases\DeleteRole\DeleteRoleRequest();
        });

        $this->app->bind('DeleteRoleResponse', function ($app) {
            return new \App\GaelO\UseCases\DeleteRole\DeleteRoleResponse();
        });*/
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
