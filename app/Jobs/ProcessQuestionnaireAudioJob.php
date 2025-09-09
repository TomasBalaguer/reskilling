<?php

namespace App\Jobs;

use App\Models\CampaignResponse;
use App\Services\AIInterpretationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessQuestionnaireAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $responseId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $responseId)
    {
        $this->responseId = $responseId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("=== INICIANDO PROCESAMIENTO DE AUDIO ===", [
                'response_id' => $this->responseId,
                'timestamp' => now()->toDateTimeString()
            ]);
            
            $response = CampaignResponse::find($this->responseId);
            
            if (!$response) {
                Log::error("❌ Response no encontrada", ['response_id' => $this->responseId]);
                return;
            }

            Log::info("✅ Response encontrada", [
                'response_id' => $this->responseId,
                'campaign_id' => $response->campaign_id,
                'questionnaire_id' => $response->questionnaire_id,
                'processing_status' => $response->processing_status
            ]);

            // Actualizar estado inicial
            $response->update([
                'processing_status' => 'processing',
                'processing_started_at' => now()
            ]);

            // Solo procesar si Google Gemini está habilitado
            $apiKey = config('services.google.api_key');
            if (!$apiKey) {
                Log::warning('🔑 Google Gemini API no configurada, saltando análisis de audio');
                $this->markAsCompleted($response);
                return;
            }
            Log::info('🔑 Google API Key configurada', ['key_length' => strlen($apiKey)]);

            $responses = $response->responses;
            
            // Verificar que responses sea válido
            if (!$responses || !is_array($responses)) {
                Log::warning("❌ Responses inválido", [
                    'response_id' => $this->responseId,
                    'responses_type' => gettype($responses),
                    'responses_data' => $responses
                ]);
                $this->markAsCompleted($response);
                return;
            }

            Log::info("📋 Responses válido", [
                'response_id' => $this->responseId,
                'questions_count' => count($responses),
                'questions' => array_keys($responses)
            ]);

            $updatedResponses = $responses;
            $hasUpdates = false;
            $transcriptions = [];
            $prosody = [];
            
            $aiService = app(AIInterpretationService::class);
            
            // Procesar cada respuesta con audio
            foreach ($responses as $questionId => $responseData) {
                Log::info("🔍 Procesando pregunta", [
                    'question_id' => $questionId,
                    'has_audio_path' => isset($responseData['audio_file_path']),
                    'audio_path' => $responseData['audio_file_path'] ?? null,
                    'has_transcription' => isset($responseData['transcription_text']) && !empty($responseData['transcription_text']),
                    'response_data_keys' => array_keys($responseData)
                ]);

                if (isset($responseData['audio_file_path']) && 
                    !empty($responseData['audio_file_path']) && 
                    empty($responseData['transcription_text'])) {
                    
                    try {
                        // Construir path completo al archivo
                        $audioPath = storage_path('app/' . $responseData['audio_file_path']);
                        
                        Log::info("🎵 Verificando archivo de audio", [
                            'question_id' => $questionId,
                            'relative_path' => $responseData['audio_file_path'],
                            'full_path' => $audioPath,
                            'file_exists' => file_exists($audioPath),
                            'file_size' => file_exists($audioPath) ? filesize($audioPath) : 0
                        ]);
                        
                        if (!file_exists($audioPath)) {
                            Log::warning("❌ Archivo de audio no encontrado", [
                                'question_id' => $questionId,
                                'path' => $audioPath
                            ]);
                            continue;
                        }
                        
                        // Obtener texto de la pregunta para contexto
                        $questionText = $this->getQuestionText($questionId);
                        
                        Log::info("🔊 Iniciando análisis de audio", [
                            'question_id' => $questionId,
                            'audio_path' => $audioPath,
                            'question_text_length' => strlen($questionText)
                        ]);

                        // Actualizar estado
                        $response->update(['processing_status' => 'transcribing']);
                        
                        // Analizar audio con Gemini
                        $analysis = $aiService->analyzeAudioWithGemini($audioPath, $questionText);
                        
                        Log::info("📝 Resultado del análisis", [
                            'question_id' => $questionId,
                            'analysis_keys' => array_keys($analysis),
                            'has_transcripcion' => isset($analysis['transcripcion']),
                            'has_metricas' => isset($analysis['metricas_prosodicas']),
                            'has_error' => isset($analysis['error'])
                        ]);
                        
                        // Actualizar respuesta con transcripción y análisis
                        if (isset($analysis['transcripcion']) && !empty($analysis['transcripcion'])) {
                            $updatedResponses[$questionId]['transcription_text'] = $analysis['transcripcion'];
                            $transcriptions[$questionId] = $analysis['transcripcion'];
                            $hasUpdates = true;
                            
                            Log::info("✅ Transcripción completada", [
                                'question_id' => $questionId,
                                'transcription_length' => strlen($analysis['transcripcion'])
                            ]);
                        } else {
                            Log::warning("⚠️ Sin transcripción en análisis", [
                                'question_id' => $questionId,
                                'analysis' => $analysis
                            ]);
                        }
                        
                        // Guardar análisis prosódico
                        if (isset($analysis['metricas_prosodicas'])) {
                            $prosody[$questionId] = $analysis['metricas_prosodicas'];
                            Log::info("📊 Métricas prosódicas guardadas", ['question_id' => $questionId]);
                        }
                        
                        // Guardar análisis completo
                        if (!isset($analysis['error'])) {
                            $updatedResponses[$questionId]['gemini_analysis'] = $analysis;
                            Log::info("💾 Análisis completo guardado", ['question_id' => $questionId]);
                        }
                        
                    } catch (\Exception $e) {
                        Log::error("❌ Error analizando audio", [
                            'question_id' => $questionId,
                            'error_message' => $e->getMessage(),
                            'error_trace' => $e->getTraceAsString()
                        ]);
                        continue;
                    }
                } else {
                    Log::info("⏩ Saltando pregunta", [
                        'question_id' => $questionId,
                        'reason' => 'Sin audio o ya transcrita'
                    ]);
                }
            }
            
            // Actualizar respuestas si hay cambios
            if ($hasUpdates) {
                $response->update([
                    'responses' => $updatedResponses,
                    'transcriptions' => $transcriptions,
                    'prosodic_analysis' => $prosody,
                    'processing_status' => 'transcribed'
                ]);
                
                Log::info("Transcripciones guardadas para response {$this->responseId}");
                
                // Generar interpretación con IA
                $this->generateAIInterpretation($response);
            } else {
                $this->markAsCompleted($response);
            }
            
            Log::info("Procesamiento de audio completado para response: {$this->responseId}");
            
        } catch (\Exception $e) {
            Log::error('Error en procesamiento de audio: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generar interpretación con IA basada en transcripciones
     */
    private function generateAIInterpretation(CampaignResponse $response): void
    {
        try {
            $response->update(['processing_status' => 'generating_ai_interpretation']);
            
            $aiService = app(AIInterpretationService::class);
            
            // Preparar datos del respondente
            $respondentData = [
                'age' => $response->respondent_age,
                'name' => $response->respondent_name,
                'email' => $response->respondent_email,
                'additional_info' => $response->respondent_additional_info
            ];
            
            // Preparar resultados para el análisis
            $questionnaireResults = [
                'questionnaire_id' => $response->questionnaire_id,
                'questionnaire_name' => $response->questionnaire?->name ?? 'Cuestionario',
                'scoring_type' => 'REFLECTIVE_QUESTIONS',
                'responses' => $response->responses,
                'transcriptions' => $response->transcriptions,
                'prosodic_analysis' => $response->prosodic_analysis
            ];
            
            // Generar interpretación
            $interpretation = $aiService->generateInterpretation($respondentData, $questionnaireResults);
            
            $response->update([
                'ai_analysis' => $interpretation,
                'processing_status' => 'completed',
                'processing_completed_at' => now()
            ]);
            
            Log::info("Interpretación IA completada para response {$this->responseId}");
            
        } catch (\Exception $e) {
            Log::error("Error generando interpretación IA: " . $e->getMessage());
            $response->update(['processing_status' => 'failed', 'processing_error' => $e->getMessage()]);
        }
    }

    /**
     * Marcar como completado sin análisis IA
     */
    private function markAsCompleted(CampaignResponse $response): void
    {
        $response->update([
            'processing_status' => 'completed',
            'processing_completed_at' => now()
        ]);
    }

    /**
     * Obtener texto de pregunta por ID
     */
    private function getQuestionText(string $questionId): string
    {
        // Esto debería venir de la base de datos o configuración
        $questions = [
            'q1' => 'Si pudieras mandarte un mensaje a vos mismo/a hace unos años, ¿qué te dirías sobre quién sos hoy y lo que fuiste aprendiendo de vos?',
            'q2' => 'Contame una vez en la que algo que te importaba no salió como esperabas. ¿Qué hiciste después? ¿Qué aprendiste de eso?',
            'q3' => 'Tuviste que decidir entre seguir con algo que querías o cambiar de camino por algo nuevo. ¿Qué hiciste? ¿Cómo lo pensaste?',
            'q4' => 'Contame alguna situacion, en un grupo de estudio, equipo o trabajo en donde algo no funcionaba (alguien no participaba, hubo malentendidos o tensión). ¿Cómo lo manejaste? ¿Qué dijiste o hiciste?',
            'q5' => 'Contame una vez que resolviste un problema de una manera poco común. ¿Qué hiciste diferente y por qué creés que funcionó?',
            'q6' => '¿En qué cosas te dan ganas de esforzarte hoy? ¿Qué te gustaría lograr a futuro (en la carrera, en tu vida o en lo que hacés)?',
            'q7' => 'Lee el siguiente relato y contesta las preguntas: "Después de meses trabajando en mi idea, finalmente presenté mi Proyecto frente a un grupo de profesores..." ¿Qué creés que sintió esa persona? ¿Qué harías en su lugar? ¿Qué le dirías si fuera parte de tu equipo o un amigo?'
        ];
        
        return $questions[$questionId] ?? 'Pregunta reflexiva';
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job ProcessQuestionnaireAudioJob falló para response {$this->responseId}: " . $exception->getMessage());
        
        // Marcar respuesta como fallida
        $response = CampaignResponse::find($this->responseId);
        if ($response) {
            $response->update([
                'processing_status' => 'failed',
                'processing_error' => $exception->getMessage()
            ]);
        }
    }
}