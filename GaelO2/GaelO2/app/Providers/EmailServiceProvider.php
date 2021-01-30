<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(
            \App\GaelO\Interfaces\MailInterface::class,
            \App\GaelO\Adapters\SendEmailAdapter::class
        );

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
