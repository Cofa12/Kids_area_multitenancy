<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CategoryVideoSortTest extends TestCase
{
    // use RefreshDatabase;

    private function makeUserAndCategory(): array
    {
        $user = User::factory()->create([
            'name' => 'cofa',
            'phone' => '+201012345671',
            'password' => 'CDCD12345##',
        ]);

        $category = Category::create([
            'title_en' => 'Kids',
            'title_ar' => 'أطفال',
        ]);

        return [$user, $category];
    }

    public function test_sort_videos_index_by_newest(): void
    {
        [$user, $category] = $this->makeUserAndCategory();
        $token = JWTAuth::fromUser($user);

        $v1 = Video::create([
            'title_en' => 'A Old',
            'title_ar' => 'قديم',
            'description_en' => 'desc',
            'description_ar' => 'وصف',
            'video_url_en' => 'v1.mp4',
            'video_url_ar' => 'v1_ar.mp4',
            'thumbnail_url_en' => 't1.png',
            'thumbnail_url_ar' => 't1_ar.png',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);

        $v2 = Video::create([
            'title_en' => 'B Middle',
            'title_ar' => 'وسط',
            'description_en' => 'desc',
            'description_ar' => 'وصف',
            'video_url_en' => 'v2.mp4',
            'video_url_ar' => 'v2_ar.mp4',
            'thumbnail_url_en' => 't2.png',
            'thumbnail_url_ar' => 't2_ar.png',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        $v3 = Video::create([
            'title_en' => 'C New',
            'title_ar' => 'جديد',
            'description_en' => 'desc',
            'description_ar' => 'وصف',
            'video_url_en' => 'v3.mp4',
            'video_url_ar' => 'v3_ar.mp4',
            'thumbnail_url_en' => 't3.png',
            'thumbnail_url_ar' => 't3_ar.png',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $resNewest = $this->getJson("/api/v1/category/{$category->id}/videos?sort=newest", headers: [
            'Accept' => 'application/json',
            'Accept-language' => 'en',
            'Authorization' => 'Bearer ' . $token,
        ]);

        $resNewest->assertStatus(JsonResponse::HTTP_OK);
        $idsNewest = array_column($resNewest->json('data'), 'id');
        $this->assertSame([$v3->id, $v2->id, $v1->id], $idsNewest);
    }

    public function test_sort_videos_index_by_oldest(): void
    {
        [$user, $category] = $this->makeUserAndCategory();
        $token = JWTAuth::fromUser($user);

        $v1 = Video::create([
            'title_en' => 'A Old',
            'title_ar' => 'قديم',
            'description_en' => 'desc',
            'description_ar' => 'وصف',
            'video_url_en' => 'v1.mp4',
            'video_url_ar' => 'v1_ar.mp4',
            'thumbnail_url_en' => 't1.png',
            'thumbnail_url_ar' => 't1_ar.png',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);

        $v2 = Video::create([
            'title_en' => 'B Middle',
            'title_ar' => 'وسط',
            'description_en' => 'desc',
            'description_ar' => 'وصف',
            'video_url_en' => 'v2.mp4',
            'video_url_ar' => 'v2_ar.mp4',
            'thumbnail_url_en' => 't2.png',
            'thumbnail_url_ar' => 't2_ar.png',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        $v3 = Video::create([
            'title_en' => 'C New',
            'title_ar' => 'جديد',
            'description_en' => 'desc',
            'description_ar' => 'وصف',
            'video_url_en' => 'v3.mp4',
            'video_url_ar' => 'v3_ar.mp4',
            'thumbnail_url_en' => 't3.png',
            'thumbnail_url_ar' => 't3_ar.png',
            'category_id' => $category->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $resOldest = $this->getJson("/api/v1/category/{$category->id}/videos?sort=oldest", headers: [
            'Accept' => 'application/json',
            'Accept-language' => 'en',
            'Authorization' => 'Bearer ' . $token,
        ]);

        $resOldest->assertStatus(JsonResponse::HTTP_OK);
        $idsOldest = array_column($resOldest->json('data'), 'id');
        $this->assertSame([$v1->id, $v2->id, $v3->id], $idsOldest);
    }
}
