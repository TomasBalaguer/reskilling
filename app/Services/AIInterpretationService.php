<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\CampaignResponse;
use App\Models\Questionnaire;
use Carbon\Carbon;

class AIInterpretationService
{
    protected $apiKey;
    protected $model;
    protected $project;
    protected $location;

    public function __construct()
    {
        // Configuración desde variables de entorno
        $this->apiKey = config('services.google.api_key');
        $modelName = config('services.google.model', 'gemini-1.5-flash');
        // Ensure model name has 'models/' prefix for API compatibility
        $this->model = str_starts_with($modelName, 'models/') ? $modelName : 'models/' . $modelName;
        $this->project = config('services.google.project_id');
        $this->location = config('services.google.location', 'us-central1');
    }

    /**
     * Genera una interpretación avanzada basada en IA para los resultados del cuestionario
     *
     * @param array $respondentData Datos del respondente
     * @param array $questionnaireResults Resultados del cuestionario
     * @return array Interpretación generada por IA
     */
    public function generateInterpretation(
        array $respondentData,
        array $questionnaireResults
    ): array
    {
        try {
            // Verificar si el servicio está configurado
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'message' => 'El servicio de IA no está configurado correctamente. Falta API key.',
                    'interpretation' => null
                ];
            }

            // Construir el prompt para la IA
            $prompt = $this->buildPrompt($respondentData, $questionnaireResults);
            
            // Llamar a Gemini API
            $aiResponse = $this->callGeminiAI($prompt);

