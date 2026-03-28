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
    private $category;
    private $landlordUser;
    private $video;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::first();

        $this->landlordUser = LandlordUser::create([
            'name'     => 'Landlord Admin',
            'phone'    => '1234567890',
            'password' => bcrypt('password'),
        ]);

        $this->category = Category::create(['title_en' => 'Test Cat', 'title_ar' => 'اختبار']);
        $this->video = Video::create([
            'title_en'     => 'Test Video',
            'video_url_en' => 'http://example.com/video.mp4',
            'category_id'  => $this->category->id,
            'user_id'      => $this->landlordUser->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function landlord_can_access_single_video_without_x_tenant()
    {
        $token = JWTAuth::fromUser($this->landlordUser);

        // No X-Tenant header needed for landlord users
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/videos/{$this->video->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $this->video->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function landlord_can_also_access_single_video_with_x_tenant()
    {
        $token = JWTAuth::fromUser($this->landlordUser);

        // X-Tenant is optional but still accepted for landlord users
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Tenant', $this->tenant->name)
            ->getJson("/api/v1/videos/{$this->video->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $this->video->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function tenant_user_can_access_single_video_with_x_tenant()
    {
        $this->tenant->makeCurrent();
        $tenantUser = User::create([
            'name'     => 'Tenant User',
            'phone'    => '0987654321',
            'password' => bcrypt('password'),
        ]);

        $token = JWTAuth::fromUser($tenantUser);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Tenant', $this->tenant->name)
            ->getJson("/api/v1/videos/{$this->video->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $this->video->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unauthenticated_request_returns_401()
    {
        // No token at all
        $response = $this->getJson("/api/v1/videos/{$this->video->id}");

        $response->assertStatus(401);
    }
}
