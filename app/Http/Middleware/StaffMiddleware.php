<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('staff');
        $user = Auth::guard('staff')->user();
        if (! $user) {
            return redirect()->route('staff.login');
        }

        if ($user->role !== 'staff') {
            abort(403);
        }

        return $next($request);
    }
}
