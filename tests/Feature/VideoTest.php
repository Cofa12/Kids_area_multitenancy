<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Models\User;
use App\Models\LandlordUser;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_a_video(): void
    {
        $lang = 'en';

        $user = LandlordUser::create([
            'name' => 'cofa',
            'phone' => '+201012345671',
            'password' => bcrypt('CDCD12345##')
        ]);

        $category = Category::create([
            'title_en' => 'Test Category',
            'title_ar' => 'اختبار'
        ]);

        $userToken = auth('admin')->attempt(['phone' => $user->phone, 'password' => 'CDCD12345##']);

        $videoData = [
            'title_en' => 'Test Video',
            'title_ar' => 'اختبار',
            'description_en' => 'Testing upload...',
            'description_ar' => 'اختبار التحميل',
            'thumbnail_url_en' => UploadedFile::fake()->image('photo.png', 1000, 1000),
            'thumbnail_url_ar' => UploadedFile::fake()->image('photo.png', 1000, 1000),
            'video_url_en' => "video.mp4",
            'video_url_ar' => "video1.mp4",
            'category_id' => $category->id,
            'user_id' => $user->id
        ];

        $sut = $this->postJson('/api/v1/videos', $videoData, [
            'Accept' => 'application/json',
            'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A',
            'Authorization' => 'Bearer ' . $userToken,
            'Accept-Language' => $lang,
        ]);

        $sut->assertStatus(\Illuminate\Http\JsonResponse::HTTP_CREATED);
        $sut->assertJsonStructure([
            'message'
        ]);

        $this->assertDatabaseHas('videos', [
            'title_en' => $videoData['title_en'],
            'title_ar' => $videoData['title_ar'],
            'description_en' => $videoData['description_en'],
            'description_ar' => $videoData['description_ar'],
            'category_id' => $category->id,
            'user_id' => $user->id
        ], 'landlord');
    }

    public function test_get_videos(): void
    {
        $category = Category::create([
            'title_en' => 'Test Category',
            'title_ar' => 'اختبار'
        ]);

        Video::create([
            'title_en' => 'Test Video',
            'title_ar' => 'اختبار',
            'description_en' => 'Testing upload...',
            'description_ar' => 'اختبار التحميل',
            'thumbnail_url_en' => 'thumbnail.png',
            'thumbnail_url_ar' => 'thumbnail.png',
            'video_url_en' => "video.mp4",
            'video_url_ar' => "video1.mp4",
            'category_id' => $category->id,
            'user_id' => 1
        ]);

        $sut = $this->getJson('/api/v1/videos', headers: [
            'Accept' => 'application/json',
            'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A',
        ]);

        $sut->assertStatus(JsonResponse::HTTP_OK);
        $sut->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'thumbnail_url',
                    'created_at',
                ]
            ]
        ]);
    }

}
