<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AccountingRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        Auth::shouldUse('admin');
        $user = Auth::guard('admin')->user();
        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['error' => 'انتهت الجلسة'], 401)
                : redirect()->route('Admin.login');
        }

        if ($user->is_super_admin || ! in_array($user->role, $roles, true)) {
            abort(403, 'ليس لديك صلاحية لتنفيذ هذا الإجراء');
        }

        return $next($request);
    }
}
