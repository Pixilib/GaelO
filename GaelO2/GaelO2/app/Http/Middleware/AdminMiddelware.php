<?php

namespace App\Http\Middleware;

use Closure;

class AdminMiddelware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if($user->administrator){
            return $next($request);
        }else{
            return response()->json('Unauthorized', 401);
        }

    }
}
