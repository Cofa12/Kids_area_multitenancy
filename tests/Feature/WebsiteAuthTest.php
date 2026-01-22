<?php

namespace Tests\Feature;

use App\Http\Exceptions\UnAuthenticatedUserException;
use App\Models\Campaign;
use App\Models\User;
use App\Services\V1\LoginService;
use App\Services\V1\SubscriptionHandling;
use App\Services\V1\WebsiteRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class WebsiteAuthTest extends TestCase
{
    use RefreshDatabase;
    private array $headers = [
        'Accept' => 'application/json',
        'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A',
        'Accept-Language' => 'en',
    ];
    public function test_register_returns_authenticated_user_on_success(): void
    {
        $campaignOwner = User::factory()->create();
        $campaign = Campaign::create([
            'country' => 'EG',
            'operator' => 'Vodafone',
            'service' => 'KidsArea',
            'start_date' => now()->toDateString(),
            'user_id' => $campaignOwner->id,
        ]);
        $refOwner = User::factory()->create([
            'referral_code' => '123456',
        ]);

        $user = User::factory()->create([
            'name' => 'cofa',
        ]);

        $this->mock(WebsiteRegisterService::class, function ($mock) use ($user) {
            $mock->shouldReceive('register')
                ->once()
                ->andReturnNull();
        });

        $tokens = [
            'access_token' => 'access.xxx.yyy',
            'expires_in' => 60,
            'refresh_token' => 'refresh.aaa.bbb',
            'refresh_expires_in' => 120,
        ];
        $this->mock(LoginService::class, function ($mock) use ($tokens) {
            $mock->shouldReceive('Authenticate')
                ->once()
                ->andReturn($tokens);
        });

        $this->actingAs($user);

        $payload = [
            'name' => 'cofa',
            'phone' => '+201000000000',
            'password' => 'Aa12345#',
        ];

        $res = $this->postJson('/api/v1/website/register', $payload, $this->headers);

        $res->assertOk();
        $res->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'phone',
                'created_at'
            ],
            'access_token',
            'expires_in',
            'refresh_token',
            'refresh_expires_in'
        ]);
        $res->assertJsonFragment([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
        ]);
    }
    public function test_return_un_authenticated_user_if_expiration_date_comes(): void
    {
        User::factory()->create([
            'expiration_date' => today()->subDay(),
            'name' => 'cofa33',
        ]);


        $res = $this->getJson('/api/v1/analytics', headers: $this->headers);

        $res->assertStatus(Response::HTTP_UNAUTHORIZED);

    }
    public function test_register_with_referral_creates_renewal_for_owner(): void
    {
        $campaignOwner = User::factory()->create();
        $campaign = Campaign::create([
            'country' => 'EG',
            'operator' => 'Vodafone',
            'service' => 'KidsArea',
            'start_date' => now()->toDateString(),
            'user_id' => $campaignOwner->id,
        ]);

        $refOwner = User::factory()->create([
            'referral_code' => '654321',
        ]);

        User::factory()->create([
            'name' => 'cofa-ref',
            'phone' => null,
        ]);


        $payload = [
            'name' => 'cofa-ref',
            'phone' => '+201099999999',
            'password' => 'Aa12345#',
            'campaign_id' => $campaign->id,
            'referral_code' => $refOwner->referral_code,
        ];

        $res = $this->postJson('/api/v1/website/register', $payload, $this->headers);

        $res->assertOk();

        $refOwner->refresh();
        $this->assertDatabaseCount('referrals', 1, 'tenant');
        $this->assertEquals(1, $refOwner->referrals()->count());

        $referral = $refOwner->referrals()->latest('referred_at')->first();
        $this->assertNotNull($referral);
        $this->assertEquals($refOwner->id, $referral->owner_id);
        $this->assertEquals(
            $refOwner->created_at->copy()->addDay()->toDateTimeString(),
            $referral->referred_at->toDateTimeString()
        );
    }
    public function test_login_returns_authenticated_user_when_subscribed(): void
    {
        $user = User::factory()->create([
            'phone' => '+201012345671',
        ]);

        $tokens = [
            'access_token' => 'access.xxx.yyy',
            'expires_in' => 60,
            'refresh_token' => 'refresh.aaa.bbb',
            'refresh_expires_in' => 120,
        ];
        $this->mock(LoginService::class, function ($mock) use ($tokens) {
            $mock->shouldReceive('Authenticate')
                ->once()
                ->andReturn($tokens);
        });

        $this->mock(SubscriptionHandling::class, function ($mock) {
            $mock->shouldReceive('canAccessContent')->andReturn(true);
        });

        $this->actingAs($user);

        $payload = [
            'phone' => $user->phone,
            'password' => 'Aa12345#',
        ];

        $res = $this->postJson('/api/v1/website/login', $payload, $this->headers);

        $res->assertOk();
        $res->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email',
                'phone',
                'created_at'
            ],
            'access_token',
            'expires_in',
            'refresh_token',
            'refresh_expires_in'
        ]);
    }

    //    public function test_login_throws_exception_when_not_subscribed(): void
//    {
//        $this->withoutExceptionHandling();
//
//        $user = User::factory()->create([
//            'phone' => '+201055555555',
//        ]);
//
//        $this->mock(LoginService::class, function ($mock) {
//            $mock->shouldReceive('Authenticate')->andReturn([
//                'access_token' => 't',
//                'expires_in' => 60,
//                'refresh_token' => 'rt',
//                'refresh_expires_in' => 120,
//            ]);
//        });
//
//        $this->mock(SubscriptionHandling::class, function ($mock) {
//            $mock->shouldReceive('canAccessContent')->andReturn(false);
//        });
//
//        $this->actingAs($user);
//
//        $this->expectException(UnAuthenticatedUserException::class);
//
//        $payload = [
//            'phone' => $user->phone,
//            'password' => 'Aa12345#',
//        ];
//
//        $this->postJson('/api/v1/website/login', $payload, $this->headers);
//    }
}
