<?php

use App\Http\Middleware\ChangeTenantMiddleware;
use App\Http\Middleware\CheckAuthenticationUser;
use App\Http\Middleware\CheckTenantsLog;
use App\Http\Middleware\ForceLandlordConnection;
use App\Http\Middleware\LandlordAuthenticationUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \Spatie\Multitenancy\Http\Middleware\NeedsTenant::class,
            'landlord' => ForceLandlordConnection::class,
            'CheckAuthenticationUser' => CheckAuthenticationUser::class,
            'LandlordAuthenticationUser' => LandlordAuthenticationUser::class,
            'ChangeTenantMiddleware' => ChangeTenantMiddleware::class,
            'CheckTenant' => CheckTenantsLog::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
