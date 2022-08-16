<?php

namespace App\Http\Middleware;

use App\GaelO\Constants\Constants;
use App\GaelO\Util;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class EnsureUserOnboarded
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (Util::isVersionHigher(Config::get('app.onboarding_version'), $user->onboarding_version)) {
            return response(Constants::USER_NOT_ONBOARDED, 403);
        }

        return $next($request);
    }
}
