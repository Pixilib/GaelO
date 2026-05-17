<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        Fortify::ignoreRoutes();
        // No view for 2FA challenge
        Fortify::twoFactorChallengeView(function () {
            return response()->json(['message' => 'Unauthorized'], 401);
        });
    }
}