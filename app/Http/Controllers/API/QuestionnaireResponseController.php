<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignResponse;
use App\Models\CampaignInvitation;
use App\Models\Questionnaire;
use App\Events\QuestionnaireResponseSubmitted;
use App\Http\Resources\CampaignResponseResource;
use App\Jobs\ProcessQuestionnaireAudioJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuestionnaireResponseController extends Controller
{
    /**
     * Submit responses for a questionnaire in a campaign (public access)
     */
    public function submitByCampaignCode(Request $request, string $campaignCode, int $questionnaireId): JsonResponse
    {
        try {
            // Validate campaign
            $campaign = Campaign::where('code', $campaignCode)
                ->where('status', 'active')
                ->where('active_from', '<=', now())
                ->where('active_until', '>=', now())
                ->first();

            if (!$campaign) {
                return response()->json(['error' => 'Campaña no encontrada o no activa'], 404);
            }

            // Check capacity
            if ($campaign->responses_count >= $campaign->max_responses) {
                return response()->json([
                    'error' => 'Esta campaña ha alcanzado el máximo de respuestas permitidas'
                ], 423);
            }

            // Validate questionnaire belongs to campaign
            $questionnaire = $campaign->questionnaires()->find($questionnaireId);
            if (!$questionnaire) {
                return response()->json(['error' => 'Cuestionario no encontrado en esta campaña'], 404);
            }

            return $this->processQuestionnaireResponse($request, $campaign, $questionnaire, 'public_link');

        } catch (\Exception $e) {
            Log::error('Error submitting campaign response', [
                'campaign_code' => $campaignCode,
                'questionnaire_id' => $questionnaireId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error procesando la respuesta',
                'message' => 'Por favor intenta de nuevo'
            ], 500);
        }
    }

    /**
     * Submit responses for a questionnaire via invitation token
     */
    public function submitByInvitation(Request $request, string $token, int $questionnaireId): JsonResponse
    {
        try {
            // Validate invitation
            $invitation = CampaignInvitation::with(['campaign'])
                ->where('token', $token)
                ->notExpired()
                ->first();

            if (!$invitation) {
                return response()->json(['error' => 'Invitación no encontrada o expirada'], 404);
            }

            $campaign = $invitation->campaign;

            // Validate questionnaire belongs to campaign
            $questionnaire = $campaign->questionnaires()->find($questionnaireId);
            if (!$questionnaire) {
                return response()->json(['error' => 'Cuestionario no encontrado en esta campaña'], 404);
            }

            return $this->processQuestionnaireResponse($request, $campaign, $questionnaire, 'email_invitation', $invitation);

        } catch (\Exception $e) {
            Log::error('Error submitting invitation response', [
                'token' => $token,
                'questionnaire_id' => $questionnaireId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error procesando la respuesta',
                'message' => 'Por favor intenta de nuevo'
            ], 500);
        }
    }

    /**
     * Get response status and progress
     */
    public function getResponseStatus(int $responseId): JsonResponse
    {
        try {
            $response = CampaignResponse::find($responseId);
            
            if (!$response) {
                return response()->json(['error' => 'Respuesta no encontrada'], 404);
            }

            $progress = $this->getProcessingProgress($response->processing_status);
            $message = $this->getProcessingMessage($response->processing_status);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'response_id' => $response->id,
                    'status' => $response->processing_status,
                    'progress' => $progress,
                    'message' => $message,
                    'has_audio_files' => $response->hasAudioResponses,
                    'processing_started_at' => $response->processing_started_at,
                    'processing_completed_at' => $response->processing_completed_at,
                    'created_at' => $response->created_at,
                    'estimated_completion' => $response->processing_status === 'completed' ? null : $this->getEstimatedCompletion($response),
                    'analysis_available' => in_array($response->processing_status, ['completed', 'report_completed']),
                    'error' => $response->processing_status === 'failed' ? $response->processing_error : null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting response status', [
                'response_id' => $responseId,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error obteniendo estado de respuesta'], 500);
        }
    }

    /**
     * Get detailed analysis results (when processing is complete)
     */
    public function getResponseAnalysis(int $responseId): JsonResponse
    {
        try {
            $response = CampaignResponse::with(['questionnaire', 'campaign.company'])
                ->find($responseId);
            
            if (!$response) {
                return response()->json(['error' => 'Respuesta no encontrada'], 404);
            }

            // Check if analysis is complete
            if (!in_array($response->processing_status, ['completed', 'report_completed'])) {
                return response()->json([
                    'error' => 'El análisis aún no está completo',
                    'current_status' => $response->processing_status,
                    'progress' => $this->getProcessingProgress($response->processing_status)
                ], 202); // 202 Accepted - processing
            }

            return response()->json([
                'success' => true,
                'data' => new \App\Http\Resources\QuestionnaireAnalysisResource($response)
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting response analysis', [
                'response_id' => $responseId,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Error obteniendo análisis de respuesta'], 500);
        }
    }

    /**
     * Process questionnaire response (shared logic)
     */
    private function processQuestionnaireResponse(
        Request $request, 
        Campaign $campaign, 
        Questionnaire $questionnaire, 
        string $accessType,
        ?CampaignInvitation $invitation = null
    ): JsonResponse {
        
        // Validate request data
        $validatedData = $this->validateQuestionnaireResponses($request, $questionnaire);

        // Create response record
        $responseData = [
            'campaign_id' => $campaign->id,
            'questionnaire_id' => $questionnaire->id,
            'session_id' => Str::uuid()->toString(),
            'respondent_name' => $validatedData['respondent']['name'] ?? null,
            'respondent_email' => $validatedData['respondent']['email'] ?? null,
            'respondent_type' => $accessType === 'email_invitation' ? 'invited_candidate' : 'candidate',
            'respondent_age' => $validatedData['respondent']['age'] ?? null,
            'respondent_additional_info' => $validatedData['respondent']['additional_info'] ?? [],
            'responses' => $validatedData['responses'],
            'raw_responses' => $validatedData['responses'],
            'processing_status' => 'pending',
            'access_type' => $accessType,
            'access_token' => $accessType === 'email_invitation' ? $invitation?->token : $campaign->code
        ];

        $response = CampaignResponse::create($responseData);

        // Link to invitation if applicable
        if ($invitation) {
            $invitation->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }

        // Process responses using strategy pattern
        try {
            $processedResponses = $questionnaire->processResponses($validatedData['responses']);
            $response->update(['processed_responses' => $processedResponses]);

            // Check if response has audio files to process
            $hasAudioFiles = $this->responseHasAudioFiles($validatedData['responses']);
            
            if ($hasAudioFiles) {
                // Dispatch audio processing job
                ProcessQuestionnaireAudioJob::dispatch($response->id);
                Log::info('Audio processing job dispatched for response', ['response_id' => $response->id]);
            } else {
                // Mark as completed if no audio processing needed
                $response->update([
                    'processing_status' => 'completed',
                    'processing_completed_at' => now()
                ]);
            }

            // Dispatch processing event
            QuestionnaireResponseSubmitted::dispatch(
                $response, 
                $processedResponses, 
                $questionnaire->requiresAIProcessing()
            );

            Log::info('Questionnaire response submitted successfully', [
                'response_id' => $response->id,
                'campaign_id' => $campaign->id,
                'questionnaire_type' => $questionnaire->questionnaire_type?->value,
                'access_type' => $accessType,
                'has_audio_files' => $hasAudioFiles
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing questionnaire response', [
                'response_id' => $response->id,
                'error' => $e->getMessage()
            ]);

            $response->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'response_id' => $response->id,
                'status' => 'submitted',
                'processing_status' => $response->processing_status,
                'message' => 'Respuesta enviada correctamente. El análisis comenzará en breve.',
                'estimated_completion' => now()->addMinutes($this->getEstimatedProcessingTime($questionnaire))
            ]
        ], 201);
    }

    /**
     * Validate questionnaire responses
     */
    private function validateQuestionnaireResponses(Request $request, Questionnaire $questionnaire): array
    {
        $request->validate([
            'respondent' => 'required|array',
            'respondent.name' => 'required|string|max:255',
            'respondent.email' => 'required|email|max:255',
            'respondent.age' => 'nullable|integer|min:16|max:100',
            'respondent.additional_info' => 'nullable|array',
            'responses' => 'required|array|min:1',
            'audio_files' => 'sometimes|array',
            'audio_files.*' => 'file|mimes:mp3,wav,m4a,aac,webm,mp4|max:51200', // 50MB max
        ]);

        // Process audio files if present
        $responses = $request->input('responses');
        if ($request->hasFile('audio_files')) {
            $responses = $this->processAudioFiles($request, $responses);
        }

        // Validate responses using questionnaire strategy
        $validationErrors = $questionnaire->validateResponses($responses);

        if (!empty($validationErrors)) {
            return response()->json([
                'error' => 'Errores de validación en las respuestas',
                'validation_errors' => $validationErrors
            ], 422);
        }

        return [
            'respondent' => $request->input('respondent'),
            'responses' => $responses
        ];
    }

    /**
     * Process uploaded audio files and add paths to responses
     */
    private function processAudioFiles(Request $request, array $responses): array
    {
        foreach ($request->file('audio_files') as $questionId => $audioFile) {
            if ($audioFile && $audioFile->isValid()) {
                // Create unique filename
                $timestamp = time();
                $extension = $audioFile->getClientOriginalExtension();
                $filename = "private/questionnaire_responses/{$questionId}_{$timestamp}.{$extension}";
                
                // Ensure directory exists
                $fullPath = storage_path('app/' . dirname($filename));
                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }
                
                // Store file directly to avoid double private folder
                $audioFile->move($fullPath, basename($filename));
                $path = $filename;
                
                // Add audio path to responses
                if (isset($responses[$questionId])) {
                    $responses[$questionId]['audio_file_path'] = $path;
                    $responses[$questionId]['audio_duration'] = $responses[$questionId]['audio_duration'] ?? null;
                } else {
                    $responses[$questionId] = [
                        'audio_file_path' => $path,
                        'audio_duration' => null
                    ];
                }
            }
        }

        return $responses;
    }

    /**
     * Check if responses contain audio files
     */
    private function responseHasAudioFiles(array $responses): bool
    {
        foreach ($responses as $questionId => $responseData) {
            if (isset($responseData['audio_file_path']) && !empty($responseData['audio_file_path'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get processing progress percentage
     */
    private function getProcessingProgress(string $status): float
    {
        return match($status) {
            'pending' => 5,
            'processing' => 15,
            'transcribing' => 30,
            'transcribed' => 50,
            'analyzing_text' => 60,
            'generating_ai_interpretation' => 75,
            'analyzed' => 85,
            'calculating_scores' => 95,
            'completed' => 100,
            'report_completed' => 100,
            'failed' => 0,
            default => 0
        };
    }

    /**
     * Get processing status message
     */
    private function getProcessingMessage(string $status): string
    {
        return match($status) {
            'pending' => 'Respuesta recibida, iniciando procesamiento...',
            'processing' => 'Procesando respuestas...',
            'transcribing' => 'Transcribiendo archivos de audio...',
            'transcribed' => 'Transcripción completada, analizando contenido...',
            'analyzing_text' => 'Analizando contenido textual...',
            'generating_ai_interpretation' => 'Generando interpretación con IA...',
            'analyzed' => 'Análisis completado, finalizando...',
            'calculating_scores' => 'Calculando puntuaciones finales...',
            'completed' => '¡Análisis completado! Los resultados están disponibles.',
            'report_completed' => '¡Reporte final completado!',
            'failed' => 'Error en el procesamiento. Por favor contacta soporte.',
            default => 'Estado desconocido'
        };
    }

    /**
     * Get estimated processing time in minutes
     */
    private function getEstimatedProcessingTime(Questionnaire $questionnaire): int
    {
        return match($questionnaire->questionnaire_type?->value) {
            'REFLECTIVE_QUESTIONS' => 5, // Audio processing takes longer
            'TEXT_RESPONSE', 'PERSONALITY_ASSESSMENT' => 3, // AI text analysis
            'MIXED_FORMAT' => 4, // Variable processing
            default => 2 // Statistical processing only
        };
    }

    /**
     * Get estimated completion time based on current status
     */
    private function getEstimatedCompletion(CampaignResponse $response): ?\DateTime
    {
        if ($response->processing_status === 'completed') {
            return null;
        }

        // Estimate remaining time based on status
        $remainingMinutes = match($response->processing_status) {
            'pending' => 5,
            'processing' => 4,
            'transcribing' => 3,
            'transcribed' => 2,
            'analyzing_text' => 2,
            'generating_ai_interpretation' => 1,
            default => 3
        };

        return now()->addMinutes($remainingMinutes);
    }
}