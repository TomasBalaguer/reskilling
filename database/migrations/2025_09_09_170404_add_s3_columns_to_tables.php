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
        // Add S3 path column to companies table for logos
        Schema::table('companies', function (Blueprint $table) {
            if (!Schema::hasColumn('companies', 'logo_s3_path')) {
                $table->string('logo_s3_path')->nullable()->after('phone');
            }
        });

        // Add S3 paths for audio files to campaign responses
        Schema::table('campaign_responses', function (Blueprint $table) {
            if (!Schema::hasColumn('campaign_responses', 'audio_s3_paths')) {
                $table->json('audio_s3_paths')->nullable()->after('audio_files');
            }
            if (!Schema::hasColumn('campaign_responses', 's3_files')) {
                $table->json('s3_files')->nullable()->after('comprehensive_report');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('logo_s3_path');
        });

        Schema::table('campaign_responses', function (Blueprint $table) {
            $table->dropColumn(['audio_s3_paths', 's3_files']);
        });
    }
};
