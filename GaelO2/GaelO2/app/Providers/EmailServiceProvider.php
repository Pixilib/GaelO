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

        $this->app->bind('MailServices', function ($app) {
            return new \App\GaelO\Services\Mails\MailServices(new \App\GaelO\Adapters\SendEmailAdapter, new \App\GaelO\Repositories\UserRepository);
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
