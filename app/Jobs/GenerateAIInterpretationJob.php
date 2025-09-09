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

class GenerateAIInterpretationJob implements ShouldQueue
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

            Log::info('🤖 STEP 4: Generating AI interpretation', [
                'step' => 4,
                'response_id' => $response->id,
                'questionnaire_type' => $questionnaire->questionnaire_type?->value,
                'has_transcriptions' => !empty($response->transcriptions),
                'job' => 'GenerateAIInterpretationJob'
            ]);

            // Update processing status
            $response->update([
                'processing_status' => 'generating_ai_interpretation',
                'ai_analysis_started_at' => now()
            ]);

            // Prepare data for AI interpretation
            $questionnaireResults = $this->prepareQuestionnaireData($response);
            
            if (empty($questionnaireResults)) {
                throw new \Exception("No data available for AI interpretation");
            }

            // Generate AI interpretation
            $startTime = microtime(true);
            
            // Prepare respondent data for the service
            $respondentData = [
                'name' => $response->respondent_name,
                'email' => $response->respondent_email,
                'type' => $response->respondent_type,
                'age' => $response->respondent_age,
                'additional_info' => $response->respondent_additional_info
            ];
            
            $aiServiceResult = $aiService->generateInterpretation($respondentData, $questionnaireResults);
            $processingTime = round((microtime(true) - $startTime), 2);

            // Transform AI service result to expected structure
            $analysisResults = $this->transformAIServiceResult($aiServiceResult);

            // Enhance results with processing metadata
            $analysisResults = $this->enhanceResultsWithMetadata($analysisResults, $processingTime, $response);

            Log::info('✅ STEP 4.1: AI interpretation completed, dispatching analysis event', [
                'step' => '4.1',
                'response_id' => $response->id,
                'processing_time' => $processingTime,
                'interpretations_count' => count($analysisResults['interpretations'] ?? []),
                'confidence_score' => $analysisResults['overall_confidence'] ?? 0,
                'next_event' => 'AIAnalysisCompleted'
            ]);

            // Fire AI analysis completed event
            AIAnalysisCompleted::dispatch($response, $analysisResults, true);

        } catch (\Exception $e) {
            Log::error('AI interpretation job failed', [
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
     * Prepare questionnaire data for AI interpretation
     */
    private function prepareQuestionnaireData(CampaignResponse $response): array
    {
        $questionnaireResults = [];

        // Include transcriptions if available (for audio responses)
        if ($response->transcriptions) {
            $questionnaireResults['transcriptions'] = $response->transcriptions;
            $questionnaireResults['has_audio_content'] = true;
        }

        // Include processed responses
        if ($response->processed_responses) {
            $questionnaireResults['processed_responses'] = $response->processed_responses;
        }

        // Include raw responses as fallback
        if ($response->raw_responses) {
            $questionnaireResults['raw_responses'] = $response->raw_responses;
        }

        // Add response metadata
        $questionnaireResults['response_metadata'] = [
            'response_id' => $response->id,
            'campaign_id' => $response->campaign_id,
            'questionnaire_id' => $response->questionnaire_id,
            'submitted_at' => $response->created_at,
            'questionnaire_type' => $response->questionnaire?->questionnaire_type?->value,
            'respondent_data' => [
                'name' => $response->respondent_name,
                'email' => $response->respondent_email,
                'type' => $response->respondent_type
            ]
        ];

        return $questionnaireResults;
    }

    /**
     * Enhance analysis results with processing metadata
     */
    private function enhanceResultsWithMetadata(array $analysisResults, float $processingTime, CampaignResponse $response): array
    {
        // Add processing metadata
        $analysisResults['processing_metadata'] = [
            'processing_time' => $processingTime,
            'analyzed_at' => now(),
            'analysis_type' => 'ai_interpretation',
            'model_version' => config('ai.gemini.model_version', 'gemini-1.5-flash'),
            'response_id' => $response->id
        ];

        // Calculate overall confidence score if not already present
        if (!isset($analysisResults['overall_confidence'])) {
            $analysisResults['overall_confidence'] = $this->calculateOverallConfidence($analysisResults);
        }

        // Add quality indicators
        $analysisResults['quality_indicators'] = [
            'has_interpretations' => !empty($analysisResults['interpretations']),
            'has_soft_skills_analysis' => !empty($analysisResults['soft_skills_analysis']),
            'has_prosodic_analysis' => !empty($analysisResults['prosodic_analysis']),
            'content_richness' => $this->assessContentRichness($analysisResults),
            'analysis_completeness' => $this->calculateAnalysisCompleteness($analysisResults)
        ];

        return $analysisResults;
    }

    /**
     * Calculate overall confidence score
     */
    private function calculateOverallConfidence(array $analysisResults): float
    {
        $confidenceScores = [];

        // Extract confidence scores from interpretations
        if (isset($analysisResults['interpretations'])) {
            foreach ($analysisResults['interpretations'] as $interpretation) {
                if (isset($interpretation['confidence_score'])) {
                    $confidenceScores[] = $interpretation['confidence_score'];
                }
            }
        }

        // Extract confidence from soft skills analysis
        if (isset($analysisResults['soft_skills_analysis'])) {
            foreach ($analysisResults['soft_skills_analysis'] as $skill) {
                if (isset($skill['confidence'])) {
                    $confidenceScores[] = $skill['confidence'];
                }
            }
        }

        return count($confidenceScores) > 0 ? 
            array_sum($confidenceScores) / count($confidenceScores) : 0.0;
    }

    /**
     * Assess content richness
     */
    private function assessContentRichness(array $analysisResults): string
    {
        $richness = 0;
        
        if (!empty($analysisResults['interpretations'])) $richness++;
        if (!empty($analysisResults['soft_skills_analysis'])) $richness++;
        if (!empty($analysisResults['prosodic_analysis'])) $richness++;
        if (!empty($analysisResults['transcriptions'])) $richness++;

        return match(true) {
            $richness >= 4 => 'very_high',
            $richness >= 3 => 'high',
            $richness >= 2 => 'medium',
            $richness >= 1 => 'low',
            default => 'minimal'
        };
    }

    /**
     * Calculate analysis completeness percentage
     */
    private function calculateAnalysisCompleteness(array $analysisResults): float
    {
        $criteria = [
            'has_interpretations' => !empty($analysisResults['interpretations']),
            'has_soft_skills' => !empty($analysisResults['soft_skills_analysis']),
            'has_summary' => !empty($analysisResults['summary']),
            'has_confidence' => isset($analysisResults['overall_confidence'])
        ];

        $completedCriteria = count(array_filter($criteria));
        $totalCriteria = count($criteria);

        return ($completedCriteria / $totalCriteria) * 100;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateAIInterpretationJob failed permanently', [
            'response_id' => $this->responseId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Update response status
        $response = CampaignResponse::find($this->responseId);
        if ($response) {
            $response->update([
                'processing_status' => 'ai_interpretation_failed',
                'processing_error' => $exception->getMessage(),
                'processing_failed_at' => now()
            ]);
        }
    }

    /**
     * Transform AI service result to expected analysis structure
     */
    private function transformAIServiceResult(array $aiServiceResult): array
    {
        if (!$aiServiceResult['success']) {
            return [
                'interpretations' => [],
                'soft_skills_analysis' => [],
                'prosodic_analysis' => [],
                'summary' => $aiServiceResult['message'] ?? 'AI analysis failed',
                'overall_confidence' => 0.0
            ];
        }

        $interpretation = $aiServiceResult['interpretation'] ?? '';
        
        return [
            'interpretations' => [
                [
                    'content' => $interpretation,
                    'confidence_score' => 0.8, // Default confidence for successful AI response
                    'analysis_type' => 'comprehensive_interpretation',
                    'generated_at' => now()
                ]
            ],
            'soft_skills_analysis' => $this->extractSoftSkillsFromInterpretation($interpretation),
            'prosodic_analysis' => [],
            'summary' => $this->generateSummaryFromInterpretation($interpretation),
            'overall_confidence' => 0.8,
            'prompt_used' => $aiServiceResult['prompt'] ?? null
        ];
    }

    /**
     * Extract soft skills insights from interpretation text
     */
    private function extractSoftSkillsFromInterpretation(string $interpretation): array
    {
        // Simple extraction based on keywords
        $softSkills = [];
        
        if (strpos(strtolower($interpretation), 'comunicación') !== false) {
            $softSkills['comunicacion'] = ['score' => 0.7, 'confidence' => 0.6];
        }
        
        if (strpos(strtolower($interpretation), 'eficiencia') !== false) {
            $softSkills['eficiencia'] = ['score' => 0.8, 'confidence' => 0.7];
        }
        
        if (strpos(strtolower($interpretation), 'reflexión') !== false || strpos(strtolower($interpretation), 'análisis') !== false) {
            $softSkills['pensamiento_analitico'] = ['score' => 0.6, 'confidence' => 0.5];
        }
        
        return $softSkills;
    }

    /**
     * Generate summary from interpretation
     */
    private function generateSummaryFromInterpretation(string $interpretation): string
    {
        // Extract first paragraph or first 200 characters as summary
        $lines = explode("\n", $interpretation);
        $firstParagraph = trim($lines[0]);
        
        if (strlen($firstParagraph) > 200) {
            return substr($firstParagraph, 0, 200) . '...';
        }
        
        return $firstParagraph;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['ai-interpretation', 'response:' . $this->responseId];
    }
}