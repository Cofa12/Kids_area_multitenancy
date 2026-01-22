<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Multitenancy\Models\Tenant;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Tenant::checkCurrent()
            ? $this->runTenantSpecificSeeders()
            : $this->runLandlordSpecificSeeders();
    }

    public function runTenantSpecificSeeders()
    {
        $this->call([
            TempAccountWebsiteSeeder::class,
            CampaignSeeder::class,
            CampaignSubscriberSeeder::class,
            PreconversionSeeder::class
        ]);
    }

    public function runLandlordSpecificSeeders()
    {
        $this->call([
            AdminSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
