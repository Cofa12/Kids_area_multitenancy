<?php

namespace App\TenantResolution\Strategies;

use App\Models\Tenant;
use App\TenantResolution\Contracts\TenantResolutionStrategy;
use Illuminate\Http\Request;

class MtnNaijriaTenantStrategy implements TenantResolutionStrategy
{
    public function supports(Request $request): bool
    {
        $path = strtolower($request->path());

        return str_contains($path, 'mtn') || str_contains($path, 'sdp');
    }

    public function resolve(Request $request): ?Tenant
    {
        $identifier = (string) config('services.mtn.tenant', 'naijria');
        $landlordConn = config('multitenancy.landlord_database_connection_name', 'landlord');

        return Tenant::on($landlordConn)
            ->where(function ($q) use ($identifier) {
                $q->whereRaw('LOWER(name) = ?', [strtolower($identifier)])
                  ->orWhereRaw('LOWER(domain) = ?', [strtolower($identifier)]);
            })
            ->first();
    }
}
