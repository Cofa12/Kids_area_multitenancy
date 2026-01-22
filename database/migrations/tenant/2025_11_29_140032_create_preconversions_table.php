<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('preconversions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('click_id')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preconversions');
    }
};
