<?php

namespace App\Http\Middleware;

use App\Support\SystemPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemPermissionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->is_super_admin
            || ($user->role !== 'admin' && ! $user->system_permissions_managed)) {
            if ($request->route()?->getName() === 'operatoins.dashboard'
                && ! $user->hasPermissionTo('operations.delete', 'web')) {
                abort(403);
            }

            return $next($request);
        }

        if (str_ends_with((string) $request->route()?->getName(), '.logout')) {
            return $next($request);
        }

        $module = SystemPermissions::moduleForRoute($request->route()?->uri() ?? '');
        abort_unless($module, 403, 'لا توجد صلاحية معرفة لهذا الجزء من النظام.');

        if ($module === 'accounting_dashboard' && $user->role === 'admin') {
            $module = 'admin_dashboard';
        }

        $action = SystemPermissions::actionForRoute(
            $request->method(),
            $request->route()?->getName(),
            $request->route()?->uri() ?? ''
        );
        $allowed = SystemPermissions::allows($user, $module, $action);

        if ($request->route()?->getName() === 'accounting.company-prices.save') {
            $allowed = SystemPermissions::allows($user, $module, 'create')
                || SystemPermissions::allows($user, $module, 'update');
        }

        abort_unless($allowed, 403);

        return $next($request);
    }
}
