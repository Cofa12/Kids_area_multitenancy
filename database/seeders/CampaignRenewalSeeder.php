<?php
//
//namespace Database\Seeders;
//
//use App\Models\CampaignRenewal;
//use App\Models\User;
//use Illuminate\Database\Seeder;
//
//class CampaignRenewalSeeder extends Seeder
//{
//    /**
//     * Seed referrals for the influencer campaign created in CampaignSeeder.
//     */
//    public function run(): void
//    {
//        $campaignId = '00000000-0000-0000-0000-000000000111';
//
//        $u0 = User::where('phone', '+201000000000')->first();
//        $u1 = User::where('phone', '+201000000001')->first();
//
//        if ($u0) {
//            CampaignRenewal::updateOrCreate([
//                'campaign_id' => $campaignId,
//                'user_id' => $u0->id,
//                'renewed_at' => '2025-11-17 10:00:00',
//            ], [
//                'campaign_id' => $campaignId,
//                'user_id' => $u0->id,
//                'renewed_at' => '2025-11-17 10:00:00',
//            ]);
//        }
//
//        if ($u1) {
//            CampaignRenewal::updateOrCreate([
//                'campaign_id' => $campaignId,
//                'user_id' => $u1->id,
//                'renewed_at' => '2025-11-19 10:00:00',
//            ], [
//                'campaign_id' => $campaignId,
//                'user_id' => $u1->id,
//                'renewed_at' => '2025-11-19 10:00:00',
//            ]);
//        }
//    }
//}
