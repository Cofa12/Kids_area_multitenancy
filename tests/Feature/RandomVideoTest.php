<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Models\LandlordUser;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class RandomVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_random_videos_with_language_filtering(): void
    {
        // 1. Setup Data
        $user = LandlordUser::create([
            'name' => 'cofa',
            'phone' => '+201012345671',
            'password' => bcrypt('CDCD12345##')
        ]);

        $category = Category::create([
            'title_en' => 'Test Category',
            'title_ar' => 'اختبار'
        ]);

        // Video with only EN title
        Video::create([
            'title_en' => 'Video EN',
            'title_ar' => null,
            'description_en' => 'Description EN',
            'description_ar' => null,
            'video_url_en' => "video_en.mp4",
            'video_url_ar' => null,
            'thumbnail_url_en' => 'thumb_en.png',
            'thumbnail_url_ar' => null,
            'category_id' => $category->id,
            'user_id' => $user->id
        ]);

        // Video with only AR title
        Video::create([
            'title_en' => null,
            'title_ar' => 'فيديو عربي',
            'description_en' => null,
            'description_ar' => 'وصف عربي',
            'video_url_en' => null,
            'video_url_ar' => "video_ar.mp4",
            'thumbnail_url_en' => null,
            'thumbnail_url_ar' => 'thumb_ar.png',
            'category_id' => $category->id,
            'user_id' => $user->id
        ]);

        // Video with both
        Video::create([
            'title_en' => 'Video Both',
            'title_ar' => 'فيديو مشترك',
            'description_en' => 'Description Both',
            'description_ar' => 'وصف مشترك',
            'video_url_en' => "video_both_en.mp4",
            'video_url_ar' => "video_both_ar.mp4",
            'thumbnail_url_en' => 'thumb_both_en.png',
            'thumbnail_url_ar' => 'thumb_both_ar.png',
            'category_id' => $category->id,
            'user_id' => $user->id
        ]);

        // 2. Authenticate
        $token = auth('admin')->attempt(['phone' => $user->phone, 'password' => 'CDCD12345##']);

        // 3. Test EN language
        $responseEn = $this->getJson('/api/v1/videos/random', [
            'Authorization' => 'Bearer ' . $token,
            'Accept-Language' => 'en',
        ]);

        $responseEn->assertStatus(JsonResponse::HTTP_OK);
        $responseEn->assertJsonCount(2); // Only Video EN and Video Both
        $responseEn->assertJsonFragment(['title' => 'Video EN']);
        $responseEn->assertJsonFragment(['title' => 'Video Both']);
        $responseEn->assertJsonMissing(['title' => 'فيديو عربي']);
        $responseEn->assertJsonStructure([
            '*' => ['title', 'description', 'url', 'thumbnail']
        ]);

        // 4. Test AR language
        $responseAr = $this->getJson('/api/v1/videos/random', [
            'Authorization' => 'Bearer ' . $token,
            'Accept-Language' => 'ar',
        ]);

        $responseAr->assertStatus(JsonResponse::HTTP_OK);
        $responseAr->assertJsonCount(2); // Only Video AR and Video Both
        $responseAr->assertJsonFragment(['title' => 'فيديو عربي']);
        $responseAr->assertJsonFragment(['title' => 'فيديو مشترك']);
        $responseAr->assertJsonMissing(['title' => 'Video EN']);
    }
}
