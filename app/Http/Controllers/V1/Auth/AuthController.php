<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Requests\V1\RefreshTokenRequest;
use App\Http\Requests\V1\RegisterFromLandingRequest;
use App\Http\Resources\V1\AuthenticatedUser;
use App\Services\V1\LoginService;
use App\Services\V1\RefreshTokenService;
use App\Services\V1\RegisterService;
use Illuminate\Http\JsonResponse;

/**
 * @psalm-suppress UnusedClass
 */
class AuthController extends Controller
{
    public function __construct(private RegisterService $registerService, private LoginService $loginService, private RefreshTokenService $refreshTokenService)
    {
    }

    public function login(LoginRequest $request)
    {
        $tokens = $this->loginService->Authenticate($request->toArray());
        return new AuthenticatedUser($tokens, auth()->user());
    }

    public function registerFromLandingPage(RegisterFromLandingRequest $request): AuthenticatedUser
    {
        $tokens = $this->registerService->register($request->toArray());
        return new AuthenticatedUser($tokens, auth()->user());
    }

    public function refreshToken(RefreshTokenRequest $request): JsonResponse
    {
        $data = $this->refreshTokenService->handle($request->refreshToken);
        return response()->json($data, JsonResponse::HTTP_OK);

    }


}
