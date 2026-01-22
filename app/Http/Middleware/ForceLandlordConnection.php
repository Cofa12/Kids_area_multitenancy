<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;


class ForceLandlordConnection
{
    public function handle($request, Closure $next)
    {
        Tenant::forgetCurrent();

        Config::set('database.default', 'landlord');
        DB::purge('tenant');

        if (!app()->environment('testing')) {
            DB::reconnect('landlord');
        }

        return $next($request);
    }
}
