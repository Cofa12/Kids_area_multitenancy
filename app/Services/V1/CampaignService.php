<?php

namespace App\Services\V1;

use App\Http\Exceptions\CostNotProvidedException;
use App\Http\Exceptions\CpaNotProvidedException;
use App\Models\Campaign;
use App\Models\CampaignRenewal;
use App\Models\CampaignSubscriber;
use Carbon\Carbon;

class CampaignService
{
    public function returnCampaignStatusDependOnDate(string $startDate):string
    {
        return date($startDate) > date(now())?'scheduled':'active';
    }

    public function checkIfHasAgencyWithCpAndReturnException(string|null $agencyId, int|null $cpa,string $lang) :void
    {
        if($agencyId && !$cpa)
            throw new CpaNotProvidedException($lang);
    }

    public function checkIfHasInfluencerWithCostAndReturnException(string|null $influencer_id, int|null $cost, string $lang):void
    {
        if($influencer_id && !$cost)
            throw new CostNotProvidedException($lang);
    }

    /**
     * Build analytics rows for the given campaigns.
     * Each row has: date, country, operator, service, source, type, new_subscribers, referrals, cpa
     * - source: agency_id or influencer_id (whichever exists)
     * - rows are generated for each date from start_date to end_date (inclusive)
     * - campaigns with null end_date are ignored
     * - CPA rules:
     *   - agency: return campaign.cpa as-is
     *   - influencer: if end_date <= today - 1 day then
     *       billable: influencer_cost / total_new_subscribers
     *       non-billable: influencer_cost / total_new_subscribers
     *     else null. If denominator is 0 => null
     *
     * @param Campaign[] $campaigns
     * @return array<int,array<string,mixed>>
     */
    public function buildAnalytics(array $campaigns): array
    {
        $rows = [];
        $today = Carbon::today();

        foreach ($campaigns as $campaign) {
            if (empty($campaign->end_date)) {
                // skip campaigns without end_date as requested
                continue;
            }

            $start = Carbon::parse($campaign->start_date)->startOfDay();
            $end = Carbon::parse($campaign->end_date)->startOfDay();

            // Pre-compute CPA for the campaign (campaign-level, same value per day)
            $cpa = null;
            $source = $campaign->agency_id ?: $campaign->influencer_id;

            if (!empty($campaign->agency_id)) {
                $cpa = $campaign->cpa; // passthrough for agency
            } elseif (!empty($campaign->influencer_id)) {
                // Only compute if end_date is at least 1 day in the past
                if ($end->lt($today->copy()->subDay())) {
                    // denominator: total new subscribers during campaign period
                    $totalNewSubs = CampaignSubscriber::where('campaign_id', $campaign->id)
                        ->whereBetween('subscribed_at', [$start, $end->copy()->endOfDay()])
                        ->count();

                    if ($totalNewSubs > 0 && $campaign->influencer_cost) {
                        // Both billable and non-billable use the same denominator per latest clarification
                        $cpa = round(((float)$campaign->influencer_cost) / $totalNewSubs, 2);
                    } else {
                        $cpa = null;
                    }
                }
            }

            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $newSubscribers = CampaignSubscriber::where('campaign_id', $campaign->id)
                    ->whereDate('subscribed_at', $cursor->toDateString())
                    ->count();

                $renewals = CampaignRenewal::where('campaign_id', $campaign->id)
                    ->whereDate('renewed_at', $cursor->toDateString())
                    ->count();

                $rows[] = [
                    'date' => $cursor->toDateString(),
                    'country' => $campaign->country,
                    'operator' => $campaign->operator,
                    'service' => $campaign->service,
                    'source' => $source,
                    'type' => $campaign->type,
                    'new_subscribers' => $newSubscribers,
                    'referrals' => $renewals,
                    'cpa' => $cpa,
                ];

                $cursor->addDay();
            }
        }

        return $rows;
    }
}
