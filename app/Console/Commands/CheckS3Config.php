<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckS3Config extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'config:check-s3';

    /**
     * The console command description.
     */
    protected $description = 'Check AWS S3 configuration and show missing variables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking AWS S3 Configuration...');
        
        $requiredConfigs = [
            'AWS_ACCESS_KEY_ID' => config('filesystems.disks.s3.key'),
            'AWS_SECRET_ACCESS_KEY' => config('filesystems.disks.s3.secret'),
            'AWS_DEFAULT_REGION' => config('filesystems.disks.s3.region'),
            'AWS_BUCKET' => config('filesystems.disks.s3.bucket'),
            'AWS_ENDPOINT' => config('filesystems.disks.s3.endpoint'),
        ];

        $missing = [];
        $configured = [];

        foreach ($requiredConfigs as $envVar => $value) {
            if (empty($value)) {
                $missing[] = $envVar;
            } else {
                $configured[] = $envVar;
                
                // Show partial values for security
                if (in_array($envVar, ['AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY'])) {
                    $displayValue = substr($value, 0, 8) . '...';
                } else {
                    $displayValue = $value;
                }
                
                $this->line("✅ {$envVar}: {$displayValue}");
            }
        }

        if (!empty($missing)) {
            $this->error('Missing configuration variables:');
            foreach ($missing as $var) {
                $this->error("❌ {$var}");
            }
            
            $this->line('');
            $this->info('To fix this, add the missing variables to your .env file:');
            foreach ($missing as $var) {
                $this->line("{$var}=your_value_here");
            }
            
            return 1;
        }

        $this->info('✅ All S3 configuration variables are set!');
        
        // Test S3 connection
        $this->info('Testing S3 connection...');
        try {
            \Storage::disk('s3')->put('test-connection.txt', 'Connection test');
            \Storage::disk('s3')->delete('test-connection.txt');
            $this->info('✅ S3 connection successful!');
        } catch (\Exception $e) {
            $this->error('❌ S3 connection failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}