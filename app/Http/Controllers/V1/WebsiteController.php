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
use App\Http\Exceptions\UnAuthenticatedUserException;
use App\Http\Exceptions\UserNotRegisteredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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

    public function checkUserExists(RegisterFromLandingRequest $request): AuthenticatedUser|JsonResponse
    {
        // TTL = 0 → non-expiring access token for the website registration flow.
        // The user will use this token immediately to call updateProfile.
        try {
            $tokens = $this->loginService->Authenticate([
                'phone'    => $request['phone'],
            ], 0);
        } catch (UnAuthenticatedUserException $e) {
            throw new UserNotRegisteredException();
        }

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

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = User::where('phone', $request->phone)->first();

        // Guard 1: user not found
        if (!$user) {
            return response()->json([
                'message' => "User doesn't exist",
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Guard 2: profile already updated (name column has a value)
        if (!empty($user->name)) {
            return response()->json([
                'message' => 'This user is already existed',
            ], JsonResponse::HTTP_CONFLICT);
        }

        if (empty($user->referral_code)) {
            $user->referral_code = $this->generateRandomReferralCode();
            $user->save();
        }

        $user->update($request->except('phone'));

        return response()->json([
            'message' => 'Profile is updated Successfully',
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'referral_code' => $user->referral_code,
            'number_of_referrals' => $user->number_of_referrals,
        ], JsonResponse::HTTP_OK);
    }

    public function getDate()
    {
        return response()->json(['date' => now()->toDateString()]);
    }

    public function isUserExpired(User $user) : bool
    {
        return $user->subscription_status;   
    }


        private function generateRandomReferralCode(): string
    {
        do {
            $referralCode = (string) random_int(100000, 999999);
        } while (User::where('referral_code', $referralCode)->exists());

        return $referralCode;
    }

}
