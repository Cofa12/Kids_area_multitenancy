<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vendor_name')->nullable()->after('transaction_id');
            $table->string('circle')->nullable()->after('vendor_name');
            $table->string('amount')->nullable()->after('circle');
            $table->string('action')->nullable()->after('amount');
            $table->string('operator')->nullable()->after('action');
            $table->string('channel')->nullable()->after('operator');
            $table->string('pack_name')->nullable()->after('channel');
            $table->string('start_date')->nullable()->after('pack_name');
            $table->string('end_date')->nullable()->after('start_date');
            $table->string('language')->nullable()->after('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_name',
                'circle',
                'amount',
                'action',
                'operator',
                'channel',
                'pack_name',
                'start_date',
                'end_date',
                'language',
            ]);
        });
    }
};
