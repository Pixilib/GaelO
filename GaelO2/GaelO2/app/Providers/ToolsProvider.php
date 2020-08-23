<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ToolsProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('ResetPassword', \App\GaelO\UseCases\ResetPassword\ResetPassword::class);
        $this->app->bind('ResetPasswordRequest', \App\GaelO\UseCases\ResetPassword\ResetPasswordRequest::class);
        $this->app->bind('ResetPasswordResponse', \App\GaelO\UseCases\ResetPassword\ResetPasswordResponse::class);
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
