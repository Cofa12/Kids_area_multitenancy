<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'country'=>$this->country,
            'operator'=>$this->operator,
            'service'=>$this->service,
            'start_date'=>$this->start_date,
            'end_date'=>$this->end_date,
            'agency_id'=>$this->agency_id,
            'cpa'=>$this->cpa,
            'influencer_id'=>$this->influencer_id,
            'influencer_cost'=>$this->influencer_cost,
            'type'=>$this->type,
            'status'=>$this->status,
            'created_at'=>$this->created_at,
        ];
    }
}
