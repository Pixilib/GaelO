<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Forcer toutes les routes Fortify sous le middleware API avec auth Sanctum
        config(['fortify.middleware' => ['api', 'auth:sanctum']]);
    }

    public function boot(): void
    {
        Fortify::ignoreRoutes();
        // Pas de vue pour le challenge 2FA — on retourne du JSON
        Fortify::twoFactorChallengeView(function () {
            return response()->json(['message' => 'Unauthorized'], 401);
        });
    }
}