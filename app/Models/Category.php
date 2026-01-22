<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Category extends Model
{
    protected $connection = 'landlord';

    //
    protected $guarded = [];
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function getConnectionName()
    {
        return 'landlord';
    }
}
