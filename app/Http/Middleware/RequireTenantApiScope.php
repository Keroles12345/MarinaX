<?php

namespace App\Http\Middleware;

use App\Support\TenantApiScopes;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTenantApiScope
{
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $scopes = $request->attributes->get('tenant_api_scopes', TenantApiScopes::DEFAULT);
        if (! in_array($scope, $scopes, true)) {
            return response()->json([
                'message' => 'مفتاح API لا يملك الصلاحية المطلوبة.',
                'required_scope' => $scope,
            ], 403);
        }

        return $next($request);
    }
}
