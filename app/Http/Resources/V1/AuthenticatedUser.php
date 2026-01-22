<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class AuthenticatedUser extends JsonResource
{
    public function __construct(private array $tokens, Authenticatable $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'created_at' => $this->resource->created_at,
        ];

        if (DB::getConnections()!='landlord')
        {
            $user['referral_code'] = $this->resource->referral_code;
            $user['number_of_referrals']= $this->resource->number_of_referrals;
        }
        return [
            'user' => $user,
            'access_token' => $this->tokens['access_token'],
            'expires_in' => $this->tokens['expires_in'],
            'refresh_token' => $this->tokens['refresh_token'],
            'refresh_expires_in' => $this->tokens['refresh_expires_in'],

        ];
    }
}
