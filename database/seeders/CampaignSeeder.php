<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Seed base campaigns for analytics testing.
     */
    public function run(): void
    {
        $influencerId = '00000000-0000-0000-0000-000000000111';
        $agencyId     = '00000000-0000-0000-0000-000000000222';
        $noEndId      = '00000000-0000-0000-0000-000000000333';

        Campaign::updateOrCreate([
            'id' => $influencerId,
        ], [
            'country' => 'EG',
            'operator' => 'mtn',
            'service' => 'kidsArea',
            'start_date' => '2025-11-15',
            'end_date' => '2025-11-20',
            'influencer_id' => 'inf-123',
            'influencer_cost' => 200,
            'type' => 'billable',
            'status' => 'ended',
            'cpa' => 0,
        ]);

        Campaign::updateOrCreate([
            'id' => $agencyId,
        ], [
            'country' => 'KE',
            'operator' => 'safaricom',
            'service' => 'kidsArea',
            'start_date' => '2025-11-10',
            'end_date' => '2025-11-12',
            'agency_id' => 'ag-999',
            'cpa' => 150,
            'type' => 'billable',
            'status' => 'ended',
        ]);

        Campaign::updateOrCreate([
            'id' => $noEndId,
        ], [
            'country' => 'NG',
            'operator' => 'airtel',
            'service' => 'kidsArea',
            'start_date' => '2025-11-01',
            'end_date' => null,
            'influencer_id' => 'inf-x',
            'influencer_cost' => 99,
            'type' => 'non-billable',
            'status' => 'active',
            'cpa' => 0,
        ]);
    }
}
