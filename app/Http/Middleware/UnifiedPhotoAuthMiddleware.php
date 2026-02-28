<?php

namespace App\Http\Middleware;

use App\Http\Exceptions\UnAuthorizedException;
use App\Models\LandlordUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class UnifiedPhotoAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
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

        // Try Landlord User first (checking landlord connection)
        $landlordUser = LandlordUser::where('id', $payload['sub'])
            ->where('phone', $payload['phone'])
            ->first();

        // Follow existing LandlordAuthenticationUser logic for expiration if any
        if ($landlordUser) {
            // In LandlordAuthenticationUser: if ($user->expiration_date && $user->expiration_date>=today()) throw UnAuthorizedException
            // We only proceed if it doesn't match that condition
            if (!($landlordUser->expiration_date && $landlordUser->expiration_date >= today())) {
                return $next($request);
            }
        }

        // Try Tenant User (checking current tenant connection)
        // Note: ChangeTenantMiddleware should have already run to set the connection
        $tenantUser = User::where('id', $payload['sub'])
            ->where('phone', $payload['phone'])
            ->first();

        if ($tenantUser) {
            return $next($request);
        }

        throw new UnAuthorizedException();
    }
}
