<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SupervisorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if(!Auth::check()){
return redirect('/staff/login');
        }

        if (Auth::user()->role !== 'supervisor') {
            abort(403);
        }
        if(Auth::user())
        return $next($request);
    }
}
