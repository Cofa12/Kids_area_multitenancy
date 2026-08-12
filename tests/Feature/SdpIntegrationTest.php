<?php

namespace Tests\Feature;

use App\Models\SdpResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SdpIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mtn.tenant', 'Test Tenant');
    }

    public function test_sdp_redirect_generates_trxid_appends_trfsrc_and_redirects_302(): void
    {
        $response = $this->get('http://backend.kids-station.com.ng/api/v1/sdp/redirect?url=http://mtn-nigeria-prod.mfilterit.org/sid/234102200008618/23410220000051559?trfsrc=&trfsrc=test_campaign_123');

        $response->assertStatus(302);
        $redirectUrl = $response->headers->get('Location');

        $this->assertStringContainsString('mtn-nigeria-prod.mfilterit.org', $redirectUrl);
        $this->assertStringContainsString('trfsrc=test_campaign_123', $redirectUrl);
        $this->assertStringContainsString('trxId=', $redirectUrl);
    }

    public function test_sdp_redirect_with_plan_id_maps_correct_url(): void
    {
        $response = $this->get('http://backend.kids-station.com.ng/api/v1/sdp/redirect?plan_id=23410220000051560&trfsrc=weekly_promo');

        $response->assertStatus(302);
        $redirectUrl = $response->headers->get('Location');

        $this->assertStringContainsString('23410220000051560', $redirectUrl);
        $this->assertStringContainsString('trfsrc=weekly_promo', $redirectUrl);
        $this->assertStringContainsString('trxId=', $redirectUrl);
    }

    public function test_sdp_success_saves_all_payload_keys_in_tenant_database(): void
    {
        $payload = [
            'trfsrc'                  => 'campaign_source_xyz',
            'trxId'                   => '5678ijhdxxxx',
            'msisdn'                  => '2348012345678',
            'subscriptionId'          => '234102200008618',
            'subscriptionDescription' => '23410220000051559',
            'autoRenew'               => 'true',
            'sdp_status'              => 'SUCCESS',
        ];

        $response = $this->postJson('http://backend.kids-station.com.ng/api/v1/sdp/success', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Charged successfully',
        ]);

        $this->assertDatabaseHas('sdp_responses', [
            'trfsrc'                  => 'campaign_source_xyz',
            'trxId'                   => '5678ijhdxxxx',
            'msisdn'                  => '2348012345678',
            'subscriptionId'          => '234102200008618',
            'subscriptionDescription' => '23410220000051559',
            'autoRenew'               => 'true',
            'sdp_status'              => 'SUCCESS',
        ], 'tenant');
    }
}
