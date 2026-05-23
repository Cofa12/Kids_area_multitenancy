<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\V1\ChildPhotoRequest;
use App\Http\Requests\V1\RegisterFromLandingRequest;
use App\Http\Requests\V1\SafaricomLoginRequest;
use App\Http\Resources\V1\AuthenticatedUser;
use App\Models\ChildPhoto;
use App\Models\User;
use App\Services\V1\FileHandling;
use App\Services\V1\LoginService;
use App\Services\V1\SubscriptionHandling;
use App\Services\V1\WebsiteRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\Multitenancy\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @psalm-suppress UnusedClass
 */

class WebsiteController extends Controller
{
    public function __construct(private FileHandling $fileHandling, private LoginService $loginService, private WebsiteRegisterService $websiteRegisterService, private SubscriptionHandling $subscriptionHandling)
    {
        Tenant::current()->setConnection('tenant');
    }

    public function uploadChildPhoto(ChildPhotoRequest $request): JsonResponse
    {
        $url = $this->fileHandling->upload($request->file('photo'), 'child_photos');
        ChildPhoto::create([
            'image_url' => $url,
            'child_id' => auth()->user()->id,
            'description' => $request->description
        ]);
        return response()->json(['message' => 'Photo Uploaded Successfully'], JsonResponse::HTTP_CREATED);
    }

    public function checkUserExists(RegisterFromLandingRequest $request): AuthenticatedUser|JsonResponse
    {
        // TTL = 0 → non-expiring access token for the website registration flow.
        // The user will use this token immediately to call updateProfile.
        $tokens = $this->loginService->Authenticate([
            'phone'    => $request['phone'],
        ], 0);

        if (!$this->isUserExpired(auth()->user())) {
            return response()->json([
                'message' => 'this user is expired',
            ], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return new AuthenticatedUser($tokens, auth()->user());
    }

    public function login(SafaricomLoginRequest $request)
    {

        $tokens = $this->loginService->Authenticate($request->toArray());

        if (!$this->isUserExpired(auth()->user()))
            return response()->json([
                'message'=>'this user is expired'
            ],JsonResponse::HTTP_UNAUTHORIZED);

        return new AuthenticatedUser($tokens, auth()->user());
    }

    public function updateProfile(UpdateProfileRequest $request):JsonResponse
    {
        $user = Auth::user();
        $user->update($request->all());

        return response()->json([
            'message'=>'Profile is updated Successfully'
        ],JsonResponse::HTTP_OK);
    }

    public function getDate()
    {
        return response()->json(['date' => now()->toDateString()]);
    }

    public function isUserExpired(User $user) : bool
    {
        return $user->subscription_status;   
    }


}
