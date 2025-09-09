<?php

namespace App\Listeners;

use App\Events\AIAnalysisCompleted;
use App\Jobs\GenerateQuestionnaireScoresJob;
use App\Jobs\GenerateComprehensiveReportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessAIAnalysisResults implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Handle the event.
     */
    public function handle(AIAnalysisCompleted $event): void
    {
        try {
            Log::info('📊 STEP 5: Processing AI analysis results', [
                'step' => 5,
                'response_id' => $event->response->id,
                'successful' => $event->isSuccessful(),
                'summary' => $event->getAnalysisSummary(),
                'listener' => 'ProcessAIAnalysisResults'
            ]);

            if ($event->isSuccessful()) {
                // Update response with AI analysis data
                $this->updateResponseWithAnalysis($event);
                
                // Dispatch final scoring job
                $this->dispatchFinalProcessing($event);
            } else {
                // Handle AI analysis failure
                $this->handleAnalysisFailure($event);
            }

        } catch (\Exception $e) {
            Log::error('Failed to process AI analysis results', [
                'response_id' => $event->response->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Update response with AI analysis data
     */
    private function updateResponseWithAnalysis(AIAnalysisCompleted $event): void
    {
        $analysisSummary = $event->getAnalysisSummary();
        
        $event->response->update([
            'ai_analysis' => $event->analysisResults,
            'ai_analysis_status' => 'completed',
            'ai_analysis_completed_at' => now(),
            'analysis_summary' => $analysisSummary,
            'processing_status' => 'analyzed',
            'completion_percentage' => $event->calculateCompletionPercentage()
        ]);

        Log::info('Response updated with AI analysis data', [
            'response_id' => $event->response->id,
            'interpretations_count' => $analysisSummary['interpretations_generated'],
            'competencies_analyzed' => count($analysisSummary['competencies_evaluated']),
            'confidence_score' => $analysisSummary['analysis_confidence']
        ]);
    }

    /**
     * Dispatch final processing jobs
     */
    private function dispatchFinalProcessing(AIAnalysisCompleted $event): void
    {
        // First, generate the final questionnaire scores
        GenerateQuestionnaireScoresJob::dispatch($event->response->id)
            ->onQueue('scoring')
            ->delay(now()->addMinutes(2));

        Log::info('🎯 STEP 5.1: Final scoring job dispatched', [
            'step' => '5.1',
            'response_id' => $event->response->id,
            'next_job' => 'GenerateQuestionnaireScoresJob',
            'queue' => 'scoring',
            'delay_minutes' => 2
        ]);

        // If the response is ready for comprehensive reporting, generate it
        if ($event->isReadyForReport()) {
            GenerateComprehensiveReportJob::dispatch($event->response->id)
                ->onQueue('reporting')
                ->delay(now()->addMinutes(5)); // Allow time for scoring to complete

            Log::info('Comprehensive report job dispatched', ['response_id' => $event->response->id]);
        }

        // Check if this completes the campaign response
        $this->checkCampaignCompletion($event);
    }

    /**
     * Handle AI analysis failure
     */
    private function handleAnalysisFailure(AIAnalysisCompleted $event): void
    {
        $event->response->update([
            'ai_analysis_status' => 'failed',
            'ai_analysis_error' => $event->error,
            'ai_analysis_failed_at' => now(),
            'processing_status' => 'ai_analysis_failed'
        ]);

        Log::error('AI analysis failed for response', [
            'response_id' => $event->response->id,
            'error' => $event->error,
            'summary' => $event->getAnalysisSummary()
        ]);

        // Even if AI analysis fails, we can still generate basic scores
        // using the transcriptions and raw responses
        GenerateQuestionnaireScoresJob::dispatch($event->response->id)
            ->onQueue('scoring')
            ->delay(now()->addMinutes(1));

        Log::info('Fallback scoring job dispatched after AI failure', ['response_id' => $event->response->id]);
    }

    /**
     * Check if campaign response is complete and trigger any completion workflows
     */
    private function checkCampaignCompletion(AIAnalysisCompleted $event): void
    {
        $campaign = $event->response->campaign;
        if (!$campaign) return;

        // Check if all questionnaires in the campaign are completed
        $totalQuestionnaires = $campaign->questionnaires()->count();
        $completedQuestionnaires = $campaign->responses()
            ->where('processing_status', 'completed')
            ->distinct('questionnaire_id')
            ->count();

        if ($completedQuestionnaires >= $totalQuestionnaires) {
            // Campaign is complete, trigger completion workflows
            Log::info('Campaign completed', [
                'campaign_id' => $campaign->id,
                'response_id' => $event->response->id,
                'completed_questionnaires' => $completedQuestionnaires,
                'total_questionnaires' => $totalQuestionnaires
            ]);

            // Dispatch campaign completion job
            // GenerateCampaignSummaryJob::dispatch($campaign->id)->onQueue('reporting');
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(AIAnalysisCompleted $event, \Throwable $exception): void
    {
        Log::error('ProcessAIAnalysisResults listener failed', [
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