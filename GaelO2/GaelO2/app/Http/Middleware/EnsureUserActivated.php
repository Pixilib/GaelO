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
        if ($user->status === Constants::USER_STATUS_UNCONFIRMED) {
            return response('Unconfirmed Account', 403);
        }else if ($user->status === Constants::USER_STATUS_DEACTIVATED) {
            return response('', 403);
        }else if ($user->status === Constants::USER_STATUS_BLOCKED) {
            return response('Account Blocked', 403);
        }

        return $next($request);
    }
}
