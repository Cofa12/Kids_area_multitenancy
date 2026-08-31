<?php

namespace Tests\Feature;

use App\Models\LandlordUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class FinancialMetricsControllerTest extends TestCase
{
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = LandlordUser::create([
            'name' => 'admin_test',
            'phone' => '+201099999999',
            'password' => bcrypt('Password123#')
        ]);

        $this->adminToken = auth('admin')->attempt([
            'phone' => $admin->phone,
            'password' => 'Password123#'
        ]);
    }

    public function test_get_daily_financial_metrics_paginated(): void
    {
        // Seed test users for today
        User::create([
            'phone' => '+2348000000001',
            'plan_id' => '2341022000051559', // Daily
            'subscription_status' => true,
        ]);
        User::create([
            'phone' => '+2348000000002',
            'plan_id' => '2341022000051560', // Weekly
            'subscription_status' => true,
        ]);

        $response = $this->getJson('/api/v1/dashboard/financial-metrics?per_page=5', [
            'Authorization' => 'Bearer ' . $this->adminToken,
            'X-Tenant' => 'test.localhost',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonStructure([
            'success',
            'tenant',
            'currency',
            'exchange_rate',
            'start_date',
            'end_date',
            'pagination' => [
                'current_page',
                'data' => [
                    '*' => [
                        'date',
                        'subscribers_count',
                        'renewals_count',
                        'daily_revenue',
                        'net_revenue_after_vat',
                        'mtn_share',
                        'aggregator_share',
                        'wht',
                        'balance_before_ncc',
                        'ncc_levy',
                        'net_balance',
                        'yns_net_revenue_local',
                        'vas_sunych_share_local',
                        'yns_net_revenue_usd',
                        'ads_cost_usd',
                        'pnl_usd',
                        'daily_roi',
                        'daily_roi_display',
                        'daily_revenue_variation',
                        'watch_alert',
                    ]
                ],
                'total',
                'per_page',
            ],
            'totals' => [
                'date',
                'subscribers_count',
                'renewals_count',
                'daily_revenue',
                'net_revenue_after_vat',
                'mtn_share',
                'aggregator_share',
                'wht',
                'balance_before_ncc',
                'ncc_levy',
                'net_balance',
                'yns_net_revenue_local',
                'vas_sunych_share_local',
                'yns_net_revenue_usd',
                'ads_cost_usd',
                'pnl_usd',
                'daily_roi',
            ]
        ]);

        $this->assertNotEmpty($response->json('pagination.data'));
    }

    public function test_get_daily_financials_alias_route(): void
    {
        $response = $this->getJson('/api/v1/performance/daily-financials', [
            'Authorization' => 'Bearer ' . $this->adminToken,
            'X-Tenant' => 'test.localhost',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonPath('success', true);
    }

    public function test_get_daily_financials_with_from_and_to_params(): void
    {
        $response = $this->getJson('/api/v1/performance/daily-financials?from=2026-08-10&to=2026-08-20&per_page=20', [
            'Authorization' => 'Bearer ' . $this->adminToken,
            'X-Tenant' => 'test.localhost',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(JsonResponse::HTTP_OK);
        $response->assertJsonPath('from', '2026-08-10');
        $response->assertJsonPath('to', '2026-08-20');
        $this->assertEquals(11, $response->json('pagination.total')); // 11 days inclusive
    }

    public function test_export_financial_metrics_as_csv_stream(): void
    {
        $response = $this->get('/api/v1/dashboard/financial-metrics/export', [
            'Authorization' => 'Bearer ' . $this->adminToken,
            'X-Tenant' => 'test.localhost',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="financial_breakdown_', (string) $response->headers->get('Content-Disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Date,Subscribers,Renewals', $content);
        $this->assertStringContainsString('"Daily Revenue (NGN)"', $content);
        $this->assertStringContainsString('"Net Revenue After 7.5% VAT"', $content);
        $this->assertStringContainsString('TOTAL', $content);
    }

    public function test_export_financial_metrics_with_from_and_to_params(): void
    {
        $response = $this->get('/api/v1/dashboard/financial-metrics/export?from=2026-08-15&to=2026-08-18', [
            'Authorization' => 'Bearer ' . $this->adminToken,
            'X-Tenant' => 'test.localhost',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('financial_breakdown_test.localhost_2026-08-15_to_2026-08-18.csv', (string) $response->headers->get('Content-Disposition'));
    }
}
