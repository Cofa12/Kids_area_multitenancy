<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignRequest;
use App\Http\Resources\V1\CampaignResource;
use App\Models\Campaign;
use App\Services\V1\CampaignService;
use App\Services\V1\CpaCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Date;
use App\Models\NonBillableCampaignClick;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Multitenancy\Models\Tenant;

/**
 * @psalm-suppress UnusedClass
 */
class CampaignController extends Controller
{
    private string $guard;
    public function __construct(private CampaignService $campaignService, private CpaCalculation $CpaCalculation)
    {
        $this->guard = 'admin';
        if (Tenant::current()->getDatabaseName()=='landlord')
            $this->guard = 'api';
    }


    public function index(): AnonymousResourceCollection
    {
        $campaigns = Campaign::query()->latest()->get();
        return CampaignResource::collection($campaigns);
    }


    public function show(string $id): CampaignResource
    {
        $campaign = Campaign::query()->findOrFail($id);
        return new CampaignResource($campaign);
    }

    public function initCampaign(): JsonResponse
    {
        $lang = app()->getLocale();

        $campaign = Campaign::create();
        return response()->json([
            'message' => $lang == 'ar' ? 'تم إنشاد الحملة بنجاح' : 'Campaign initiated',
            'campaign_id' => $campaign->id
        ], JsonResponse::HTTP_CREATED);
    }

    public function updateData(CampaignRequest $request): JsonResponse
    {
        $lang = $this->getLanguage($request);
        $campaignData = $request->except('campaign_id', '_method');

        // @todo use middleware design pattern
        $campaignData['status'] = $this->campaignService->returnCampaignStatusDependOnDate($campaignData['start_date']);
        $this->campaignService->checkIfHasAgencyWithCpAndReturnException($campaignData['agency_id'] ?? null, $campaignData['cpa'] ?? null, $lang);
        $this->campaignService->checkIfHasInfluencerWithCostAndReturnException($campaignData['influencer_id'] ?? null, $campaignData['influencer_cost'] ?? null, $lang);

        Campaign::where('id', $request->campaign_id)->update($campaignData);
        return response()->json([
            'message' => $lang == 'en' ? 'campaign was created successfully' : 'تم انشاء الحملة بنجاح'
        ], JsonResponse::HTTP_CREATED);
    }

    public function endCampaign(Request $request, string $id): JsonResponse
    {
        $campaign = Campaign::query()->findOrFail($id);
        $campaign->update(['status' => 'ended']);
        return response()->json([
            'message' => $this->getLanguage($request) == 'en' ? 'Campaign ended successfully' : 'تم إنهاء الحملة بنجاح'
        ]);
    }
    public function pauseCampaign(Request $request, string $id): JsonResponse
    {
        $campaign = Campaign::query()->findOrFail($id);
        $campaign->update(['status' => 'paused']);
        return response()->json([
            'message' => $this->getLanguage($request) == 'en' ? 'Campaign paused successfully' : 'تم إيقاف الحملة مؤقتا'
        ]);
    }
    public function activeCampaign(Request $request, string $id): JsonResponse
    {
        $campaign = Campaign::query()->findOrFail($id);
        $campaign->update(['status' => 'active']);
        return response()->json([
            'message' => $this->getLanguage($request) == 'en' ? 'Campaign paused successfully' : 'تم إيقاف الحملة مؤقتا'
        ]);
    }

    public function updateCpa(Request $request, string $id): JsonResponse
    {
        $lang = $this->getLanguage($request);
        $validated = $request->validate([
            'cpa' => ['required', 'integer', 'min:0']
        ]);

        $campaign = Campaign::query()->findOrFail($id);
        $campaign->update(['cpa' => $validated['cpa']]);

        return response()->json([
            'message' => $lang == 'en' ? 'CPA updated successfully' : 'تم تحديث قيمة CPA بنجاح',
        ]);
    }

    private function getLanguage(Request $request)
    {
        return $request->header('Accept-Language');
    }

