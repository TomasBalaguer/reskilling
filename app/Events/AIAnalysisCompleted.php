<?php

namespace App\Events;

use App\Models\CampaignResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AIAnalysisCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CampaignResponse $response,
        public array $analysisResults = [],
        public bool $success = true,
        public ?string $error = null
    ) {}

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'campaign:' . $this->response->campaign_id,
            'response:' . $this->response->id,
            'ai-analysis:completed',
        ];
    }

    /**
     * Check if AI analysis was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success && !empty($this->analysisResults);
    }

    /**
     * Get analysis summary
     */
    public function getAnalysisSummary(): array
    {
        if (!$this->isSuccessful()) {
            return [
                'status' => 'failed',
                'error' => $this->error,
                'interpretations_generated' => 0,
                'soft_skills_analyzed' => 0
            ];
        }

        $interpretations = $this->analysisResults['interpretations'] ?? [];
        $softSkillsAnalysis = $this->analysisResults['soft_skills_analysis'] ?? [];

        return [
            'status' => 'completed',
            'interpretations_generated' => count($interpretations),
            'soft_skills_analyzed' => count($softSkillsAnalysis),
            'analysis_confidence' => $this->calculateOverallConfidence(),
            'competencies_evaluated' => $this->getEvaluatedCompetencies(),
            'processing_time' => $this->analysisResults['processing_time'] ?? null,
        ];
    }

    /**
     * Calculate overall confidence score
     */
    private function calculateOverallConfidence(): float
    {
        $interpretations = $this->analysisResults['interpretations'] ?? [];
        
        if (empty($interpretations)) {
            return 0.0;
        }

        $confidenceScores = [];
        foreach ($interpretations as $interpretation) {
            if (isset($interpretation['confidence_score'])) {
                $confidenceScores[] = $interpretation['confidence_score'];
            }
        }

        return count($confidenceScores) > 0 ? 
            array_sum($confidenceScores) / count($confidenceScores) : 0.0;
    }

    /**
     * Get list of evaluated competencies
     */
    private function getEvaluatedCompetencies(): array
    {
        $softSkillsAnalysis = $this->analysisResults['soft_skills_analysis'] ?? [];
        return array_keys($softSkillsAnalysis);
    }

    /**
     * Get metadata for final processing stage
     */
    public function getProcessingMetadata(): array
    {
        return [
            'response_id' => $this->response->id,
            'campaign_id' => $this->response->campaign_id,
            'questionnaire_id' => $this->response->questionnaire_id,
            'analysis_status' => $this->success ? 'completed' : 'failed',
            'analysis_summary' => $this->getAnalysisSummary(),
            'next_stage' => $this->success ? 'report_generation' : 'error_handling',
            'completion_percentage' => $this->calculateCompletionPercentage(),
            'processed_at' => now()
        ];
    }

    /**
     * Calculate completion percentage for the response
     */
    public function calculateCompletionPercentage(): float
    {
        if (!$this->success) {
            return 0.0;
        }

        // This is a simplified calculation
        // In a real scenario, you'd check various completion criteria
        $criteria = [
            'has_transcriptions' => !empty($this->analysisResults['transcriptions']),
            'has_interpretations' => !empty($this->analysisResults['interpretations']),
            'has_soft_skills_analysis' => !empty($this->analysisResults['soft_skills_analysis']),
            'has_prosodic_analysis' => !empty($this->analysisResults['prosodic_analysis']),
        ];

        $completedCriteria = count(array_filter($criteria));
        $totalCriteria = count($criteria);

        return ($completedCriteria / $totalCriteria) * 100;
    }

    /**
     * Check if response is ready for final report generation
     */
    public function isReadyForReport(): bool
    {
        return $this->isSuccessful() && $this->calculateCompletionPercentage() >= 75;
    }
}