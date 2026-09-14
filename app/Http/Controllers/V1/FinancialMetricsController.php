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
            'data' => $result['pagination']['data'] ?? [],
            'pagination' => $result['pagination'],
            'totals' => $result['totals'],
        ], JsonResponse::HTTP_OK);
    }

    /**
     * Downloadable Financial Breakdown Report endpoint (CSV / Excel ready) with the whole keys.
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

        return response()->stream(function () use ($rows, $totals) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Complete keys matching all fields returned in dashboard/financial-metrics
            $keys = [
                'date',
                'subscribers_count',
                'renewals_count',
                'subscribers_by_plan',
                'renewals_by_plan',
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
                'currency',
                'exchange_rate',
                'yns_net_revenue_usd',
                'ads_cost_usd',
                'pnl_usd',
                'daily_roi',
                'daily_roi_display',
                'daily_revenue_variation',
                'daily_revenue_variation_display',
                'roi_trend',
                'watch_alert',
            ];

            // Header row with the whole keys
            fputcsv($handle, $keys);

            $formatRow = function (array $row) use ($keys): array {
                $formatted = [];
                foreach ($keys as $key) {
                    $val = $row[$key] ?? null;
                    if (is_array($val)) {
                        $formatted[] = empty($val) ? '' : json_encode($val);
                    } elseif (is_bool($val)) {
                        $formatted[] = $val ? 'true' : 'false';
                    } elseif ($val === null) {
                        $formatted[] = '';
                    } else {
                        $formatted[] = $val;
                    }
                }
                return $formatted;
            };

            // Chunk and stream data rows
            $chunkSize = 200;
            $chunks = array_chunk($rows, $chunkSize);

            foreach ($chunks as $chunk) {
                foreach ($chunk as $row) {
                    fputcsv($handle, $formatRow($row));
                }
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            // Append TOTAL Summary Row at the bottom with the whole keys
            fputcsv($handle, $formatRow($totals));

            fclose($handle);
        }, 200, $headers);
    }
}

