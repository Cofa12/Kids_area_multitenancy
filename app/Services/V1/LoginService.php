<?php

namespace App\Services\V1;

use App\Http\Exceptions\UnAuthenticatedUserException;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;
use Tymon\JWTAuth\Facades\JWTAuth;

class LoginService
{
    public function Authenticate(array $credentials): array
    {
        $guard = 'admin';
        if (Tenant::current())
            $guard = 'api';

        $token = auth($guard)->attempt($credentials);

        if (!$token)
            throw new UnAuthenticatedUserException();

        JWTAuth::factory()->setTTL(config('jwt.refresh_ttl'));
        $refreshToken = JWTAuth::claims([
            'token_type' => 'refresh'
        ])->fromUser(auth($guard)->user());

        return ['access_token' => $token, 'expires_in' => config('jwt.ttl'), 'refresh_token' => $refreshToken, 'refresh_expires_in' => config('jwt.refresh_ttl')];
    }
}
