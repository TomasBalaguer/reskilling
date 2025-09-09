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
            // Add comprehensive report field
            if (!Schema::hasColumn('campaign_responses', 'comprehensive_report')) {
                $table->json('comprehensive_report')->nullable()->after('analysis_summary');
            }
            
            // Add questionnaire scores field  
            if (!Schema::hasColumn('campaign_responses', 'questionnaire_scores')) {
                $table->json('questionnaire_scores')->nullable()->after('comprehensive_report');
            }
            
            // Add report generated timestamp
            if (!Schema::hasColumn('campaign_responses', 'report_generated_at')) {
                $table->timestamp('report_generated_at')->nullable()->after('questionnaire_scores');
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
                'comprehensive_report',
                'questionnaire_scores', 
                'report_generated_at'
            ]);
        });
    }
};
