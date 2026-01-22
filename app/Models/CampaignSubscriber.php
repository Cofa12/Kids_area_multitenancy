<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class CampaignSubscriber extends Model
{
    use UsesTenantConnection;

    protected $guarded = [];
}
