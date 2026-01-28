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
    public function requests_without_x_tenant_header_fail_with_400_for_website_routes()
    {
        // /api/v1/get-date is now protected by 'ChangeTenantMiddleware'
        $response = $this->getJson('/api/v1/get-date');

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Tenant identifier is required']);
    }

    /** @test */
    public function header_tenant_finder_identifies_tenant_from_header()
    {
        $tenant = Tenant::first();
        $request = Request::create('/api/v1/get-date', 'GET');
        $request->headers->set('X-Tenant', $tenant->domain);

        $finder = new \App\TenantFinder\HeaderTenantFinder();
        $foundTenant = $finder->findForRequest($request);

        $this->assertNotNull($foundTenant);
        $this->assertEquals($tenant->id, $foundTenant->id);
    }

    /** @test */
    public function header_tenant_finder_returns_null_if_header_missing()
    {
        $request = Request::create('/api/v1/get-date', 'GET');

        $finder = new \App\TenantFinder\HeaderTenantFinder();
        $foundTenant = $finder->findForRequest($request);

        $this->assertNull($foundTenant);
    }

    /** @test */
    public function website_routes_correctly_identify_tenant_via_header()
    {
        $tenant = Tenant::first();

        $response = $this->withHeaders([
            'X-Tenant' => $tenant->domain,
        ])->getJson('/api/v1/get-date');

        $response->assertStatus(200);
    }

    /** @test */
    public function requests_with_invalid_tenant_fail_with_404()
    {
        $response = $this->withHeaders([
            'X-Tenant' => 'invalid-tenant',
        ])->getJson('/api/v1/get-date');

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Tenant not found']);
    }
}
