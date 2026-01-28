<?php

namespace App\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Spatie\Multitenancy\Contracts\IsTenant;

class HeaderTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $tenantIdentifier = $request->header('X-Tenant');

        if (!$tenantIdentifier) {
            return null;
        }

        /** @var Tenant $tenantModel */
        $tenantModel = app(IsTenant::class);

        return $tenantModel::where('domain', $tenantIdentifier)
            ->orWhere('name', $tenantIdentifier)
            ->first();
    }
}
