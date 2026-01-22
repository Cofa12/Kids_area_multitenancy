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
        Schema::create('non_billable_campaign_clicks', function (Blueprint $table) {
            $table->id();
            // Campaigns use UUIDs for primary keys
            $table->uuid('campaign_id');
            // Click ID comes from the endpoint only and is unique globally
            $table->string('click_id')->unique();
            $table->timestamps();

            $table->foreign('campaign_id')
                ->references('id')->on('campaigns')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('non_billable_campaign_clicks');
    }
};
