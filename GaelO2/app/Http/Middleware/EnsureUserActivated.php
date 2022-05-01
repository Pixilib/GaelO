<?php

namespace App\Http\Middleware;

use App\GaelO\Constants\Constants;
use Closure;
use Illuminate\Http\Request;

class EnsureUserActivated
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
        if ($user->email_verified_at === null) {
            return response(Constants::USER_EMAIL_NOT_VERIFIED, 403);
        }else if ($user->deleted_at !== null ) {
            return response(Constants::USER_DELETED, 403);
        }else if ($user->attempts >= 3 ) {
            return response(Constants::USER_BLOCKED, 403);
        }

        return $next($request);
    }
}
