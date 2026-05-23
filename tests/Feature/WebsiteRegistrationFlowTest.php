<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\V1\LoginService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

/**
 * Tests for the website registration flow:
 *
 *   Step 1 – POST /api/v1/website/checkuser/exists
 *             ↳ Looks up the subscriber by phone.
 *             ↳ Returns a NON-EXPIRING JWT (expires_in = 0) if the user is active.
 *             ↳ Returns 401 if the user's subscription is inactive.
 *
 *   Step 2 – POST /api/v1/user/profile/update   (requires Bearer token from Step 1)
 *             ↳ Updates name and password for the authenticated user.
 *             ↳ Returns 200 on success.
 */
class WebsiteRegistrationFlowTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey = 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A';

    private function headers(?string $bearerToken = null): array
    {
        $h = [
            'Accept'          => 'application/json',
            'x-api-key'       => $this->apiKey,
            'Accept-Language' => 'en',
            'X-Tenant'        => 'test.localhost',
        ];

        if ($bearerToken !== null) {
            $h['Authorization'] = 'Bearer ' . $bearerToken;
        }

        return $h;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 1 – checkUserExists
    // ─────────────────────────────────────────────────────────────────────────

    public function test_check_existing_subscribed_user_returns_non_expiring_jwt(): void
    {
        $phone = '+201012345678';

        $user = User::factory()->create([
            'phone'               => $phone,
            'subscription_status' => true,
        ]);

        $fakeTokens = [
            'access_token'       => 'no-expire.access.token',
            'expires_in'         => 0,        // ← must be 0 for non-expiring
            'refresh_token'      => 'refresh.token.xyz',
            'refresh_expires_in' => 20160,
        ];

        $this->mock(LoginService::class, function ($mock) use ($fakeTokens) {
            $mock->shouldReceive('Authenticate')
                ->once()
                ->withArgs(function (array $credentials, int $ttl) {
                    // Verify the controller is requesting TTL = 0 (non-expiring)
                    return $ttl === 0;
                })
                ->andReturn($fakeTokens);
        });

        $this->actingAs($user);

        $response = $this->postJson(
            'http://test.localhost/api/v1/website/checkuser/exists',
            ['phone' => $phone],
            $this->headers()
        );

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'phone', 'created_at'],
            'access_token',
            'expires_in',
            'refresh_token',
            'refresh_expires_in',
        ]);

        // expires_in = 0 signals a non-expiring token
        $response->assertJsonFragment([
            'access_token' => 'no-expire.access.token',
            'expires_in'   => 0,
        ]);
    }

    public function test_check_existing_unsubscribed_user_returns_401(): void
    {
        $phone = '+201099999999';

        $user = User::factory()->create([
            'phone'               => $phone,
            'subscription_status' => false,   // ← inactive / unsubscribed
        ]);

        $this->mock(LoginService::class, function ($mock) {
            $mock->shouldReceive('Authenticate')
                ->once()
                ->andReturn([
                    'access_token'       => 'some.token',
                    'expires_in'         => 0,
                    'refresh_token'      => 'refresh.token',
                    'refresh_expires_in' => 20160,
                ]);
        });

        $this->actingAs($user);

        $response = $this->postJson(
            'http://test.localhost/api/v1/website/checkuser/exists',
            ['phone' => $phone],
            $this->headers()
        );

        $response->assertStatus(JsonResponse::HTTP_UNAUTHORIZED);
        $response->assertJsonFragment(['message' => 'this user is expired']);
    }

    public function test_check_existing_returns_422_when_phone_missing(): void
    {
        $response = $this->postJson(
            'http://test.localhost/api/v1/website/checkuser/exists',
            [],
            $this->headers()
        );

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['phone']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 2 – updateProfile  (uses the JWT from Step 1)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_update_profile_succeeds_with_valid_jwt(): void
    {
        $user = User::factory()->create([
            'phone'               => '+201012345679',
            'subscription_status' => true,
            'password'            => 'OldPass123#',
        ]);

        // Generate a real JWT for the user so the auth middleware accepts it
        $token = auth('api')->login($user);

        $response = $this->postJson(
            'http://test.localhost/api/v1/user/profile/update',
            [
                'name'     => 'NewName',
                'password' => 'NewPass123#',
            ],
            $this->headers($token)
        );

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonFragment(['message' => 'Profile is updated Successfully']);
    }

    public function test_update_profile_returns_401_without_token(): void
    {
        $response = $this->postJson(
            'http://test.localhost/api/v1/user/profile/update',
            [
                'name'     => 'SomeName',
                'password' => 'SomePass123#',
            ],
            $this->headers()   // no Bearer token
        );

        $response->assertStatus(JsonResponse::HTTP_UNAUTHORIZED);
    }

    public function test_update_profile_returns_422_when_name_missing(): void
    {
        $user  = User::factory()->create(['subscription_status' => true]);
        $token = auth('api')->login($user);

        $response = $this->postJson(
            'http://test.localhost/api/v1/user/profile/update',
            ['password' => 'NewPass123#'],   // name omitted
            $this->headers($token)
        );

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_update_profile_returns_422_when_password_missing(): void
    {
        $user  = User::factory()->create(['subscription_status' => true]);
        $token = auth('api')->login($user);

        $response = $this->postJson(
            'http://test.localhost/api/v1/user/profile/update',
            ['name' => 'SomeName'],   // password omitted
            $this->headers($token)
        );

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['password']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Full end-to-end flow (Step 1 → Step 2 with mocked auth service)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_full_registration_flow_check_then_update_profile(): void
    {
        $phone = '+201055512345';

        $user = User::factory()->create([
            'phone'               => $phone,
            'subscription_status' => true,
        ]);

        // ── Step 1: checkUserExists → get a non-expiring JWT ─────────────────
        $this->mock(LoginService::class, function ($mock) use ($user) {
            // Return a real JWT so Step 2 auth works
            $realToken = auth('api')->login($user);
            auth('api')->forgetUser();

            $mock->shouldReceive('Authenticate')
                ->once()
                ->andReturn([
                    'access_token'       => $realToken,
                    'expires_in'         => 0,
                    'refresh_token'      => 'refresh.token.abc',
                    'refresh_expires_in' => 20160,
                ]);
        });

        $this->actingAs($user);

        $step1 = $this->postJson(
            'http://test.localhost/api/v1/website/checkuser/exists',
            ['phone' => $phone],
            $this->headers()
        );

        $step1->assertStatus(JsonResponse::HTTP_OK);
        $step1->assertJsonFragment(['expires_in' => 0]);

        $accessToken = $step1->json('access_token');
        $this->assertNotEmpty($accessToken);

        // ── Step 2: updateProfile using the token from Step 1 ────────────────
        $step2 = $this->postJson(
            'http://test.localhost/api/v1/user/profile/update',
            [
                'name'     => 'RegisteredUser',
                'password' => 'StrongPass1#',
            ],
            $this->headers($accessToken)
        );

        $step2->assertStatus(JsonResponse::HTTP_OK);
        $step2->assertJsonFragment(['message' => 'Profile is updated Successfully']);

        // Confirm name was persisted
        $this->assertDatabaseHas('users', [
            'phone' => $phone,
            'name'  => 'RegisteredUser',
        ], 'tenant');
    }

    public function test_e2e_subscription_callback_registration_flow(): void
    {
        $msisdn = '94721847130';
        $transactionId = '66463227898';

        // ── Step 1: Callback to subscribe a new user ───────────────────────
        // We will call the new POST /callback-handler/api/callback/ route
        // directly, passing the data with quotes inside strings as in the curl
        $callbackPayload = [
            'vendorName'    => '"HUxxxL"',
            'circle'        => '"HUxxxxL"',
            'msisdn'        => '"94721847130"',
            'amount'        => '"5"',
            'transactionId' => '"66463227898"',
            'action'        => '"Subscription"',
            'userStatus'    => '"1"',
            'operator'      => '"Hxxxx"',
            'channel'       => '"WEB"',
            'packName'      => '"1003"',
            'startDate'     => '"1775551549537"',
            'endDate'     => '"1775551549537"',
            'language'      => '"English"',
        ];

        $callbackResponse = $this->post(
            'http://test.localhost/callback-handler/api/callback',
            $callbackPayload,
            [
                'Accept'   => 'application/json',
                'x-api-key' => $this->apiKey,
                'X-Tenant' => 'test.localhost',
            ]
        );

        $callbackResponse->assertStatus(JsonResponse::HTTP_OK);
        $callbackResponse->assertJsonFragment(['message' => 'User created and subscribed']);

        // Assert user exists with cleaned values in database
        $this->assertDatabaseHas('users', [
            'phone'               => $msisdn,
            'subscription_status' => 1,
            'transaction_id'      => $transactionId,
        ], 'tenant');

        $user = User::where('phone', $msisdn)->first();
        $this->assertNotNull($user);

        // ── Step 2: checkUserExists → returns a non-expiring JWT ────────────
        $this->mock(LoginService::class, function ($mock) use ($user) {
            // Generate a real JWT
            $realToken = auth('api')->login($user);
            auth('api')->forgetUser();

            $mock->shouldReceive('Authenticate')
                ->once()
                ->withArgs(function (array $credentials, int $ttl) {
                    return $ttl === 0;
                })
                ->andReturn([
                    'access_token'       => $realToken,
                    'expires_in'         => 0,
                    'refresh_token'      => 'refresh.token.abc',
                    'refresh_expires_in' => 20160,
                ]);
        });

        $this->actingAs($user);

        $checkResponse = $this->postJson(
            'http://test.localhost/api/v1/website/checkuser/exists',
            ['phone' => $msisdn],
            $this->headers()
        );

        $checkResponse->assertStatus(JsonResponse::HTTP_OK);
        $checkResponse->assertJsonFragment(['expires_in' => 0]);
        $accessToken = $checkResponse->json('access_token');
        $this->assertNotEmpty($accessToken);

        // ── Step 3: updateProfile → updates profile successfully ────────────
        $updateResponse = $this->postJson(
            'http://test.localhost/api/v1/user/profile/update',
            [
                'name'     => 'PremiumUserUpdated',
                'password' => 'NewPassword123#',
            ],
            $this->headers($accessToken)
        );

        $updateResponse->assertStatus(JsonResponse::HTTP_OK);
        $updateResponse->assertJsonFragment(['message' => 'Profile is updated Successfully']);

        // Verify update in database
        $this->assertDatabaseHas('users', [
            'phone' => $msisdn,
            'name'  => 'PremiumUserUpdated',
        ], 'tenant');

        // ── Step 4: Callback to unsubscribe user ─────────────────────────────
        $unsubPayload = [
            'vendorName'    => '"HUxxxL"',
            'circle'        => '"HUxxxxL"',
            'msisdn'        => '"94721847130"',
            'amount'        => '"5"',
            'transactionId' => '"DifferentTxn999"',
            'action'        => '"Unsubscription"',
            'userStatus'    => '"0"',
            'operator'      => '"Hxxxx"',
            'channel'       => '"WEB"',
            'packName'      => '"1003"',
            'startDate'     => '"1775551549537"',
            'endDate'     => '"1775551549537"',
            'language'      => '"English"',
        ];

        $unsubResponse = $this->post(
            'http://test.localhost/callback-handler/api/callback/',
            $unsubPayload,
            [
                'Accept'   => 'application/json',
                'x-api-key' => $this->apiKey,
                'X-Tenant' => 'test.localhost',
            ]
        );

        $unsubResponse->assertStatus(JsonResponse::HTTP_OK);
        $unsubResponse->assertJsonFragment(['message' => 'User unsubscribed successfully']);

        // Verify subscription is false in database
        $this->assertDatabaseHas('users', [
            'phone'               => $msisdn,
            'subscription_status' => 0,
        ], 'tenant');
    }
}
