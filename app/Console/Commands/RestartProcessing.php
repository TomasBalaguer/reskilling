<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CampaignResponse;
use App\Events\QuestionnaireResponseSubmitted;

class RestartProcessing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:restart-processing {--response-id= : ID específico de respuesta a reprocesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restart queue workers and reprocess failed audio responses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Reiniciando procesamiento de audio...');
        
        // Step 1: Restart queue workers
        $this->info('1️⃣  Reiniciando workers...');
        $this->call('queue:restart');
        
        // Step 2: Clear failed jobs
        $this->info('2️⃣  Limpiando jobs fallidos...');
        $this->call('queue:flush');
        
        // Step 3: Reprocess responses
        $responseId = $this->option('response-id');
        
        if ($responseId) {
            // Reprocess specific response
            $this->info("3️⃣  Reprocesando respuesta ID {$responseId}...");
            $response = CampaignResponse::find($responseId);
            
            if ($response) {
                $response->update(['processing_status' => 'pending']);
                QuestionnaireResponseSubmitted::dispatch($response);
                $this->info("✅ Respuesta ID {$responseId} enviada a la cola");
            } else {
                $this->error("❌ Respuesta ID {$responseId} no encontrada");
                return 1;
            }
        } else {
            // Reprocess all failed responses
            $this->info('3️⃣  Reprocesando todas las respuestas fallidas...');
            
            $failedResponses = CampaignResponse::whereIn('processing_status', ['failed'])
                ->get();
            
            $pendingResponses = CampaignResponse::where('processing_status', 'pending')
                ->get();
            
            if ($failedResponses->isEmpty() && $pendingResponses->isEmpty()) {
                $this->info('ℹ️  No hay respuestas para reprocesar');
            } else {
                // Reprocess failed responses
                foreach ($failedResponses as $response) {
                    $response->update(['processing_status' => 'pending']);
                    QuestionnaireResponseSubmitted::dispatch($response);
                    $this->line("   - Reprocesando respuesta ID {$response->id}");
                }
                
                // Reprocess pending responses that might be stuck
                foreach ($pendingResponses as $response) {
                    QuestionnaireResponseSubmitted::dispatch($response);
                    $this->line("   - Reenviando respuesta pendiente ID {$response->id}");
                }
                
                $total = $failedResponses->count() + $pendingResponses->count();
                $this->info("✅ {$total} respuestas enviadas a la cola");
            }
        }
        
        // Step 4: Show status
        $this->newLine();
        $this->info('4️⃣  Estado actual:');
        $this->call('queue:monitor');
        
        $this->newLine();
        $this->info('🎉 ¡Reinicio completado!');
        $this->info('💡 Monitorea el progreso con: php artisan queue:monitor');
        
        return 0;
    }
}
