<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Exceptions\AlreadyExistedException;
use App\Http\Requests\V1\SafaricomRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * @psalm-suppress UnusedClass
 */

class LandingPage extends Controller
{
    public function __construct()
    {
    }

    /**
     * Safaricom callback: create or activate a user record using phone only.
     * Other fields remain placeholders until full registration.
     */
    public function callback(SafaricomRequest $request): JsonResponse
    {
        $userName = $request->get('userName');

        $user = User::where('name', $userName)->first();

        if ($user) {
            throw new AlreadyExistedException();
        }

        User::create([
            'name' => $userName,
            'subscription_status' => true
        ]);
        return response()->json(['message' => 'User Created Successfully'], 201);
    }

}
