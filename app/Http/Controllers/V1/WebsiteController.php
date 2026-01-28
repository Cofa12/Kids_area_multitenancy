<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Exceptions\UnAuthenticatedUserException;
use App\Http\Requests\V1\ChildPhotoRequest;
use App\Http\Requests\V1\RegisterFromLandingRequest;
use App\Http\Requests\V1\SafaricomLoginRequest;
use App\Http\Resources\V1\AuthenticatedUser;
use App\Models\ChildPhoto;
use App\Services\V1\FileHandling;
use App\Services\V1\LoginService;
use App\Services\V1\SubscriptionHandling;
use App\Services\V1\WebsiteRegisterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

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

    public function register(RegisterFromLandingRequest $request): AuthenticatedUser
    {
        $this->websiteRegisterService->register($request->toArray());

        $tokens = $this->loginService->Authenticate([
            'name' => $request['name'],
            'password' => $request['password']
        ]);


        return new AuthenticatedUser($tokens, auth()->user());

    }

    public function login(SafaricomLoginRequest $request)
    {

        $tokens = $this->loginService->Authenticate($request->toArray());

        return new AuthenticatedUser($tokens, auth()->user());
    }

    public function getDate()
    {
        return response()->json(['date' => now()->toDateString()]);
    }


}