    public function getDailyAnalytics(): JsonResponse
    {
        $campaigns = Campaign::latest()->paginate(10);

        $datesPage = request()->input('dates_page', 1);
        $datesPerPage = request()->input('dates_per_page', 10);
        $offset = ($datesPage - 1) * $datesPerPage;

        $finalData = $campaigns->getCollection()->transform(function ($campaign) use ($datesPage, $datesPerPage, $offset) {
            $currentDate = Date::parse($campaign->start_date);
            $endDate = Date::parse($campaign->end_date);

            $actualEndDate = $endDate->min(now());
            if ($currentDate > $actualEndDate) {
                $totalDays = 0;
            } else {
                $totalDays = $currentDate->diffInDays($actualEndDate) + 1;
            }

            $currentDate->addDays((int) $offset);

            $dates = [];
            for ($i = 0; $i < $datesPerPage; $i++) {
                if ($currentDate > $actualEndDate) {
                    break;
                }
                $dates[] = [
                    'date' => $currentDate->format('Y-m-d'),
                    'num_subscribers' => $campaign->subscribers->count(),
                    'cpa' => $this->CpaCalculation->calculateCpa($campaign, $currentDate),
                ];
                $currentDate->addDay();
            }

            $paginator = new LengthAwarePaginator(
                $dates,
                $totalDays,
                $datesPerPage,
                $datesPage,
                ['path' => request()->url(), 'query' => request()->query(), 'pageName' => 'dates_page']
            );

            return [
                'agency_id' => $campaign->agency_id,
                'influencer_id' => $campaign->influencer_id,
                'campaign_id' => $campaign->id,
                'country' => $campaign->country,
                'operator' => $campaign->operator,
                'type' => $campaign->type,
                'data' => $paginator
            ];
        });

        $campaigns->setCollection($finalData);

        return response()->json($campaigns, JsonResponse::HTTP_OK);
    }

    public function getMonthlyAnalytics(): JsonResponse
    {
        $campaigns = Campaign::latest()->paginate(10);

        $monthsPage = request()->input('months_page', 1);
        $monthsPerPage = request()->input('months_per_page', 10);
        $offset = ($monthsPage - 1) * $monthsPerPage;

        $finalData = $campaigns->getCollection()->transform(function ($campaign) use ($monthsPage, $monthsPerPage, $offset) {
            $startDate = Date::parse($campaign->start_date)->startOfMonth();
            $endDate = Date::parse($campaign->end_date);
            $actualEndDate = $endDate->min(now());

            $allMonths = [];
            $tempDate = $startDate->copy();

            // Check validity
            if ($tempDate <= $actualEndDate) {
                while ($tempDate <= $actualEndDate) {
                    $allMonths[] = $tempDate->copy();
                    $tempDate->addMonth()->startOfMonth();
                }
            }

            $totalMonths = count($allMonths);

            $targetMonths = array_slice($allMonths, $offset, $monthsPerPage);

            $months = [];
            foreach ($targetMonths as $date) {
                $months[] = [
                    'month' => $date->format('Y-m'),
                    'num_subscribers' => $campaign->subscribers->count(),
                    'total_campaign_cost' => $campaign->subscribers->sum('amount') * $this->CpaCalculation->calculateCpa($campaign, $date),
                    'cpa' => $this->CpaCalculation->calculateCpa($campaign, $date),
                ];
            }

            $paginator = new LengthAwarePaginator(
                $months,
                $totalMonths,
                $monthsPerPage,
                $monthsPage,
                ['path' => request()->url(), 'query' => request()->query(), 'pageName' => 'months_page']
            );

            return [
                'agency_id' => $campaign->agency_id,
                'influencer_id' => $campaign->influencer_id,
                'campaign_id' => $campaign->id,
                'country' => $campaign->country,
                'operator' => $campaign->operator,
                'type' => $campaign->type,
                'data' => $paginator
            ];
        });

        $campaigns->setCollection($finalData);

        return response()->json($campaigns, JsonResponse::HTTP_OK);
    }
    public function getCampaignDailyAnalytics(string $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);
        $startDate = Date::parse($campaign->start_date);
        $endDate = Date::parse($campaign->end_date);
        $now = now();

