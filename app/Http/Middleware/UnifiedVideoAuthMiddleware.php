<?php

namespace App\Http\Middleware;

use App\Http\Exceptions\UnAuthorizedException;
use App\Models\LandlordUser;
use App\Models\Tenant;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Unified authentication for the public video endpoints.
 *
 * - A landlord token (no X-Tenant required) → granted.
 * - A tenant token + X-Tenant header          → granted.
 * - No token                                  → 401.
 */
class UnifiedVideoAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->bearerToken()) {
            throw new UnAuthorizedException();
        }

        try {
            $payload = JWTAuth::parseToken()->getPayload();
        } catch (\Exception $e) {
            throw new UnAuthorizedException();
        }

        // 1. Try landlord DB first — no X-Tenant needed
        $landlordUser = LandlordUser::where('id', $payload['sub'])
            ->where('phone', $payload['phone'])
            ->first();

        if ($landlordUser) {
            // Respect the same expiration logic as LandlordAuthenticationUser
            if (!($landlordUser->expiration_date && $landlordUser->expiration_date >= today())) {
                return $next($request);
            }
        }

        // 2. Try tenant DB — requires X-Tenant header to identify the tenant
        $tenantIdentifier = $request->header('X-Tenant');

        if ($tenantIdentifier) {
            $tenant = Tenant::where('name', $tenantIdentifier)
                ->orWhere('domain', $tenantIdentifier)
                ->first();

            if ($tenant) {
                $tenant->makeCurrent();

                $tenantUser = User::where('id', $payload['sub'])
                    ->where('phone', $payload['phone'])
                    ->first();

                if ($tenantUser) {
                    return $next($request);
                }
            }
        }

        throw new UnAuthorizedException();
    }
}
