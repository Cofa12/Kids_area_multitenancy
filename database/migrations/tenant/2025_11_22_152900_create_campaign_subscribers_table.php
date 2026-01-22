<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaign_subscribers', function (Blueprint $table) {
            $table->id();
            $table->uuid('campaign_id');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->index(['campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_subscribers');
    }
};
