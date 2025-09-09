<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'campaign' => [
                'id' => $this->campaign_id,
                'name' => $this->campaign->name ?? null,
                'code' => $this->campaign->code ?? null
            ],
            'questionnaire' => [
                'id' => $this->questionnaire_id,
                'name' => $this->questionnaire->name ?? null,
                'type' => $this->questionnaire?->questionnaire_type?->value,
                'scoring_type' => $this->questionnaire?->scoring_type
            ],
            'respondent' => [
                'name' => $this->respondent_name,
                'email' => $this->respondent_email,
                'type' => $this->respondent_type,
                'age' => $this->respondent_age,
                'additional_info' => $this->respondent_additional_info
            ],
            'responses' => [
                'raw_responses' => $this->when($this->shouldIncludeRawData(), $this->raw_responses),
                'processed_responses' => $this->when($this->shouldIncludeProcessedData(), $this->processed_responses),
                'response_count' => $this->getResponseCount(),
                'completion_rate' => $this->getCompletionRate()
            ],
            'processing' => [
                'status' => $this->processing_status ?? 'pending',
                'progress' => $this->getProcessingProgress(),
                'started_at' => $this->processing_started_at,
                'completed_at' => $this->getCompletionTimestamp(),
                'total_processing_time' => $this->total_processing_time,
                'error_message' => $this->processing_error
            ],
            'transcription' => [
                'status' => $this->transcription_status ?? 'not_applicable',
                'data' => $this->when($this->transcriptions, $this->formatTranscriptions()),
                'completed_at' => $this->transcription_completed_at,
                'summary' => $this->transcription_summary
            ],
            'ai_analysis' => [
                'status' => $this->ai_analysis_status ?? 'not_applicable',
                'has_data' => !empty($this->ai_analysis),
                'completed_at' => $this->ai_analysis_completed_at,
                'summary' => $this->when(!empty($this->ai_analysis), $this->getAIAnalysisSummary()),
                'interpretations_count' => $this->getInterpretationsCount()
            ],
            'scores' => [
                'has_scores' => !empty($this->questionnaire_scores),
                'scoring_completed_at' => $this->scoring_completed_at,
                'summary' => $this->when(!empty($this->questionnaire_scores), $this->getScoresSummary()),
                'completion_percentage' => $this->getOverallCompletionPercentage()
            ],
            'reports' => [
                'has_comprehensive_report' => !empty($this->comprehensive_report),
                'report_generated_at' => $this->report_generated_at,
                'report_sections_count' => $this->getReportSectionsCount()
            ],
            'timestamps' => [
                'submitted_at' => $this->created_at,
                'last_updated_at' => $this->updated_at,
                'processing_timeline' => $this->getProcessingTimeline()
            ]
        ];
    }

    /**
     * Determine if raw response data should be included
     */
    private function shouldIncludeRawData(): bool
    {
        // Include raw data for admins or when specifically requested
        return request()->has('include_raw') || auth()->user()?->hasRole('admin');
    }

    /**
     * Determine if processed response data should be included
     */
    private function shouldIncludeProcessedData(): bool
    {
        // Include processed data when analysis is complete or when requested
        return !empty($this->processed_responses) && 
               (request()->has('include_processed') || $this->processing_status === 'completed');
    }

    /**
     * Get response count
     */
    private function getResponseCount(): int
    {
        if (!empty($this->raw_responses)) {
            return count(array_filter($this->raw_responses, fn($response) => !empty($response)));
        }
        
        return 0;
    }

    /**
     * Get completion rate percentage
     */
    private function getCompletionRate(): float
    {
        $questionnaire = $this->questionnaire;
        if (!$questionnaire) return 0;

        $totalQuestions = $this->getTotalQuestionsCount($questionnaire);
        $answeredQuestions = $this->getResponseCount();

        return $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100, 2) : 0;
    }

    /**
     * Get total questions count from questionnaire structure
     */
    private function getTotalQuestionsCount($questionnaire): int
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
     * Get processing progress percentage
     */
    private function getProcessingProgress(): float
    {
        $status = $this->processing_status;
        
        return match($status) {
            'pending' => 0,
            'processing' => 10,
            'transcribing' => 25,
            'transcribed' => 35,
            'analyzing_text' => 45,
            'generating_ai_interpretation' => 55,
            'analyzed' => 75,
            'calculating_scores' => 85,
            'scoring_completed' => 90,
            'generating_report' => 95,
            'completed', 'report_completed' => 100,
            'failed' => 0,
            default => 0
        };
    }

    /**
     * Get completion timestamp based on processing status
     */
    private function getCompletionTimestamp()
    {
        return match($this->processing_status) {
            'completed', 'report_completed' => $this->scoring_completed_at ?? $this->updated_at,
            'report_failed' => $this->processing_failed_at,
            'failed' => $this->processing_failed_at,
            default => null
        };
    }

    /**
     * Format transcriptions for clean presentation
     */
    private function formatTranscriptions(): array
    {
        if (empty($this->transcriptions)) {
            return [];
        }

        $formatted = [];
        foreach ($this->transcriptions as $questionId => $transcription) {
            $formatted[] = [
                'question_id' => $questionId,
                'transcription' => $transcription['transcription_text'] ?? $transcription['transcription'] ?? '',
                'duration' => $transcription['duration'] ?? null,
                'language' => $transcription['language'] ?? 'es',
                'confidence' => $transcription['confidence'] ?? null
            ];
        }

        return $formatted;
    }

    /**
     * Get AI analysis summary
     */
    private function getAIAnalysisSummary(): array
    {
        $analysis = $this->ai_analysis ?? [];
        
        return [
            'interpretations_count' => count($analysis['interpretations'] ?? []),
            'soft_skills_analyzed' => count($analysis['soft_skills_analysis'] ?? []),
            'overall_confidence' => $analysis['overall_confidence'] ?? null,
            'summary' => $analysis['summary'] ?? null,
            'processing_time' => $analysis['processing_metadata']['processing_time'] ?? null
        ];
    }

    /**
     * Get interpretations count
     */
    private function getInterpretationsCount(): int
    {
        $analysis = $this->ai_analysis ?? [];
        return count($analysis['interpretations'] ?? []);
    }

    /**
     * Get scores summary
     */
    private function getScoresSummary(): array
    {
        $scores = $this->questionnaire_scores ?? [];
        
        return [
            'scoring_type' => $scores['scoring_type'] ?? null,
            'total_score' => $scores['total_score'] ?? null,
            'max_possible_score' => $scores['max_possible_score'] ?? null,
            'completion_percentage' => $scores['completion_percentage'] ?? null,
            'quality_indicators' => $scores['quality_indicators'] ?? [],
            'summary' => $scores['summary'] ?? null
        ];
    }

    /**
     * Get overall completion percentage
     */
    private function getOverallCompletionPercentage(): float
    {
        $scores = $this->questionnaire_scores ?? [];
        return $scores['completion_percentage'] ?? 0;
    }

    /**
     * Get report sections count
     */
    private function getReportSectionsCount(): int
    {
        $report = $this->comprehensive_report ?? [];
        return count($report['sections'] ?? []);
    }

    /**
     * Get processing timeline
     */
    private function getProcessingTimeline(): array
    {
        return array_filter([
            'submitted' => $this->created_at,
            'processing_started' => $this->processing_started_at,
            'transcription_completed' => $this->transcription_completed_at,
            'ai_analysis_completed' => $this->ai_analysis_completed_at,
            'scoring_completed' => $this->scoring_completed_at,
            'report_generated' => $this->report_generated_at,
            'processing_failed' => $this->processing_failed_at
        ]);
    }
}