<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PublicTicketTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');
        abort_unless(is_string($token) && $token !== '', 404);

        // Read only the token and tenant ID before the tenant-scoped ticket query.
        $ticketContext = DB::table('tickets')
            ->select(['token', 'tenant_id'])
            ->where('token', $token)
            ->first();

        abort_unless($ticketContext && $ticketContext->tenant_id !== null, 404);

        $tenantId = (int) $ticketContext->tenant_id;
        $wasInitialized = TenantContext::isInitialized();
        $previousTenantId = TenantContext::id();

        abort_if(
            $wasInitialized && $previousTenantId !== null && $previousTenantId !== $tenantId,
            404,
        );

        try {
            TenantContext::initialize($tenantId);

            return $next($request);
        } finally {
            if ($wasInitialized) {
                TenantContext::initialize($previousTenantId);
            } else {
                TenantContext::clear();
            }
        }
    }
}
