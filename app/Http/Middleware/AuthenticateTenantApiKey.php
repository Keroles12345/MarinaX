<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantApiScopes;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateTenantApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route()?->uri() ?? $request->path();
        $key = $request->bearerToken();
        if (! is_string($key) || ! str_starts_with($key, 'mx_live_')) {
            Log::warning('Tenant API authentication rejected', [
                'reason' => 'missing_or_malformed_key',
                'method' => $request->method(),
                'route' => $route,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'مفتاح API غير صالح.'], 401);
        }

        $prefix = substr($key, 0, 24);
        $tenant = Tenant::query()->where('api_key_prefix', $prefix)->first();
        if (! $tenant || ! hash_equals((string) $tenant->api_key_hash, hash('sha256', $key))) {
            Log::warning('Tenant API authentication rejected', [
                'reason' => 'invalid_key',
                'method' => $request->method(),
                'route' => $route,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'مفتاح API غير صالح.'], 401);
        }

        if ($tenant->api_key_expires_at && today()->gt($tenant->api_key_expires_at)) {
            Log::warning('Tenant API authentication rejected', [
                'reason' => 'expired_key',
                'tenant_id' => $tenant->id,
                'method' => $request->method(),
                'route' => $route,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'انتهت صلاحية مفتاح API.'], 401);
        }

        if (! $tenant->status) {
            Log::warning('Tenant API authentication rejected', [
                'reason' => 'inactive_tenant',
                'tenant_id' => $tenant->id,
                'method' => $request->method(),
                'route' => $route,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'حساب الشركة موقوف.'], 403);
        }

        DB::table('tenants')->where('id', $tenant->id)->update(['api_key_last_used_at' => now()]);
        TenantContext::initialize((int) $tenant->id);
        $request->attributes->set('tenant_api_scopes', $tenant->api_key_scopes ?? TenantApiScopes::DEFAULT);
        $startedAt = hrtime(true);

        try {
            $response = $next($request);
            Log::info('Tenant API request completed', [
                'tenant_id' => $tenant->id,
                'method' => $request->method(),
                'route' => $route,
                'status' => $response->getStatusCode(),
                'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
            ]);

            return $response;
        } catch (Throwable $exception) {
            Log::error('Tenant API request failed', [
                'tenant_id' => $tenant->id,
                'method' => $request->method(),
                'route' => $route,
                'exception' => $exception::class,
                'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
            ]);

            throw $exception;
        } finally {
            TenantContext::clear();
        }
    }
}
