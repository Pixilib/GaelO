<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class DocumentationRepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(
            [\App\GaelO\UseCases\CreateDocumentation\CreateDocumentation::class])
        ->give(\App\GaelO\Repositories\DocumentationRepository::class);
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
