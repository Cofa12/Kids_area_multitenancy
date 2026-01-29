<?php

use App\Http\Controllers\V1\AdminAuth\AdminAuthController;
use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\CampaignController;
use App\Http\Controllers\V1\CategoryController;
use App\Http\Controllers\V1\DashboardController;
use App\Http\Controllers\V1\LandingPage;
use App\Http\Controllers\V1\VideoController;
use App\Http\Controllers\V1\WebsiteController;
use Illuminate\Support\Facades\Route;
use Spatie\Multitenancy\Http\Middleware\NeedsTenant;

use App\Http\Controllers\V1\Landlord\TenantController;

Route::apiResource('/categories', CategoryController::class);
Route::get('/all/categories-with-videos', [CategoryController::class, 'getCategoriesWithVideos']);
Route::get('/category/{id}/videos/search', [CategoryController::class, 'searchVideos']);
Route::get('/videos', [VideoController::class, 'index']);
Route::get('/videos/search', [VideoController::class, 'search']);

Route::middleware('landlord')->group(function () {
    Route::get('/landlord/tenants', [TenantController::class, 'index']);
    Route::post('/admin/login', [AdminAuthController::class, 'login']);

    Route::middleware('LandlordAuthenticationUser')->group(function () {
        Route::apiResource('/videos', VideoController::class)->except('index');
        Route::middleware('ChangeTenantMiddleware')->group(function () {
            Route::get('/get-unaccepted-child-photos', [DashboardController::class, 'getUnAcceptedChildPhotos']);
            Route::get('/get-accepted-child-photos', [DashboardController::class, 'getAcceptedChildPhotos']);
            Route::put('/accept-child-photo/{id}', [DashboardController::class, 'acceptChildPhoto']);
            Route::delete('/reject-child-photo/{id}', [DashboardController::class, 'rejectChildPhoto']);
            Route::get('/analytics', [DashboardController::class, 'getAnalytics']);
            Route::get('/child-photo/{id}', [DashboardController::class, 'showChildPhoto']);


            Route::get('dashboard/campaigns', [CampaignController::class, 'index']);
            Route::get('dashboard/campaigns/daily/analytics', [CampaignController::class, 'getDailyAnalytics']);
            Route::get('dashboard/campaigns/monthly/analytics', [CampaignController::class, 'getMonthlyAnalytics']);
            Route::get('dashboard/campaigns/{id}/daily/analytics', [CampaignController::class, 'getCampaignDailyAnalytics']);
            Route::get('dashboard/campaigns/{id}/monthly/analytics', [CampaignController::class, 'getCampaignMonthlyAnalytics']);

            Route::put('dashboard/campaigns', [CampaignController::class, 'updateData']);
            Route::post('dashboard/campaign/init', [CampaignController::class, 'initCampaign']);
            Route::put('dashboard/campaigns/{id}/end', [CampaignController::class, 'endCampaign'])->whereUuid('id');
            Route::put('dashboard/campaigns/{id}/pause', [CampaignController::class, 'pauseCampaign'])->whereUuid('id');
            Route::put('dashboard/campaigns/{id}/active', [CampaignController::class, 'activeCampaign'])->whereUuid('id');
            Route::put('dashboard/campaigns/{id}/cpa', [CampaignController::class, 'updateCpa'])->whereUuid('id');

            Route::post('dashboard/campaigns/non-billable-clicks', [CampaignController::class, 'storeNonBillableClick']);
        });
    });

});

Route::middleware('CheckAuthenticationUser')->get('/category/{id}/videos', [CategoryController::class, 'show']);

Route::middleware(['ChangeTenantMiddleware'])->group(function () {
    Route::post('/safaricom/callback', [LandingPage::class, 'callback']);
    Route::get('/get-date', [WebsiteController::class, 'getDate']);
    Route::post('/website/register', [WebsiteController::class, 'register']);
    Route::post('/website/login', [WebsiteController::class, 'login']);


    Route::middleware('CheckAuthenticationUser')->group(function () {
        Route::post('/upload-child-photo', [WebsiteController::class, 'uploadChildPhoto']);
    });

    Route::post('refresh-token', [AuthController::class, 'refreshToken']);

    Route::post('/landing/auth', [AuthController::class, 'registerFromLandingPage']);
    Route::post('/login', [AuthController::class, 'login']);
});
