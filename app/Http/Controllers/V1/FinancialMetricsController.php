<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\V1\FinancialMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @psalm-suppress UnusedClass
 */
class FinancialMetricsController extends Controller
{
    public function __construct(
        protected FinancialMetricsService $financialMetricsService
    ) {
    }

    /**
     * Paginated Daily Financial Breakdown Dashboard endpoint.
     * Accessible at /api/v1/dashboard/financial-metrics and /api/v1/performance/daily-financials.
     */
    public function index(Request $request): JsonResponse
    {
        $startDate = $request->query('from') ?? $request->query('start_date') ?? $request->query('start');
        $endDate = $request->query('to') ?? $request->query('end_date') ?? $request->query('end');
        $exchangeRate = $request->query('exchange_rate') ? (float) $request->query('exchange_rate') : null;
        $tenant = $request->header('X-Tenant') ?: $request->query('tenant');
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);

        if ($page < 1) {
            $page = 1;
        }
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 15;
        }

        $result = $this->financialMetricsService->getPaginatedBreakdown(
            $startDate,
            $endDate,
            $exchangeRate,
            $tenant,
            $page,
            $perPage
        );

        return response()->json([
            'success' => true,
            'tenant' => $this->financialMetricsService->resolveTenantName($tenant),
            'currency' => $result['currency'],
            'exchange_rate' => $result['exchange_rate'],
            'from' => $result['start_date'],
            'to' => $result['end_date'],
            'start_date' => $result['start_date'],
            'end_date' => $result['end_date'],
            'pagination' => $result['pagination'],
            'totals' => $result['totals'],
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Downloadable Financial Breakdown Report endpoint (CSV / Excel ready).
     * Accessible at /api/v1/dashboard/financial-metrics/export and /api/v1/performance/daily-financials/export.
     */
    public function export(Request $request): StreamedResponse
    {
        $startDate = $request->query('from') ?? $request->query('start_date') ?? $request->query('start');
        $endDate = $request->query('to') ?? $request->query('end_date') ?? $request->query('end');
        $exchangeRate = $request->query('exchange_rate') ? (float) $request->query('exchange_rate') : null;
        $tenant = $request->header('X-Tenant') ?: $request->query('tenant');

        $tenantName = $this->financialMetricsService->resolveTenantName($tenant);
        $result = $this->financialMetricsService->getBreakdownData($startDate, $endDate, $exchangeRate, $tenantName);

        $currency = $result['currency'];
        $rows = $result['rows'];
        $totals = $result['totals'];
        $filename = sprintf(
            'financial_breakdown_%s_%s_to_%s.csv',
            strtolower($tenantName),
            $result['start_date'],
            $result['end_date']
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($rows, $totals, $currency) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Column Headers (matching Word document and specifications)
            fputcsv($handle, [
                'Date',
                'Subscribers',
                'Renewals',
                "Daily Revenue ({$currency})",
                'Net Revenue After 7.5% VAT',
                'MTN Share 50%',
                'Aggregator Share 10%',
                'WHT 4%',
                'Balance Before NCC',
                'NCC Levy 1%',
                'Net Balance',
                "YNS Net Revenue ({$currency}) - 80%",
                "VAS SUNYCH Share ({$currency}) - 20%",
                'YNS Net Revenue (USD)',
                'Ads Cost (USD)',
                'P&L (USD)',
                'Daily ROI %',
                'Daily Revenue Variation %',
                'roi_trend',
                'watch_alert',
            ]);

            // Chunk and stream data rows
            $chunkSize = 200;
            $chunks = array_chunk($rows, $chunkSize);

            foreach ($chunks as $chunk) {
                foreach ($chunk as $row) {
                    fputcsv($handle, [
                        $row['date'],
                        $row['subscribers_count'],
                        $row['renewals_count'],
                        $row['daily_revenue'],
                        $row['net_revenue_after_vat'],
                        $row['mtn_share'],
                        $row['aggregator_share'],
                        $row['wht'],
                        $row['balance_before_ncc'],
                        $row['ncc_levy'],
                        $row['net_balance'],
                        $row['yns_net_revenue_local'],
                        $row['vas_sunych_share_local'],
                        $row['yns_net_revenue_usd'],
                        $row['ads_cost_usd'],
                        $row['pnl_usd'],
                        $row['daily_roi_display'],
                        $row['daily_revenue_variation_display'],
                        $row['roi_trend'] ?? '—',
                        $row['watch_alert'] ? 'true' : 'false',
                    ]);
                }
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            // Append TOTAL Summary Row at the bottom
            fputcsv($handle, [
                $totals['date'],
                $totals['subscribers_count'],
                $totals['renewals_count'],
                $totals['daily_revenue'],
                $totals['net_revenue_after_vat'],
                $totals['mtn_share'],
                $totals['aggregator_share'],
                $totals['wht'],
                $totals['balance_before_ncc'],
                $totals['ncc_levy'],
                $totals['net_balance'],
                $totals['yns_net_revenue_local'],
                $totals['vas_sunych_share_local'],
                $totals['yns_net_revenue_usd'],
                $totals['ads_cost_usd'],
                $totals['pnl_usd'],
                $totals['daily_roi_display'],
                $totals['daily_revenue_variation_display'],
                $totals['roi_trend'] ?? '—',
                $totals['watch_alert'] ? 'true' : 'false',
            ]);

            fclose($handle);
        }, 200, $headers);
    }
}
