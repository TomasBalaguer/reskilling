<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for detailed questionnaire analysis and scoring results
 */
class QuestionnaireAnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'response_id' => $this->id,
            'questionnaire' => [
                'id' => $this->questionnaire_id,
                'name' => $this->questionnaire->name ?? 'Unknown',
                'type' => $this->questionnaire?->questionnaire_type?->value,
                'scoring_type' => $this->questionnaire?->scoring_type
            ],
            'respondent' => [
                'name' => $this->respondent_name,
                'email' => $this->respondent_email,
                'type' => $this->respondent_type
            ],
            'analysis_summary' => $this->getAnalysisSummary(),
            'scores' => $this->formatScores(),
            'ai_insights' => $this->formatAIInsights(),
            'transcription_analysis' => $this->formatTranscriptionAnalysis(),
            'processing_metadata' => $this->getProcessingMetadata(),
            'quality_assessment' => $this->getQualityAssessment(),
            'recommendations' => $this->getRecommendations(),
            'timestamps' => [
                'submitted_at' => $this->created_at,
                'completed_at' => $this->getAnalysisCompletionTime(),
                'processing_duration' => $this->total_processing_time
            ]
        ];
    }

    /**
     * Get analysis summary
     */
    private function getAnalysisSummary(): array
    {
        $scores = $this->questionnaire_scores ?? [];
        $aiAnalysis = $this->ai_analysis ?? [];

        return [
            'overall_completion' => $this->getOverallCompletion(),
            'processing_status' => $this->processing_status,
            'analysis_type' => $this->getAnalysisType(),
            'key_findings' => $this->extractKeyFindings(),
            'confidence_level' => $this->getOverallConfidence(),
            'data_reliability' => $this->getDataReliability()
        ];
    }

    /**
     * Format questionnaire scores for presentation
     */
    private function formatScores(): array
    {
        $scores = $this->questionnaire_scores ?? [];
        
        if (empty($scores)) {
            return [
                'available' => false,
                'reason' => 'Scores not yet calculated'
            ];
        }

        $formatted = [
            'available' => true,
            'scoring_type' => $scores['scoring_type'] ?? 'unknown',
            'questionnaire_name' => $scores['questionnaire_name'] ?? null,
            'summary' => $scores['summary'] ?? null
        ];

        // Add type-specific score formatting
        switch ($scores['scoring_type'] ?? '') {
            case 'REFLECTIVE_QUESTIONS':
                $formatted['audio_analysis'] = [
                    'transcriptions_count' => count($scores['transcriptions'] ?? []),
                    'analysis_indicators' => $scores['analysis_indicators'] ?? [],
                    'soft_skills_analysis' => $scores['soft_skills_analysis'] ?? []
                ];
                break;

            case 'BIG_FIVE':
                $formatted['personality_profile'] = [
                    'dimension_scores' => $scores['dimension_scores'] ?? [],
                    'personality_type' => $scores['personality_profile']['personality_type'] ?? null,
                    'dominant_traits' => $scores['personality_profile']['dominant_traits'] ?? [],
                    'statistical_summary' => $scores['statistical_summary'] ?? []
                ];
                break;

            case 'MULTIPLE_CHOICE':
            case 'SINGLE_CHOICE':
                $formatted['quantitative_results'] = [
                    'total_score' => $scores['total_score'] ?? 0,
                    'max_possible_score' => $scores['max_possible_score'] ?? 0,
                    'completion_percentage' => $scores['completion_percentage'] ?? 0,
                    'correct_answers' => $scores['correct_answers'] ?? null,
                    'accuracy_percentage' => $scores['accuracy_percentage'] ?? null
                ];
                break;

            case 'TEXT_RESPONSE':
                $formatted['text_analysis'] = [
                    'text_analytics' => $scores['text_analytics'] ?? [],
                    'content_quality_indicators' => $scores['content_quality_indicators'] ?? [],
                    'response_analysis' => count($scores['response_analysis'] ?? [])
                ];
                break;

            case 'SCALE_RATING':
                $formatted['statistical_analysis'] = [
                    'statistical_analysis' => $scores['statistical_analysis'] ?? [],
                    'profile_analysis' => $scores['profile_analysis'] ?? [],
                    'response_patterns' => $scores['response_patterns'] ?? []
                ];
                break;
        }

        return $formatted;
    }

    /**
     * Format AI insights for presentation
     */
    private function formatAIInsights(): array
    {
        $aiAnalysis = $this->ai_analysis ?? [];
        
        if (empty($aiAnalysis)) {
            return [
                'available' => false,
                'reason' => 'AI analysis not performed or not available'
            ];
        }

        return [
            'available' => true,
            'interpretations_count' => count($aiAnalysis['interpretations'] ?? []),
            'soft_skills_evaluated' => array_keys($aiAnalysis['soft_skills_analysis'] ?? []),
            'confidence_scores' => $aiAnalysis['confidence_scores'] ?? [],
            'key_themes' => $aiAnalysis['thematic_analysis']['main_themes'] ?? [],
            'summary' => $aiAnalysis['summary'] ?? null,
            'processing_metadata' => $aiAnalysis['processing_metadata'] ?? []
        ];
    }

    /**
     * Format transcription analysis
     */
    private function formatTranscriptionAnalysis(): array
    {
        if (empty($this->transcriptions)) {
            return [
                'available' => false,
                'reason' => 'No audio transcriptions available'
            ];
        }

        $totalDuration = 0;
        $transcriptionCount = 0;
        $languages = [];

        foreach ($this->transcriptions as $transcription) {
            if (isset($transcription['duration'])) {
                $totalDuration += $transcription['duration'];
            }
            if (!empty($transcription['transcription_text'])) {
                $transcriptionCount++;
            }
            if (isset($transcription['language'])) {
                $languages[] = $transcription['language'];
            }
        }

        return [
            'available' => true,
            'total_audio_files' => count($this->transcriptions),
            'successful_transcriptions' => $transcriptionCount,
            'total_duration_seconds' => $totalDuration,
            'total_duration_display' => $this->formatDuration($totalDuration),
            'languages_detected' => array_unique($languages),
            'success_rate' => count($this->transcriptions) > 0 ? 
                round(($transcriptionCount / count($this->transcriptions)) * 100, 2) : 0,
            'transcription_summary' => $this->transcription_summary ?? []
        ];
    }

    /**
     * Get processing metadata
     */
    private function getProcessingMetadata(): array
    {
        return [
            'processing_steps_completed' => $this->getCompletedProcessingSteps(),
            'processing_timeline' => $this->getProcessingTimeline(),
            'quality_indicators' => $this->extractQualityIndicators(),
            'technical_details' => [
                'ai_model_used' => 'gemini-1.5-flash',
                'processing_version' => '1.0',
                'questionnaire_strategy' => $this->questionnaire?->getQuestionnaireType()?->getStrategyClass()
            ]
        ];
    }

    /**
     * Get quality assessment
     */
    private function getQualityAssessment(): array
    {
        $scores = $this->questionnaire_scores ?? [];
        $qualityIndicators = $scores['quality_indicators'] ?? [];

        return [
            'data_reliability' => $qualityIndicators['data_reliability'] ?? 'unknown',
            'response_completeness' => $qualityIndicators['response_completeness'] ?? 0,
            'has_ai_enhancement' => $qualityIndicators['has_ai_enhancement'] ?? false,
            'has_transcriptions' => $qualityIndicators['has_transcriptions'] ?? false,
            'overall_quality_score' => $this->calculateOverallQualityScore(),
            'confidence_level' => $this->getOverallConfidence()
        ];
    }

    /**
     * Get recommendations based on analysis
     */
    private function getRecommendations(): array
    {
        $report = $this->comprehensive_report ?? [];
        $recommendations = $report['recommendations'] ?? [];

        if (!empty($recommendations)) {
            return $recommendations;
        }

        // Generate basic recommendations based on available data
        return $this->generateBasicRecommendations();
    }

    /**
     * Helper methods
     */

    private function getOverallCompletion(): float
    {
        $scores = $this->questionnaire_scores ?? [];
        return $scores['completion_percentage'] ?? 0;
    }

    private function getAnalysisType(): string
    {
        if (!empty($this->ai_analysis)) return 'ai_enhanced';
        if (!empty($this->transcriptions)) return 'audio_based';
        if (!empty($this->questionnaire_scores)) return 'statistical';
        return 'basic';
    }

    private function extractKeyFindings(): array
    {
        $findings = [];
        
        $scores = $this->questionnaire_scores ?? [];
        if (isset($scores['summary'])) {
            $findings[] = $scores['summary'];
        }

        $aiAnalysis = $this->ai_analysis ?? [];
        if (isset($aiAnalysis['summary'])) {
            $findings[] = $aiAnalysis['summary'];
        }

        return $findings;
    }

    private function getOverallConfidence(): float
    {
        $aiAnalysis = $this->ai_analysis ?? [];
        return $aiAnalysis['overall_confidence'] ?? $aiAnalysis['confidence_scores']['overall_analysis'] ?? 0.0;
    }

    private function getDataReliability(): string
    {
        $scores = $this->questionnaire_scores ?? [];
        return $scores['quality_indicators']['data_reliability'] ?? 'unknown';
    }

    private function getAnalysisCompletionTime()
    {
        return $this->report_generated_at ?? 
               $this->scoring_completed_at ?? 
               $this->ai_analysis_completed_at ?? 
               $this->transcription_completed_at;
    }

    private function getCompletedProcessingSteps(): array
    {
        $steps = [];
        
        if ($this->transcription_completed_at) $steps[] = 'transcription';
        if ($this->ai_analysis_completed_at) $steps[] = 'ai_analysis';
        if ($this->scoring_completed_at) $steps[] = 'scoring';
        if ($this->report_generated_at) $steps[] = 'reporting';
        
        return $steps;
    }

    private function getProcessingTimeline(): array
    {
        return array_filter([
            'submitted' => $this->created_at,
            'transcription_completed' => $this->transcription_completed_at,
            'ai_analysis_completed' => $this->ai_analysis_completed_at,
            'scoring_completed' => $this->scoring_completed_at,
            'report_generated' => $this->report_generated_at
        ]);
    }

    private function extractQualityIndicators(): array
    {
        $scores = $this->questionnaire_scores ?? [];
        return $scores['quality_indicators'] ?? [];
    }

    private function calculateOverallQualityScore(): float
    {
        $factors = [];
        
        // Response completeness
        $completeness = $this->getOverallCompletion();
        $factors[] = $completeness;
        
        // AI analysis availability
        if (!empty($this->ai_analysis)) {
            $factors[] = 85; // High score for AI enhancement
        }
        
        // Data reliability
        $reliability = $this->getDataReliability();
        $reliabilityScore = match($reliability) {
            'high' => 90,
            'medium' => 70,
            'low' => 50,
            default => 60
        };
        $factors[] = $reliabilityScore;

        return count($factors) > 0 ? round(array_sum($factors) / count($factors), 2) : 0;
    }

    private function generateBasicRecommendations(): array
    {
        $recommendations = [
            'immediate_actions' => [],
            'development_opportunities' => [],
            'follow_up_suggestions' => []
        ];

        $completeness = $this->getOverallCompletion();
        
        if ($completeness < 50) {
            $recommendations['immediate_actions'][] = 'Consider reviewing incomplete responses';
        }

        if (!empty($this->ai_analysis)) {
            $recommendations['development_opportunities'][] = 'Review AI insights for development areas';
        }

        $recommendations['follow_up_suggestions'][] = 'Schedule discussion of results with respondent';

        return $recommendations;
    }

    private function formatDuration(int $seconds): string
    {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes > 0) {
            return "{$minutes}m {$remainingSeconds}s";
        } else {
            return "{$seconds}s";
        }
    }
}