<?php

namespace App\Http\Middleware;

use App\Http\Exceptions\UnAuthorizedException;
use App\Models\LandlordUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class LandlordAuthenticationUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!$request->bearerToken())
            throw new UnAuthorizedException();

        $payload = JWTAuth::parseToken()->getPayload();
        $user = LandlordUser::where('id',$payload['sub'])->where('phone',$payload['phone'])->first();

        if (!$user || ($user->expiration_date && $user->expiration_date>=today())) {
            throw new UnAuthorizedException();
        }

        return $next($request);
    }
}
