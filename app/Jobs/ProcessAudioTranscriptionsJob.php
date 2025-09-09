<?php

namespace App\Jobs;

use App\Models\CampaignResponse;
use App\Services\AIInterpretationService;
use App\Jobs\GenerateAIInterpretationJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessAudioTranscriptionsJob implements ShouldQueue
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
            Log::info("🎧 STEP 3: Starting audio transcription processing", [
                'step' => 3,
                'response_id' => $this->responseId,
                'job' => 'ProcessAudioTranscriptionsJob'
            ]);
            
            $response = CampaignResponse::find($this->responseId);
            
            if (!$response) {
                Log::error("Respuesta no encontrada: {$this->responseId}");
                return;
            }

            // Detectar si hay respuestas de audio (formato público o formato anterior)
            $hasAudioResponses = false;
            $isPublicFormat = false;
            
            // Validar y obtener respuestas
            $responses = $response->responses;
            
            // Log inicial para debug
            Log::info("🔍 INSPECCIONANDO DATOS DE RESPUESTA", [
                'response_id' => $this->responseId,
                'responses_type' => gettype($responses),
                'responses_is_string' => is_string($responses),
                'responses_is_array' => is_array($responses),
                'responses_preview' => is_string($responses) ? substr($responses, 0, 200) : 'not_string'
            ]);
            
            // Si responses es string, intentar decodificar JSON
            if (is_string($responses)) {
                $decoded = json_decode($responses, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $responses = $decoded;
                    Log::info("✅ JSON decodificado exitosamente");
                } else {
                    Log::error("❌ Error decodificando JSON de responses", [
                        'json_error' => json_last_error_msg(),
                        'raw_responses' => $responses
                    ]);
                    return;
                }
            }
            
            // Verificar que responses sea array después de procesamiento
            if (!is_array($responses)) {
                Log::error("❌ responses no es array después de procesamiento", [
                    'type' => gettype($responses),
                    'value' => $responses
                ]);
                return;
            }
            
            foreach ($responses as $key => $questionnaireResponse) {
                // Formato anterior: tiene scoring_type = REFLECTIVE_QUESTIONS
                if (isset($questionnaireResponse['scoring_type']) && $questionnaireResponse['scoring_type'] === 'REFLECTIVE_QUESTIONS') {
                    $hasAudioResponses = true;
                    break;
                }
                
                // Formato público: clave como "reflective_questions_q1" con type = "audio"
                if (is_string($key) && str_contains($key, 'reflective_questions_') && 
                    isset($questionnaireResponse['type']) && $questionnaireResponse['type'] === 'audio') {
                    $hasAudioResponses = true;
                    $isPublicFormat = true;
                    break;
                }
            }
            
            if (!$hasAudioResponses) {
                Log::info("No hay respuestas de audio para procesar, saltando procesamiento");
                return;
            }
            
            Log::info("Formato detectado: " . ($isPublicFormat ? "público" : "anterior"), [
                'response_id' => $this->responseId,
                'has_audio_files' => !empty($response->audio_files)
            ]);

            // Solo procesar si Vertex AI está habilitado
            if (!config('services.google.vertex_enabled', false)) {
                Log::info('Vertex AI no está habilitado, saltando transcripción de audio');
                return;
            }

            // Usar las respuestas ya validadas y procesadas
            $campaignResponses = $responses;
            $updatedResponses = $campaignResponses;
            $hasUpdates = false;
            
            $aiService = app(AIInterpretationService::class);
            
            // Procesar respuestas según el formato detectado
            if ($isPublicFormat) {
                // Formato público: respuestas directas con claves como "reflective_questions_q1"
                foreach ($campaignResponses as $responseKey => $responseData) {
                    if (!is_string($responseKey) || !str_contains($responseKey, 'reflective_questions_') || 
                        !isset($responseData['type']) || $responseData['type'] !== 'audio') {
                        continue;
                    }
                    
                    // Extraer el ID de pregunta (ej: "reflective_questions_q1" -> "q1")
                    $questionId = str_replace('reflective_questions_', '', $responseKey);
                    
                    // Buscar archivo de audio en audio_files
                    if (isset($response->audio_files[$responseKey])) {
                        $audioFileInfo = $response->audio_files[$responseKey];
                        
                        try {
                            // Verificar si tenemos audio local o S3
                            $audioPath = null;
                            $isS3Audio = false;
                            
                            Log::info("🔍 ANALIZANDO UBICACIÓN DEL AUDIO", [
                                'question_id' => $questionId,
                                'audio_file_info' => $audioFileInfo
                            ]);
                            
                            if (isset($audioFileInfo['path']) && !empty($audioFileInfo['path'])) {
                                // Audio local
                                $audioPath = storage_path('app/public/' . $audioFileInfo['path']);
                                Log::info("📁 AUDIO LOCAL DETECTADO", [
                                    'question_id' => $questionId,
                                    'path' => $audioPath,
                                    'exists' => file_exists($audioPath)
                                ]);
                            } elseif (isset($audioFileInfo['s3_path']) && isset($response->audio_s3_paths[$responseKey])) {
                                // Audio en S3 - descargar temporalmente
                                $s3Path = $response->audio_s3_paths[$responseKey];
                                $fileStorageService = app(\App\Services\FileStorageService::class);
                                $isS3Audio = true;
                                
                                Log::info("☁️ AUDIO EN S3 DETECTADO", [
                                    'question_id' => $questionId,
                                    's3_path' => $s3Path,
                                    'downloading' => true
                                ]);
                                
                                try {
                                    $audioPath = $fileStorageService->downloadAudioForProcessing($s3Path);
                                    Log::info("✅ Audio descargado desde S3", [
                                        'question_id' => $questionId,
                                        'temp_path' => $audioPath,
                                        'file_size' => filesize($audioPath)
                                    ]);
                                } catch (\Exception $s3Error) {
                                    Log::error("❌ ERROR DESCARGANDO DESDE S3", [
                                        'question_id' => $questionId,
                                        'error' => $s3Error->getMessage(),
                                        's3_path' => $s3Path
                                    ]);
                                    continue;
                                }
                            }
                            
                            if (!$audioPath || !file_exists($audioPath)) {
                                Log::warning("❌ ARCHIVO DE AUDIO NO ENCONTRADO", [
                                    'question_id' => $questionId,
                                    'path' => $audioPath,
                                    'audio_file_info' => $audioFileInfo,
                                    'is_s3' => $isS3Audio
                                ]);
                                continue;
                            }
                            
                            // Obtener texto de la pregunta para contexto
                            $questionText = $this->getQuestionTextForTranscription($questionId);
                            
                            Log::info("🚀 INICIANDO ANÁLISIS CON GEMINI", [
                                'response_id' => $this->responseId,
                                'question_id' => $questionId,
                                'question_text' => $questionText,
                                'audio_path' => $audioPath,
                                'file_size' => filesize($audioPath),
                                'is_s3_audio' => $isS3Audio
                            ]);
                            
                            // Analizar audio con Gemini, pasando el nombre del archivo S3 para detectar MIME correcto
                            $originalFileName = $isS3Audio ? $s3Path : '';
                            $analysis = $aiService->analyzeAudioWithGemini($audioPath, $questionText, $originalFileName);
                            
                            Log::info("📊 RESULTADO DEL ANÁLISIS GEMINI", [
                                'response_id' => $this->responseId,
                                'question_id' => $questionId,
                                'has_transcripcion' => isset($analysis['transcripcion']),
                                'transcripcion_length' => isset($analysis['transcripcion']) ? strlen($analysis['transcripcion']) : 0,
                                'has_error' => isset($analysis['error']),
                                'analysis_keys' => array_keys($analysis),
                                'has_prosodic' => isset($analysis['metricas_prosodicas']),
                                'has_emotional' => isset($analysis['analisis_emocional'])
                            ]);
                            
                            // Actualizar respuesta con transcripción
                            if (isset($analysis['transcripcion']) && !empty($analysis['transcripcion'])) {
                                $updatedResponses[$responseKey]['transcription_text'] = $analysis['transcripcion'];
                                $hasUpdates = true;
                                
                                Log::info("✅ TRANSCRIPCIÓN GUARDADA", [
                                    'response_id' => $this->responseId,
                                    'question_id' => $questionId,
                                    'transcription_preview' => substr($analysis['transcripcion'], 0, 100) . '...'
                                ]);
                            }
                            
                            // Guardar análisis completo en un campo separado
                            if (!isset($analysis['error'])) {
                                $updatedResponses[$responseKey]['gemini_analysis'] = $analysis;
                                Log::info("💾 ANÁLISIS COMPLETO GUARDADO", [
                                    'response_id' => $this->responseId,
                                    'question_id' => $questionId,
                                    'has_prosodic_metrics' => isset($analysis['metricas_prosodicas']),
                                    'has_emotional_analysis' => isset($analysis['analisis_emocional'])
                                ]);
                                
                                // Log específico de métricas prosódicas
                                if (isset($analysis['metricas_prosodicas'])) {
                                    Log::info("🎭 MÉTRICAS PROSÓDICAS", [
                                        'response_id' => $this->responseId,
                                        'question_id' => $questionId,
                                        'prosodic_data' => $analysis['metricas_prosodicas']
                                    ]);
                                }
                            } else {
                                Log::error("❌ ANÁLISIS CON ERROR", [
                                    'response_id' => $this->responseId,
                                    'question_id' => $questionId,
                                    'error' => $analysis['error']
                                ]);
                            }
                            
                            // Limpiar archivo temporal si es de S3
                            if ($isS3Audio && file_exists($audioPath)) {
                                unlink($audioPath);
                                Log::info("🗑️ Archivo temporal S3 eliminado", [
                                    'question_id' => $questionId,
                                    'path' => $audioPath
                                ]);
                            }
                            
                        } catch (\Exception $e) {
                            Log::error("💥 ERROR CRÍTICO PROCESANDO AUDIO", [
                                'response_id' => $this->responseId,
                                'question_id' => $questionId,
                                'error_message' => $e->getMessage(),
                                'error_file' => $e->getFile(),
                                'error_line' => $e->getLine(),
                                'audio_file_info' => $audioFileInfo,
                                'trace' => $e->getTraceAsString()
                            ]);
                            // Continúa con las demás preguntas aunque una falle
                        }
                    }
                }
            } else {
                // Formato anterior: cuestionarios con scoring_type
                foreach ($campaignResponses as $questionnaireId => $questionnaireResponse) {
                    if (!isset($questionnaireResponse['scoring_type']) || $questionnaireResponse['scoring_type'] !== 'REFLECTIVE_QUESTIONS') {
                        continue;
                    }
                    
                    if (!isset($questionnaireResponse['answers']) || !is_array($questionnaireResponse['answers'])) {
                        continue;
                    }
                    
                    // Procesar respuestas de audio en este cuestionario
                    foreach ($questionnaireResponse['answers'] as $questionId => $answerData) {
                        // Solo procesar si hay archivo de audio y no hay transcripción previa
                        if (isset($answerData['audio_file']) && 
                            !empty($answerData['audio_file']) && 
                            empty($answerData['transcription_text'])) {
                            
                            try {
                                // Construir path completo al archivo
                                $audioPath = storage_path('app/' . $answerData['audio_file']);
                                
                                if (!file_exists($audioPath)) {
                                    Log::warning("Archivo de audio no encontrado: {$audioPath}");
                                    continue;
                                }
                                
                                // Obtener texto de la pregunta para contexto
                                $questionText = $this->getQuestionTextForTranscription($questionId);
                                
                                Log::info("Transcribiendo audio para pregunta {$questionId} del cuestionario {$questionnaireId}: {$audioPath}");
                                
                                // Analizar audio con Gemini (archivo local, MIME se detecta del path)
                                $analysis = $aiService->analyzeAudioWithGemini($audioPath, $questionText);
                                
                                // Actualizar respuesta con transcripción
                                if (isset($analysis['transcripcion']) && !empty($analysis['transcripcion'])) {
                                    $updatedResponses[$questionnaireId]['answers'][$questionId]['transcription_text'] = $analysis['transcripcion'];
                                    $hasUpdates = true;
                                    
                                    Log::info("Transcripción completada para pregunta {$questionId}");
                                }
                                
                                // Guardar análisis completo en un campo separado
                                if (!isset($analysis['error'])) {
                                    $updatedResponses[$questionnaireId]['answers'][$questionId]['gemini_analysis'] = $analysis;
                                }
                                
                            } catch (\Exception $e) {
                                Log::error("Error transcribiendo audio para pregunta {$questionId}: " . $e->getMessage());
                                // Continúa con las demás preguntas aunque una falle
                            }
                        }
                    }
                }
            }
            
            // Guardar respuestas actualizadas si hay cambios
            if ($hasUpdates) {
                $response->update(['responses' => $updatedResponses]);
                Log::info("Transcripciones guardadas para respuesta {$this->responseId}");
            }
            
            Log::info("Procesamiento asíncrono completado para respuesta: {$this->responseId}");
            
            // Después de completar las transcripciones, disparar el análisis de IA
            Log::info("🧠 STEP 3.1: Dispatching AI interpretation job", [
                'step' => '3.1',
                'response_id' => $this->responseId,
                'next_job' => 'GenerateAIInterpretationJob',
                'queue' => 'ai-processing',
                'delay_seconds' => 5
            ]);
            
            GenerateAIInterpretationJob::dispatch($this->responseId)
                ->onQueue('ai-processing')
                ->delay(now()->addSeconds(5)); // Pequeño delay para asegurar que las transcripciones estén guardadas
            
        } catch (\Exception $e) {
            Log::error('Error general en procesamiento asíncrono de transcripciones: ' . $e->getMessage());
            throw $e; // Re-throw para que el job se marque como failed
        }
    }

    /**
     * Get question text by ID for transcription context
     */
    private function getQuestionTextForTranscription(string $questionId): string
    {
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
        Log::error("Job ProcessAudioTranscriptionsJob falló para respuesta {$this->responseId}: " . $exception->getMessage());
    }
}