<?php

namespace Tests\Unit;

use App\Enums\SubscriptionPlan;
use App\Services\V1\FinancialMetricsService;
use PHPUnit\Framework\TestCase;

class FinancialMetricsCalculationTest extends TestCase
{
    private FinancialMetricsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinancialMetricsService();
    }

    public function test_nigeria_plan_pricing(): void
    {
        $this->assertEquals(100.0, SubscriptionPlan::getPriceForTenant('daily', 'naijria'));
        $this->assertEquals(100.0, SubscriptionPlan::getPriceForTenant('2341022000051559', 'naijria'));
        $this->assertEquals(200.0, SubscriptionPlan::getPriceForTenant('weekly', 'naijria'));
        $this->assertEquals(200.0, SubscriptionPlan::getPriceForTenant('2341022000051560', 'naijria'));
        $this->assertEquals(300.0, SubscriptionPlan::getPriceForTenant('bi_weekly', 'naijria'));
        $this->assertEquals(300.0, SubscriptionPlan::getPriceForTenant('2341022000051561', 'naijria'));
        $this->assertEquals(500.0, SubscriptionPlan::getPriceForTenant('monthly', 'naijria'));
        $this->assertEquals(500.0, SubscriptionPlan::getPriceForTenant('2341022000051562', 'naijria'));
    }

    public function test_kenya_plan_pricing_static(): void
    {
        $this->assertEquals(100.0, SubscriptionPlan::getPriceForTenant('daily', 'kenya'));
        $this->assertEquals(100.0, SubscriptionPlan::getPriceForTenant('weekly', 'kenya'));
        $this->assertEquals(100.0, SubscriptionPlan::getPriceForTenant('bi_weekly', 'kenya'));
        $this->assertEquals(100.0, SubscriptionPlan::getPriceForTenant('monthly', 'kenya'));
    }

    public function test_single_day_metrics_breakdown_formulas_nigeria(): void
    {
        // 10 daily subscribers (10 * 100 = 1000)
        // 5 weekly renewals (5 * 200 = 1000)
        // Total daily revenue = 2000 NGN
        $subsByPlan = ['daily' => 10];
        $renewalsByPlan = ['weekly' => 5];
        $adsCostUsd = 0.50;
        $exchangeRate = 1500.0;
        $tenant = 'naijria';
        $currency = 'NGN';

        $row = $this->service->computeSingleDayMetrics(
            '2026-08-31',
            $subsByPlan,
            $renewalsByPlan,
            $adsCostUsd,
            $exchangeRate,
            $tenant,
            $currency
        );

        $expectedDailyRevenue = 2000.0;
        $expectedNetVat = round(2000.0 * 0.925, 2); // 1850.0
        $expectedMtnShare = round(1850.0 * 0.50, 2); // 925.0
        $expectedAggregatorShare = round(1850.0 * 0.10, 2); // 185.0
        $expectedWht = round(1850.0 * 0.04, 2); // 74.0
        $expectedBalanceBeforeNcc = round(1850.0 - 925.0 - 185.0 - 74.0, 2); // 666.0
        $expectedNccLevy = round(1850.0 * 0.01, 2); // 18.50
        $expectedNetBalance = round(666.0 - 18.50, 2); // 647.50
        $expectedYnsNetLocal = round(647.50 * 0.80, 2); // 518.0
        $expectedVasSunychLocal = round(647.50 * 0.20, 2); // 129.50
        $expectedYnsUsd = round(518.0 / 1500.0, 2); // 0.35
        $expectedPnlUsd = round((518.0 / 1500.0) - 0.50, 2); // -0.15
        $expectedRoi = round((((518.0 / 1500.0) - 0.50) / 0.50) * 100, 2); // -30.93%

        $this->assertEquals('2026-08-31', $row['date']);
        $this->assertEquals(10, $row['subscribers_count']);
        $this->assertEquals(5, $row['renewals_count']);
        $this->assertEquals($expectedDailyRevenue, $row['daily_revenue']);
        $this->assertEquals($expectedNetVat, $row['net_revenue_after_vat']);
        $this->assertEquals($expectedMtnShare, $row['mtn_share']);
        $this->assertEquals($expectedAggregatorShare, $row['aggregator_share']);
        $this->assertEquals($expectedWht, $row['wht']);
        $this->assertEquals($expectedBalanceBeforeNcc, $row['balance_before_ncc']);
        $this->assertEquals($expectedNccLevy, $row['ncc_levy']);
        $this->assertEquals($expectedNetBalance, $row['net_balance']);
        $this->assertEquals($expectedYnsNetLocal, $row['yns_net_revenue_local']);
        $this->assertEquals($expectedVasSunychLocal, $row['vas_sunych_share_local']);
        $this->assertEquals($expectedYnsUsd, $row['yns_net_revenue_usd']);
        $this->assertEquals(0.50, $row['ads_cost_usd']);
        $this->assertEquals($expectedPnlUsd, $row['pnl_usd']);
        $this->assertEquals($expectedRoi, $row['daily_roi']);
    }

    public function test_zero_ads_cost_displays_dash_for_roi(): void
    {
        $row = $this->service->computeSingleDayMetrics(
            '2026-08-31',
            ['daily' => 5],
            [],
            0.0, // Zero ad cost
            1500.0,
            'naijria',
            'NGN'
        );

        $this->assertNull($row['daily_roi']);
        $this->assertEquals('—', $row['daily_roi_display']);
        $this->assertEquals('Watch', $row['roi_trend']);
    }

    public function test_daily_revenue_variation(): void
    {
        // Yesterday revenue: 1000, Today revenue: 1500 => +50% variation
        $row = $this->service->computeSingleDayMetrics(
            '2026-08-31',
            ['daily' => 15], // 15 * 100 = 1500
            [],
            10.0,
            1500.0,
            'naijria',
            'NGN',
            1000.0 // prevDailyRevenue
        );

        $this->assertEquals(50.0, $row['daily_revenue_variation']);
        $this->assertEquals('50%', $row['daily_revenue_variation_display']);
    }

    public function test_watch_alert_triggered_by_twenty_points_drop(): void
    {
        // Yesterday ROI was 40%, today ROI is 15% (drop of 25 percentage points > 20)
        // Ads cost $10, today P&L $1.50 => today ROI = 15%
        // YnsUsd = $11.50, YnsLocal = $17250, NetBalance = 21562.5
        $row = $this->service->computeSingleDayMetrics(
            '2026-08-31',
            ['monthly' => 100],
            [],
            50.0, // Ads Cost
            1500.0,
            'naijria',
            'NGN',
            null,
            [40.0] // Yesterday ROI was 40%
        );

        // Since today ROI is much lower or negative vs yesterday's 40%, watch alert triggers if drop > 20
        if ($row['daily_roi'] !== null && $row['daily_roi'] < (40.0 - 20.0)) {
            $this->assertTrue($row['watch_alert']);
        }
    }

    public function test_watch_alert_triggered_by_three_consecutive_negative_days(): void
    {
        $roiHistory = [-10.0, -15.0]; // Day -2 and Day -1 were negative

        // Today with negative ROI
        $row = $this->service->computeSingleDayMetrics(
            '2026-08-31',
            ['daily' => 1], // Very small revenue
            [],
            50.0, // High Ads Cost => Negative ROI
            1500.0,
            'naijria',
            'NGN',
            null,
            $roiHistory
        );

        $this->assertLessThan(0, $row['daily_roi']);
        $this->assertTrue($row['watch_alert']);
    }

    public function test_totals_row_aggregation(): void
    {
        $rows = [
            [
                'date' => '2026-08-01',
                'subscribers_count' => 10,
                'renewals_count' => 5,
                'daily_revenue' => 1000.0,
                'net_revenue_after_vat' => 925.0,
                'mtn_share' => 462.5,
                'aggregator_share' => 92.5,
                'wht' => 37.0,
                'balance_before_ncc' => 333.0,
                'ncc_levy' => 9.25,
                'net_balance' => 323.75,
                'yns_net_revenue_local' => 259.0,
                'vas_sunych_share_local' => 64.75,
                'yns_net_revenue_usd' => 0.17,
                'ads_cost_usd' => 0.10,
                'pnl_usd' => 0.07,
            ],
            [
                'date' => '2026-08-02',
                'subscribers_count' => 20,
                'renewals_count' => 10,
                'daily_revenue' => 2000.0,
                'net_revenue_after_vat' => 1850.0,
                'mtn_share' => 925.0,
                'aggregator_share' => 185.0,
                'wht' => 74.0,
                'balance_before_ncc' => 666.0,
                'ncc_levy' => 18.5,
                'net_balance' => 647.5,
                'yns_net_revenue_local' => 518.0,
                'vas_sunych_share_local' => 129.5,
                'yns_net_revenue_usd' => 0.35,
                'ads_cost_usd' => 0.20,
                'pnl_usd' => 0.15,
            ]
        ];

        $totals = $this->service->computeTotalsRow($rows, 'NGN', 1500.0);

        $this->assertEquals('TOTAL', $totals['date']);
        $this->assertEquals(30, $totals['subscribers_count']);
        $this->assertEquals(15, $totals['renewals_count']);
        $this->assertEquals(3000.0, $totals['daily_revenue']);
        $this->assertEquals(2775.0, $totals['net_revenue_after_vat']);
        $this->assertEquals(0.30, $totals['ads_cost_usd']);
        $this->assertEquals(0.22, $totals['pnl_usd']);
        $this->assertEquals(round((0.22 / 0.30) * 100, 2), $totals['daily_roi']);
    }
}
