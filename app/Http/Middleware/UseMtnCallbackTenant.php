<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\TenantResolution\TenantResolutionStrategyFactory;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UseMtnCallbackTenant
{
    public function __construct(private TenantResolutionStrategyFactory $factory)
    {
    }

    /**
     * Select the tenant that owns MTN callbacks without relying on a request header.
     *
     * The factory selects a strategy from the URL. The MTN strategy resolves
     * the naijria tenant, so User queries use naijria's tenant database.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $strategy = $this->factory->for($request);

        if (! $strategy) {
            return response()->json(['message' => 'No tenant strategy matches this callback URL'], 404);
        }

        $tenant = $strategy->resolve($request);

        if (! $tenant) {
            return response()->json(['message' => 'MTN naijria tenant not found'], 404);
        }

        $tenant->makeCurrent();

        try {
            return $next($request);
        } finally {
            if (! app()->environment('testing')) {
                Tenant::forgetCurrent();
                DB::setDefaultConnection(config('multitenancy.landlord_database_connection_name'));
            }
        }
    }
}
