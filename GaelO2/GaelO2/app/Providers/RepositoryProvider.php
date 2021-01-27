<?php

namespace App\Providers;

use App\GaelO\Interfaces\CenterRepositoryInterface;
use App\GaelO\Interfaces\DocumentationRepositoryInterface;
use App\GaelO\Interfaces\TrackerRepositoryInterface;
use App\GaelO\Interfaces\UserRepositoryInterface;
use App\GaelO\Repositories\CenterRepository;
use App\GaelO\Repositories\DocumentationRepository;
use App\GaelO\Repositories\TrackerRepository;
use App\GaelO\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CenterRepositoryInterface::class, CenterRepository::class);
        $this->app->bind(TrackerRepositoryInterface::class, TrackerRepository::class);
        $this->app->bind(DocumentationRepositoryInterface::class, DocumentationRepository::class);
        //
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
