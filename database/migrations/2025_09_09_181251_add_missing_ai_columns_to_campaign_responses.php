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
            // Add missing AI analysis columns
            if (!Schema::hasColumn('campaign_responses', 'ai_analysis')) {
                $table->json('ai_analysis')->nullable()->after('responses');
            }
            if (!Schema::hasColumn('campaign_responses', 'transcriptions')) {
                $table->json('transcriptions')->nullable()->after('ai_analysis');
            }
            if (!Schema::hasColumn('campaign_responses', 'prosodic_analysis')) {
                $table->json('prosodic_analysis')->nullable()->after('transcriptions');
            }
            if (!Schema::hasColumn('campaign_responses', 'processing_started_at')) {
                $table->timestamp('processing_started_at')->nullable()->after('prosodic_analysis');
            }
            if (!Schema::hasColumn('campaign_responses', 'processing_completed_at')) {
                $table->timestamp('processing_completed_at')->nullable()->after('processing_started_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_responses', function (Blueprint $table) {
            $table->dropColumn([
                'ai_analysis',
                'transcriptions',
                'prosodic_analysis',
                'processing_started_at',
                'processing_completed_at'
            ]);
        });
    }
};
