<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::clear();

        $routeMiddleware = $request->route()?->gatherMiddleware() ?? [];
        if (in_array('superadmin', $routeMiddleware, true)) {
            $guard = 'superadmin';
        } elseif (in_array('staff', $routeMiddleware, true)) {
            $guard = 'staff';
        } elseif (in_array('admin', $routeMiddleware, true) || $this->hasAccountingRoleMiddleware($routeMiddleware)) {
            $guard = 'admin';
        } else {
            $guard = 'superadmin';
        }

        $user = auth($guard)->user()
            ?? ($guard !== 'admin' ? auth('admin')->user() : null)
            ?? ($guard !== 'staff' ? auth('staff')->user() : null)
            ?? ($guard !== 'superadmin' ? auth('superadmin')->user() : null)
            ?? auth()->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->is_super_admin && (! $user->tenant_id || ! $user->tenant?->status)) {
            abort(403, 'الشركة المرتبط بها المستخدم غير مفعلة');
        }

        TenantContext::initialize($user->is_super_admin ? null : (int) $user->tenant_id);

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
        }
    }

    private function hasAccountingRoleMiddleware(array $middleware): bool
    {
        foreach ($middleware as $entry) {
            if (str_starts_with($entry, 'accounting-role:')) {
                return true;
            }
        }

        return false;
    }
}
