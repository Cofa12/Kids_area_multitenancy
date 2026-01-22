<?php

namespace Database\Seeders;

use App\Models\CampaignSubscriber;
use App\Models\User;
use Illuminate\Database\Seeder;

class CampaignSubscriberSeeder extends Seeder
{
    /**
     * Seed subscribers for the influencer campaign created in CampaignSeeder.
     */
    public function run(): void
    {
        $campaignId = '00000000-0000-0000-0000-000000000111';

        $users = [];
        for ($i = 0; $i < 4; $i++) {
            $users[$i] = User::firstOrCreate(
                ['phone' => '+2010' . str_pad((string)$i, 9, '0', STR_PAD_LEFT)],
                ['name' => 'u' . $i, 'password' => 'pass12345']
            );
        }


        CampaignSubscriber::updateOrCreate([
            'campaign_id' => $campaignId,
            'user_id' => $users[0]->id,
        ], [
            'campaign_id' => $campaignId,
            'user_id' => $users[0]->id,
        ]);

        CampaignSubscriber::updateOrCreate([
            'campaign_id' => $campaignId,
            'user_id' => $users[1]->id,
        ], [
            'campaign_id' => $campaignId,
            'user_id' => $users[1]->id,
        ]);

        CampaignSubscriber::updateOrCreate([
            'campaign_id' => $campaignId,
            'user_id' => $users[2]->id,
        ], [
            'campaign_id' => $campaignId,
            'user_id' => $users[2]->id,
        ]);

        CampaignSubscriber::updateOrCreate([
            'campaign_id' => $campaignId,
            'user_id' => $users[3]->id,
        ], [
            'campaign_id' => $campaignId,
            'user_id' => $users[3]->id,
        ]);
    }
}
