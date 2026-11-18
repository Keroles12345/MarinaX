<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTenantApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->bearerToken();
        if (! is_string($key) || ! str_starts_with($key, 'mx_live_')) {
            return response()->json(['message' => 'مفتاح API غير صالح.'], 401);
        }

        $prefix = substr($key, 0, 24);
        $tenant = Tenant::query()->where('api_key_prefix', $prefix)->first();
        if (! $tenant || ! hash_equals((string) $tenant->api_key_hash, hash('sha256', $key))) {
            return response()->json(['message' => 'مفتاح API غير صالح.'], 401);
        }

        if ($tenant->api_key_expires_at && today()->gt($tenant->api_key_expires_at)) {
            return response()->json(['message' => 'انتهت صلاحية مفتاح API.'], 401);
        }

        if (! $tenant->status) {
            return response()->json(['message' => 'حساب الشركة موقوف.'], 403);
        }

        TenantContext::initialize((int) $tenant->id);

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
        }
    }
}