            return [
                'success' => true,
                'interpretation' => $aiResponse,
                'prompt' => $prompt // Útil para debugging
            ];

        } catch (\Exception $e) {
            Log::error('Error al generar interpretación de IA: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al generar la interpretación: ' . $e->getMessage(),
                'interpretation' => null
            ];
        }
    }


    /**
     * Construye el prompt para la IA con la información del respondente y los resultados
     */
    private function buildPrompt(array $respondentData, array $questionnaireResults): string
    {
        $prompt = "# Análisis psicológico basado en cuestionarios\n\n";
        
        // Añadir información del candidato
        if (!empty($respondentData)) {
            $prompt .= "## Información del candidato\n";
            $prompt .= "- Nombre: " . ($respondentData['name'] ?? 'No disponible') . "\n";
            $prompt .= "- Edad: " . ($respondentData['age'] ?? 'No disponible') . "\n";
            $prompt .= "- Género: " . ($respondentData['gender'] ?? 'No disponible') . "\n";
            $prompt .= "- Ocupación: " . ($respondentData['occupation'] ?? 'No disponible') . "\n\n";
        }
        
        // TODO: Recuperar información del cuestionario desde questionnaireResults metadata
        $prompt .= "## Resultados del cuestionario\n\n";

        // Usar estructura genérica para todos los tipos por ahora
        $prompt .= $this->buildGenericPromptSection($questionnaireResults);

        return $prompt;
    }
    
    /**
     * Construye la sección genérica del prompt para cualquier cuestionario
     */
    private function buildGenericPromptSection(array $questionnaireResults): string
    {
        $promptSection = "";
        
        // Añadir respuestas
        if (!empty($questionnaireResults)) {
            $promptSection .= "### Respuestas del cuestionario:\n";
            
            foreach ($questionnaireResults as $questionId => $answer) {
                $promptSection .= "- **Pregunta {$questionId}**: " . 
                    (is_array($answer) ? json_encode($answer) : $answer) . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Instrucciones genéricas
        $promptSection .= "## Instrucciones para el análisis\n\n";
        $promptSection .= "Por favor, proporciona un análisis detallado de las respuestas del cuestionario, ";
        $promptSection .= "incluyendo patrones identificados, fortalezas y áreas de mejora.\n\n";
        
        return $promptSection;
    }



    /**
     * Llama a la API REST de Gemini para generar la interpretación
     */
    private function callGeminiAI(string $prompt): string
    {
        try {
            // URL de la API REST de Gemini - usando v1beta para Gemini 1.5 Flash
            $url = "https://generativelanguage.googleapis.com/v1beta/{$this->model}:generateContent";
            
            // Prepara los datos
            $data = [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 1024,  // Start with lower token count for testing
                    'topP' => 0.95,
                    'topK' => 40
                ]
            ];
            
            // Realiza la solicitud con la API key
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url . "?key=" . $this->apiKey, $data);
            
            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text'];
            } else {
                Log::error('Error en respuesta de API Gemini: ' . $response->body());
                throw new \Exception('Error en respuesta: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Error al llamar a Gemini AI: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analiza un archivo de audio usando Gemini y retorna transcripción + métricas
     *
     * @param string $audioFilePath Path completo al archivo de audio
     * @param string $questionText Texto de la pregunta para contexto
     * @return array Array con transcripción y análisis prosódico
     */
    public function analyzeAudioWithGemini(string $audioFilePath, string $questionText = '', string $originalFileName = ''): array
    {
        Log::info('🎙️ INICIANDO ANÁLISIS DE AUDIO', [
            'audio_file_path' => $audioFilePath,
            'question_text_length' => strlen($questionText),
            'gemini_model' => $this->model,
            'timestamp' => now()->toISOString()
        ]);
        
        try {
            // Verificar que el archivo existe
            if (!Storage::exists($audioFilePath) && !file_exists($audioFilePath)) {
                Log::error('❌ ARCHIVO DE AUDIO NO ENCONTRADO', [
                    'path_checked' => $audioFilePath,
                    'storage_exists' => Storage::exists($audioFilePath),
                    'file_exists' => file_exists($audioFilePath)
                ]);
                throw new \Exception("Archivo de audio no encontrado: {$audioFilePath}");
            }

            // Si es un path relativo al storage, obtener el path completo
            $fullPath = Storage::exists($audioFilePath) ? Storage::path($audioFilePath) : $audioFilePath;
            Log::info('📁 Usando archivo desde: ' . ($fullPath === $audioFilePath ? 'path directo' : 'Storage'), ['full_path' => $fullPath]);

            // Leer y codificar el archivo de audio
            Log::info('🔄 Codificando archivo a base64...');
            $audioContent = file_get_contents($fullPath);
            if ($audioContent === false) {
                Log::error('❌ No se pudo leer el contenido del archivo', ['path' => $fullPath]);
                throw new \Exception("No se pudo leer el contenido del archivo de audio: {$fullPath}");
            }
            $audioData = base64_encode($audioContent);
            
            // Ensure base64 data is clean (no data:...;base64, prefix)
            if (str_starts_with($audioData, 'data:')) {
                $audioData = preg_replace('/^data:[^;]+;base64,/', '', $audioData);
                Log::info('🧹 Se limpió el prefijo de datos base64');
            }
            
            $encodedSize = strlen($audioData);
            Log::info('✅ Archivo codificado', [
                'base64_size_bytes' => $encodedSize,
                'base64_size_mb' => round($encodedSize / 1024 / 1024, 2)
            ]);
            
            // Detectar mime type basado en el archivo temporal (que es el que realmente existe)
            // Solo usar el originalFileName para obtener la extensión si es necesario
            $mimeType = $this->getAudioMimeType($fullPath, $originalFileName);
            
            // Verificar si el formato es soportado por Gemini (v1beta endpoint)
            $supportedFormats = ['audio/mpeg', 'audio/mp4', 'audio/wav', 'audio/aiff', 'audio/aac', 'audio/ogg', 'audio/flac'];
            
            if (!in_array($mimeType, $supportedFormats)) {
                Log::error('❌ Formato de audio no soportado por Gemini', [
                    'mime_type' => $mimeType,
                    'supported_formats' => $supportedFormats
                ]);
                throw new \Exception("Formato de audio no soportado: {$mimeType}. Por favor, use MP3, MP4 o WAV.");
            }
            
            Log::info('🎵 Formato de audio válido para Gemini', [
                'mime_type' => $mimeType,
                'file_path' => $fullPath
            ]);
            
            // Construir prompt específico para análisis de audio
            $analysisPrompt = $this->buildAudioAnalysisPrompt($questionText);
            Log::info('📝 Prompt construido', [
                'prompt_length' => strlen($analysisPrompt),
                'includes_question' => !empty($questionText)
            ]);
            
            // URL de la API REST de Gemini - usando v1beta para audio support
            $url = "https://generativelanguage.googleapis.com/v1beta/{$this->model}:generateContent";
            Log::info('🌐 Preparando llamada a Gemini API', [
                'url' => $url,
                'model' => $this->model,
                'api_endpoint' => 'v1beta',
                'api_key_configured' => !empty($this->apiKey)
            ]);
            
            // Preparar datos para envío multimodal
            $data = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data' => $audioData
                                ]
                            ],
                            [
                                'text' => $analysisPrompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1, // Baja temperatura para análisis consistente
                    'maxOutputTokens' => 2048,
                    'topP' => 0.95,
                    'topK' => 40
                ]
            ];
            
            Log::info('📤 ENVIANDO SOLICITUD A GEMINI API...');
            
            // Realizar solicitud con timeout extendido para audio
            $startTime = microtime(true);
            $response = Http::timeout(120) // Aumentado el timeout para archivos grandes
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url . "?key=" . $this->apiKey, $data);
            $endTime = microtime(true);
            
            $responseTime = round($endTime - $startTime, 2);
            
            Log::info('📥 RESPUESTA RECIBIDA DE GEMINI', [
                'response_time_seconds' => $responseTime,
                'status_code' => $response->status(),
                'successful' => $response->successful(),
                'response_size' => strlen($response->body())
            ]);
            
            if ($response->successful()) {
                $responseBody = $response->json();
                
                if (!isset($responseBody['candidates'][0]['content']['parts'][0]['text'])) {
                    Log::error('❌ ESTRUCTURA DE RESPUESTA INESPERADA', ['response_body' => $responseBody]);
                    throw new \Exception('Respuesta de Gemini no contiene el texto esperado.');
                }
                
                $rawResponse = $responseBody['candidates'][0]['content']['parts'][0]['text'];
                
                $analysisResult = $this->extractJsonFromResponse($rawResponse);
                
                if (!$analysisResult) {
                    Log::error('❌ NO SE PUDO OBTENER ANÁLISIS VÁLIDO', [
                        'raw_response' => $rawResponse
                    ]);
                    throw new \Exception('No se pudo obtener un análisis válido del audio. La IA no devolvió un JSON bien formado.');
                }
                
                Log::info('🎉 ANÁLISIS DE AUDIO COMPLETADO EXITOSAMENTE', [
                    'file' => basename($fullPath),
                    'total_time_seconds' => $responseTime
                ]);
                
                return $analysisResult;
                
            } else {
                $errorBody = $response->body();
                $errorJson = $response->json();
                
                Log::error('❌ ERROR EN RESPUESTA DE GEMINI API', [
                    'status_code' => $response->status(),
                    'error_body' => $errorBody,
                    'error_json' => $errorJson,
                    'headers' => $response->headers(),
                    'request_details' => [
                        'url' => $url,
                        'mime_type_sent' => $mimeType,
                        'audio_size_mb' => round(strlen($audioData) / 1024 / 1024, 2),
                        'prompt_length' => strlen($analysisPrompt)
                    ]
                ]);
                
                $errorMessage = 'Error en análisis de audio: ' . $response->status();
                if ($errorJson && isset($errorJson['error']['message'])) {
                    $errorMessage .= ' - ' . $errorJson['error']['message'];
                } else {
                    $errorMessage .= ' - ' . $errorBody;
                }
                
                throw new \Exception($errorMessage);
            }
            
        } catch (\Exception $e) {
            Log::error('💥 ERROR CRÍTICO EN ANÁLISIS DE AUDIO', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'audio_file' => $audioFilePath,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Retornar estructura básica en caso de error
            return [
                'transcripcion' => '',
                'error' => $e->getMessage(),
                'analisis_emocional' => [],
                'metricas_prosodicas' => [],
                'indicadores_psicologicos' => []
            ];
        }
    }

    /**
     * Construye el prompt específico para análisis de audio
     */
    private function buildAudioAnalysisPrompt(string $questionText): string
    {
        return "Analiza este archivo de audio de una respuesta a la pregunta: \"{$questionText}\"

Por favor, devuelve ÚNICAMENTE un objeto JSON con la siguiente estructura exacta:

{
  \"transcripcion\": \"[transcripción completa y precisa del audio]\",
  \"duracion_segundos\": [duración total en segundos],
  \"analisis_emocional\": {
    \"felicidad\": [0.0-1.0],
    \"tristeza\": [0.0-1.0],
    \"ansiedad\": [0.0-1.0],
    \"enojo\": [0.0-1.0],
    \"miedo\": [0.0-1.0],
    \"emocion_dominante\": \"[nombre de la emoción más fuerte]\"
  },
  \"metricas_prosodicas\": {
    \"velocidad_habla\": \"[lento/normal/rapido]\",
    \"pausas_significativas\": [número de pausas > 2 segundos],
    \"titubeos\": [número de eh, um, este, etc],
    \"energia_vocal\": [0.0-1.0],
    \"variacion_tonal\": \"[monotono/variado/muy_variado]\",
    \"claridad_diccion\": [0.0-1.0]
  },
  \"analisis_temporal\": {
    \"tiempo_hablando\": [segundos de habla activa],
    \"tiempo_silencio\": [segundos de pausas/silencios],
    \"ritmo_consistente\": [true/false]
  },
  \"indicadores_psicologicos\": {
    \"nivel_estres\": [0.0-1.0],
    \"nivel_confianza\": [0.0-1.0],
    \"coherencia_emocional\": [0.0-1.0],
    \"autenticidad\": [0.0-1.0],
    \"nivel_reflexion\": [0.0-1.0]
  },
  \"observaciones\": \"[observaciones adicionales sobre el tono, la forma de expresarse, etc]\"
}

