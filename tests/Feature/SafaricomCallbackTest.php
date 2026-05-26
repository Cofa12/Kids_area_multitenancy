<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

/**
 * Tests for POST /api/v1/safaricom/callback
 *
 * The callback endpoint accepts form-urlencoded data from the operator and:
 *   - Creates a new user when userStatus=1 and phone is unknown
 *   - Updates subscription_status=true for an existing user when userStatus=1
 *   - Updates subscription_status=false for an existing user when userStatus=0
 *   - Skips (returns 200) when the same transactionId arrives again (deduplication)
 *   - Returns 404 when userStatus=0 and the phone is unknown
 *   - Returns 422 when required fields are missing
 */
class SafaricomCallbackTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey = 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A';

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function callbackPayload(array $overrides = []): array
    {
        return array_merge([
            'vendorName'    => 'HUxxxL',
            'circle'        => 'HUxxxxL',
            'msisdn'        => '94721847130',
            'amount'        => '5',
            'transactionId' => '66463227898',
            'action'        => 'Subscription',
            'userStatus'    => '1',
            'operator'      => 'Hxxxx',
            'channel'       => 'WEB',
            'packName'      => '1003',
            'startDate'     => '1775551549537',
            'endDate'       => '1775551549537',
            'language'      => 'English',
        ], $overrides);
    }

    private function postCallback(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->post(
            'http://test.localhost/api/v1/mtn/callback',
            $payload,
            [
                'Accept'   => 'application/json',
                'x-api-key' => $this->apiKey,
                'X-Tenant' => 'test.localhost',   // required by ChangeTenantMiddleware
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Subscription – new user
    // ─────────────────────────────────────────────────────────────────────────

    public function test_subscription_callback_creates_new_user_when_phone_unknown(): void
    {
        $msisdn = '94721847130';

        $response = $this->postCallback($this->callbackPayload([
            'msisdn'        => $msisdn,
            'transactionId' => 'TXN-NEW-001',
            'userStatus'    => '1',
        ]));

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonFragment(['message' => 'User created and subscribed']);

        $this->assertDatabaseHas('users', [
            'phone'               => $msisdn,
            'subscription_status' => 1,
            'transaction_id'      => 'TXN-NEW-001',
        ], 'tenant');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Subscription – existing user
    // ─────────────────────────────────────────────────────────────────────────

    public function test_subscription_callback_activates_existing_user(): void
    {
        $msisdn = '94721800000';

        User::factory()->create([
            'phone'               => $msisdn,
            'subscription_status' => false,
            'transaction_id'      => null,
        ]);

        $response = $this->postCallback($this->callbackPayload([
            'msisdn'        => $msisdn,
            'transactionId' => 'TXN-SUB-002',
            'userStatus'    => '1',
        ]));

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonFragment(['message' => 'User updated successfully']);

        $this->assertDatabaseHas('users', [
            'phone'               => $msisdn,
            'subscription_status' => 1,
            'transaction_id'      => 'TXN-SUB-002',
        ], 'tenant');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Unsubscription – existing user
    // ─────────────────────────────────────────────────────────────────────────

    public function test_unsubscription_callback_deactivates_existing_user(): void
    {
        $msisdn = '94721811111';

        User::factory()->create([
            'phone'               => $msisdn,
            'subscription_status' => true,
            'transaction_id'      => 'TXN-PREV-001',
        ]);

        $response = $this->postCallback($this->callbackPayload([
            'msisdn'        => $msisdn,
            'transactionId' => 'TXN-UNSUB-003',
            'action'        => 'Unsubscription',
            'userStatus'    => '0',
        ]));

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonFragment(['message' => 'User is deactivated successfully']);

        $this->assertDatabaseHas('users', [
            'phone'               => $msisdn,
            'subscription_status' => 0,
            'transaction_id'      => 'TXN-UNSUB-003',
        ], 'tenant');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Deduplication
    // ─────────────────────────────────────────────────────────────────────────

    public function test_duplicate_transaction_id_is_ignored(): void
    {
        $msisdn        = '94721822222';
        $transactionId = 'TXN-DUP-004';

        // Seed user with the transactionId already recorded
        User::factory()->create([
            'phone'               => $msisdn,
            'subscription_status' => true,
            'transaction_id'      => $transactionId,
        ]);

        // Same transactionId but asking to UNsubscribe – should be skipped
        $response = $this->postCallback($this->callbackPayload([
            'msisdn'        => $msisdn,
            'transactionId' => $transactionId,
            'action'        => 'Unsubscription',
            'userStatus'    => '0',
        ]));

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonFragment(['message' => 'Duplicate transaction, already processed']);

        // subscription_status must NOT have flipped
        $this->assertDatabaseHas('users', [
            'phone'               => $msisdn,
            'subscription_status' => 1,
        ], 'tenant');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Unsubscription – unknown phone
    // ─────────────────────────────────────────────────────────────────────────

    public function test_unsubscription_callback_returns_404_when_phone_unknown(): void
    {
        $response = $this->postCallback($this->callbackPayload([
            'msisdn'        => '00000000000',
            'transactionId' => 'TXN-GHOST-005',
            'action'        => 'Unsubscription',
            'userStatus'    => '0',
        ]));

        $response->assertStatus(JsonResponse::HTTP_NOT_FOUND);
        $response->assertJsonFragment(['message' => 'User not found']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validation
    // ─────────────────────────────────────────────────────────────────────────

    public function test_callback_returns_422_when_msisdn_is_missing(): void
    {
        $payload = $this->callbackPayload();
        unset($payload['msisdn']);

        $response = $this->postCallback($payload);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['msisdn']);
    }

    public function test_callback_returns_422_when_transaction_id_is_missing(): void
    {
        $payload = $this->callbackPayload();
        unset($payload['transactionId']);

        $response = $this->postCallback($payload);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['transactionId']);
    }

    public function test_callback_returns_422_when_user_status_is_missing(): void
    {
        $payload = $this->callbackPayload();
        unset($payload['userStatus']);

        $response = $this->postCallback($payload);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['userStatus']);
    }

    public function test_callback_returns_422_when_user_status_is_invalid(): void
    {
        $response = $this->postCallback($this->callbackPayload(['userStatus' => '2']));

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['userStatus']);
    }
}
