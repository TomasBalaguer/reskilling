<?php

namespace App\Jobs;

use App\Models\CampaignResponse;
use App\Services\QuestionnaireProcessing\QuestionnaireProcessorFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateQuestionnaireScoresJob implements ShouldQueue
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
    public function handle(QuestionnaireProcessorFactory $processorFactory): void
    {
        try {
            $response = CampaignResponse::findOrFail($this->responseId);
            $questionnaire = $response->questionnaire;

            if (!$questionnaire) {
                throw new \Exception("Questionnaire not found for response {$this->responseId}");
            }

            Log::info('Generating questionnaire scores', [
                'response_id' => $response->id,
                'questionnaire_type' => $questionnaire->questionnaire_type?->value,
                'has_ai_analysis' => !empty($response->ai_analysis)
            ]);

            // Update processing status
            $response->update([
                'processing_status' => 'calculating_scores',
                'scoring_started_at' => now()
            ]);

            // Get the appropriate processor for this questionnaire type
            $processor = $processorFactory->getProcessor($questionnaire->scoring_type);
            
            // Prepare questionnaire data for scoring
            $questionnaireData = $this->prepareQuestionnaireData($response);

            // Calculate scores using the strategy pattern
            $startTime = microtime(true);
            $scores = $questionnaire->calculateScores(
                $questionnaireData['processed_responses'],
                $questionnaireData['respondent_data']
            );
            $processingTime = round((microtime(true) - $startTime), 2);

            // Enhance scores with additional data
            $enhancedScores = $this->enhanceScoresWithMetadata($scores, $response, $processingTime);

            // Save scores to response
            $response->update([
                'questionnaire_scores' => $enhancedScores,
                'processing_status' => 'completed',
                'scoring_completed_at' => now(),
                'total_processing_time' => $this->calculateTotalProcessingTime($response)
            ]);

            Log::info('Questionnaire scores generated successfully', [
                'response_id' => $response->id,
                'scoring_type' => $enhancedScores['scoring_type'] ?? 'unknown',
                'processing_time' => $processingTime,
                'total_score' => $enhancedScores['total_score'] ?? 'N/A'
            ]);

            // Trigger any post-scoring workflows
            $this->triggerPostScoringWorkflows($response, $enhancedScores);

        } catch (\Exception $e) {
            Log::error('Questionnaire scoring job failed', [
                'response_id' => $this->responseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update response with failure status
            $response = CampaignResponse::find($this->responseId);
            if ($response) {
                $response->update([
                    'processing_status' => 'scoring_failed',
                    'processing_error' => $e->getMessage(),
                    'processing_failed_at' => now()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Prepare questionnaire data for scoring
     */
    private function prepareQuestionnaireData(CampaignResponse $response): array
    {
        $processedResponses = $response->processed_responses ?? [];

        // If no processed responses, try to process raw responses
        if (empty($processedResponses) && $response->raw_responses) {
            $questionnaire = $response->questionnaire;
            if ($questionnaire) {
                $processedResponses = $questionnaire->processResponses($response->raw_responses);
            }
        }

        // Include AI analysis results if available
        if ($response->ai_analysis) {
            foreach ($processedResponses as $questionId => &$responseData) {
                if (isset($response->ai_analysis['interpretations'][$questionId])) {
                    $responseData['ai_interpretation'] = $response->ai_analysis['interpretations'][$questionId];
                }
            }
        }

        // Include transcriptions if available
        if ($response->transcriptions) {
            foreach ($processedResponses as $questionId => &$responseData) {
                if (isset($response->transcriptions[$questionId])) {
                    $responseData['transcription'] = $response->transcriptions[$questionId];
                }
            }
        }

        return [
            'processed_responses' => $processedResponses,
            'respondent_data' => [
                'name' => $response->respondent_name,
                'email' => $response->respondent_email,
                'type' => $response->respondent_type,
                'age' => $response->respondent_age ?? null,
                'additional_info' => $response->respondent_additional_info ?? []
            ],
            'response_metadata' => [
                'response_id' => $response->id,
                'campaign_id' => $response->campaign_id,
                'submitted_at' => $response->created_at,
                'processing_started_at' => $response->scoring_started_at
            ]
        ];
    }

    /**
     * Enhance scores with additional metadata
     */
    private function enhanceScoresWithMetadata(array $scores, CampaignResponse $response, float $processingTime): array
    {
        // Add processing metadata
        $scores['processing_metadata'] = [
            'scoring_time' => $processingTime,
            'scored_at' => now(),
            'response_id' => $response->id,
            'questionnaire_type' => $response->questionnaire?->questionnaire_type?->value,
            'processor_version' => '1.0'
        ];

        // Add quality indicators
        $scores['quality_indicators'] = [
            'has_ai_enhancement' => !empty($response->ai_analysis),
            'has_transcriptions' => !empty($response->transcriptions),
            'response_completeness' => $this->calculateResponseCompleteness($response),
            'data_reliability' => $this->assessDataReliability($response, $scores)
        ];

        // Add completion percentage
        $scores['completion_percentage'] = $this->calculateOverallCompletion($response, $scores);

        // Add timestamps for audit trail
        $scores['audit_trail'] = [
            'response_submitted_at' => $response->created_at,
            'transcription_completed_at' => $response->transcription_completed_at,
            'ai_analysis_completed_at' => $response->ai_analysis_completed_at,
            'scoring_completed_at' => now()
        ];

        return $scores;
    }

    /**
     * Calculate response completeness
     */
    private function calculateResponseCompleteness(CampaignResponse $response): float
    {
        $rawResponses = $response->raw_responses ?? [];
        $questionnaire = $response->questionnaire;

        if (!$questionnaire || empty($rawResponses)) {
            return 0.0;
        }

        $totalQuestions = $this->countTotalQuestions($questionnaire);
        $answeredQuestions = count(array_filter($rawResponses, fn($r) => !empty($r)));

        return $totalQuestions > 0 ? ($answeredQuestions / $totalQuestions) * 100 : 0.0;
    }

    /**
     * Count total questions in questionnaire
     */
    private function countTotalQuestions($questionnaire): int
    {
        $structure = $questionnaire->buildStructure();
        $totalQuestions = 0;

        if (isset($structure['sections'])) {
            foreach ($structure['sections'] as $section) {
                if (isset($section['questions'])) {
                    $totalQuestions += count($section['questions']);
                }
            }
        }

        return $totalQuestions;
    }

    /**
     * Assess data reliability
     */
    private function assessDataReliability(CampaignResponse $response, array $scores): string
    {
        $reliabilityFactors = 0;
        $totalFactors = 4;

        // Factor 1: Complete responses
        if (($scores['completion_percentage'] ?? 0) >= 90) $reliabilityFactors++;

        // Factor 2: Has AI analysis
        if (!empty($response->ai_analysis)) $reliabilityFactors++;

        // Factor 3: Has transcriptions (for audio responses)
        if (!empty($response->transcriptions)) $reliabilityFactors++;

        // Factor 4: Processing completed without errors
        if (empty($response->processing_error)) $reliabilityFactors++;

        $reliabilityScore = ($reliabilityFactors / $totalFactors) * 100;

        return match(true) {
            $reliabilityScore >= 75 => 'high',
            $reliabilityScore >= 50 => 'medium',
            $reliabilityScore >= 25 => 'low',
            default => 'very_low'
        };
    }

    /**
     * Calculate overall completion percentage
     */
    private function calculateOverallCompletion(CampaignResponse $response, array $scores): float
    {
        $completionFactors = [];

        // Response completeness
        $completionFactors[] = $scores['quality_indicators']['response_completeness'] ?? 0;

        // AI analysis completion
        if ($response->questionnaire && $response->questionnaire->requiresAIProcessing()) {
            $completionFactors[] = !empty($response->ai_analysis) ? 100 : 0;
        } else {
            $completionFactors[] = 100; // Not required, so consider complete
        }

        // Scoring completion
        $completionFactors[] = !empty($scores) ? 100 : 0;

        return count($completionFactors) > 0 ? 
            array_sum($completionFactors) / count($completionFactors) : 0.0;
    }

    /**
     * Calculate total processing time from start to finish
     */
    private function calculateTotalProcessingTime(CampaignResponse $response): ?float
    {
        $startTime = $response->created_at;
        $endTime = now();

        return $startTime ? $startTime->diffInSeconds($endTime) : null;
    }

    /**
     * Trigger post-scoring workflows
     */
    private function triggerPostScoringWorkflows(CampaignResponse $response, array $scores): void
    {
        // Check if this is a high-priority response that needs immediate reporting
        if ($this->isHighPriorityResponse($scores)) {
            // GenerateImmediateReportJob::dispatch($response->id)->onQueue('high-priority');
            Log::info('High-priority response detected', ['response_id' => $response->id]);
        }

        // Check if campaign has minimum responses for preliminary analysis
        $campaign = $response->campaign;
        if ($campaign) {
            $completedResponses = $campaign->responses()->where('processing_status', 'completed')->count();
            
            if ($completedResponses >= ($campaign->min_responses_for_analysis ?? 5)) {
                // GeneratePreliminaryAnalysisJob::dispatch($campaign->id)->delay(now()->addMinutes(10));
                Log::info('Campaign ready for preliminary analysis', [
                    'campaign_id' => $campaign->id,
                    'completed_responses' => $completedResponses
                ]);
            }
        }
    }

    /**
     * Determine if this is a high-priority response
     */
    private function isHighPriorityResponse(array $scores): bool
    {
        // Example criteria for high priority
        if (isset($scores['completion_percentage']) && $scores['completion_percentage'] < 50) {
            return true; // Low completion might need attention
        }

        if (isset($scores['quality_indicators']['data_reliability']) && 
            $scores['quality_indicators']['data_reliability'] === 'very_low') {
            return true; // Low reliability needs review
        }

        return false;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateQuestionnaireScoresJob failed permanently', [
            'response_id' => $this->responseId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['scoring', 'response:' . $this->responseId];
    }
}