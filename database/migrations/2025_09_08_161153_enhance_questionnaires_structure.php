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
        Schema::table('questionnaires', function (Blueprint $table) {
            // Only add columns that don't already exist
            // questionnaire_type, structure, metadata, configuration, estimated_duration_minutes, version already exist
        });

        // Enhance campaign_responses to store richer response data
        Schema::table('campaign_responses', function (Blueprint $table) {
            // Most columns already exist: invitation_id, response_type, raw_responses, processed_responses, 
            // ai_analysis, transcriptions, prosodic_analysis, processing_started_at, processing_completed_at
            // Only add if they don't exist (they already exist from previous migrations)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionnaires', function (Blueprint $table) {
            $table->dropColumn([
                'questionnaire_type',
                'structure', 
                'metadata',
                'configuration',
                'estimated_duration_minutes',
                'version'
            ]);
        });

        Schema::table('campaign_responses', function (Blueprint $table) {
            $table->dropForeign(['invitation_id']);
            $table->dropColumn([
                'invitation_id',
                'response_type',
                'raw_responses',
                'processed_responses', 
                'ai_analysis',
                'transcriptions',
                'prosodic_analysis',
                'processing_started_at',
                'processing_completed_at'
                // Note: don't drop processing_status as it's part of the original table
            ]);
        });
    }
};
