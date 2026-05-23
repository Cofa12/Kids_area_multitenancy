<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SafaricomRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * @psalm-suppress UnusedClass
 */
class LandingPage extends Controller
{
    public function __construct()
    {
    }

    /**
     * Callback endpoint: handles subscription / unsubscription notifications.
     *
     * Required parameters:
     *   - msisdn        : the subscriber's phone number
     *   - transactionId : unique ID used to prevent duplicate processing
     *   - userStatus    : 1 = subscribed, 0 = unsubscribed
     */
    public function callback(SafaricomRequest $request): JsonResponse
    {
        $msisdn        = $request->get('msisdn');
        $transactionId = $request->get('transactionId');
        $userStatus    = (int) $request->get('userStatus'); // 1 = subscribed, 0 = unsubscribed

        // ── Deduplication ────────────────────────────────────────────────────
        // If this exact transactionId has already been processed, skip silently.
        if (User::where('transaction_id', $transactionId)->exists()) {
            return response()->json(
                ['message' => 'Duplicate transaction, already processed'],
                JsonResponse::HTTP_OK
            );
        }

        $subscriptionStatus = $userStatus === 1 ? 1 : 0;

        // ── Find existing user by phone ───────────────────────────────────────
        $user = User::where('phone', $msisdn)->first();

        if ($user) {
            $user->update([
                'subscription_status' => $subscriptionStatus,
                'transaction_id'      => $transactionId,
            ]);

            $msg = $subscriptionStatus ? 'User subscribed successfully' : 'User is deactivated successfully';
            return response()->json(['message' => $msg], JsonResponse::HTTP_OK);
        }

        // ── No user found ─────────────────────────────────────────────────────
        // Only create a new record when the action is a subscription.
        if ($subscriptionStatus) {
            User::create([
                'phone'               => $msisdn,
                'subscription_status' => $subscriptionStatus,
                'transaction_id'      => $transactionId,
            ]);

            return response()->json(['message' => 'User created and subscribed'], JsonResponse::HTTP_OK);
        }

        // Unsubscribe callback for an unknown number – nothing to do.
        return response()->json(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
    }
}
