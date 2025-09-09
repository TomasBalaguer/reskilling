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
            Log::info("Iniciando procesamiento asíncrono de transcripciones para respuesta: {$this->responseId}");
            
            $response = CampaignResponse::find($this->responseId);
            
            if (!$response) {
                Log::error("Respuesta no encontrada: {$this->responseId}");
                return;
            }

            // Detectar si hay respuestas de audio (formato público o formato anterior)
            $hasAudioResponses = false;
            $isPublicFormat = false;
            
            foreach ($response->responses ?? [] as $key => $questionnaireResponse) {
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

            $campaignResponses = $response->responses;
            
            // Verificar que responses no sea null
            if (!$campaignResponses || !is_array($campaignResponses)) {
                Log::warning("Responses es null o no es array para respuesta {$this->responseId}");
                return;
            }

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
                            // Construir path completo al archivo
                            $audioPath = storage_path('app/public/' . $audioFileInfo['path']);
                            
                            if (!file_exists($audioPath)) {
                                Log::warning("Archivo de audio no encontrado: {$audioPath}");
                                continue;
                            }
                            
                            // Obtener texto de la pregunta para contexto
                            $questionText = $this->getQuestionTextForTranscription($questionId);
                            
                            Log::info("Transcribiendo audio para pregunta {$questionId}: {$audioPath}");
                            
                            // Analizar audio con Gemini
                            $analysis = $aiService->analyzeAudioWithGemini($audioPath, $questionText);
                            
                            // Actualizar respuesta con transcripción
                            if (isset($analysis['transcripcion']) && !empty($analysis['transcripcion'])) {
                                $updatedResponses[$responseKey]['transcription_text'] = $analysis['transcripcion'];
                                $hasUpdates = true;
                                
                                Log::info("Transcripción completada para pregunta {$questionId}");
                            }
                            
                            // Guardar análisis completo en un campo separado
                            if (!isset($analysis['error'])) {
                                $updatedResponses[$responseKey]['gemini_analysis'] = $analysis;
                            }
                            
                        } catch (\Exception $e) {
                            Log::error("Error transcribiendo audio para pregunta {$questionId}: " . $e->getMessage());
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
                                
                                // Analizar audio con Gemini
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
            Log::info("Disparando trabajo de análisis de IA para respuesta: {$this->responseId}");
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