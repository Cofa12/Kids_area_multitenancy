<?php

namespace Tests\Feature;

use Database\Seeders\AdminRolePermissionSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    // use RefreshDatabase;
    public function test_database_has_users_table_with_credentials(): void
    {
        $this->assertTrue(
            Schema::connection('tenant')->hasColumns('users', [
                'id',
                'name',
                'phone',
                'password',
                'subscription_status',
                'created_at',
                'updated_at'
            ])
        );
    }

    public function test_database_has_admin_role(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'api']);

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
            'guard_name' => 'api'
        ], 'tenant');
    }

    public function test_admin_role_has_permission(): void
    {
        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $permission = Permission::create(['name' => 'upload-video', 'guard_name' => 'api']);
        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermissionTo('upload-video'));
    }

    public function test_admin_has_role_and_permission(): void
    {
        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $permission = Permission::create(['name' => 'upload-video', 'guard_name' => 'api']);
        $role->givePermissionTo($permission);

        // Create a tenant user instead of relying on AdminSeeder which creates a LandlordUser
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'phone' => '+1234567890',
            'password' => bcrypt('password'),
        ]);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasPermissionTo('upload-video'));
    }


    public function test_database_has_campaigns_columns(): void
    {
        $this->assertTrue(
            Schema::connection('tenant')->hasColumns('campaigns', [
                'id',
                'country',
                'operator',
                'service',
                'start_date',
                'end_date',
                'agency_id',
                'influencer_id',
                'influencer_cost',
                'type',
                'cpa',
                'status',
                'created_at',
                'updated_at',
                'user_id'
            ])
        );
    }


}
