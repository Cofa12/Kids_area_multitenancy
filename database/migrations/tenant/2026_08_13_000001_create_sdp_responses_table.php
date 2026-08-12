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
        Schema::create('sdp_responses', function (Blueprint $table) {
            $table->id();
            $table->string('trfsrc')->nullable();
            $table->string('trxId')->nullable();
            $table->string('msisdn')->nullable();
            $table->string('subscriptionId')->nullable();
            $table->string('subscriptionDescription')->nullable();
            $table->string('autoRenew')->nullable();
            $table->string('sdp_status')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sdp_responses');
    }
};
