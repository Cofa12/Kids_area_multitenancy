<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class HeEntry extends Model
{
    use UsesTenantConnection;

    protected $table = 'he_entries';

    protected $guarded = [];

    protected $casts = [
        'headers' => 'array',
        'query_params' => 'array',
    ];
}
