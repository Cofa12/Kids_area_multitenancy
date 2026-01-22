<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChildPhoto;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    public function test_child_can_upload_a_photo(): void
    {
        $user = User::factory()->create([
            'name' => 'cofa',
            'phone' => '+201012345671',
            'password' => 'CDCD12345##'
        ]);

        $payload = [
            'photo' => \Illuminate\Http\UploadedFile::fake()->image('photo.png', 1000, 1000),
            'description' => 'hello world'
        ];

        $sut = $this->postJson('http://test.localhost/api/v1/upload-child-photo', $payload, [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . auth()->attempt(['phone' => $user->phone, 'password' => 'CDCD12345##']),
            'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A',
            'Accept-Language' => 'en',
        ]);

        $sut->assertStatus(\Illuminate\Http\JsonResponse::HTTP_CREATED);
        $sut->assertJsonStructure([
            'message'
        ]);
    }

    public function test_get_category_videos(): void
    {


        $user = User::factory()->create([
            'name' => 'cofa',
            'phone' => '+201012345671',
            'password' => 'CDCD12345##'
        ]);

        $category = Category::create([
            'title_en' => 'Test Category',
            'title_ar' => 'اختبار'
        ]);

        Video::create([
            'title_en' => 'Test Video',
            'title_ar' => 'اختبار',
            'description_en' => 'Testing upload...',
            'description_ar' => 'اختبار التحميل',
            'thumbnail_url_en' => 'video/12656.png',
            'thumbnail_url_ar' => 'video/126٧6.png',
            'video_url_en' => 'video/12656.mp4',
            'video_url_ar' => 'video/1265٧.mp4',
            'category_id' => $category->id,
            'user_id' => $user->id
        ]);

        $this->withoutExceptionHandling(); // See exact error

        $sut = $this->getJson('http://test.localhost/api/v1/category/' . $category->id . '/videos', headers: [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . auth()->attempt(['phone' => $user->phone, 'password' => 'CDCD12345##']),
            'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A',
            'Accept-Language' => 'en',
        ]);

        $sut->assertStatus(\Illuminate\Http\JsonResponse::HTTP_OK);
        $sut->assertJsonStructure([
            'videos' => [
                '*' => [
                    'id',
                    'title',
                    'thumbnail_url',
                    'created_at',
                ]
            ]
        ]);
    }

    public function test_get_all_categories(): void
    {

        $user = User::factory()->create([
            'name' => 'cofa',
            'phone' => '+201012345671',
            'password' => 'CDCD12345##'
        ]);

        ChildPhoto::create([
            'image_url' => 'video/12656.png',
            'child_id' => $user->id,
            'description' => "description"
        ]);

        $sut = $this->getJson('http://test.localhost/api/v1/categories', headers: [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . auth()->attempt(['phone' => $user->phone, 'password' => 'CDCD12345##']),
            'x-api-key' => 'qNw0_Is6InOnG1HiDCT2fstk33JGwuD-6ftdGa4d8hn3RcXx5GT86kvTLop6BgZx732rdGWXnqhUhUJGjQU6pr-40PYzLceAX-up8hiDfyPQ1IJcTR84YPC_IBF2FzKr3QIX6LroF-lZYr67cg8-hNiSeK39cJWlAoZjbKUU6FSLOO3-8kW2xmejNSTR3FQBbLpGFgsfmuJra90jbI1dI7SNO9TDqOZgD6kYZYyEdGA684Iri2-mSB-zKvYLON7vJtadbFcpbHkac1F6Iqil7ZsDSJFrQVYLVGt9kYJDkf3wgkgOmRpsOijWeQ9eE63sywD4sGMmckdqZ27kU2cl6A',
            'Accept-Language' => 'en',
        ]);

        $sut->assertStatus(JsonResponse::HTTP_OK);
        $sut->assertJsonStructure([
            '*' => [
                'id',
                'title',
            ]
        ]);

    }
}
