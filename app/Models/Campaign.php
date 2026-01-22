<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Campaign extends Model
{
    use HasUuids, UsesTenantConnection;
    //

    protected $guarded = [];

    public function subscribers(): HasMany
    {
        return $this->hasMany(CampaignSubscriber::class, 'campaign_id', 'id');
    }

    public function preConversions(): HasMany
    {
        return $this->hasMany(PreConversion::class, 'campaign_id', 'id');
    }

    public function nonBillableClicks(): HasMany
    {
        return $this->hasMany(NonBillableCampaignClick::class, 'campaign_id', 'id');
    }
}
