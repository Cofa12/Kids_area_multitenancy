<?php

namespace Feature;

use Database\Seeders\TempAccountWebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use App\Models\Campaign;
use App\Models\User;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CampaignTest extends TestCase
{
    // use RefreshDatabase;
    private function generateAuthToken(): string
    {
        TempAccountWebsiteSeeder::run();
        return auth()->attempt(['phone' => '+2010123456789', 'password' => 'mK5lj2jlk##']);
    }
    public function test_get_all_campaigns_using_resource(): void
    {
        $language = 'en';
        $user = User::create([
            'name' => 'cofa',
            'phone' => '+2010123456789',
            'password' => 'mK5lj2jlk##'
        ]);

        $token = JWTAuth::fromUser($user);
        Campaign::create([
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-11-20',
            'status' => 'active',
            'user_id' => $user->id,
        ]);

        Campaign::create([
            'country' => 'kenya',
            'operator' => 'safaricom',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-12-20',
            'status' => 'active',
            'user_id' => $user->id,
        ]);

        $sut = $this->getJson('/api/v1/dashboard/campaigns', [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $sut->assertStatus(JsonResponse::HTTP_OK);


        $sut->assertJsonStructure([
            '*' => [
                'id',
                'country',
                'operator',
                'service',
                'start_date',
                'end_date'
            ]
        ]);
    }
    public function test_end_campaign_status(): void
    {
        $language = 'en';
        $user = User::create([
            'name' => 'cofa',
            'phone' => '+2010123456789',
            'password' => 'mK5lj2jlk##'
        ]);
        $token = JWTAuth::fromUser($user);

        $campaign = Campaign::create([
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-11-20',
            'status' => 'active',
            'user_id' => $user->id,
        ]);

        $sut = $this->putJson('/api/v1/dashboard/campaigns/' . $campaign->id . '/end', [], [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $sut->assertStatus(JsonResponse::HTTP_OK);
        $sut->assertJson([
            'message' => $language == 'en' ? 'Campaign ended successfully' : 'تم إنهاء الحملة بنجاح'
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'ended'
        ], 'tenant');
    }
    public function test_pause_campaign_status(): void
    {
        $language = 'en';
        $user = User::create([
            'name' => 'cofa',
            'phone' => '+2010123456789',
            'password' => 'mK5lj2jlk##'
        ]);
        $token = JWTAuth::fromUser($user);

        $campaign = Campaign::create([
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-11-20',
            'status' => 'active',
            'user_id' => $user->id,
        ]);

        $sut = $this->putJson('/api/v1/dashboard/campaigns/' . $campaign->id . '/pause', [], [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $sut->assertStatus(JsonResponse::HTTP_OK);
        $sut->assertJson([
            'message' => $language == 'en' ? 'Campaign paused successfully' : 'تم إيقاف الحملة مؤقتا'
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'paused'
        ], 'tenant');
    }
    public function test_active_campaign_status(): void
    {
        $language = 'en';
        $user = User::create([
            'name' => 'cofa',
            'phone' => '+2010123456789',
            'password' => 'mK5lj2jlk##'
        ]);
        $token = JWTAuth::fromUser($user);

        $campaign = Campaign::create([
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-11-20',
            'status' => 'active',
            'user_id' => $user->id,
        ]);

        $sut = $this->putJson('/api/v1/dashboard/campaigns/' . $campaign->id . '/active', [], [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $sut->assertStatus(JsonResponse::HTTP_OK);
        $sut->assertJson([
            'message' => $language == 'en' ? 'Campaign paused successfully' : 'تم إيقاف الحملة مؤقتا'
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'active'
        ], 'tenant');
    }
    public function test_update_campaign_cpa_success(): void
    {
        $language = 'en';
        $user = User::create([
            'name' => 'cofa',
            'phone' => '+2010123456789',
            'password' => 'mK5lj2jlk##'
        ]);
        $token = JWTAuth::fromUser($user);

        $campaign = Campaign::create([
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-11-20',
            'status' => 'active',
            'user_id' => $user->id,
            'cpa' => 100,
        ]);

        $payload = ['cpa' => 450];

        $sut = $this->putJson('/api/v1/dashboard/campaigns/' . $campaign->id . '/cpa', $payload, [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $sut->assertStatus(JsonResponse::HTTP_OK);
        $sut->assertJson([
            'message' => $language == 'en' ? 'CPA updated successfully' : 'تم تحديث قيمة CPA بنجاح'
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'cpa' => 450
        ], 'tenant');
    }
    public function test_update_campaign_cpa_validation_error(): void
    {
        $language = 'en';
        $user = User::create([
            'name' => 'cofa',
            'phone' => '+2010123456789',
            'password' => 'mK5lj2jlk##'
        ]);
        $token = JWTAuth::fromUser($user);

        $campaign = Campaign::create([
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-11-20',
            'status' => 'active',
            'user_id' => $user->id,
            'cpa' => 100,
        ]);

        // Missing cpa
        $sut = $this->putJson('/api/v1/dashboard/campaigns/' . $campaign->id . '/cpa', [], [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $token,
        ]);

        $sut->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $sut->assertJsonValidationErrors(['cpa']);
    }
    public function test_initialize_campaign(): void
    {
        $language = 'en';

        $lastRowId = Campaign::latest()->first()->id ?? 0;

        $sut = $this->postJson('/api/v1/dashboard/campaign/init', headers: [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
        ]);

        $sut->assertStatus(JsonResponse::HTTP_CREATED);
        $sut->assertJsonStructure([
            'message'
        ]);

        $sut->assertJson([
            'message' => $language == 'ar' ? 'تم إنشاد الحملة بنجاح' : 'Campaign initiated',
        ]);

        $this->assertDatabaseCount('campaigns', 1, 'tenant');
    }
    public function test_update_campaign_when_from_is_in_future(): void
    {
        $user = User::factory()->create();

        $language = 'en';
        $initializationCampaign = Campaign::create([
            'user_id' => $user->id,
        ]);
        $payload = [
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->addDay()->format('Y-m-d')),
            'end_date' => '2025-11-25',
            'agency_id' => 'hello',
            'cpa' => 260,
            'type' => 'billable',
            'campaign_id' => $initializationCampaign->id,
        ];


        $sut = $this->putJson('/api/v1/dashboard/campaigns', $payload, [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
        ]);

        $sut->assertStatus(JsonResponse::HTTP_CREATED);
        $sut->assertJsonStructure([
            'message'
        ]);

        $sut->assertJson([
            'message' => $language == 'en' ? 'campaign was created successfully' : 'تم انشاء الحملة بنجاح'
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $initializationCampaign->id,
            'country' => $payload['country'],
            'operator' => $payload['operator'],
            'service' => $payload['service'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
            'agency_id' => $payload['agency_id'],
            'cpa' => $payload['cpa'],
            'type' => $payload['type'],
            'status' => 'scheduled'
        ], 'tenant');
    }
    public function test_create_campaign_when_from_is_in_past(): void
    {
        $user = User::factory()->create();

        $language = 'en';
        $initializationCampaign = Campaign::create([
            'user_id' => $user->id,
        ]);

        $payload = [
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => '2025-11-10',
            'end_date' => '2025-11-15',
            'agency_id' => 'hello',
            'campaign_id' => $initializationCampaign->id,
        ];
        $sut = $this->putJson('/api/v1/dashboard/campaigns', $payload, [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
        ]);
        $sut->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
    }
    public function test_return_exception_when_has_agency_id_do_not_has_cpa(): void
    {
        $user = User::factory()->create();

        $language = 'en';
        $initializationCampaign = Campaign::create([
            'user_id' => $user->id,
        ]);
        $payload = [
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-11-10',
            'agency_id' => 'hello',
            'type' => 'billable',
            'campaign_id' => $initializationCampaign->id,
        ];

        $sut = $this->putJson('/api/v1/dashboard/campaigns', $payload, [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
        ]);
        $sut->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $sut->assertJson([
            'message' => $language == 'en' ? 'must has cpa with agency' : 'يجب إدخال cpa'
        ]);
    }
    public function test_return_created_when_has_agency_id_and_has_cpa(): void
    {
        $user = User::factory()->create();

        $language = 'en';
        $initializationCampaign = Campaign::create([
            'user_id' => $user->id,
        ]);

        $payload = [
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-11-10',
            'agency_id' => 'hello',
            'type' => 'billable',
            'cpa' => 260,
            'campaign_id' => $initializationCampaign->id,
        ];

        $sut = $this->putJson('/api/v1/dashboard/campaigns', $payload, [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
        ]);
        $sut->assertStatus(JsonResponse::HTTP_CREATED);
        $sut->assertJson([
            'message' => $language == 'en' ? 'campaign was created successfully' : 'تم انشاء الحملة بنجاح'
        ]);
    }
    public function test_return_exception_when_has_influencer_id_do_not_has_cost(): void
    {
        $user = User::factory()->create();

        $language = 'en';
        $initializationCampaign = Campaign::create([
            'user_id' => $user->id,
        ]);

        $payload = [
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => date(now()->format('Y-m-d')),
            'end_date' => '2025-11-10',
            'influencer_id' => 'hello',
            'type' => 'billable',
            'campaign_id' => $initializationCampaign->id,
        ];

        $sut = $this->putJson('/api/v1/dashboard/campaigns', $payload, [
            'Accept' => 'application/json',
            'Accept-language' => $language,
            'Authorization' => 'Bearer ' . $this->generateAuthToken(),
        ]);
        $sut->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        $sut->assertJson([
            'message' => $language == 'en' ? 'must has cost with influencer' : 'يجب إدخال cost'
        ]);
    }
}