        // Ensure we don't go beyond today or the end date
        $actualEndDate = $endDate->min($now);

        // Calculate total days
        $totalDays = $startDate->diffInDays($actualEndDate) + 1;

        $page = request()->input('page', 1);
        $perPage = request()->input('per_page', 10);
        $offset = ($page - 1) * $perPage;

        $dates = [];
        $currentDate = $startDate->copy()->addDays((int) $offset);

        // Iterate for the current page
        for ($i = 0; $i < $perPage; $i++) {
            if ($currentDate > $actualEndDate) {
                break;
            }

            $dates[] = [
                'date' => $currentDate->format('Y-m-d'),
                'num_subscribers' => $campaign->subscribers->count(),
                'cpa' => $this->CpaCalculation->calculateCpa($campaign, $currentDate),
            ];
            $currentDate->addDay();
        }

        $paginator = new LengthAwarePaginator(
            $dates,
            $totalDays,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $metadata = [
            'agency_id' => $campaign->agency_id,
            'influencer_id' => $campaign->influencer_id,
            'campaign_id' => $campaign->id,
            'country' => $campaign->country,
            'operator' => $campaign->operator,
            'type' => $campaign->type,
        ];

        return response()->json($paginator->toArray() + $metadata, JsonResponse::HTTP_OK);
    }
    public function getCampaignMonthlyAnalytics(string $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);
        $startDate = Date::parse($campaign->start_date)->startOfMonth();
        $endDate = Date::parse($campaign->end_date);
        $now = now();

        $actualEndDate = $endDate->min($now);

        $allMonths = [];
        $tempDate = $startDate->copy();
        while ($tempDate <= $actualEndDate) {
            $allMonths[] = $tempDate->copy();
            $tempDate->addMonth()->startOfMonth();
        }

        $totalMonths = count($allMonths);

        $page = request()->input('page', 1);
        $perPage = request()->input('per_page', 10);
        $offset = ($page - 1) * $perPage;

        $months = [];
        $targetMonths = array_slice($allMonths, $offset, $perPage);

        foreach ($targetMonths as $date) {
            $months[] = [
                'month' => $date->format('Y-m'),
                'num_subscribers' => $campaign->subscribers->count(),
                'total_campaign_cost' => $campaign->subscribers->sum('amount') * $this->CpaCalculation->calculateCpa($campaign, $date),
                'cpa' => $this->CpaCalculation->calculateCpa($campaign, $date),
            ];
        }

        $paginator = new LengthAwarePaginator(
            $months,
            $totalMonths,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $metadata = [
            'agency_id' => $campaign->agency_id,
            'influencer_id' => $campaign->influencer_id,
            'campaign_id' => $campaign->id,
            'country' => $campaign->country,
            'operator' => $campaign->operator,
            'type' => $campaign->type,
        ];

        return response()->json($paginator->toArray() + $metadata, JsonResponse::HTTP_OK);
    }
    public function storeNonBillableClick(Request $request): JsonResponse
    {

        $lang = $this->getLanguage($request);

        $validated = $request->validate([
            'campaign_id' => ['required', 'uuid', 'exists:tenant.campaigns,id'],
            'click_id' => ['required', 'string', 'max:255', 'unique:tenant.non_billable_campaign_clicks,click_id'],
        ]);

        Campaign::query()->findOrFail($validated['campaign_id']);

        NonBillableCampaignClick::create([
            'campaign_id' => $validated['campaign_id'],
            'click_id' => $validated['click_id'],
        ]);

        return response()->json([
            'message' => $lang == 'ar' ? 'تم حفظ النقرة بنجاح' : 'Click stored successfully',
        ], JsonResponse::HTTP_CREATED);
    }
}
