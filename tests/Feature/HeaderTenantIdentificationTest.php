<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tests\TestCase;

class HeaderTenantIdentificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // TestCase::setUp already created a tenant and made it current.
        // We want to test the identification logic, so we forget the current tenant.
        Tenant::forgetCurrent();
        DB::purge('tenant');
    }

    /** @test */
    public function requests_without_x_tenant_header_fail_for_routes_requiring_a_tenant()
    {
        $response = $this->getJson('/api/v1/tenant-check'); // Route protected by 'tenant' middleware

        $response->assertStatus(500); // Spatie NeedsTenant throws exception if no tenant found
    }

    /** @test */
    public function header_tenant_finder_identifies_tenant_from_header()
    {
        $tenant = Tenant::first();
        $request = Request::create('/api/v1/tenant-check', 'GET');
        $request->headers->set('X-Tenant', $tenant->domain);

        $finder = new \App\TenantFinder\HeaderTenantFinder();
        $foundTenant = $finder->findForRequest($request);

        $this->assertNotNull($foundTenant);
        $this->assertEquals($tenant->id, $foundTenant->id);
    }

    /** @test */
    public function header_tenant_finder_returns_null_if_header_missing()
    {
        $request = Request::create('/api/v1/tenant-check', 'GET');

        $finder = new \App\TenantFinder\HeaderTenantFinder();
        $foundTenant = $finder->findForRequest($request);

        $this->assertNull($foundTenant);
    }

    /** @test */
    public function change_tenant_middleware_works_with_x_tenant_header()
    {
        $tenant = Tenant::first();

        // Test with a route that only uses ChangeTenantMiddleware and doesn't require authentication
        $response = $this->withHeaders([
            'X-Tenant' => $tenant->domain,
        ])->getJson('/api/v1/test-change-tenant');

        $response->assertStatus(200);
        $response->assertJson(['tenant_id' => $tenant->id]);
    }
}
