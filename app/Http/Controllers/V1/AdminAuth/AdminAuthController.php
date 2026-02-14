<?php

namespace App\Http\Controllers\V1\AdminAuth;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Resources\V1\AuthenticatedUser;
use App\Services\V1\LoginService;
use App\Services\V1\RefreshTokenService;
use App\Services\V1\RegisterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

class AdminAuthController extends Controller
{
    public function __construct(private LoginService $loginService, private RefreshTokenService $refreshTokenService)
    {

    }


    public function login(LoginRequest $request)
    {
        $tokens = $this->loginService->Authenticate($request->toArray());
        return new AuthenticatedUser($tokens, auth('admin')->user());
    }

    public function refreshToken(\App\Http\Requests\V1\RefreshTokenRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $this->refreshTokenService->handle($request->refreshToken);
        return response()->json($data, \Illuminate\Http\JsonResponse::HTTP_OK);
    }
}
