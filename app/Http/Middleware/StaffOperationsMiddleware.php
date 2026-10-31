<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffOperationsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('staff');
        $user = Auth::guard('staff')->user();
        if (! $user) {
            return redirect('/staff/login');
        }
        $staffProfile = $user->staffProfile;
        if(!$staffProfile){
         abort(403, 'Unauthorized');
        }
        if($staffProfile->department_id !=1){
                 abort(403, 'Unauthorized');
        }
        return $next($request);
    }
}
