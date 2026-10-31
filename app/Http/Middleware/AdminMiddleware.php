<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('admin');
        $user = Auth::guard('admin')->user();
        if (! $user) {
            return redirect()->route('Admin.login');
        }

        abort_unless($user->role === 'admin' && ! $user->is_super_admin, 403);

        return $next($request);
    }
}
