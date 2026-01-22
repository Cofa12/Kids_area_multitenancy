<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class NonBillableCampaignClick extends Model
{
    use UsesTenantConnection;

    protected $table = 'non_billable_campaign_clicks';

    protected $fillable = [
        'campaign_id',
        'click_id',
    ];
}
