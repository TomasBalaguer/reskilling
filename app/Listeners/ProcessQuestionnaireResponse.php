<?php

namespace App\Listeners;

use App\Events\QuestionnaireResponseSubmitted;
use App\Jobs\ProcessAudioTranscriptionsJob;
use App\Jobs\ProcessTextAnalysisJob;
use App\Jobs\GenerateQuestionnaireScoresJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessQuestionnaireResponse implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * Handle the event.
     */
    public function handle(QuestionnaireResponseSubmitted $event): void
    {
        try {
            Log::info('Processing questionnaire response', [
                'response_id' => $event->response->id,
                'campaign_id' => $event->response->campaign_id,
                'questionnaire_type' => $event->response->questionnaire?->questionnaire_type?->value,
                'requires_ai_processing' => $event->requiresAIProcessing()
            ]);

            $questionnaire = $event->response->questionnaire;
            if (!$questionnaire) {
                Log::warning('No questionnaire found for response', ['response_id' => $event->response->id]);
                return;
            }

            $questionnaireType = $questionnaire->getQuestionnaireType();

            // Dispatch appropriate processing jobs based on questionnaire type
            match($questionnaireType->value) {
                'REFLECTIVE_QUESTIONS' => $this->processAudioBasedQuestionnaire($event),
                'TEXT_RESPONSE', 'PERSONALITY_ASSESSMENT', 'MIXED_FORMAT' => $this->processTextBasedQuestionnaire($event),
                default => $this->processStatisticalQuestionnaire($event)
            };

        } catch (\Exception $e) {
            Log::error('Failed to process questionnaire response', [
                'response_id' => $event->response->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger job failure and retry logic
        }
    }

    /**
     * Process audio-based questionnaires (like reflective questions)
     */
    private function processAudioBasedQuestionnaire(QuestionnaireResponseSubmitted $event): void
    {
        // First, process audio transcriptions
        ProcessAudioTranscriptionsJob::dispatch($event->response->id)
            ->onQueue('audio-processing')
            ->delay(now()->addSeconds(5)); // Small delay to ensure response is fully saved

        Log::info('Audio transcription job dispatched', ['response_id' => $event->response->id]);
    }

    /**
     * Process text-based questionnaires that require AI analysis
     */
    private function processTextBasedQuestionnaire(QuestionnaireResponseSubmitted $event): void
    {
        // Process text analysis with AI
        ProcessTextAnalysisJob::dispatch($event->response->id)
            ->onQueue('ai-processing')
            ->delay(now()->addSeconds(2));

        Log::info('Text analysis job dispatched', ['response_id' => $event->response->id]);
    }

    /**
     * Process questionnaires that only need statistical processing
     */
    private function processStatisticalQuestionnaire(QuestionnaireResponseSubmitted $event): void
    {
        // Generate scores immediately for statistical questionnaires
        GenerateQuestionnaireScoresJob::dispatch($event->response->id)
            ->onQueue('scoring')
            ->delay(now()->addSeconds(1));

        Log::info('Statistical scoring job dispatched', ['response_id' => $event->response->id]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(QuestionnaireResponseSubmitted $event, \Throwable $exception): void
    {
        Log::error('ProcessQuestionnaireResponse listener failed', [
            'response_id' => $event->response->id,
            'campaign_id' => $event->response->campaign_id,
            'error' => $exception->getMessage(),
            'metadata' => $event->getMetadata()
        ]);

        // Update response status to indicate processing failure
        $event->response->update([
            'processing_status' => 'failed',
            'processing_error' => $exception->getMessage(),
            'processing_failed_at' => now()
        ]);
    }
}