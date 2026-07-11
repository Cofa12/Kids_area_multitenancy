<?php

namespace App\TenantResolution\Strategies;

use App\Models\Tenant;
use App\TenantResolution\Contracts\TenantResolutionStrategy;
use Illuminate\Http\Request;

class MtnNaijriaTenantStrategy implements TenantResolutionStrategy
{
    public function supports(Request $request): bool
    {
        return str_contains(strtolower($request->path()), 'mtn');
    }

    public function resolve(Request $request): ?Tenant
    {
        $identifier = (string) config('services.mtn.tenant', 'naijria');

        return Tenant::whereRaw('LOWER(name) = ?', [strtolower($identifier)])
            ->orWhereRaw('LOWER(domain) = ?', [strtolower($identifier)])
            ->first();
    }
}
