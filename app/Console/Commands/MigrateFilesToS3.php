<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\CampaignResponse;
use App\Models\Company;
use App\Services\FileStorageService;

class MigrateFilesToS3 extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'files:migrate-to-s3 {--type=all : Type of files to migrate (all, audio, images)} {--force : Force migration even if S3 path exists}';

    /**
     * The console command description.
     */
    protected $description = 'Migrate existing local files to AWS S3';

    private $fileStorageService;

    public function __construct(FileStorageService $fileStorageService)
    {
        parent::__construct();
        $this->fileStorageService = $fileStorageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $force = $this->option('force');

        $this->info('Starting file migration to S3...');

        if ($type === 'all' || $type === 'audio') {
            $this->migrateAudioFiles($force);
        }

        if ($type === 'all' || $type === 'images') {
            $this->migrateCompanyLogos($force);
        }

        $this->info('File migration completed!');
    }

    private function migrateAudioFiles($force = false)
    {
        $this->info('Migrating audio files...');

        $responses = CampaignResponse::whereNotNull('audio_files')
            ->when(!$force, function ($query) {
                return $query->whereNull('audio_s3_paths');
            })
            ->get();

        $progressBar = $this->output->createProgressBar($responses->count());
        $progressBar->start();

        foreach ($responses as $response) {
            try {
                $audioFiles = $response->audio_files ?? [];
                $s3Paths = [];
                $updatedAudioFiles = [];

                foreach ($audioFiles as $key => $audioFile) {
                    if (isset($audioFile['path']) && Storage::disk('public')->exists($audioFile['path'])) {
                        // Read file content
                        $fileContent = Storage::disk('public')->get($audioFile['path']);
                        
                        // Generate filename
                        $extension = pathinfo($audioFile['filename'], PATHINFO_EXTENSION);
                        $filename = 'migrated_' . $response->id . '_' . $key . '_' . time() . '.' . $extension;
                        
                        // Upload to S3
                        $s3Path = 'responses/' . $filename;
                        Storage::disk('audio-storage')->put($s3Path, $fileContent);
                        
                        $s3Paths[$key] = $s3Path;
                        
                        // Update audio file info
                        $updatedAudioFiles[$key] = array_merge($audioFile, [
                            's3_path' => $s3Path,
                            'storage' => 's3',
                            'migrated_at' => now()->toISOString()
                        ]);
                        
                        $this->line("\nMigrated audio: {$audioFile['path']} -> {$s3Path}");
                    } else {
                        $updatedAudioFiles[$key] = $audioFile;
                    }
                }

                // Update response with S3 paths
                $response->update([
                    'audio_s3_paths' => $s3Paths,
                    'audio_files' => $updatedAudioFiles
                ]);

            } catch (\Exception $e) {
                $this->error("\nError migrating response {$response->id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line("\nAudio files migration completed.");
    }

    private function migrateCompanyLogos($force = false)
    {
        $this->info('Migrating company logos...');

        $companies = Company::whereNotNull('logo_url')
            ->when(!$force, function ($query) {
                return $query->whereNull('logo_s3_path');
            })
            ->get();

        $progressBar = $this->output->createProgressBar($companies->count());
        $progressBar->start();

        foreach ($companies as $company) {
            try {
                if ($company->logo_url && filter_var($company->logo_url, FILTER_VALIDATE_URL)) {
                    // Skip if it's already a URL (external)
                    continue;
                }

                // Check if it's a local path
                $logoPath = str_replace('/storage/', '', $company->logo_url);
                
                if (Storage::disk('public')->exists($logoPath)) {
                    // Read file content
                    $fileContent = Storage::disk('public')->get($logoPath);
                    
                    // Generate filename
                    $extension = pathinfo($logoPath, PATHINFO_EXTENSION);
                    $filename = 'logo_' . $company->id . '_' . time() . '.' . $extension;
                    
                    // Upload to S3
                    $s3Path = 'companies/' . $company->id . '/logo/' . $filename;
                    Storage::disk('images')->put($s3Path, $fileContent);
                    
                    // Update company
                    $company->update(['logo_s3_path' => $s3Path]);
                    
                    $this->line("\nMigrated logo: {$logoPath} -> {$s3Path}");
                }

            } catch (\Exception $e) {
                $this->error("\nError migrating company {$company->id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line("\nCompany logos migration completed.");
    }
}