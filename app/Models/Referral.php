<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Referral extends Model
{
    use UsesTenantConnection;

    protected $table = 'referrals';

    protected $fillable = [
        'user_id',
        'owner_id',
        'referred_id',
        'referred_at',
    ];

    protected $casts = [
        'referred_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
