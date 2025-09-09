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
            // Add AI analysis related fields
            if (!Schema::hasColumn('campaign_responses', 'ai_analysis_status')) {
                $table->enum('ai_analysis_status', ['pending', 'processing', 'completed', 'failed'])->nullable()->after('processing_error');
            }
            if (!Schema::hasColumn('campaign_responses', 'ai_analysis_completed_at')) {
                $table->timestamp('ai_analysis_completed_at')->nullable()->after('ai_analysis_status');
            }
            if (!Schema::hasColumn('campaign_responses', 'ai_analysis_failed_at')) {
                $table->timestamp('ai_analysis_failed_at')->nullable()->after('ai_analysis_completed_at');
            }
            if (!Schema::hasColumn('campaign_responses', 'analysis_summary')) {
                $table->json('analysis_summary')->nullable()->after('ai_analysis_failed_at');
            }
            if (!Schema::hasColumn('campaign_responses', 'completion_percentage')) {
                $table->decimal('completion_percentage', 5, 2)->nullable()->after('analysis_summary');
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
                'ai_analysis_status',
                'ai_analysis_completed_at', 
                'ai_analysis_failed_at',
                'analysis_summary',
                'completion_percentage'
            ]);
        });
    }
};
