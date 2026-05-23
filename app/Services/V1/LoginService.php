<?php

namespace App\Services\V1;

use App\Http\Exceptions\UnAuthenticatedUserException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginService
{
    /**
     * Authenticate a user and return a token pair.
     *
     * @param  array       $credentials  Fields passed to the guard's attempt().
     * @param  int|null    $ttl          Access-token TTL in minutes.
     *                                   Pass 0 for a non-expiring token (registration flow).
     *                                   Pass null to use the default jwt.ttl config value.
     * @return array{access_token: string, expires_in: int, refresh_token: string, refresh_expires_in: int}
     */
    public function Authenticate(array $credentials, ?int $ttl = null): array
    {
        $guard = 'admin';
        if (Tenant::current()) {
            $guard = 'api';
        }

        // Set access-token TTL before logging the user in.
        // TTL = 0 means the token never expires (used by the website registration flow).
        if ($ttl !== null) {
            JWTAuth::factory()->setTTL($ttl === 0 ? null : $ttl);
        }

        if (array_key_exists('password', $credentials)) {
            $token = auth($guard)->attempt($credentials);
        } elseif (array_key_exists('phone', $credentials)) {
            $user = User::where('phone', $credentials['phone'])->first();

            if (!$user) {
                throw new UnAuthenticatedUserException();
            }

            $token = auth($guard)->login($user);
        } else {
            throw new UnAuthenticatedUserException();
        }

        if (!$token) {
            throw new UnAuthenticatedUserException();
        }

        // Always generate the refresh token with the configured refresh TTL.
        JWTAuth::factory()->setTTL(config('jwt.refresh_ttl'));
        $refreshToken = JWTAuth::claims([
            'token_type' => 'refresh',
        ])->fromUser(auth($guard)->user());

        $effectiveTtl = $ttl ?? config('jwt.ttl');

        return [
            'access_token'      => $token,
            'expires_in'        => $effectiveTtl,
            'refresh_token'     => $refreshToken,
            'refresh_expires_in' => config('jwt.refresh_ttl'),
        ];
    }
}
