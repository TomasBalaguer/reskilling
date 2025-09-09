<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAudioTranscriptionsJob;
use App\Models\Campaign;
use App\Models\CampaignResponse;
use App\Models\Questionnaire;
use App\Services\AIInterpretationService;
use App\Services\QuestionnaireProcessing\QuestionnaireProcessorFactory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampaignResponseController extends Controller
{
    /**
     * Start a new campaign response session
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_code' => 'required|string|exists:campaigns,code',
            'respondent_name' => 'required|string|max:255',
            'respondent_email' => 'required|email|max:255',
            'respondent_age' => 'nullable|integer|min:16|max:100',
            'respondent_gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'respondent_occupation' => 'nullable|string|max:255',
        ]);

        $campaign = Campaign::where('code', $request->campaign_code)
            ->where('status', 'active')
            ->where('active_from', '<=', now())
            ->where('active_until', '>=', now())
            ->first();

        if (!$campaign) {
            return response()->json(['error' => 'Campaña no válida o no activa'], 404);
        }

        if ($campaign->responses_count >= $campaign->max_responses) {
            return response()->json(['error' => 'Campaña completa'], 423);
        }

        $response = CampaignResponse::create([
            'campaign_id' => $campaign->id,
            'respondent_name' => $request->respondent_name,
            'respondent_email' => $request->respondent_email,
            'age' => $request->respondent_age,
            'gender' => $request->respondent_gender,
            'occupation' => $request->respondent_occupation,
            'session_id' => Str::uuid(),
            'responses' => [],
            'processing_status' => 'pending',
            'started_at' => now(),
        ]);

        return response()->json([
            'session_id' => $response->session_id,
            'response_id' => $response->id,
            'status' => 'started',
            'message' => 'Sesión iniciada correctamente'
        ]);
    }

    /**
     * Submit questionnaire answers
     */
    public function submitAnswers(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|exists:campaign_responses,session_id',
            'questionnaire_id' => 'required|integer|exists:questionnaires,id',
            'answers' => 'required|array',
            'audio_files' => 'sometimes|array',
            'audio_files.*' => 'file|mimes:mp3,wav,m4a,aac|max:51200', // 50MB max
        ]);

        $response = CampaignResponse::where('session_id', $request->session_id)
            ->where('processing_status', 'pending')
            ->first();

        if (!$response) {
            return response()->json(['error' => 'Sesión no válida'], 404);
        }

        $questionnaire = Questionnaire::findOrFail($request->questionnaire_id);

        // Process audio files if present
        $audioFiles = [];
        if ($request->hasFile('audio_files')) {
            foreach ($request->file('audio_files') as $questionId => $audioFile) {
                if ($audioFile && $audioFile->isValid()) {
                    $filename = "campaign_responses/{$response->id}/{$questionnaire->id}/{$questionId}_" . time() . '.' . $audioFile->getClientOriginalExtension();
                    $path = $audioFile->storeAs('private', $filename);
                    $audioFiles[$questionId] = $path;
                }
            }
        }

        // Store answers with audio file references
        $answersData = $request->answers;
        foreach ($audioFiles as $questionId => $audioPath) {
            $answersData[$questionId] = [
                'answer' => $answersData[$questionId] ?? null,
                'audio_file' => $audioPath,
                'submitted_at' => now()->toISOString()
            ];
        }

        // Update or create questionnaire response data
        $responses = $response->responses ?? [];
        $responses[$questionnaire->id] = [
            'questionnaire_id' => $questionnaire->id,
            'questionnaire_name' => $questionnaire->name,
            'scoring_type' => $questionnaire->scoring_type,
            'answers' => $answersData,
            'submitted_at' => now()->toISOString(),
        ];

        $response->update([
            'responses' => $responses,
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Respuestas guardadas correctamente',
            'questionnaire_completed' => true
        ]);
    }

    /**
     * Complete campaign response
     */
    public function complete(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|exists:campaign_responses,session_id'
        ]);

        $response = CampaignResponse::where('session_id', $request->session_id)
            ->where('processing_status', 'pending')
            ->first();

        if (!$response) {
            return response()->json(['error' => 'Sesión no válida'], 404);
        }

        DB::transaction(function () use ($response) {
            $response->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Update campaign response count
            $response->campaign->increment('responses_count');
            
            // Process audio transcriptions asynchronously if there are audio files
            $this->dispatchAudioProcessingIfNeeded($response);
        });

        return response()->json([
            'success' => true,
            'message' => 'Evaluación completada exitosamente',
            'response_id' => $response->id
        ]);
    }

    /**
     * Get response status
     */
    public function status(string $sessionId): JsonResponse
    {
        $response = CampaignResponse::where('session_id', $sessionId)->first();

        if (!$response) {
            return response()->json(['error' => 'Sesión no encontrada'], 404);
        }

        return response()->json([
            'session_id' => $response->session_id,
            'status' => $response->processing_status,
            'progress' => $this->calculateProgress($response),
            'completed_questionnaires' => count($response->responses ?? []),
            'total_questionnaires' => $response->campaign->questionnaires->count(),
            'started_at' => $response->started_at,
            'completed_at' => $response->completed_at,
        ]);
    }

    private function calculateProgress(CampaignResponse $response): int
    {
        $totalQuestionnaires = $response->campaign->questionnaires->count();
        $completedQuestionnaires = count($response->responses ?? []);
        
        if ($totalQuestionnaires === 0) return 0;
        
        return round(($completedQuestionnaires / $totalQuestionnaires) * 100);
    }

    /**
     * Generate AI interpretation report for a campaign response
     */
    public function generateReport(int $responseId): JsonResponse
    {
        $response = CampaignResponse::with(['campaign.questionnaires'])->find($responseId);
        
        if (!$response) {
            return response()->json(['error' => 'Respuesta no encontrada'], 404);
        }
        
        if ($response->status !== 'completed') {
            return response()->json(['error' => 'La respuesta debe estar completa para generar el reporte'], 400);
        }
        
        try {
            $interpretation = $this->generateInterpretationReport($response);
            
            // Update the response with the interpretation
            $response->update([
                'interpretation' => $interpretation,
                'interpretation_generated_at' => now(),
            ]);
            
            return response()->json([
                'success' => true,
                'interpretation' => $interpretation,
                'message' => 'Reporte generado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Dispatch audio processing job if response contains audio files
     */
    private function dispatchAudioProcessingIfNeeded(CampaignResponse $response): void
    {
        $hasAudioFiles = false;
        
        foreach ($response->responses ?? [] as $questionnaireResponse) {
            if (isset($questionnaireResponse['answers']) && is_array($questionnaireResponse['answers'])) {
                foreach ($questionnaireResponse['answers'] as $answer) {
                    if (isset($answer['audio_file']) && !empty($answer['audio_file'])) {
                        $hasAudioFiles = true;
                        break 2;
                    }
                }
            }
        }
        
        if ($hasAudioFiles) {
            ProcessAudioTranscriptionsJob::dispatch($response->id);
        }
    }
    
    /**
     * Generate comprehensive interpretation report using AI and questionnaire processors
     */
    private function generateInterpretationReport(CampaignResponse $response): array
    {
        $processorFactory = app(QuestionnaireProcessorFactory::class);
        $aiService = app(AIInterpretationService::class);
        
        $interpretationResults = [];
        
        // Process each questionnaire response
        foreach ($response->responses as $questionnaireId => $questionnaireData) {
            $questionnaire = Questionnaire::find($questionnaireId);
            
            if (!$questionnaire) {
                continue;
            }
            
            // Get appropriate processor
            $processor = $processorFactory->getProcessor($questionnaire->scoring_type);
            
            if (!$processor) {
                continue;
            }
            
            // Prepare respondent data
            $respondentData = [
                'age' => $response->age,
                'gender' => $response->gender,
                'occupation' => $response->occupation,
                'name' => $response->respondent_name,
                'email' => $response->respondent_email,
            ];
            
            // Calculate scores and prepare data for AI interpretation
            $processedResults = $processor->calculateScores(
                $questionnaireData['answers'] ?? [],
                $respondentData
            );
            
            // Generate AI interpretation
            $aiInterpretation = $aiService->generateInterpretation(
                $response,
                $questionnaire,
                $processedResults
            );
            
            $interpretationResults[$questionnaireId] = [
                'questionnaire_name' => $questionnaire->name,
                'scoring_type' => $questionnaire->scoring_type,
                'processed_results' => $processedResults,
                'ai_interpretation' => $aiInterpretation,
            ];
        }
        
        return [
            'response_id' => $response->id,
            'campaign_name' => $response->campaign->name,
            'respondent_name' => $response->respondent_name,
            'generated_at' => now()->toISOString(),
            'questionnaire_interpretations' => $interpretationResults,
            'summary' => $this->generateGlobalSummary($interpretationResults),
        ];
    }
    
    /**
     * Generate a global summary from all questionnaire interpretations
     */
    private function generateGlobalSummary(array $interpretationResults): string
    {
        $summary = "Reporte integral de evaluación de competencias:\n\n";
        
        foreach ($interpretationResults as $result) {
            $summary .= "- " . $result['questionnaire_name'] . ": ";
            
            if ($result['ai_interpretation']['success']) {
                $summary .= "Interpretación completada exitosamente\n";
            } else {
                $summary .= "Error en interpretación: " . $result['ai_interpretation']['message'] . "\n";
            }
        }
        
        return $summary;
    }
}