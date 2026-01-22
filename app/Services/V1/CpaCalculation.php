<?php

namespace App\Services\V1;

use App\Models\Campaign;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

class CpaCalculation
{
    public function calculateCpa(Campaign $campaign,Carbon $currentDate)
    {
        if($campaign->influencer_id)
            return $this->calculateInfluencerCpa($campaign,$currentDate);

        return $campaign->cpa;

    }

    private function calculateInfluencerCpa(Campaign $campaign,Carbon $currentDate)
    {
        if(!$this->isCampaignEnded($campaign->end_date))
            return null;

        return $this->calculateInfluencerCpaDependOnType($campaign,$currentDate);

    }

    private function isCampaignEnded(string|null $endDate):bool
    {
        return $endDate && $endDate < now();
    }

    private function calculateInfluencerCpaDependOnType($campaign,Carbon $currentDate)
    {
        $date = $currentDate->toDateString();

        if ($campaign->type === 'billable') {
            $count = $campaign->subscribers()->whereDate('created_at', $date)->count();
        } else {
            $count = $campaign->preConversions()->whereDate('created_at', $date)->count();
        }

        if ($count == 0) {
            return 0;
        }

        return $campaign->influencer_cost / $count;
    }

}
