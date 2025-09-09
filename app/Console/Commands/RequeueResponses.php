<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CampaignResponse;
use App\Events\QuestionnaireResponseSubmitted;

class RequeueResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'responses:requeue 
                          {--id=* : IDs específicos de respuestas (ej: --id=1 --id=2 --id=3)}
                          {--campaign=* : IDs de campañas (ej: --campaign=1 --campaign=2)}
                          {--status=* : Estados a reencolar (ej: --status=failed --status=pending)}
                          {--all : Reencolar TODAS las respuestas}
                          {--failed : Reencolar solo respuestas fallidas}
                          {--pending : Reencolar solo respuestas pendientes}
                          {--since= : Reencolar respuestas desde fecha (ej: --since="2025-09-09")}
                          {--limit=50 : Límite de respuestas a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Requeue campaign responses for audio processing from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Reencolando Campaign Responses desde Base de Datos');
        $this->info('=====================================================');

        // Build query based on options
        $query = CampaignResponse::query();
        $conditions = [];

        // Filter by specific IDs
        if ($ids = $this->option('id')) {
            $query->whereIn('id', $ids);
            $conditions[] = "IDs: " . implode(', ', $ids);
        }

        // Filter by campaign IDs
        if ($campaignIds = $this->option('campaign')) {
            $query->whereIn('campaign_id', $campaignIds);
            $conditions[] = "Campañas: " . implode(', ', $campaignIds);
        }

        // Filter by status
        if ($statuses = $this->option('status')) {
            $query->whereIn('processing_status', $statuses);
            $conditions[] = "Estados: " . implode(', ', $statuses);
        }

        // Quick filters
        if ($this->option('failed')) {
            $query->where('processing_status', 'failed');
            $conditions[] = "Solo fallidas";
        }

        if ($this->option('pending')) {
            $query->where('processing_status', 'pending');
            $conditions[] = "Solo pendientes";
        }

        // Filter by date
        if ($since = $this->option('since')) {
            $query->where('created_at', '>=', $since);
            $conditions[] = "Desde: {$since}";
        }

        // Apply limit
        $limit = (int) $this->option('limit');
        $query->limit($limit);

        // Show what we're going to process
        $this->info('📋 Filtros aplicados:');
        if (empty($conditions) && !$this->option('all')) {
            $this->error('❌ Debes especificar al menos un filtro o usar --all');
            $this->info('💡 Ejemplos:');
            $this->line('   responses:requeue --id=1 --id=2');
            $this->line('   responses:requeue --failed');
            $this->line('   responses:requeue --campaign=1 --pending');
            $this->line('   responses:requeue --since="2025-09-09" --limit=10');
            $this->line('   responses:requeue --all');
            return 1;
        }

        if ($this->option('all')) {
            $conditions[] = "TODAS las respuestas";
            $query = CampaignResponse::query()->limit($limit);
        }

        foreach ($conditions as $condition) {
            $this->line("   - {$condition}");
        }
        $this->line("   - Límite: {$limit} respuestas");

        // Get responses to process
        $responses = $query->get();

        if ($responses->isEmpty()) {
            $this->warn('⚠️  No se encontraron respuestas con los filtros especificados');
            return 0;
        }

        $this->info("📊 Encontradas: {$responses->count()} respuestas");

        // Show preview
        $this->info('🔍 Preview de respuestas a procesar:');
        foreach ($responses->take(5) as $response) {
            $audioCount = is_array($response->audio_files) ? count($response->audio_files) : 0;
            $this->line("   - ID {$response->id}: {$response->processing_status} ({$audioCount} audios) - Campaña {$response->campaign_id}");
        }

        if ($responses->count() > 5) {
            $remaining = $responses->count() - 5;
            $this->line("   ... y {$remaining} más");
        }

        // Confirm before processing
        if (!$this->confirm('¿Continuar con el reencolado?', true)) {
            $this->info('❌ Cancelado por el usuario');
            return 0;
        }

        // Process responses
        $this->info('🚀 Procesando respuestas...');
        $processed = 0;
        $bar = $this->output->createProgressBar($responses->count());
        $bar->start();

        foreach ($responses as $response) {
            try {
                // Reset status to pending
                $response->update([
                    'processing_status' => 'pending',
                    'processing_error' => null,
                    'processing_failed_at' => null
                ]);

                // Determine if AI processing is required (has audio files)
                $requiresAI = !empty($response->audio_files) && is_array($response->audio_files) && count($response->audio_files) > 0;
                
                // Dispatch to queue with proper AI processing flag
                QuestionnaireResponseSubmitted::dispatch($response, [], $requiresAI);
                
                $processed++;
                $bar->advance();

            } catch (\Exception $e) {
                $this->newLine();
                $this->error("❌ Error procesando respuesta ID {$response->id}: {$e->getMessage()}");
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();

        // Show results
        $this->info("✅ Procesadas exitosamente: {$processed} respuestas");
        
        if ($processed < $responses->count()) {
            $failed = $responses->count() - $processed;
            $this->warn("⚠️  Fallidas: {$failed} respuestas");
        }

        // Show current queue status
        $this->newLine();
        $this->info('📈 Estado actual de las colas:');
        $this->call('queue:monitor');

        $this->newLine();
        $this->info('🎉 ¡Reencolado completado!');
        $this->info('💡 Monitorea el progreso con: php artisan queue:monitor');

        return 0;
    }
}
