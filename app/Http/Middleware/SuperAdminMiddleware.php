<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::shouldUse('superadmin');
        $user = Auth::guard('superadmin')->user();

        if (! $user) {
            return redirect()->route('superadmin.login');
        }

        abort_unless($user->is_super_admin && $user->status, 403);
        TenantContext::initialize(null);

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
        }
    }
}
