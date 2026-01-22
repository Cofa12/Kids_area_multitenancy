<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class LandlordUser extends Authenticatable implements JWTSubject
{
    protected $connection = 'landlord';
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'subscription_status',
        'referred_id',
        'referred_at',
        'expiration_date',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = [];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'phone' => $this->phone
        ];
    }


    public function getConnectionName()
    {
        return 'landlord';
    }
}