<?php

namespace App\Http\Middleware;

use App\GaelO\Constants\Constants;
use Closure;
use Illuminate\Http\Request;

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
        if ($user->onboarding_version === '0.0.0') {
            return response(Constants::USER_NOT_ONBOARDED, 403);
        }

        return $next($request);
    }
}
