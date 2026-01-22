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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country')->nullable();
            $table->string('operator')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('service')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('agency_id')->nullable();
            $table->string('influencer_id')->nullable();
            $table->string('influencer_cost')->nullable();
            $table->enum('type', ['billable', 'non-billable'])->nullable();
            $table->enum('status', ['waiting', 'active', 'ended', 'paused', 'scheduled'])->nullable();
            $table->float('cpa')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
