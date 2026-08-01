<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HeaderEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mtn.tenant', 'Test Tenant');
    }

    public function test_redirects_to_guest_when_x_msisdn_header_is_missing(): void
    {
        $response = $this->get('http://backend.kids-station.com.ng/api/v1/mtn/he/entry');

        $response->assertRedirect('https://kids-station.com.ng/guest');
    }

    public function test_redirects_to_guest_when_phone_not_found(): void
    {
        $response = $this->withHeaders([
            'X-MSISDN' => '2348000000000',
        ])->get('http://backend.kids-station.com.ng/api/v1/mtn/he/entry');

        $response->assertRedirect('https://kids-station.com.ng/guest');
    }

    public function test_redirects_to_welcome_with_token_when_user_is_subscribed(): void
    {
        User::create([
            'phone' => '2348011112222',
            'subscription_status' => 1,
            'expiration_date' => now()->addDays(7),
            'action' => 'SUBSCRIBED_NEW',
        ]);

        $response = $this->withHeaders([
            'X-MSISDN' => '2348011112222',
        ])->get('http://backend.kids-station.com.ng/api/v1/mtn/he/entry');

        $response->assertRedirectContains('https://kids-station.com.ng/welcome?token=');
    }

    public function test_redirects_to_welcome_with_token_when_user_is_renewal(): void
    {
        User::create([
            'phone' => '2348033334444',
            'subscription_status' => 1,
            'expiration_date' => now()->addDays(30),
            'action' => 'SUBSCRIBED_RENEWAL',
        ]);

        $response = $this->withHeaders([
            'X-MSISDN' => '2348033334444',
        ])->get('http://backend.kids-station.com.ng/api/v1/mtn/he/entry');

        $response->assertRedirectContains('https://kids-station.com.ng/welcome?token=');
    }

    public function test_redirects_to_new_subscription_when_user_is_unsubscribed(): void
    {
        User::create([
            'phone' => '2348055556666',
            'subscription_status' => 0,
            'expiration_date' => now(),
            'action' => 'UNSUBSCRIPTION',
        ]);

        $response = $this->withHeaders([
            'X-MSISDN' => '2348055556666',
        ])->get('http://backend.kids-station.com.ng/api/v1/mtn/he/entry');

        $response->assertRedirect('https://kids-station.com.ng/new-subscription');
    }

    public function test_redirects_to_new_subscription_when_user_subscription_is_expired(): void
    {
        User::create([
            'phone' => '2348077778888',
            'subscription_status' => 1,
            'expiration_date' => now()->subDay(),
            'action' => 'SUBSCRIBED_NEW',
        ]);

        $response = $this->withHeaders([
            'X-MSISDN' => '2348077778888',
        ])->get('http://backend.kids-station.com.ng/api/v1/mtn/he/entry');

        $response->assertRedirect('https://kids-station.com.ng/new-subscription');
    }

    public function test_handles_post_request_over_http(): void
    {
        User::create([
            'phone' => '2348099990000',
            'subscription_status' => 1,
            'expiration_date' => now()->addDays(5),
            'action' => 'SUBSCRIBED_NEW',
        ]);

        $response = $this->withHeaders([
            'X-MSISDN' => '2348099990000',
        ])->post('http://backend.kids-station.com.ng/api/v1/mtn/he/entry');

        $response->assertRedirectContains('https://kids-station.com.ng/welcome?token=');
    }
}
