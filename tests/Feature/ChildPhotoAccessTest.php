<?php

namespace Tests\Feature;

use App\Models\ChildPhoto;
use App\Models\LandlordUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChildPhotoAccessTest extends TestCase
{
    use RefreshDatabase;

    protected bool $tenancy = true;

    private $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        // The TestCase automatically sets up a tenant in its setupTenant method.
        $this->tenant = Tenant::first();
    }

    /** @test */
    public function landlord_user_can_access_child_photo()
    {
        // 1. Create a landlord user in the landlord connection
        // The TestCase setup ensures 'landlord' connection is active for landlord models
        $landlordUser = LandlordUser::create([
            'name' => 'Landlord Admin',
            'phone' => '1234567890',
            'email' => 'admin@landlord.com',
            'password' => bcrypt('password'),
        ]);

        // 2. Create a child photo in the tenant database
        $this->tenant->makeCurrent();

        $childUser = User::create([
            'name' => 'Child Name',
            'phone' => '1112223333',
            'email' => 'child@example.com',
            'password' => bcrypt('password'),
        ]);

        $childPhoto = ChildPhoto::create([
            'child_id' => $childUser->id,
            'image_url' => 'photos/test.jpg',
            'isAccepted' => false,
            'description' => 'Test description',
        ]);

        // 3. Generate token
        $token = JWTAuth::fromUser($landlordUser);

        // 4. Request
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Tenant', $this->tenant->name)
            ->getJson("/api/v1/child-photo/{$childPhoto->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $childPhoto->id);
        $response->assertJsonPath('child_name', $childUser->name);
    }

    /** @test */
    public function tenant_user_can_access_child_photo()
    {
        // 1. Create a tenant user
        $this->tenant->makeCurrent();
        $tenantUser = User::create([
            'name' => 'Tenant User',
            'phone' => '0987654321',
            'email' => 'user@tenant.com',
            'password' => bcrypt('password'),
        ]);

        // 2. Create a child photo
        $childPhoto = ChildPhoto::create([
            'child_id' => $tenantUser->id,
            'image_url' => 'photos/test.jpg',
            'isAccepted' => false,
            'description' => 'Test description',
        ]);

        // 3. Generate token
        $token = JWTAuth::fromUser($tenantUser);

        // 4. Request
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Tenant', $this->tenant->name)
            ->getJson("/api/v1/child-photo/{$childPhoto->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $childPhoto->id);
        $response->assertJsonPath('child_name', $tenantUser->name);
    }

    /** @test */
    public function unauthorized_user_cannot_access_child_photo()
    {
        $this->tenant->makeCurrent();
        $childUser = User::create([
            'name' => 'Child Name',
            'phone' => '1112223333',
            'email' => 'child@example.com',
            'password' => bcrypt('password'),
        ]);

        $childPhoto = ChildPhoto::create([
            'child_id' => $childUser->id,
            'image_url' => 'photos/test.jpg',
            'isAccepted' => false,
            'description' => 'Test description',
        ]);

        $response = $this->withHeader('X-Tenant', $this->tenant->name)
            ->getJson("/api/v1/child-photo/{$childPhoto->id}");

        $response->assertStatus(401);
    }
}
