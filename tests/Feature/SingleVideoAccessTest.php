<?php

namespace Tests\Feature;

use App\Models\Video;
use App\Models\LandlordUser;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SingleVideoAccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $tenancy = true;
    private $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::first();
    }

    /** @test */
    public function landlord_user_can_access_single_video()
    {
        // 1. Create a landlord user
        $landlordUser = LandlordUser::create([
            'name' => 'Landlord Admin',
            'phone' => '1234567890',
            'password' => bcrypt('password'),
        ]);

        // 2. Create a category and video in landlord DB
        $category = Category::create(['title_en' => 'Test Cat', 'title_ar' => 'اختبار']);
        $video = Video::create([
            'title_en' => 'Test Video',
            'video_url_en' => 'http://example.com/video.mp4',
            'category_id' => $category->id,
            'user_id' => $landlordUser->id,
        ]);

        // 3. Generate token
        $token = JWTAuth::fromUser($landlordUser);

        // 4. Request
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Tenant', $this->tenant->name)
            ->getJson("/api/v1/videos/{$video->id}");

        if ($response->status() !== 200) {
            dd($response->json());
        }
        $response->assertStatus(200);
        $response->assertJsonPath('id', $video->id);
    }

    /** @test */
    public function tenant_user_can_access_single_video()
    {
        // 1. Create a tenant user
        $this->tenant->makeCurrent();
        $tenantUser = User::create([
            'name' => 'Tenant User',
            'phone' => '0987654321',
            'password' => bcrypt('password'),
        ]);

        // 2. Create a landlord admin for video ownership
        $landlordUser = LandlordUser::create([
            'name' => 'Admin',
            'phone' => '1112223333',
            'password' => bcrypt('password'),
        ]);

        // 3. Create a video in landlord DB
        $category = Category::create(['title_en' => 'Test Cat', 'title_ar' => 'اختبار']);
        $video = Video::create([
            'title_en' => 'Test Video',
            'video_url_en' => 'http://example.com/video.mp4',
            'category_id' => $category->id,
            'user_id' => $landlordUser->id,
        ]);

        // 4. Generate token for tenant user
        $token = JWTAuth::fromUser($tenantUser);

        // 5. Request
        $this->tenant->makeCurrent(); // Ensure connection is tenant for middleware to find user
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Tenant', $this->tenant->name)
            ->getJson("/api/v1/videos/{$video->id}");

        if ($response->status() !== 200) {
            dd($response->json());
        }
        $response->assertStatus(200);
        $response->assertJsonPath('id', $video->id);
    }

    /** @test */
    public function unauthorized_user_cannot_access_single_video()
    {
        $category = Category::create(['title_en' => 'Test Cat', 'title_ar' => 'اختبار']);
        $video = Video::create([
            'title_en' => 'Test Video',
            'video_url_en' => 'http://example.com/video.mp4',
            'category_id' => $category->id,
            'user_id' => 1,
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->name)
            ->getJson("/api/v1/videos/{$video->id}");

        $response->assertStatus(401);
    }
}
