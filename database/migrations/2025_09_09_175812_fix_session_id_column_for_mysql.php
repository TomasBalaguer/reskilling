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
        // Only run this for MySQL to ensure session_id column can handle varchar(40) 
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('campaign_responses', function (Blueprint $table) {
                // Change session_id to varchar(40) to handle longer Laravel session IDs
                // but we'll use UUID in code to ensure compatibility
                $table->string('session_id', 40)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('campaign_responses', function (Blueprint $table) {
                $table->uuid('session_id')->change();
            });
        }
    }
};
