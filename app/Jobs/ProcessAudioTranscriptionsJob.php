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
            
            // Log inicial DETALLADO para debug - incluyendo datos raw de la BD
            $rawAttributes = $response->getRawOriginal();
            Log::info("🔍 INSPECCIONANDO DATOS DE RESPUESTA AL CARGAR", [
                'response_id' => $this->responseId,
                'step' => 'initial_data_load',
                'cast_responses_type' => gettype($responses),
                'cast_responses_is_string' => is_string($responses),
                'cast_responses_is_array' => is_array($responses),
                'cast_responses_preview' => is_string($responses) ? substr($responses, 0, 200) : 
                    (is_array($responses) ? 'array_with_' . count($responses) . '_items' : gettype($responses)),
                'raw_responses_available' => isset($rawAttributes['responses']),
                'raw_responses_type' => isset($rawAttributes['responses']) ? gettype($rawAttributes['responses']) : 'not_available',
                'raw_responses_is_string' => isset($rawAttributes['responses']) ? is_string($rawAttributes['responses']) : false,
                'raw_responses_preview' => isset($rawAttributes['responses']) && is_string($rawAttributes['responses']) ? 
                    substr($rawAttributes['responses'], 0, 200) . '...' : 'not_string_or_not_available',
                'model_cast_config' => [
                    'responses_cast' => 'array',
                    'cast_working' => is_array($responses),
                    'cast_should_convert_json_to_array' => true
                ],
                'data_consistency_check' => [
                    'raw_is_json_string' => isset($rawAttributes['responses']) && is_string($rawAttributes['responses']) && 
                        json_decode($rawAttributes['responses']) !== null,
                    'cast_is_array' => is_array($responses),
                    'conversion_working' => isset($rawAttributes['responses']) && is_string($rawAttributes['responses']) && 
                        is_array($responses)
                ]
            ]);
            
            // Si responses es string, intentar decodificar JSON
            if (is_string($responses)) {
                Log::warning("⚠️ RESPONSES ES STRING - CAST NO FUNCIONÓ", [
                    'response_id' => $this->responseId,
                    'step' => 'manual_json_decode_needed',
                    'responses_length' => strlen($responses),
                    'responses_first_char' => substr($responses, 0, 1),
                    'responses_last_char' => substr($responses, -1),
                    'looks_like_json' => (substr($responses, 0, 1) === '{' || substr($responses, 0, 1) === '['),
                    'cast_config_issue' => 'Laravel array cast should have auto-converted JSON string to array',
                    'possible_causes' => [
                        'database_field_not_json_type',
                        'cast_not_properly_configured',
                        'data_saved_incorrectly',
                        'model_cache_issue'
                    ]
                ]);
                
                $decoded = json_decode($responses, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $responses = $decoded;
                    Log::info("✅ JSON DECODIFICADO EXITOSAMENTE (FALLBACK)", [
                        'response_id' => $this->responseId,
                        'step' => 'manual_json_decode_success',
                        'decoded_type' => gettype($decoded),
                        'decoded_count' => count($decoded),
                        'first_key' => array_key_first($decoded),
                        'note' => 'This should not be necessary if Laravel casting is working properly'
                    ]);
                } else {
                    Log::error("❌ ERROR DECODIFICANDO JSON DE RESPONSES", [
                        'response_id' => $this->responseId,
                        'step' => 'manual_json_decode_failed',
                        'json_error' => json_last_error_msg(),
                        'json_error_code' => json_last_error(),
                        'raw_responses_length' => strlen($responses),
                        'raw_responses_preview' => substr($responses, 0, 500),
                        'raw_responses_full' => $responses
                    ]);
                    return;
                }
            } else {
                Log::info("✅ RESPONSES YA ES ARRAY - CAST FUNCIONÓ CORRECTAMENTE", [
                    'response_id' => $this->responseId,
                    'step' => 'cast_working_properly',
                    'responses_type' => gettype($responses),
                    'responses_count' => count($responses),
                    'note' => 'Laravel array cast converted JSON string to array automatically'
                ]);
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
                                
                                Log::info("✅ TRANSCRIPCIÓN GUARDADA EN MEMORIA", [
                                    'response_id' => $this->responseId,
                                    'question_id' => $questionId,
                                    'response_key' => $responseKey,
                                    'has_updates_now' => $hasUpdates,
                                    'transcription_preview' => substr($analysis['transcripcion'], 0, 100) . '...'
                                ]);
                            } else {
                                Log::warning("⚠️ NO HAY TRANSCRIPCIÓN EN EL ANÁLISIS", [
                                    'response_id' => $this->responseId,
                                    'question_id' => $questionId,
                                    'analysis_keys' => array_keys($analysis)
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
                                    'error' => $analysis['error'],
                                    'error_type' => $analysis['error_type'] ?? 'unknown'
                                ]);
                                
                                // Guardar la transcripción de error para que el usuario sepa qué pasó
                                if (isset($analysis['transcripcion'])) {
                                    $updatedResponses[$responseKey]['transcription_text'] = $analysis['transcripcion'];
                                    $updatedResponses[$responseKey]['transcription_error'] = $analysis['error'];
                                    $hasUpdates = true;
                                    
                                    Log::info("⚠️ TRANSCRIPCIÓN DE ERROR GUARDADA", [
                                        'response_id' => $this->responseId,
                                        'question_id' => $questionId,
                                        'error_type' => $analysis['error_type'] ?? 'unknown',
                                        'transcription' => $analysis['transcripcion']
                                    ]);
                                }
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
            
            Log::info("📊 ESTADO ANTES DE GUARDAR", [
                'response_id' => $this->responseId,
                'has_updates' => $hasUpdates,
                'updated_responses_count' => count($updatedResponses),
                'is_public_format' => $isPublicFormat
            ]);
            
            // Guardar respuestas actualizadas si hay cambios
            if ($hasUpdates) {
                // Extraer transcripciones y análisis prosódico para guardar en campos separados
                $transcriptions = [];
                $prosodicAnalysis = [];
                
                foreach ($updatedResponses as $responseKey => $responseData) {
                    // Extraer transcripciones
                    if (isset($responseData['transcription_text'])) {
                        $transcriptions[$responseKey] = $responseData['transcription_text'];
                    }
                    
                    // Extraer análisis prosódico (métricas de Gemini)
                    if (isset($responseData['gemini_analysis'])) {
                        $prosodicAnalysis[$responseKey] = $responseData['gemini_analysis'];
                    }
                }
                
                // Log detallado del estado ANTES de preparar el update
                Log::info("📝 DATOS PREPARADOS PARA GUARDAR", [
                    'response_id' => $this->responseId,
                    'step' => 'pre_update_preparation',
                    'original_responses_type' => gettype($response->responses),
                    'original_responses_is_string' => is_string($response->responses),
                    'original_responses_is_array' => is_array($response->responses),
                    'updated_responses_type' => gettype($updatedResponses),
                    'updated_responses_is_array' => is_array($updatedResponses),
                    'updated_responses_count' => count($updatedResponses),
                    'transcriptions_count' => count($transcriptions),
                    'prosodic_count' => count($prosodicAnalysis),
                    'sample_response_structure' => !empty($updatedResponses) ? [
                        'first_key' => array_key_first($updatedResponses),
                        'first_value_keys' => is_array(reset($updatedResponses)) ? array_keys(reset($updatedResponses)) : 'not_array'
                    ] : 'empty'
                ]);
                
                // Log información del entorno y configuración
                Log::info("🔧 INFORMACIÓN DEL ENTORNO Y CONFIGURACIÓN", [
                    'response_id' => $this->responseId,
                    'step' => 'environment_info',
                    'laravel_version' => app()->version(),
                    'database_driver' => config('database.default'),
                    'database_config' => config('database.connections.' . config('database.default')),
                    'model_info' => [
                        'table' => $response->getTable(),
                        'connection' => $response->getConnectionName(),
                        'casts' => $response->getCasts(),
                        'responses_cast' => $response->getCasts()['responses'] ?? 'not_set'
                    ],
                    'php_version' => PHP_VERSION,
                    'json_functions' => [
                        'json_encode_available' => function_exists('json_encode'),
                        'json_decode_available' => function_exists('json_decode')
                    ]
                ]);
                
                // Actualizar todos los campos relevantes
                $updateData = [
                    'responses' => $updatedResponses,
                    'transcriptions' => !empty($transcriptions) ? $transcriptions : null,
                    'prosodic_analysis' => !empty($prosodicAnalysis) ? $prosodicAnalysis : null
                ];
                
                // Log detallado de lo que se va a guardar
                Log::info("🔄 DATOS A ACTUALIZAR EN BD", [
                    'response_id' => $this->responseId,
                    'step' => 'pre_database_save',
                    'update_data_keys' => array_keys($updateData),
                    'responses_field_type' => gettype($updateData['responses']),
                    'responses_field_is_array' => is_array($updateData['responses']),
                    'responses_field_json_preview' => substr(json_encode($updateData['responses']), 0, 200),
                    'responses_field_serialized_length' => strlen(json_encode($updateData['responses'])),
                    'transcriptions_is_null' => is_null($updateData['transcriptions']),
                    'prosodic_is_null' => is_null($updateData['prosodic_analysis']),
                    'model_casts' => [
                        'responses' => 'array (should auto-encode)',
                        'transcriptions' => 'array (should auto-encode)',
                        'prosodic_analysis' => 'array (should auto-encode)'
                    ]
                ]);
                
                // Log del estado del modelo ANTES del update
                Log::info("📊 ESTADO DEL MODELO ANTES DEL UPDATE", [
                    'response_id' => $this->responseId,
                    'step' => 'model_state_before_save',
                    'model_responses_type' => gettype($response->responses),
                    'model_responses_is_string' => is_string($response->responses),
                    'model_responses_is_array' => is_array($response->responses),
                    'model_responses_preview' => is_string($response->responses) ? 
                        substr($response->responses, 0, 200) : 
                        (is_array($response->responses) ? 'array_with_' . count($response->responses) . '_items' : 'other'),
                    'model_dirty' => $response->getDirty(),
                    'model_original' => array_keys($response->getOriginal()),
                    'model_exists' => $response->exists,
                    'model_was_recently_created' => $response->wasRecentlyCreated
                ]);
                
                try {
                    // Ejecutar el update
                    $updateResult = $response->update($updateData);
                    
                    // Log inmediato después del update (antes del refresh)
                    Log::info("💾 RESULTADO INMEDIATO DEL UPDATE", [
                        'response_id' => $this->responseId,
                        'step' => 'post_update_immediate',
                        'update_result' => $updateResult,
                        'model_responses_type_after_update' => gettype($response->responses),
                        'model_responses_is_string_after' => is_string($response->responses),
                        'model_responses_is_array_after' => is_array($response->responses),
                        'model_was_changed' => $response->wasChanged(),
                        'model_changes' => $response->getChanges()
                    ]);
                    
                    // Verificar que realmente se guardó
                    $response->refresh();
                    
                    // Log después del refresh para ver cómo Laravel cargó los datos desde BD
                    Log::info("🔄 ESTADO DESPUÉS DEL REFRESH DESDE BD", [
                        'response_id' => $this->responseId,
                        'step' => 'post_refresh_from_database',
                        'refreshed_responses_type' => gettype($response->responses),
                        'refreshed_responses_is_string' => is_string($response->responses),
                        'refreshed_responses_is_array' => is_array($response->responses),
                        'refreshed_responses_preview' => is_string($response->responses) ? 
                            substr($response->responses, 0, 200) . '...' : 
                            (is_array($response->responses) ? 'array_with_' . count($response->responses) . '_items' : gettype($response->responses)),
                        'attribute_casting_working' => [
                            'responses_cast' => 'array',
                            'actual_type' => gettype($response->responses),
                            'cast_working' => is_array($response->responses)
                        ]
                    ]);
                    
                    // Verificación adicional: acceso directo a atributos raw
                    $rawAttributes = $response->getRawOriginal();
                    if (isset($rawAttributes['responses'])) {
                        Log::info("🔍 RAW ATTRIBUTES DESDE BD", [
                            'response_id' => $this->responseId,
                            'step' => 'raw_database_inspection',
                            'raw_responses_type' => gettype($rawAttributes['responses']),
                            'raw_responses_is_string' => is_string($rawAttributes['responses']),
                            'raw_responses_preview' => is_string($rawAttributes['responses']) ? 
                                substr($rawAttributes['responses'], 0, 200) . '...' : 
                                gettype($rawAttributes['responses']),
                            'raw_vs_cast_comparison' => [
                                'raw_type' => gettype($rawAttributes['responses']),
                                'cast_type' => gettype($response->responses),
                                'raw_is_json' => is_string($rawAttributes['responses']) && 
                                    json_decode($rawAttributes['responses']) !== null,
                                'cast_is_array' => is_array($response->responses)
                            ]
                        ]);
                    }
                    
                    Log::info("✅ GUARDADO COMPLETO Y VERIFICADO", [
                        'response_id' => $this->responseId,
                        'step' => 'save_verification_complete',
                        'update_result' => $updateResult,
                        'transcriptions_count' => count($transcriptions),
                        'prosodic_count' => count($prosodicAnalysis),
                        'fields_updated' => array_keys($updateData),
                        'saved_transcriptions' => !empty($response->transcriptions),
                        'saved_prosodic' => !empty($response->prosodic_analysis),
                        'final_responses_format' => [
                            'type' => gettype($response->responses),
                            'is_array' => is_array($response->responses),
                            'count' => is_array($response->responses) ? count($response->responses) : 'not_array'
                        ]
                    ]);
                } catch (\Exception $saveError) {
                    Log::error("❌ ERROR AL GUARDAR EN BD", [
                        'response_id' => $this->responseId,
                        'step' => 'database_save_error',
                        'error' => $saveError->getMessage(),
                        'error_code' => $saveError->getCode(),
                        'error_file' => $saveError->getFile(),
                        'error_line' => $saveError->getLine(),
                        'update_data_types' => array_map('gettype', $updateData),
                        'trace' => $saveError->getTraceAsString()
                    ]);
                    throw $saveError;
                }
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