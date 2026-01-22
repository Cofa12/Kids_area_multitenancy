<?php

namespace Tests\Feature\Landlord;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    protected bool $tenancy = true;


    /** @test */
    public function it_can_list_all_tenants_as_landlord()
    {

        Tenant::create([
            'name' => 'Kenya',
            'domain' => 'kenya.kidsarea.com',
            'database' => 'tenant_kenya_test',
        ]);

        $response = $this->getJson('/api/v1/landlord/tenants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'domain']
                ]
            ])
            ->assertJsonCount(2, 'data');
    }
}
