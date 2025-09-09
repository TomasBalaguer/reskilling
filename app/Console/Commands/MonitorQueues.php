<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\CampaignResponse;

class MonitorQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor queue status and audio processing jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Laravel Queue Monitor Dashboard');
        $this->info('=====================================');

        // Queue Statistics
        $this->newLine();
        $this->info('📊 ESTADÍSTICAS DE COLAS:');
        
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        
        $this->line("   Jobs pendientes: {$pendingJobs}");
        $this->line("   Jobs fallidos: {$failedJobs}");

        // Campaign Response Statistics
        $this->newLine();
        $this->info('🎙️ ESTADÍSTICAS DE PROCESAMIENTO DE AUDIO:');
        
        $pendingResponses = CampaignResponse::where('processing_status', 'pending')->count();
        $processingResponses = CampaignResponse::where('processing_status', 'processing')->count();
        $completedResponses = CampaignResponse::where('processing_status', 'completed')->count();
        $failedResponses = CampaignResponse::where('processing_status', 'failed')->count();
        
        $this->line("   Respuestas pendientes: {$pendingResponses}");
        $this->line("   Respuestas procesando: {$processingResponses}");
        $this->line("   Respuestas completadas: {$completedResponses}");
        $this->line("   Respuestas fallidas: {$failedResponses}");

        // Recent Failed Jobs
        if ($failedJobs > 0) {
            $this->newLine();
            $this->error('❌ JOBS FALLIDOS RECIENTES:');
            
            $recentFailedJobs = DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($recentFailedJobs as $job) {
                $payload = json_decode($job->payload, true);
                $displayName = $payload['displayName'] ?? 'Unknown Job';
                $this->line("   - {$displayName} (Failed: {$job->failed_at})");
            }
        }

        // Recent Responses Needing Processing
        $this->newLine();
        $this->info('🔄 RESPUESTAS RECIENTES PARA PROCESAR:');
        
        $recentResponses = CampaignResponse::whereIn('processing_status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        if ($recentResponses->isEmpty()) {
            $this->line('   No hay respuestas pendientes');
        } else {
            foreach ($recentResponses as $response) {
                $status = strtoupper($response->processing_status);
                $audioCount = is_array($response->audio_files) ? count($response->audio_files) : 0;
                $this->line("   - ID {$response->id}: {$status} ({$audioCount} audios) - {$response->created_at->diffForHumans()}");
            }
        }

        // Recommendations
        $this->newLine();
        $this->info('💡 RECOMENDACIONES:');
        
        if ($failedJobs > 0) {
            $this->warn('   - Hay jobs fallidos, revisa con: php artisan queue:failed');
        }
        
        if ($pendingResponses > 0) {
            $this->warn('   - Hay respuestas pendientes, asegúrate de que los workers estén corriendo');
        }
        
        if ($pendingJobs == 0 && $pendingResponses == 0) {
            $this->info('   - ✅ Todo está al día!');
        }

        $this->newLine();
        $this->info('Para más detalles:');
        $this->line('   php artisan queue:failed    - Ver jobs fallidos detallados');
        $this->line('   php artisan queue:restart   - Reiniciar workers');
        $this->line('   php artisan queue:retry all - Reintentar jobs fallidos');

        return 0;
    }
}
