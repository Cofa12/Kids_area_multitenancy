<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\V1\FinancialMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Full Financial Breakdown export endpoint returning exact JSON structure as index.
     * Accessible at /api/v1/dashboard/financial-metrics/export
     * and /api/v1/performance/daily-financials/export.
     */
    public function export(Request $request): JsonResponse
    {
        $startDate    = $request->query('from') ?? $request->query('start_date') ?? $request->query('start');
        $endDate      = $request->query('to') ?? $request->query('end_date') ?? $request->query('end');
        $exchangeRate = $request->query('exchange_rate') ? (float) $request->query('exchange_rate') : null;
        $tenant       = $request->header('X-Tenant') ?: $request->query('tenant');

        $tenantName = $this->financialMetricsService->resolveTenantName($tenant);
        $result     = $this->financialMetricsService->getBreakdownData($startDate, $endDate, $exchangeRate, $tenantName);

        $totalRows = count($result['rows']);
        $pagination = [
            'current_page'   => 1,
            'data'           => $result['rows'],
            'first_page_url' => $request->url() . '?page=1',
            'from'           => $totalRows > 0 ? 1 : null,
            'last_page'      => 1,
            'last_page_url'  => $request->url() . '?page=1',
            'links'          => [],
            'next_page_url'  => null,
            'path'           => $request->url(),
            'per_page'       => $totalRows,
            'prev_page_url'  => null,
            'to'             => $totalRows,
            'total'          => $totalRows,
        ];

        return response()->json([
            'success'       => true,
            'tenant'        => $tenantName,
            'currency'      => $result['currency'],
            'exchange_rate' => $result['exchange_rate'],
            'from'          => $result['start_date'],
            'to'            => $result['end_date'],
            'start_date'    => $result['start_date'],
            'end_date'      => $result['end_date'],
            'data'          => $result['rows'],
            'pagination'    => $pagination,
            'totals'        => $result['totals'],
        ], JsonResponse::HTTP_OK);
    }
}

