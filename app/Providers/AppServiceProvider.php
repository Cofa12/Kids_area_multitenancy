<?php

namespace App\Providers;

use App\Http\Resources\V1\AuthenticatedUser;
use App\Http\Resources\V1\CampaignResource;
use App\Http\Resources\V1\ShowVideosOfCategory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        AuthenticatedUser::withoutWrapping();
        ShowVideosOfCategory::withoutWrapping();
        CampaignResource::withoutWrapping();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
