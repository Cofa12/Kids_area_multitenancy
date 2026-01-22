<?php

namespace Database\Seeders;

use App\Models\Preconversion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PreconversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Preconversion::create([
            'campaign_id' => '00000000-0000-0000-0000-000000000111',
            'click_id' => '00000000',
        ]);
        Preconversion::create([
            'campaign_id' => '00000000-0000-0000-0000-000000000111',
            'click_id' => '00000001',
        ]);
    }
}
