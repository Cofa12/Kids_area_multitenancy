<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SafaricomRequest;
use App\Models\HeEntry;
use App\Models\SdpResponse;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\SubscriptionAction;
use App\Enums\SubscriptionPlan;
use App\Services\V1\LoginService;
use App\Services\V1\SubscriptionHandling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @psalm-suppress UnusedClass
 */
class LandingPage extends Controller
{
    public function __construct(
        private SubscriptionHandling $subscriptionHandling,
        private LoginService $loginService
    ) {
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
        // Some operators send the plan ID as transactionId rather than productId.
        $productId     = $request->get('productId') ?: $transactionId ?: $packName;
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

        // Build callback payload
        $callbackPayload = [
            'transaction_id' => $transactionId,
            'vendor_name'    => $vendorName,
            'circle'         => $circle,
            'amount'         => $amount,
            'action'         => $action,
            'operator'       => $operator,
            'channel'        => $channel,
            'pack_name'      => $packName,
            'plan_id'        => $productId,  // store the productId as plan_id
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'language'       => $language,
        ];

        // ── Find existing user by phone ───────────────────────────────────────
        $user = User::where('phone', $msisdn)->first();

        // Determine subscription duration based on productId (plan ID)
        if ($subscriptionAction === SubscriptionAction::SUBSCRIBED_NEW || $subscriptionAction === SubscriptionAction::SUBSCRIBED_RENEWAL) {
            $callbackPayload['subscription_status'] = 1;
            $days = SubscriptionPlan::getDaysForPlan($productId);

            if ($user && $user->expiration_date && $user->expiration_date->isFuture()) {
                $callbackPayload['expiration_date'] = $user->expiration_date->addDays($days);
            } else {
                $callbackPayload['expiration_date'] = now()->addDays($days);
            }
        } elseif ($subscriptionAction === SubscriptionAction::UNSUBSCRIPTION) {
            $callbackPayload['subscription_status'] = 0;
            $callbackPayload['expiration_date'] = now();
        }

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

    /**
     * Header Enrichment (HE) entry point.
     * Checks X-MSISDN header to determine user status and redirects accordingly:
     * - Subscribed / renewal -> https://kids-station.com.ng/welcome?token={access_token}
     * - Unsubscribed -> https://kids-station.com.ng/new-subscription
     * - Phone not found / missing header -> https://kids-station.com.ng/guest
     *
     * Each request is logged into the he_entries database table.
     */
    public function heEntry(Request $request): RedirectResponse
    {
        $msisdn = $request->header('X-MSISDN') ?? $request->header('x-msisdn');

        $headersReceived = [];
        foreach ($request->headers->all() as $name => $values) {
            $headersReceived[strtolower($name)] = is_array($values) ? implode(', ', $values) : $values;
        }

        if (empty($msisdn)) {
            $redirectUrl = 'https://kids-station.com.ng/guest';
            $status = 'missing_msisdn';
        } else {
            $user = User::where('phone', $msisdn)->first();

            if (! $user) {
                $redirectUrl = 'https://kids-station.com.ng/guest';
                $status = 'user_not_found';
            } elseif ($this->subscriptionHandling->canAccessContent($user)) {
                $tokens = $this->loginService->Authenticate(['phone' => $user->phone]);
                $accessToken = $tokens['access_token'];
                $redirectUrl = 'https://kids-station.com.ng/welcome?token=' . urlencode($accessToken);
                $status = 'subscribed';
            } else {
                $redirectUrl = 'https://kids-station.com.ng/new-subscription';
                $status = 'unsubscribed';
            }
        }

        HeEntry::create([
            'msisdn' => $msisdn,
            'headers' => $headersReceived,
            'query_params' => $request->query(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'redirect_url' => $redirectUrl,
            'status' => $status,
        ]);

        return redirect($redirectUrl, 302);
    }

    /**
     * Header Enrichment (HE) echo endpoint.
     * Returns list of headers received in the request.
     */
    public function heEcho(Request $request): JsonResponse
    {
        $headersReceived = [];
        foreach ($request->headers->all() as $name => $values) {
            $headersReceived[strtolower($name)] = is_array($values) ? implode(', ', $values) : $values;
        }

        return response()->json([
            'success' => true,
            'headersReceived' => $headersReceived,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * SDP redirect entry point for MTN Nigeria.
     * Publicly accessible — no token or tenant header required.
     * Accepts plan URL / plan ID and trfsrc parameter, generates a trxId,
     * appends them to the destination URL, and redirects (302).
     */
    public function sdpRedirect(Request $request): RedirectResponse
    {
        $this->ensureTenantContext();

        $planUrls = [
            '23410220000051559' => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051559?trfsrc=',
            '2341022000051559'  => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051559?trfsrc=',
            '1day'             => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051559?trfsrc=',

            '23410220000051560' => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051560?trfsrc=',
            '2341022000051560'  => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051560?trfsrc=',
            '1week'            => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051560?trfsrc=',

            '23410220000051561' => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051561?trfsrc=',
            '2341022000051561'  => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051561?trfsrc=',
            '2weeks'           => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051561?trfsrc=',

            '23410220000051562' => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051562?trfsrc=',
            '2341022000051562'  => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051562?trfsrc=',
            '1month'           => 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051562?trfsrc=',
        ];

        $targetUrl = $request->get('url') ?? $request->get('plan_url');
        $planId    = (string) ($request->get('plan_id') ?? $request->get('plan'));

        if (! $targetUrl && isset($planUrls[$planId])) {
            $targetUrl = $planUrls[$planId];
        }

        if (! $targetUrl) {
            $targetUrl = 'http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051559?trfsrc=';
        }

        // Generate transaction ID
        $trxId = (string) ($request->get('trxId') ?? $request->get('trx_id') ?? Str::random(16));

        // Resolve trfsrc parameter
        $trfsrc = (string) $request->get('trfsrc', '');

        // Parse query string of target URL
        $parsedUrl = parse_url($targetUrl);
        $queryArr = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryArr);
        }

        if ($trfsrc !== '') {
            $queryArr['trfsrc'] = $trfsrc;
        } elseif (! isset($queryArr['trfsrc'])) {
            $queryArr['trfsrc'] = '';
        }

        $queryArr['trxId'] = $trxId;

        // Reconstruct destination URL
        $scheme   = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host     = $parsedUrl['host'] ?? '';
        $port     = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $path     = $parsedUrl['path'] ?? '';
        $query    = '?' . http_build_query($queryArr);
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        $finalUrl = "{$scheme}{$host}{$port}{$path}{$query}{$fragment}";

        return redirect($finalUrl, 302);
    }

    /**
     * SDP success callback.
     * Publicly accessible — no token or tenant header required.
     * Stores response details into tenant database sdp_responses table.
     */
    public function sdpSuccess(Request $request): JsonResponse
    {
        $this->ensureTenantContext();

        $payload = $request->all();

        $trfsrc                  = $request->input('trfsrc');
        $trxId                   = $request->input('trxId') ?? $request->input('trx_id');
        $msisdn                  = $request->input('msisdn');
        $subscriptionId          = $request->input('subscriptionId') ?? $request->input('subscription_id');
        $subscriptionDescription = $request->input('subscriptionDescription') ?? $request->input('subscription_description');
        $autoRenew               = $request->input('autoRenew') ?? $request->input('auto_renew');

        if (is_bool($autoRenew)) {
            $autoRenew = $autoRenew ? 'true' : 'false';
        }

        $sdpStatus = $request->input('sdp_status') ?? $request->input('sdp_s') ?? $request->input('sdpStatus');

        $sdpResponse = SdpResponse::create([
            'trfsrc'                  => $trfsrc,
            'trxId'                   => $trxId,
            'msisdn'                  => $msisdn,
            'subscriptionId'          => $subscriptionId,
            'subscriptionDescription' => $subscriptionDescription,
            'autoRenew'               => $autoRenew !== null ? (string) $autoRenew : null,
            'sdp_status'              => $sdpStatus,
            'payload'                 => $payload,
        ]);

        return response()->json([
            'message' => 'Charged successfully',
            'data'    => $sdpResponse,
        ], JsonResponse::HTTP_OK);
    }

    /**
     * SDP failure callback.
     * Publicly accessible — no token or tenant header required.
     * Returns a JSON message indicating the charge failed.
     */
    public function sdpFailure(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Fail to charge',
        ], JsonResponse::HTTP_OK);
    }

    private function ensureTenantContext(): void
    {
        if (! Tenant::checkCurrent()) {
            $tenant = Tenant::whereRaw('LOWER(name) = ?', ['naijria'])
                ->orWhereRaw('LOWER(domain) = ?', ['naijria'])
                ->first();

            $tenant?->makeCurrent();
        }
    }

    private function generateRandomReferralCode(): string
    {
        do {
            $referralCode = (string) random_int(100000, 999999);
        } while (User::where('referral_code', $referralCode)->exists());

        return $referralCode;
    }
}
