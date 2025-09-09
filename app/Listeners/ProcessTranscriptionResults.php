<?php

namespace App\Listeners;

use App\Events\AudioTranscriptionCompleted;
use App\Jobs\GenerateAIInterpretationJob;
use App\Jobs\GenerateQuestionnaireScoresJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessTranscriptionResults implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(AudioTranscriptionCompleted $event): void
    {
        try {
            Log::info('Processing transcription results', [
                'response_id' => $event->response->id,
                'successful' => $event->isSuccessful(),
                'summary' => $event->getTranscriptionSummary()
            ]);

            if ($event->isSuccessful()) {
                // Update response with transcription data
                $this->updateResponseWithTranscriptions($event);
                
                // Dispatch AI analysis job
                $this->dispatchAIAnalysis($event);
            } else {
                // Handle transcription failure
                $this->handleTranscriptionFailure($event);
            }

        } catch (\Exception $e) {
            Log::error('Failed to process transcription results', [
                'response_id' => $event->response->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Update response with transcription data
     */
    private function updateResponseWithTranscriptions(AudioTranscriptionCompleted $event): void
    {
        $transcriptionSummary = $event->getTranscriptionSummary();
        
        $event->response->update([
            'transcriptions' => $event->transcriptionData,
            'transcription_status' => 'completed',
            'transcription_completed_at' => now(),
            'transcription_summary' => $transcriptionSummary,
            'processing_status' => 'transcribed'
        ]);

        Log::info('Response updated with transcription data', [
            'response_id' => $event->response->id,
            'transcriptions_count' => $transcriptionSummary['transcriptions'],
            'success_rate' => $transcriptionSummary['success_rate']
        ]);
    }

    /**
     * Dispatch AI analysis job for the transcribed content
     */
    private function dispatchAIAnalysis(AudioTranscriptionCompleted $event): void
    {
        $questionnaire = $event->response->questionnaire;
        
        if ($questionnaire && $questionnaire->requiresAIProcessing()) {
            GenerateAIInterpretationJob::dispatch($event->response->id)
                ->onQueue('ai-processing')
                ->delay(now()->addMinutes(1)); // Small delay to ensure transcription data is fully processed

            Log::info('AI interpretation job dispatched', ['response_id' => $event->response->id]);
        } else {
            // If no AI processing needed, go directly to scoring
            GenerateQuestionnaireScoresJob::dispatch($event->response->id)
                ->onQueue('scoring')
                ->delay(now()->addSeconds(30));

            Log::info('Direct scoring job dispatched (no AI processing required)', ['response_id' => $event->response->id]);
        }
    }

    /**
     * Handle transcription failure
     */
    private function handleTranscriptionFailure(AudioTranscriptionCompleted $event): void
    {
        $event->response->update([
            'transcription_status' => 'failed',
            'transcription_error' => $event->error,
            'transcription_failed_at' => now(),
            'processing_status' => 'transcription_failed'
        ]);

        Log::error('Transcription failed for response', [
            'response_id' => $event->response->id,
            'error' => $event->error,
            'summary' => $event->getTranscriptionSummary()
        ]);

        // Optionally, dispatch a job to notify administrators or retry logic
        // RetryTranscriptionJob::dispatch($event->response->id)->delay(now()->addHours(1));
    }

    /**
     * Handle a job failure.
     */
    public function failed(AudioTranscriptionCompleted $event, \Throwable $exception): void
    {
        Log::error('ProcessTranscriptionResults listener failed', [
            'response_id' => $event->response->id,
            'error' => $exception->getMessage(),
            'metadata' => $event->getProcessingMetadata()
        ]);

        $event->response->update([
            'processing_status' => 'listener_failed',
            'processing_error' => $exception->getMessage(),
            'processing_failed_at' => now()
        ]);
    }
}