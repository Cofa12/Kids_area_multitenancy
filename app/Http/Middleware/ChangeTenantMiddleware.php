<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class ChangeTenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantIdentifier = $request->input('tenant')
            ?? $request->header('X-Tenant')
            ?? $request->query('tenant');

        if (!$tenantIdentifier) {
            return response()->json([
                'message' => 'Tenant identifier is required'
            ], 400);
        }

        $tenant = Tenant::where('name', $tenantIdentifier)
            ->orWhere('domain', $tenantIdentifier)
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found'
            ], 404);
        }

        $tenant->makeCurrent();

        $response = $next($request);

        Tenant::forgetCurrent();
        DB::setDefaultConnection('landlord');

        return $response;
    }
}