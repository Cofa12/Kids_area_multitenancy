<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SafaricomRequest;
use App\Models\User;
use App\Enums\SubscriptionAction;
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
        $vendorName    = $request->get('vendorName');
        $circle        = $request->get('circle');
        $amount        = $request->get('amount');
        $action        = $request->get('action');
        $operator      = $request->get('operator');
        $channel       = $request->get('channel');
        $packName      = $request->get('packName');
        $startDate     = $request->get('startDate');
        $endDate       = $request->get('endDate');
        $language      = $request->get('language');

        // Map incoming action + status to a concrete enum case
        $subscriptionAction = SubscriptionAction::fromCallback((string) $action, $userStatus);

        // ── Deduplication ────────────────────────────────────────────────────
        // If this exact transactionId has already been processed, skip silently.
        if (User::where('transaction_id', $transactionId)->exists()) {
            return response()->json(
                ['message' => 'Duplicate transaction, already processed'],
                JsonResponse::HTTP_OK
            );
        }

        // Build callback payload (exclude `subscription_status` by default so we can
        // decide whether to set it depending on the resolved action case).
        $callbackPayload = [
            'transaction_id' => $transactionId,
            'vendor_name'    => $vendorName,
            'circle'         => $circle,
            'amount'         => $amount,
            'action'         => $action,
            'operator'       => $operator,
            'channel'        => $channel,
            'pack_name'      => $packName,
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'language'       => $language,
        ];

        // Decide whether to include subscription_status in the payload:
        // - SUBSCRIBED_NEW => set subscription_status = 1 (new subscriber)
        // - SUBSCRIBED_RENEWAL => do NOT modify subscription_status (just update data)
        // - UNSUBSCRIBED => set subscription_status = 0 (deactivate)
        if ($subscriptionAction === SubscriptionAction::SUBSCRIBED_NEW) {
            $callbackPayload['subscription_status'] = 1;
        } elseif ($subscriptionAction === SubscriptionAction::UNSUBSCRIPTION) {
            $callbackPayload['subscription_status'] = 0;
        }

        // ── Find existing user by phone ───────────────────────────────────────
        $user = User::where('phone', $msisdn)->first();

        if ($user) {
            if (empty($user->referral_code)) {
                $callbackPayload['referral_code'] = $this->generateRandomReferralCode();
            }

            $user->update($callbackPayload);

            if ($subscriptionAction === SubscriptionAction::UNSUBSCRIPTION) {
                return response()->json(['message' => 'User is deactivated successfully'], JsonResponse::HTTP_OK);
            }

            return response()->json(['message' => 'User updated successfully'], JsonResponse::HTTP_OK);
        }

        // ── No user found ─────────────────────────────────────────────────────
        // Only create a new record when the action is a subscription.
        // No user found: only create when this is a new subscription.
        if ($subscriptionAction === SubscriptionAction::SUBSCRIBED_NEW) {
            $callbackPayload['referral_code'] = $this->generateRandomReferralCode();

            User::create(array_merge([
                'phone' => $msisdn,
            ], $callbackPayload));

            return response()->json(['message' => 'User created and subscribed'], JsonResponse::HTTP_OK);
        }

        // Unsubscribe callback for an unknown number – nothing to do.
        return response()->json(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    private function generateRandomReferralCode(): string
    {
        do {
            $referralCode = (string) random_int(100000, 999999);
        } while (User::where('referral_code', $referralCode)->exists());

        return $referralCode;
    }
}