Analiza cuidadosamente todos los aspectos del audio: contenido verbal, tono emocional, ritmo de habla, pausas, calidad de voz, y patrones prosódicos que puedan indicar estados emocionales o psicológicos.

Responde SOLO con el JSON, sin texto adicional.";
    }

    /**
     * Detecta el mime type del archivo de audio
     */
    private function getAudioMimeType(string $filePath, string $originalFileName = ''): string
    {
        // Usar finfo para una detección más precisa con el archivo real (temporal)
        if (class_exists(\finfo::class) && file_exists($filePath)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo->file($filePath);
            
            if ($detectedMime) {
                // Limpiar el MIME type - eliminar especificaciones de códec
                $detectedMime = explode(';', $detectedMime)[0];
                Log::info('🔍 MIME type detectado con finfo', [
                    'detected' => $detectedMime, 
                    'file' => $filePath,
                    'original_name' => $originalFileName
                ]);
                
                // Si se detecta un formato de video, ajustarlo si es un archivo de audio
                if ($detectedMime === 'video/mp4' || $detectedMime === 'video/webm') {
                    Log::info('🔄 Ajustando MIME type de video a audio');
                    return 'audio/mp4';
                }
                
                // Normalizar audio/mp3 a audio/mpeg (estándar)
                if ($detectedMime === 'audio/mp3') {
                    return 'audio/mpeg';
                }
                
                return $detectedMime;
            }
        }
        
        // Si finfo falla, usar la extensión del archivo original si está disponible
        $fileForExtension = !empty($originalFileName) ? $originalFileName : $filePath;
        $extension = strtolower(pathinfo($fileForExtension, PATHINFO_EXTENSION));
        
        // Mapeo de extensiones a MIME types
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'mp4' => 'audio/mp4',
            'wav' => 'audio/wav',
            'webm' => 'audio/webm',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
            'aiff' => 'audio/aiff',
            'aif' => 'audio/aiff'
        ];
        
        $mimeType = $mimeTypes[$extension] ?? 'audio/mpeg';
        Log::info('🔍 MIME type detectado por extensión (fallback)', [
            'detected' => $mimeType, 
            'extension' => $extension,
            'from_file' => $fileForExtension
        ]);
        
        return $mimeType;
    }
    

    /**
     * Intenta extraer JSON válido de una respuesta que puede contener texto adicional
     */
    private function extractJsonFromResponse(string $response): ?array
    {
        Log::info('🧹 LIMPIANDO RESPUESTA DE GEMINI', [
            'raw_length' => strlen($response),
            'contains_json_markers' => str_contains($response, '```json'),
            'contains_backticks' => str_contains($response, '```')
        ]);
        
        // Buscar el primer { y el último } para extraer el JSON
        $startPos = strpos($response, '{');
        $endPos = strrpos($response, '}');
        
        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $jsonString = substr($response, $startPos, $endPos - $startPos + 1);
        } else {
            Log::error('❌ No se pudo encontrar JSON válido en la respuesta');
            return null;
        }
        
        Log::info('🔍 JSON EXTRAÍDO', [
            'json_length' => strlen($jsonString),
            'json_preview' => substr($jsonString, 0, 100) . '...'
        ]);
        
        // Limpiar caracteres problemáticos y decodificar
        $jsonString = trim($jsonString);
        $jsonString = preg_replace('/[\x00-\x1F\x7F]/', '', $jsonString); // Remover caracteres de control
        
        $decoded = json_decode($jsonString, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        } else {
            Log::error('❌ ERROR DECODIFICANDO JSON LIMPIO', [
                'json_error' => json_last_error_msg(),
                'cleaned_json' => $jsonString
            ]);
        }
        
        return null;
    }

    /**
     * Generate AI analysis specifically for text-based responses
     *
     * @param CampaignResponse $response
     * @param Questionnaire $questionnaire
     * @param array $textResponses
     * @return array AI analysis results
     */
    public function generateTextAnalysis(
        CampaignResponse $response,
        Questionnaire $questionnaire,
        array $textResponses
    ): array
    {
        try {
            // Verificar si el servicio está configurado
            if (empty($this->apiKey)) {
                return [
                    'success' => false,
                    'message' => 'El servicio de IA no está configurado correctamente. Falta API key.',
                    'interpretations' => [],
                    'soft_skills_analysis' => []
                ];
            }

            // Preparar datos del respondente
            $respondentData = [
                'name' => $response->respondent_name,
                'email' => $response->respondent_email,
                'type' => $response->respondent_type,
            ];

            // Construir el prompt específico para análisis de texto
            $prompt = $this->buildTextAnalysisPrompt($respondentData, $textResponses, $questionnaire);
            
            // Llamar a Gemini AI
            $startTime = microtime(true);
            $aiResponse = $this->callGeminiAI($prompt);
            $processingTime = round((microtime(true) - $startTime), 2);

            // Procesar la respuesta de IA
            $analysisResult = $this->processTextAnalysisResponse($aiResponse, $textResponses);

            return [
                'success' => true,
                'interpretations' => $analysisResult['interpretations'] ?? [],
                'soft_skills_analysis' => $analysisResult['soft_skills_analysis'] ?? [],
                'content_analysis' => $analysisResult['content_analysis'] ?? [],
                'thematic_analysis' => $analysisResult['thematic_analysis'] ?? [],
                'summary' => $analysisResult['summary'] ?? '',
                'confidence_scores' => $analysisResult['confidence_scores'] ?? [],
                'processing_metadata' => [
                    'processing_time' => $processingTime,
                    'analyzed_responses' => count($textResponses),
                    'total_word_count' => array_sum(array_column($textResponses, 'word_count')),
                    'analysis_type' => 'text_analysis'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al generar análisis de texto con IA: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al generar el análisis de texto: ' . $e->getMessage(),
                'interpretations' => [],
                'soft_skills_analysis' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Build prompt specifically for text analysis
     */
    private function buildTextAnalysisPrompt(array $respondentData, array $textResponses, Questionnaire $questionnaire): string
    {
        $prompt = "# Análisis de respuestas de texto para evaluación de competencias\n\n";
        
        // Información del respondente
        $prompt .= "## Información del respondente\n";
        $prompt .= "- Nombre: " . ($respondentData['name'] ?? 'No disponible') . "\n";
        $prompt .= "- Tipo: " . ($respondentData['type'] ?? 'No especificado') . "\n\n";
        
        // Información del cuestionario
        $prompt .= "## Información del cuestionario\n";
        $prompt .= "- Tipo: " . ($questionnaire->questionnaire_type?->value ?? $questionnaire->scoring_type) . "\n";
        $prompt .= "- Nombre: " . $questionnaire->name . "\n";
        $prompt .= "- Descripción: " . ($questionnaire->description ?? 'No disponible') . "\n\n";

        // Respuestas de texto
        $prompt .= "## Respuestas del candidato\n\n";
        
        foreach ($textResponses as $questionId => $responseData) {
            $questionText = $questionnaire->questions[$questionId] ?? "Pregunta {$questionId}";
            $textContent = $responseData['text_content'] ?? '';
            $wordCount = $responseData['word_count'] ?? 0;
            
            $prompt .= "### {$questionText}\n\n";
            $prompt .= "**Respuesta ({$wordCount} palabras):**\n";
            $prompt .= "{$textContent}\n\n";
        }

        // Instrucciones de análisis
        $prompt .= "## Instrucciones para el análisis\n\n";
        $prompt .= "Analiza las respuestas de texto y proporciona un análisis detallado en formato JSON con la siguiente estructura:\n\n";
        
        $prompt .= "```json\n";
        $prompt .= "{\n";
        $prompt .= "  \"interpretations\": {\n";
        $prompt .= "    \"[question_id]\": {\n";
        $prompt .= "      \"content_analysis\": \"Análisis del contenido de la respuesta\",\n";
        $prompt .= "      \"competencies_demonstrated\": [\"lista de competencias (ej: 'pensamiento crítico', 'resolución de problemas')\"],\n";
        $prompt .= "      \"soft_skills_score\": \"[0.0-10.0]\",\n";
        $prompt .= "      \"sentiment_analysis\": \"Análisis del sentimiento (ej: 'positivo', 'negativo', 'neutral')\",\n";
        $prompt .= "      \"confidence_score\": \"[0.0-1.0]\"\n";
        $prompt .= "    }\n";
        $prompt .= "  },\n";
        $prompt .= "  \"soft_skills_analysis\": {\n";
        $prompt .= "    \"[soft_skill_name]\": \"[análisis de la habilidad blanda en un párrafo]\"\n";
        $prompt .= "  },\n";
        $prompt .= "  \"content_analysis\": \"Análisis general del contenido de las respuestas en un párrafo\",\n";
        $prompt .= "  \"thematic_analysis\": {\n";
        $prompt .= "    \"temas_principales\": [\"tema 1\", \"tema 2\"],\n";
        $prompt .= "    \"resumen_tematico\": \"Análisis de los temas principales que emergieron de las respuestas\"\n";
        $prompt .= "  },\n";
        $prompt .= "  \"summary\": \"Resumen ejecutivo del análisis\",\n";
        $prompt .= "  \"confidence_scores\": {\n";
        $prompt .= "    \"general_confidence\": \"[0.0-1.0]\",\n";
        $prompt .= "    \"specific_scores\": {\n";
        $prompt .= "      \"competencia_1\": \"[0.0-1.0]\",\n";
        $prompt .= "      \"competencia_2\": \"[0.0-1.0]\"\n";
        $prompt .= "    }\n";
        $prompt .= "  }\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";
        $prompt .= "Analiza la forma de redactar, la estructura de las ideas, el lenguaje técnico, el uso de metáforas, la capacidad de argumentar y la coherencia de las respuestas para identificar las competencias que se demuestran.\n";
        $prompt .= "Responde SOLO con el JSON, sin texto adicional.";
        
        return $prompt;
    }

    /**
     * Procesa la respuesta JSON de Gemini para análisis de texto
     */
    private function processTextAnalysisResponse(string $aiResponse, array $textResponses): ?array
    {
        $analysisResult = $this->extractJsonFromResponse($aiResponse);

        if ($analysisResult === null) {
            return null;
        }

        // Si la estructura es correcta, se puede retornar
        return $analysisResult;
    }
}
