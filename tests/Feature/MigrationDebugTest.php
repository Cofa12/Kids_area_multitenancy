<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationDebugTest extends TestCase
{
    public function test_debug_tenant_tables(): void
    {
        DB::connection('tenant')->select("SELECT name FROM sqlite_master WHERE type='table'");

        Schema::connection('tenant')->hasTable('campaigns');

        $this->assertTrue(true);
    }
}
