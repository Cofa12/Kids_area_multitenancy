<?php

namespace App\Services\V1;

use App\Enums\SubscriptionPlan;
use App\Models\Campaign;
use App\Models\CampaignRenewal;
use App\Models\CampaignSubscriber;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialMetricsService
{
    /**
     * VAT rate: 7.5%
     */
    public const VAT_RATE = 0.075;
    public const NET_AFTER_VAT_FACTOR = 0.925;

    /**
     * Shares based on Net Revenue After VAT
     */
    public const MTN_SHARE_FACTOR = 0.50;
    public const AGGREGATOR_SHARE_FACTOR = 0.10;
    public const WHT_FACTOR = 0.04; // 4% Withholding Tax
    public const NCC_LEVY_FACTOR = 0.01; // 1% NCC Levy

    /**
     * Net Balance Shares
     */
    public const YNS_SHARE_FACTOR = 0.80; // 80%
    public const VAS_SUNYCH_SHARE_FACTOR = 0.20; // 20%

    /**
     * Resolve default exchange rate based on tenant if not provided.
     */
    public function getDefaultExchangeRate(?string $tenant = null): float
    {
        $tenantLower = strtolower($tenant ?? '');

        if (str_contains($tenantLower, 'kenya') || str_contains($tenantLower, 'safaricom')) {
            return 130.0; // Default KES per USD
        }

        return 1500.0; // Default NGN per USD
    }

    /**
     * Resolve currency symbol / name for tenant.
     */
    public function getCurrencyForTenant(?string $tenant = null): string
    {
        $tenantLower = strtolower($tenant ?? '');

        if (str_contains($tenantLower, 'kenya') || str_contains($tenantLower, 'safaricom')) {
            return 'KES';
        }

        return 'NGN';
    }

    /**
     * Resolve active tenant identifier.
     */
    public function resolveTenantName(?string $tenant = null): string
    {
        if ($tenant) {
            return $tenant;
        }

        if (Tenant::checkCurrent()) {
            return (string) (Tenant::current()->name ?? Tenant::current()->domain ?? 'naijria');
        }

        return 'naijria';
    }

    /**
     * Fetch aggregated daily breakdown data.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @param float|null $exchangeRate
     * @param string|null $tenant
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, mixed>, currency: string, exchange_rate: float}
     */
    public function getBreakdownData(
        ?string $startDate = null,
        ?string $endDate = null,
        ?float $exchangeRate = null,
        ?string $tenant = null
    ): array {
        $tenantName = $this->resolveTenantName($tenant);
        $currency = $this->getCurrencyForTenant($tenantName);
        $rate = ($exchangeRate && $exchangeRate > 0) ? $exchangeRate : $this->getDefaultExchangeRate($tenantName);

        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::today()->endOfDay();
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : $end->copy()->subDays(29)->startOfDay();

        if ($start->gt($end)) {
            $temp = $start->copy();
            $start = $end->copy()->startOfDay();
            $end = $temp->endOfDay();
        }

        // We fetch one extra preceding day to calculate the first day's revenue variation accurately
        $queryStart = $start->copy()->subDays(3)->startOfDay();

        // 1. Highly-optimized SQL aggregates for Subscriptions
        $subsByDateAndPlan = $this->getSubscribersAggregated($queryStart, $end);

        // 2. Highly-optimized SQL aggregates for Renewals
        $renewalsByDateAndPlan = $this->getRenewalsAggregated($queryStart, $end);

        // 3. Highly-optimized SQL aggregates for Ads Cost (USD)
        $adsCostByDate = $this->getDailyAdsCostAggregated($queryStart, $end);

        // Generate date list from queryStart to end
        $period = CarbonPeriod::create($queryStart->toDateString(), $end->toDateString());
        $allDailyCalculations = [];

        $prevRevenue = null;
        $roiHistory = []; // Tracks recent ROIs for watch alert

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            $daySubs = $subsByDateAndPlan[$dateStr] ?? [];
            $dayRenewals = $renewalsByDateAndPlan[$dateStr] ?? [];
            $dayAdsCost = (float) ($adsCostByDate[$dateStr] ?? 0.0);

            $calculatedRow = $this->computeSingleDayMetrics(
                $dateStr,
                $daySubs,
                $dayRenewals,
                $dayAdsCost,
                $rate,
                $tenantName,
                $currency,
                $prevRevenue,
                $roiHistory
            );

            $allDailyCalculations[$dateStr] = $calculatedRow;
            $prevRevenue = $calculatedRow['daily_revenue'];
            $roiHistory[] = $calculatedRow['daily_roi'];
        }

        // Filter rows to requested [start, end] range
        $filteredRows = [];
        $targetPeriod = CarbonPeriod::create($start->toDateString(), $end->toDateString());

        foreach ($targetPeriod as $targetDate) {
            $dateStr = $targetDate->format('Y-m-d');
            if (isset($allDailyCalculations[$dateStr])) {
                $filteredRows[] = $allDailyCalculations[$dateStr];
            }
        }

        // Calculate Totals Summary Row
        $totals = $this->computeTotalsRow($filteredRows, $currency, $rate);

        return [
            'rows' => array_values($filteredRows),
            'totals' => $totals,
            'currency' => $currency,
            'exchange_rate' => $rate,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    /**
     * Compute paginated breakdown response.
     */
    public function getPaginatedBreakdown(
        ?string $startDate = null,
        ?string $endDate = null,
        ?float $exchangeRate = null,
        ?string $tenant = null,
        int $page = 1,
        int $perPage = 15
    ): array {
        $result = $this->getBreakdownData($startDate, $endDate, $exchangeRate, $tenant);
        $rows = $result['rows'];
        $totalRows = count($rows);

        $offset = ($page - 1) * $perPage;
        $pagedRows = array_slice($rows, $offset, $perPage);

        $paginator = new LengthAwarePaginator(
            $pagedRows,
            $totalRows,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'pagination' => $paginator->toArray(),
            'totals' => $result['totals'],
            'currency' => $result['currency'],
            'exchange_rate' => $result['exchange_rate'],
            'start_date' => $result['start_date'],
            'end_date' => $result['end_date'],
        ];
    }

    /**
     * Compute single day metrics according to MTN Nigeria & Kenya formulas.
     *
     * @param string $dateStr
     * @param array<string, int> $subsByPlan
     * @param array<string, int> $renewalsByPlan
     * @param float $adsCostUsd
     * @param float $exchangeRate
     * @param string $tenantName
     * @param string $currency
     * @param float|null $prevDailyRevenue
     * @param array<int, float|null> $roiHistory
     * @return array<string, mixed>
     */
    public function computeSingleDayMetrics(
        string $dateStr,
        array $subsByPlan,
        array $renewalsByPlan,
        float $adsCostUsd,
        float $exchangeRate,
        string $tenantName,
        string $currency,
        ?float $prevDailyRevenue = null,
        array $roiHistory = []
    ): array {
        $totalSubscribers = array_sum($subsByPlan);
        $totalRenewals = array_sum($renewalsByPlan);

        // Daily Revenue Calculation: sum((subs + renewals) * plan_price)
        $dailyRevenue = 0.0;
        $allPlanKeys = array_unique(array_merge(array_keys($subsByPlan), array_keys($renewalsByPlan)));

        if (empty($allPlanKeys)) {
            // Default 0
            $dailyRevenue = 0.0;
        } else {
            foreach ($allPlanKeys as $planKey) {
                $subCount = $subsByPlan[$planKey] ?? 0;
                $renCount = $renewalsByPlan[$planKey] ?? 0;
                $price = SubscriptionPlan::getPriceForTenant($planKey, $tenantName);
                $dailyRevenue += ($subCount + $renCount) * $price;
            }
        }

        // Net Revenue After 7.5% VAT = Daily Revenue × 0.925
        $netRevenueAfterVat = $dailyRevenue * self::NET_AFTER_VAT_FACTOR;

        // MTN Share 50% = NetRevenueAfterVAT × 0.50
        $mtnShare = $netRevenueAfterVat * self::MTN_SHARE_FACTOR;

        // Aggregator Share 10% = NetRevenueAfterVAT × 0.10
        $aggregatorShare = $netRevenueAfterVat * self::AGGREGATOR_SHARE_FACTOR;

        // WHT 4% = NetRevenueAfterVAT × 0.04
        $wht = $netRevenueAfterVat * self::WHT_FACTOR;

        // Balance Before NCC = NetRevenueAfterVAT - MTNShare - AggregatorShare - WHT
        $balanceBeforeNcc = $netRevenueAfterVat - $mtnShare - $aggregatorShare - $wht;

        // NCC Levy 1% = NetRevenueAfterVAT × 0.01
        $nccLevy = $netRevenueAfterVat * self::NCC_LEVY_FACTOR;

        // Net Balance = BalanceBeforeNCC - NCCLevy
        $netBalance = $balanceBeforeNcc - $nccLevy;

        // YNS Net Revenue (Local) - 80% = NetBalance × 0.80
        $ynsNetRevenueLocal = $netBalance * self::YNS_SHARE_FACTOR;

        // VAS SUNYCH Share (Local) - 20% = NetBalance × 0.20
        $vasSunychShareLocal = $netBalance * self::VAS_SUNYCH_SHARE_FACTOR;

        // YNS Net Revenue (USD) = YNSNetRevenueLocal / ExchangeRate
        $ynsNetRevenueUsd = $exchangeRate > 0 ? ($ynsNetRevenueLocal / $exchangeRate) : 0.0;

        // P&L (USD) = YNS Net Revenue (USD) - Ads Cost (USD)
        $pnlUsd = $ynsNetRevenueUsd - $adsCostUsd;

        // Daily ROI % = [P&L (USD) / Ads Cost (USD)] * 100
        $dailyRoi = ($adsCostUsd > 0) ? round(($pnlUsd / $adsCostUsd) * 100, 2) : null;
        $dailyRoiDisplay = ($dailyRoi !== null) ? ($dailyRoi . '%') : '—';

        // Daily Revenue Variation % = [(Current Daily Revenue - Previous Daily Revenue) / Previous Daily Revenue] * 100
        $dailyRevenueVariation = null;
        if ($prevDailyRevenue !== null && $prevDailyRevenue > 0) {
            $dailyRevenueVariation = round((($dailyRevenue - $prevDailyRevenue) / $prevDailyRevenue) * 100, 2);
        }

        // ⚠ Watch Alert Check
        // If ROI is negative for today and previous two days, OR if today's ROI is > 20 points lower than yesterday
        $watchAlert = false;
        $historyCount = count($roiHistory);
        $yesterdayRoi = $historyCount >= 1 ? $roiHistory[$historyCount - 1] : null;
        $dayBeforeYesterdayRoi = $historyCount >= 2 ? $roiHistory[$historyCount - 2] : null;

        if ($dailyRoi !== null && $yesterdayRoi !== null) {
            // Condition 1: Drop of more than 20 percentage points
            if ($dailyRoi < ($yesterdayRoi - 20)) {
                $watchAlert = true;
            }
        }

        if ($dailyRoi !== null && $yesterdayRoi !== null && $dayBeforeYesterdayRoi !== null) {
            // Condition 2: 3 consecutive days below 0%
            if ($dailyRoi < 0 && $yesterdayRoi < 0 && $dayBeforeYesterdayRoi < 0) {
                $watchAlert = true;
            }
        }

        // ROI Trend Indicator: Positive / Negative / Watch
        $roiTrend = 'Watch';
        if ($watchAlert) {
            $roiTrend = 'Watch';
        } elseif ($dailyRoi !== null && $yesterdayRoi !== null) {
            $roiTrend = $dailyRoi > $yesterdayRoi ? 'Positive' : 'Negative';
        }

        return [
            'date' => $dateStr,
            'subscribers_count' => $totalSubscribers,
            'renewals_count' => $totalRenewals,
            'subscribers_by_plan' => $subsByPlan,
            'renewals_by_plan' => $renewalsByPlan,
            'daily_revenue' => round($dailyRevenue, 2),
            'net_revenue_after_vat' => round($netRevenueAfterVat, 2),
            'mtn_share' => round($mtnShare, 2),
            'aggregator_share' => round($aggregatorShare, 2),
            'wht' => round($wht, 2),
            'balance_before_ncc' => round($balanceBeforeNcc, 2),
            'ncc_levy' => round($nccLevy, 2),
            'net_balance' => round($netBalance, 2),
            'yns_net_revenue_local' => round($ynsNetRevenueLocal, 2),
            'vas_sunych_share_local' => round($vasSunychShareLocal, 2),
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'yns_net_revenue_usd' => round($ynsNetRevenueUsd, 2),
            'ads_cost_usd' => round($adsCostUsd, 2),
            'pnl_usd' => round($pnlUsd, 2),
            'daily_roi' => $dailyRoi,
            'daily_roi_display' => $dailyRoiDisplay,
            'daily_revenue_variation' => $dailyRevenueVariation,
            'daily_revenue_variation_display' => ($dailyRevenueVariation !== null) ? ($dailyRevenueVariation . '%') : '—',
            'roi_trend' => $roiTrend,
            'watch_alert' => $watchAlert,
        ];
    }

    /**
     * Compute Total summary row for all rows in the dataset.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param string $currency
     * @param float $exchangeRate
     * @return array<string, mixed>
     */
    public function computeTotalsRow(array $rows, string $currency, float $exchangeRate): array
    {
        $totalSubscribers = 0;
        $totalRenewals = 0;
        $totalDailyRevenue = 0.0;
        $totalNetRevenueAfterVat = 0.0;
        $totalMtnShare = 0.0;
        $totalAggregatorShare = 0.0;
        $totalWht = 0.0;
        $totalBalanceBeforeNcc = 0.0;
        $totalNccLevy = 0.0;
        $totalNetBalance = 0.0;
        $totalYnsNetRevenueLocal = 0.0;
        $totalVasSunychShareLocal = 0.0;
        $totalYnsNetRevenueUsd = 0.0;
        $totalAdsCostUsd = 0.0;
        $totalPnlUsd = 0.0;

        foreach ($rows as $row) {
            $totalSubscribers += (int) ($row['subscribers_count'] ?? 0);
            $totalRenewals += (int) ($row['renewals_count'] ?? 0);
            $totalDailyRevenue += (float) ($row['daily_revenue'] ?? 0.0);
            $totalNetRevenueAfterVat += (float) ($row['net_revenue_after_vat'] ?? 0.0);
            $totalMtnShare += (float) ($row['mtn_share'] ?? 0.0);
            $totalAggregatorShare += (float) ($row['aggregator_share'] ?? 0.0);
            $totalWht += (float) ($row['wht'] ?? 0.0);
            $totalBalanceBeforeNcc += (float) ($row['balance_before_ncc'] ?? 0.0);
            $totalNccLevy += (float) ($row['ncc_levy'] ?? 0.0);
            $totalNetBalance += (float) ($row['net_balance'] ?? 0.0);
            $totalYnsNetRevenueLocal += (float) ($row['yns_net_revenue_local'] ?? 0.0);
            $totalVasSunychShareLocal += (float) ($row['vas_sunych_share_local'] ?? 0.0);
            $totalYnsNetRevenueUsd += (float) ($row['yns_net_revenue_usd'] ?? 0.0);
            $totalAdsCostUsd += (float) ($row['ads_cost_usd'] ?? 0.0);
            $totalPnlUsd += (float) ($row['pnl_usd'] ?? 0.0);
        }

        $totalRoi = ($totalAdsCostUsd > 0) ? round(($totalPnlUsd / $totalAdsCostUsd) * 100, 2) : null;
        $totalRoiDisplay = ($totalRoi !== null) ? ($totalRoi . '%') : '—';

        return [
            'date' => 'TOTAL',
            'subscribers_count' => $totalSubscribers,
            'renewals_count' => $totalRenewals,
            'daily_revenue' => round($totalDailyRevenue, 2),
            'net_revenue_after_vat' => round($totalNetRevenueAfterVat, 2),
            'mtn_share' => round($totalMtnShare, 2),
            'aggregator_share' => round($totalAggregatorShare, 2),
            'wht' => round($totalWht, 2),
            'balance_before_ncc' => round($totalBalanceBeforeNcc, 2),
            'ncc_levy' => round($totalNccLevy, 2),
            'net_balance' => round($totalNetBalance, 2),
            'yns_net_revenue_local' => round($totalYnsNetRevenueLocal, 2),
            'vas_sunych_share_local' => round($totalVasSunychShareLocal, 2),
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'yns_net_revenue_usd' => round($totalYnsNetRevenueUsd, 2),
            'ads_cost_usd' => round($totalAdsCostUsd, 2),
            'pnl_usd' => round($totalPnlUsd, 2),
            'daily_roi' => $totalRoi,
            'daily_roi_display' => $totalRoiDisplay,
            'daily_revenue_variation' => null,
            'daily_revenue_variation_display' => '—',
            'roi_trend' => 'Watch',
            'watch_alert' => false,
        ];
    }

    /**
     * High performance aggregate subscriber count grouped by (date, plan_id).
     *
     * @return array<string, array<string, int>>
     */
    protected function getSubscribersAggregated(Carbon $start, Carbon $end): array
    {
        $results = [];

        // Check if users table exists in active tenant connection
        if (Schema::hasTable('users')) {
            $hasPlanId = Schema::hasColumn('users', 'plan_id');
            $hasAction = Schema::hasColumn('users', 'action');

            $query = DB::table('users')
                ->whereBetween('created_at', [$start, $end]);

            if ($hasAction) {
                // If action is specified, filter for new subscriptions or non-renewals
                $query->where(function ($q) {
                    $q->whereNull('action')
                        ->orWhere('action', '!=', 'SUBSCRIBED_RENEWAL')
                        ->orWhere('action', 'SUBSCRIPTION')
                        ->orWhere('action', 'SUBSCRIBED_NEW');
                });
            }

            $selectPlan = $hasPlanId ? "COALESCE(plan_id, 'daily')" : "'daily'";

            $rows = $query->select(
                DB::raw('DATE(created_at) as date_val'),
                DB::raw("{$selectPlan} as plan_key"),
                DB::raw('COUNT(*) as total_count')
            )
            ->groupBy(DB::raw('DATE(created_at)'), DB::raw($selectPlan))
            ->get();

            foreach ($rows as $row) {
                $d = (string) $row->date_val;
                $p = SubscriptionPlan::normalizePlanKey((string) $row->plan_key);
                $results[$d][$p] = ($results[$d][$p] ?? 0) + (int) $row->total_count;
            }
        }

        return $results;
    }

    /**
     * High performance aggregate renewal count grouped by (date, plan_id).
     *
     * @return array<string, array<string, int>>
     */
    protected function getRenewalsAggregated(Carbon $start, Carbon $end): array
    {
        $results = [];

        // 1. Check campaign_renewals table if exists
        if (Schema::hasTable('campaign_renewals')) {
            $hasPlanId = Schema::hasColumn('campaign_renewals', 'plan_id');
            $dateColumn = Schema::hasColumn('campaign_renewals', 'renewed_at')
                ? "COALESCE(renewed_at, created_at)"
                : "created_at";

            $selectPlan = $hasPlanId ? "COALESCE(plan_id, 'daily')" : "'daily'";

            $rows = DB::table('campaign_renewals')
                ->whereBetween(DB::raw($dateColumn), [$start, $end])
                ->select(
                    DB::raw("DATE({$dateColumn}) as date_val"),
                    DB::raw("{$selectPlan} as plan_key"),
                    DB::raw('COUNT(*) as total_count')
                )
                ->groupBy(DB::raw("DATE({$dateColumn})"), DB::raw($selectPlan))
                ->get();

            foreach ($rows as $row) {
                $d = (string) $row->date_val;
                $p = SubscriptionPlan::normalizePlanKey((string) $row->plan_key);
                $results[$d][$p] = ($results[$d][$p] ?? 0) + (int) $row->total_count;
            }
        }

        // 2. Check users with action = 'SUBSCRIBED_RENEWAL'
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'action')) {
            $hasPlanId = Schema::hasColumn('users', 'plan_id');
            $selectPlan = $hasPlanId ? "COALESCE(plan_id, 'daily')" : "'daily'";

            $rows = DB::table('users')
                ->where('action', 'SUBSCRIBED_RENEWAL')
                ->whereBetween('updated_at', [$start, $end])
                ->select(
                    DB::raw('DATE(updated_at) as date_val'),
                    DB::raw("{$selectPlan} as plan_key"),
                    DB::raw('COUNT(*) as total_count')
                )
                ->groupBy(DB::raw('DATE(updated_at)'), DB::raw($selectPlan))
                ->get();

            foreach ($rows as $row) {
                $d = (string) $row->date_val;
                $p = SubscriptionPlan::normalizePlanKey((string) $row->plan_key);
                $results[$d][$p] = ($results[$d][$p] ?? 0) + (int) $row->total_count;
            }
        }

        return $results;
    }

    /**
     * High performance aggregate daily Ads Cost (USD) from campaigns.
     *
     * @return array<string, float>
     */
    protected function getDailyAdsCostAggregated(Carbon $start, Carbon $end): array
    {
        $results = [];

        if (Schema::hasTable('campaigns')) {
            $hasCost = Schema::hasColumn('campaigns', 'influencer_cost');
            $hasCpa = Schema::hasColumn('campaigns', 'cpa');

            if ($hasCost || $hasCpa) {
                // Sum influencer cost grouped by start_date or date
                $costSelect = $hasCost ? "COALESCE(CAST(influencer_cost AS DECIMAL(12,2)), 0)" : "0";

                $rows = DB::table('campaigns')
                    ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                    ->select(
                        DB::raw('DATE(start_date) as date_val'),
                        DB::raw("SUM({$costSelect}) as total_cost")
                    )
                    ->groupBy(DB::raw('DATE(start_date)'))
                    ->get();

                foreach ($rows as $row) {
                    $d = (string) $row->date_val;
                    $results[$d] = (float) $row->total_cost;
                }
            }
        }

        return $results;
    }
}
