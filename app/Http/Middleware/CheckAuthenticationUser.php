<?php

namespace App\Http\Middleware;

use App\Http\Exceptions\UnAuthorizedException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @psalm-suppress UnusedClass
 */
class CheckAuthenticationUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    /**
     * @psalm-suppress UnusedMethod
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(!$request->bearerToken())
            throw new UnAuthorizedException();

        $payload = JWTAuth::parseToken()->getPayload();
        $user = User::where('id',$payload['sub'])->where('phone',$payload['phone'])->first();

        if (!$user || ($user->expiration_date && $user->expiration_date>=today())) {
            throw new UnAuthorizedException();
        }
        return $next($request);
    }
}
