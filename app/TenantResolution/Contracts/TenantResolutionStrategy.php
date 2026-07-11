<?php

namespace App\TenantResolution\Contracts;

use App\Models\Tenant;
use Illuminate\Http\Request;

interface TenantResolutionStrategy
{
    public function supports(Request $request): bool;

    public function resolve(Request $request): ?Tenant;
}
