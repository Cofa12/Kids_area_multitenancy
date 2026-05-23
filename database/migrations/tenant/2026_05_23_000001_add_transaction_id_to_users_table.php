<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add transaction_id to the users table.
     * Used to deduplicate incoming callback notifications from the operator.
     * The column is nullable (existing users won't have one yet) and unique
     * so a duplicate transactionId can be detected with a simple exists() check.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->unique()->after('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('transaction_id');
        });
    }
};
