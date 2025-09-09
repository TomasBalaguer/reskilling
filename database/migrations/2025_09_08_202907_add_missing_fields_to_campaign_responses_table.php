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
            // Check which columns don't exist and only add those
            if (!Schema::hasColumn('campaign_responses', 'questionnaire_id')) {
                $table->foreignId('questionnaire_id')->nullable()->after('campaign_id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('campaign_responses', 'respondent_type')) {
                $table->enum('respondent_type', ['candidate', 'invited_candidate'])->default('candidate')->after('respondent_email');
            }
            if (!Schema::hasColumn('campaign_responses', 'respondent_age')) {
                $table->integer('respondent_age')->nullable()->after('respondent_type');
            }
            if (!Schema::hasColumn('campaign_responses', 'respondent_additional_info')) {
                $table->json('respondent_additional_info')->nullable()->after('respondent_age');
            }
            if (!Schema::hasColumn('campaign_responses', 'access_type')) {
                $table->enum('access_type', ['public_link', 'email_invitation'])->default('public_link')->after('processing_error');
            }
            if (!Schema::hasColumn('campaign_responses', 'access_token')) {
                $table->string('access_token')->nullable()->after('access_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaign_responses', function (Blueprint $table) {
            $table->dropForeign(['questionnaire_id']);
            $table->dropColumn([
                'questionnaire_id',
                'respondent_type',
                'respondent_age',
                'respondent_additional_info',
                'access_type',
                'access_token'
            ]);
        });
    }
};
