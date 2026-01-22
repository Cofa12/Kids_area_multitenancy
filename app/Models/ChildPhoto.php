<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ChildPhoto extends Model
{
    use UsesTenantConnection;

    //
    protected $guarded = [];
    protected $table = 'child_photos';

    public function child(): BelongsTo
    {
        return $this->belongsTo(User::class, 'child_id', 'id');
    }
}
