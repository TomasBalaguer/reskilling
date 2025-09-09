<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Extend processing_status column to handle longer status values
        Schema::table('campaign_responses', function (Blueprint $table) {
            $table->string('processing_status', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_responses', function (Blueprint $table) {
            $table->string('processing_status', 20)->change();
        });
    }
};
