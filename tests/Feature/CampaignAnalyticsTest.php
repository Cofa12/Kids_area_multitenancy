<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Database\Seeders\TempAccountWebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CampaignAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function generateAuthToken(): string
    {
        TempAccountWebsiteSeeder::run();
        // Assuming the seeder creates this user or we can create a generic one
        $user = User::factory()->create(['phone' => '+2010123456789']);
        return JWTAuth::fromUser($user);
    }

    private function createCampaign(User $user, $startDate = null, $endDate = null)
    {
        return Campaign::create([
            'country' => 'egypt',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => $startDate ?? now()->subDays(10)->format('Y-m-d'),
            'end_date' => $endDate ?? now()->addDays(10)->format('Y-m-d'),
            'status' => 'active',
            'user_id' => $user->id,
            'agency_id' => 'agency_123',
            'cpa' => 10,
        ]);
    }

    public function test_get_daily_analytics_pagination(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create 15 campaigns
        for ($i = 0; $i < 15; $i++) {
            $this->createCampaign($user);
        }

        $response = $this->getJson('/api/v1/dashboard/campaigns/daily/analytics?dates_per_page=5', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK);

        // Assert outer pagination structure
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'agency_id',
                    // The 'data' field should now be a paginator structure
                    'data' => [
                        'current_page',
                        'data',
                        'links',
                        'total'
                    ]
                ]
            ],
            'links',
            'current_page'
        ]);

        // Verify inner pagination count
        // We requested dates_per_page=5
        $firstCampaignData = $response->json('data.0.data');
        $this->assertCount(5, $firstCampaignData['data']);
        $this->assertEquals(1, $firstCampaignData['current_page']);
    }

    public function test_get_monthly_analytics_pagination(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        for ($i = 0; $i < 15; $i++) {
            // Create campaign with long enough duration for monthly pagination
            $this->createCampaign($user, now()->subMonths(20)->format('Y-m-d'), now()->format('Y-m-d'));
        }

        $response = $this->getJson('/api/v1/dashboard/campaigns/monthly/analytics?months_per_page=5', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'agency_id',
                    'data' => [
                        'current_page',
                        'data',
                        'links',
                        'total'
                    ]
                ]
            ],
            'links',
            'current_page'
        ]);

        $firstCampaignData = $response->json('data.0.data');
        $this->assertCount(5, $firstCampaignData['data']);
    }

    public function test_get_campaign_daily_analytics_pagination(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create a campaign with 20 days range
        // start: 20 days ago, end: today
        $startDate = now()->subDays(19);
        $endDate = now();
        $campaign = $this->createCampaign($user, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));

        // Request without page params (default should be 15)
        $response = $this->getJson("/api/v1/dashboard/campaigns/{$campaign->id}/daily/analytics", [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK);

        // Structure check: paginator fields + metadata fields
        $response->assertJsonStructure([
            'current_page',
            'data',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
            'agency_id', // metadata
            'campaign_id'
        ]);

        // Default per_page is 10 (changed by user)
        $data = $response->json('data');
        $this->assertCount(10, $data);

        // Test page 2 with explicit per_page to match our count logic
        // If we want to strictly test default behavior, we can leave per_page out, 
        // but explicit is clearer.
        // Total 20 items. Page 1 (def 10): 10. Page 2 (def 10): 10. Used to be 15/5.
        // Now it's 10 / 10.

        $response2 = $this->getJson("/api/v1/dashboard/campaigns/{$campaign->id}/daily/analytics?page=2", [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response2->assertStatus(JsonResponse::HTTP_OK);
        $data2 = $response2->json('data');
        $this->assertCount(10, $data2);
    }

    public function test_get_campaign_monthly_analytics_pagination(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create a campaign with 20 months range
        $startDate = now()->subMonths(19); // 20 months including this month
        $endDate = now();
        $campaign = $this->createCampaign($user, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));

        // Request page 1 with per_page 10
        $response = $this->getJson("/api/v1/dashboard/campaigns/{$campaign->id}/monthly/analytics?per_page=10", [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK);

        $response->assertJsonStructure([
            'current_page',
            'data',
            'total',
            'agency_id'
        ]);

        $this->assertCount(10, $response->json('data'));

        // Request page 2
        $response2 = $this->getJson("/api/v1/dashboard/campaigns/{$campaign->id}/monthly/analytics?page=2&per_page=10", [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response2->assertStatus(JsonResponse::HTTP_OK);
        $this->assertCount(10, $response2->json('data')); // Should be the next 10 months (total ~20)
    }
}
