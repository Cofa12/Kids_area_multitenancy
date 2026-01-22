<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected bool $tenancy = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->tenancy) {
            $this->setupTenant();
        }
    }

    protected function setupTenant()
    {
        // 1. Configure Landlord to use SQLite
        $landlordDb = database_path('landlord_test.sqlite');
        if (file_exists($landlordDb)) {
            @unlink($landlordDb);
        }
        touch($landlordDb);
        Config::set('database.connections.landlord', [
            'driver' => 'sqlite',
            'database' => $landlordDb,
            'prefix' => '',
        ]);
        DB::purge('landlord');

        // 2. Migrate Landlord
        if (!\Illuminate\Support\Facades\Schema::connection('landlord')->hasTable('tenants')) {
            $this->artisan('migrate', [
                '--database' => 'landlord',
                '--path' => 'database/migrations/landlord',
                '--force' => true,
            ]);
        }

        // 3. Configure Tenant to use SQLite
        // This is the default template for the connection
        $tenantTemplateDb = database_path('tenant_test_template.sqlite');
        if (file_exists($tenantTemplateDb)) {
            @unlink($tenantTemplateDb);
        }
        touch($tenantTemplateDb);
        Config::set('database.connections.tenant', [
            'driver' => 'sqlite',
            'database' => $tenantTemplateDb,
            'prefix' => '',
        ]);
        DB::purge('tenant');

        // 4. Create a Tenant
        $tenantDb = database_path('tenant_test.sqlite');
        if (file_exists($tenantDb)) {
            @unlink($tenantDb);
        }
        touch($tenantDb);
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test.localhost',
            'database' => $tenantDb,
        ]);

        // 5. Make it Current (switches 'tenant' connection to use the database ':memory:')
        $tenant->makeCurrent();

        // 6. Migrate Tenant
        if (!\Illuminate\Support\Facades\Schema::connection('tenant')->hasTable('users')) {
            $this->artisan('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        }

        // Ensure standard migrations are also available if needed (e.g. for common tables)
        // But for multitenancy, they should be in the tenant path.

        // Ensure the tenant connection is set as default for models that might fallback?
        // Usually not needed if models use traits.
    }
}
