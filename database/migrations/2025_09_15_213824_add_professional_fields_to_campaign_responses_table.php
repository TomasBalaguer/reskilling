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
        Schema::table('campaign_responses', function (Blueprint $table) {
            $table->string('current_position')->nullable()->after('occupation');
            $table->string('professional_goal')->nullable()->after('current_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_responses', function (Blueprint $table) {
            $table->dropColumn(['current_position', 'professional_goal']);
        });
    }
};
