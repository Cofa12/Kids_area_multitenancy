<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\LandingPage;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['ChangeTenantMiddleware'])->group(function () {
    Route::post('/callback-handler/api/callback', [LandingPage::class, 'callback']);
});
