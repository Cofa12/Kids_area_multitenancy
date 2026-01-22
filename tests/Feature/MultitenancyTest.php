<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultitenancyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $tenancy = false;

    /** @test */
    public function it_requires_a_tenant_to_access_api_routes()
    {
        $response = $this->getJson('/api/v1/categories');

        // Spatie by default throws NoCurrentTenant exception which results in 500
        // until we handle it, or it might just fail to find the tenant.
        $response->assertStatus(500);
    }

    /** @test */
    public function models_use_the_landlord_connection()
    {
        $category = new Category();

        $this->assertEquals('landlord', $category->getConnectionName());
    }

    /** @test */
    public function tenant_model_uses_the_landlord_connection()
    {
        $tenant = new Tenant();

        $this->assertEquals('landlord', $tenant->getConnectionName());
    }
}
