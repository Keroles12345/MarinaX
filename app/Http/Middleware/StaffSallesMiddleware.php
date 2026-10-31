<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffSallesMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('staff');
        $user = Auth::guard('staff')->user();
        if (! $user) {
            return redirect('/staff/login');
        }


        $staffProfile = $user->staffProfile;


        if (!$staffProfile) {
            abort(403, 'Unauthorized');
        }


        if ($staffProfile->department_id != 2) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
