<?php

namespace App\Jobs;

use App\Models\CampaignResponse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateComprehensiveReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

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
    public function handle(): void
    {
        try {
            $response = CampaignResponse::findOrFail($this->responseId);
            $questionnaire = $response->questionnaire;
            $campaign = $response->campaign;

            if (!$questionnaire || !$campaign) {
                throw new \Exception("Required models not found for response {$this->responseId}");
            }

            Log::info('Generating comprehensive report', [
                'response_id' => $response->id,
                'campaign_id' => $campaign->id,
                'questionnaire_type' => $questionnaire->questionnaire_type?->value
            ]);

            // Update processing status
            $response->update([
                'processing_status' => 'generating_report',
                'report_generation_started_at' => now()
            ]);

            // Generate comprehensive report
            $startTime = microtime(true);
            $report = $this->generateReport($response);
            $processingTime = round((microtime(true) - $startTime), 2);

            // Save report
            $response->update([
                'comprehensive_report' => $report,
                'processing_status' => 'report_completed',
                'report_generated_at' => now(),
                'report_generation_time' => $processingTime
            ]);

            Log::info('Comprehensive report generated successfully', [
                'response_id' => $response->id,
                'processing_time' => $processingTime,
                'report_sections' => count($report['sections'] ?? [])
            ]);

        } catch (\Exception $e) {
            Log::error('Comprehensive report generation failed', [
                'response_id' => $this->responseId,
                'error' => $e->getMessage()
            ]);

            $response = CampaignResponse::find($this->responseId);
            if ($response) {
                $response->update([
                    'processing_status' => 'report_failed',
                    'processing_error' => $e->getMessage(),
                    'processing_failed_at' => now()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Generate comprehensive report for the response
     */
    private function generateReport(CampaignResponse $response): array
    {
        // Use ComprehensiveReportService for AI-powered report generation
        $reportService = app(\App\Services\ComprehensiveReportService::class);
        
        // Generate the comprehensive report with AI - pass the CampaignResponse object
        $aiGeneratedReport = $reportService->generateComprehensiveReport($response);
        
        return $aiGeneratedReport;
    }

    /**
     * Generate report metadata
     */
    private function generateReportMetadata(CampaignResponse $response): array
    {
        return [
            'response_id' => $response->id,
            'campaign_id' => $response->campaign_id,
            'campaign_name' => $response->campaign->name,
            'questionnaire_id' => $response->questionnaire_id,
            'questionnaire_name' => $response->questionnaire->name,
            'questionnaire_type' => $response->questionnaire->questionnaire_type?->value,
            'respondent_name' => $response->respondent_name,
            'respondent_email' => $response->respondent_email,
            'response_submitted_at' => $response->created_at,
            'processing_completed_at' => now(),
            'company' => [
                'name' => $response->campaign->company->name,
                'id' => $response->campaign->company->id
            ]
        ];
    }

    /**
     * Generate executive summary
     */
    private function generateExecutiveSummary(CampaignResponse $response): array
    {
        $scores = $response->questionnaire_scores ?? [];
        $aiAnalysis = $response->ai_analysis ?? [];

        $summary = [
            'overall_performance' => $this->assessOverallPerformance($scores),
            'key_findings' => $this->extractKeyFindings($scores, $aiAnalysis),
            'completion_metrics' => [
                'completion_percentage' => $scores['completion_percentage'] ?? 0,
                'data_quality' => $scores['quality_indicators']['data_reliability'] ?? 'unknown',
                'processing_success' => $response->processing_status === 'report_completed'
            ]
        ];

        // Add questionnaire-specific summary
        $questionnaireType = $response->questionnaire->questionnaire_type?->value;
        switch ($questionnaireType) {
            case 'REFLECTIVE_QUESTIONS':
                $summary['audio_analysis'] = $this->generateAudioAnalysisSummary($response);
                break;
            case 'BIG_FIVE':
                $summary['personality_profile'] = $this->generatePersonalityProfileSummary($scores);
                break;
            case 'TEXT_RESPONSE':
                $summary['content_analysis'] = $this->generateContentAnalysisSummary($scores, $aiAnalysis);
                break;
        }

        return $summary;
    }

    /**
     * Generate report sections
     */
    private function generateReportSections(CampaignResponse $response): array
    {
        $sections = [];

        // Section 1: Response Overview
        $sections[] = [
            'title' => 'Resumen de Respuesta',
            'type' => 'overview',
            'content' => $this->generateResponseOverview($response)
        ];

        // Section 2: Detailed Analysis
        $sections[] = [
            'title' => 'Análisis Detallado',
            'type' => 'analysis',
            'content' => $this->generateDetailedAnalysis($response)
        ];

        // Section 3: Scores and Metrics
        if ($response->questionnaire_scores) {
            $sections[] = [
                'title' => 'Puntuaciones y Métricas',
                'type' => 'scores',
                'content' => $response->questionnaire_scores
            ];
        }

        // Section 4: AI Insights (if available)
        if ($response->ai_analysis) {
            $sections[] = [
                'title' => 'Insights de Inteligencia Artificial',
                'type' => 'ai_insights',
                'content' => $this->formatAIInsights($response->ai_analysis)
            ];
        }

        return $sections;
    }

    /**
     * Generate appendices
     */
    private function generateAppendices(CampaignResponse $response): array
    {
        $appendices = [];

        // Raw data appendix
        $appendices['raw_data'] = [
            'raw_responses' => $response->raw_responses,
            'processed_responses' => $response->processed_responses,
            'transcriptions' => $response->transcriptions
        ];

        // Processing metadata
        $appendices['processing_metadata'] = [
            'processing_timeline' => $this->generateProcessingTimeline($response),
            'quality_metrics' => $this->extractQualityMetrics($response),
            'technical_details' => $this->generateTechnicalDetails($response)
        ];

        return $appendices;
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations(CampaignResponse $response): array
    {
        $scores = $response->questionnaire_scores ?? [];
        $aiAnalysis = $response->ai_analysis ?? [];

        return [
            'immediate_actions' => $this->generateImmediateActions($scores, $aiAnalysis),
            'development_opportunities' => $this->generateDevelopmentOpportunities($scores, $aiAnalysis),
            'follow_up_suggestions' => $this->generateFollowUpSuggestions($response),
            'next_steps' => $this->generateNextSteps($response)
        ];
    }

    // Helper methods for content generation

    private function assessOverallPerformance(array $scores): string
    {
        $completionPercentage = $scores['completion_percentage'] ?? 0;
        
        return match(true) {
            $completionPercentage >= 90 => 'excellent',
            $completionPercentage >= 75 => 'good',
            $completionPercentage >= 50 => 'satisfactory',
            default => 'needs_improvement'
        };
    }

    private function extractKeyFindings(array $scores, array $aiAnalysis): array
    {
        $findings = [];

        // Extract from scores
        if (isset($scores['summary'])) {
            $findings[] = $scores['summary'];
        }

        // Extract from AI analysis
        if (isset($aiAnalysis['summary'])) {
            $findings[] = $aiAnalysis['summary'];
        }

        return $findings;
    }

    private function generateAudioAnalysisSummary(CampaignResponse $response): array
    {
        $transcriptions = $response->transcriptions ?? [];
        $aiAnalysis = $response->ai_analysis ?? [];

        return [
            'transcription_quality' => 'high',
            'total_audio_duration' => array_sum(array_column($transcriptions, 'duration')),
            'soft_skills_evaluated' => count($aiAnalysis['soft_skills_analysis'] ?? []),
            'prosodic_analysis_available' => !empty($aiAnalysis['prosodic_analysis'])
        ];
    }

    private function generatePersonalityProfileSummary(array $scores): array
    {
        return [
            'dominant_traits' => $scores['personality_profile']['dominant_traits'] ?? [],
            'personality_type' => $scores['personality_profile']['personality_type']['type_description'] ?? 'Balanced profile',
            'statistical_summary' => $scores['statistical_summary'] ?? []
        ];
    }

    private function generateContentAnalysisSummary(array $scores, array $aiAnalysis): array
    {
        return [
            'content_quality' => $scores['content_quality_indicators'] ?? [],
            'text_analytics' => $scores['text_analytics'] ?? [],
            'ai_insights_available' => !empty($aiAnalysis)
        ];
    }

    private function generateResponseOverview(CampaignResponse $response): array
    {
        return [
            'submission_date' => $response->created_at->format('Y-m-d H:i:s'),
            'processing_duration' => $response->total_processing_time ?? 0,
            'questionnaire_type' => $response->questionnaire->questionnaire_type?->value,
            'completion_status' => $response->processing_status
        ];
    }

    private function generateDetailedAnalysis(CampaignResponse $response): array
    {
        return [
            'response_patterns' => 'Detailed analysis of response patterns',
            'quality_assessment' => 'Comprehensive quality assessment',
            'consistency_check' => 'Response consistency analysis'
        ];
    }

    private function formatAIInsights(array $aiAnalysis): array
    {
        return [
            'interpretations' => $aiAnalysis['interpretations'] ?? [],
            'confidence_scores' => $aiAnalysis['overall_confidence'] ?? 0,
            'key_themes' => $aiAnalysis['key_themes'] ?? []
        ];
    }

    private function generateProcessingTimeline(CampaignResponse $response): array
    {
        return [
            'submitted' => $response->created_at,
            'transcription_completed' => $response->transcription_completed_at,
            'ai_analysis_completed' => $response->ai_analysis_completed_at,
            'scoring_completed' => $response->scoring_completed_at,
            'report_generated' => now()
        ];
    }

    private function extractQualityMetrics(CampaignResponse $response): array
    {
        $scores = $response->questionnaire_scores ?? [];
        
        return $scores['quality_indicators'] ?? [];
    }

    private function generateTechnicalDetails(CampaignResponse $response): array
    {
        return [
            'processing_version' => '1.0',
            'ai_model_used' => config('ai.gemini.model_version'),
            'processing_environment' => app()->environment(),
            'data_retention_policy' => 'Standard retention applies'
        ];
    }

    private function generateImmediateActions(array $scores, array $aiAnalysis): array
    {
        return [
            'Review results with respondent',
            'Schedule follow-up discussion',
            'Document key insights'
        ];
    }

    private function generateDevelopmentOpportunities(array $scores, array $aiAnalysis): array
    {
        return [
            'Based on analysis results, consider targeted development programs',
            'Focus on areas identified as development opportunities',
            'Leverage strengths for maximum impact'
        ];
    }

    private function generateFollowUpSuggestions(CampaignResponse $response): array
    {
        return [
            'Schedule 3-month follow-up assessment',
            'Create personalized development plan',
            'Monitor progress against baseline'
        ];
    }

    private function generateNextSteps(CampaignResponse $response): array
    {
        return [
            'Share report with relevant stakeholders',
            'Implement recommended actions',
            'Track progress and outcomes'
        ];
    }
    
    /**
     * Parse AI-generated report into structured format
     */
    private function parseAIReport(string $aiReport, CampaignResponse $response): array
    {
        // Extract sections from the AI-generated text
        $sections = [];
        
        // Parse the report text to extract structured sections
        $reportLines = explode("\n", $aiReport);
        $currentSection = null;
        $currentContent = '';
        
        foreach ($reportLines as $line) {
            // Check if this is a section header
            if (preg_match('/^###?\s+(.+)$/', $line, $matches)) {
                // Save previous section if exists
                if ($currentSection) {
                    $sections[] = [
                        'title' => $currentSection,
                        'content' => trim($currentContent)
                    ];
                }
                $currentSection = $matches[1];
                $currentContent = '';
            } else {
                $currentContent .= $line . "\n";
            }
        }
        
        // Don't forget the last section
        if ($currentSection) {
            $sections[] = [
                'title' => $currentSection,
                'content' => trim($currentContent)
            ];
        }
        
        return [
            'content' => $aiReport,
            'sections' => $sections,
            'metadata' => [
                'word_count' => str_word_count($aiReport),
                'ai_model' => config('ai.gemini.model_version', 'gemini-1.5-flash'),
                'processing_version' => '1.0'
            ],
            'generated_at' => now(),
            'response_id' => $response->id,
            'respondent_name' => $response->respondent_name,
            'campaign_info' => [
                'name' => $response->campaign->name,
                'company' => $response->campaign->company->name
            ],
            'report_type' => 'comprehensive_professional'
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GenerateComprehensiveReportJob failed permanently', [
            'response_id' => $this->responseId,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['reporting', 'comprehensive', 'response:' . $this->responseId];
    }
}