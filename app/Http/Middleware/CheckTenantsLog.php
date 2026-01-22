<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantsLog
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = Tenant::current();

        Log::info('Before tenant middleware', [
            'tenant_id' => $tenant?->id ?? 'NONE',
            'tenant_is_current' => $tenant?->isCurrent() ?? false,
            'connections' => array_keys(\DB::getConnections()),
            'default_connection' => config('database.default'),
        ]);

        $response = $next($request);

        $tenant = Tenant::current();

        Log::info('After tenant middleware', [
            'tenant_id' => $tenant?->id ?? 'NONE',
            'tenant_is_current' => $tenant?->isCurrent() ?? false,
            'connections' => array_keys(\DB::getConnections()),
            'tenant_connection_db' => config('database.connections.tenant.database'),
        ]);

        return $response;
    }
}
