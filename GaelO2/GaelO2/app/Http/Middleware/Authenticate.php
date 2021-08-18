<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    public function handle($request, Closure $next, ... $guards) {
        if($request->cookie('gaeloCookie')) {
            $value = $request->cookie('gaeloCookie');
            $request->headers->set('Authorization', 'Bearer '.$value);
        }
        $this->authenticate($request, $guards);
        return $next($request);
    }
}
