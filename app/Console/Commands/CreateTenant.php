<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-tenant {name} {domain} {database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant with its own database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');
        $database = $this->argument('database');

        $this->info("Creating tenant: {$name}...");

        // 1. Create the database (Using the landlord connection to ensure we have rights if configured)
        try {
            // We use the 'landlord' connection to execute the CREATE DATABASE command
            DB::connection('landlord')->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $this->info("Database '{$database}' created or already exists.");
        } catch (\Exception $e) {
            $this->error("Could not create database: " . $e->getMessage());
            return 1;
        }

        // 2. Create the tenant record in the landlord database
        $tenant = Tenant::create([
            'name' => $name,
            'domain' => $domain,
            'database' => $database,
        ]);

        $this->info("Tenant record created in landlord database.");

        // 3. Run migrations for the new tenant
        $this->info("Running migrations for tenant...");
        $this->call('tenants:artisan', [
            'artisanCommand' => 'migrate --path=database/migrations/tenant --database=tenant --force',
            '--tenant' => $tenant->id,
        ]);

        $this->info("Tenant '{$name}' created successfully!");
        return 0;
    }
}
