<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class SdpResponse extends Model
{
    use UsesTenantConnection;

    protected $table = 'sdp_responses';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Alias for trx_id -> trxId
     */
    protected function trxId(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => $value ?? ($attributes['trxId'] ?? null),
        );
    }
}
