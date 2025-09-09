<?php

namespace App\Jobs;

use App\Models\CampaignResponse;
use App\Events\AIAnalysisCompleted;
use App\Services\AIInterpretationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTextAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public int $responseId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AIInterpretationService $aiService): void
    {
        try {
            $response = CampaignResponse::findOrFail($this->responseId);
            $questionnaire = $response->questionnaire;

            if (!$questionnaire) {
                throw new \Exception("Questionnaire not found for response {$this->responseId}");
            }

            Log::info('Processing text analysis', [
                'response_id' => $response->id,
                'questionnaire_type' => $questionnaire->questionnaire_type?->value
            ]);

            // Update processing status
            $response->update([
                'processing_status' => 'analyzing_text',
                'ai_analysis_started_at' => now()
            ]);

            // Extract text responses from the raw responses
            $textResponses = $this->extractTextResponses($response->raw_responses ?? []);
            
            if (empty($textResponses)) {
                Log::warning('No text responses found to analyze', ['response_id' => $response->id]);
                return;
            }

            // Generate AI interpretation
            $startTime = microtime(true);
            $analysisResults = $aiService->generateTextAnalysis($response, $questionnaire, $textResponses);
            $processingTime = round((microtime(true) - $startTime), 2);

            // Add processing metadata
            $analysisResults['processing_time'] = $processingTime;
            $analysisResults['analyzed_at'] = now();
            $analysisResults['analysis_type'] = 'text_analysis';

            Log::info('Text analysis completed', [
                'response_id' => $response->id,
                'processing_time' => $processingTime,
                'interpretations_count' => count($analysisResults['interpretations'] ?? [])
            ]);

            // Fire AI analysis completed event
            AIAnalysisCompleted::dispatch($response, $analysisResults, true);

        } catch (\Exception $e) {
            Log::error('Text analysis job failed', [
                'response_id' => $this->responseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fire failed event
            $response = CampaignResponse::find($this->responseId);
            if ($response) {
                AIAnalysisCompleted::dispatch($response, [], false, $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Extract text responses from raw responses
     */
    private function extractTextResponses(array $rawResponses): array
    {
        $textResponses = [];

        foreach ($rawResponses as $questionId => $response) {
            if (is_string($response) && !empty($response)) {
                $textResponses[$questionId] = [
                    'question_id' => $questionId,
                    'text_content' => $response,
                    'word_count' => str_word_count($response),
                    'character_count' => strlen($response)
                ];
            } elseif (is_array($response) && isset($response['text']) && !empty($response['text'])) {
                $textResponses[$questionId] = [
                    'question_id' => $questionId,
                    'text_content' => $response['text'],
                    'word_count' => str_word_count($response['text']),
                    'character_count' => strlen($response['text']),
                    'metadata' => $response['metadata'] ?? []
                ];
            }
        }

        return $textResponses;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessTextAnalysisJob failed permanently', [
            'response_id' => $this->responseId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Update response status
        $response = CampaignResponse::find($this->responseId);
        if ($response) {
            $response->update([
                'processing_status' => 'text_analysis_failed',
                'processing_error' => $exception->getMessage(),
                'processing_failed_at' => now()
            ]);
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['text-analysis', 'response:' . $this->responseId];
    }
}