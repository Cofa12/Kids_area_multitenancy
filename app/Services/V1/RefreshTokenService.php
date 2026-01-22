<?php

namespace App\Services\V1;

use App\Http\Exceptions\UnAuthorizedException;
use Exception;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Facades\JWTAuth;

class RefreshTokenService
{
    public function handle(string $refreshToken): array
    {

        $tokenHash = hash('sha256', $refreshToken);


        if (Cache::has('blacklisted_refresh_tokens:' . $tokenHash)) {
            throw new UnAuthorizedException('Refresh token is expired.');
        }

        try {
        $payload = JWTAuth::setToken($refreshToken)->getPayload();
        } catch (\Exception $e) {
            throw new UnAuthorizedException('refresh token is Expired');
        }

        if ($payload->get('token_type') !== 'refresh') {
            throw new Exception('Invalid token type');
        }


        $user = JWTAuth::setToken($refreshToken)->toUser();

        $accessToken = JWTAuth::fromUser($user);


        return [
            'accessToken' => $accessToken,
            'expiresIn' => config('jwt.ttl'),
        ];
    }
}
