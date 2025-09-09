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
        $this->model = config('services.google.model', 'gemini-1.5-flash');
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
            // URL de la API REST de Gemini
            $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent";
            
            // Prepara los datos
            $data = [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 8192,
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
    public function analyzeAudioWithGemini(string $audioFilePath, string $questionText = ''): array
    {
        try {
            // Verificar que el archivo existe
            if (!Storage::exists($audioFilePath) && !file_exists($audioFilePath)) {
                throw new \Exception("Archivo de audio no encontrado: {$audioFilePath}");
            }

            // Si es un path relativo al storage, obtener el path completo
            if (Storage::exists($audioFilePath)) {
                $fullPath = Storage::path($audioFilePath);
            } else {
                $fullPath = $audioFilePath;
            }

            // Leer y codificar el archivo de audio
            $audioData = base64_encode(file_get_contents($fullPath));
            
            // Detectar mime type basado en la extensión
            $mimeType = $this->getAudioMimeType($fullPath);
            
            // Construir prompt específico para análisis de audio
            $analysisPrompt = $this->buildAudioAnalysisPrompt($questionText);
            
            // URL de la API REST de Gemini
            $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent";
            
            // Preparar datos para envío multimodal
            $data = [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
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
                    'maxOutputTokens' => 8192,
                    'topP' => 0.95,
                    'topK' => 40
                    // Note: responseMimeType is not supported in this API version
                ]
            ];
            
            // Realizar solicitud con timeout extendido para audio
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url . "?key=" . $this->apiKey, $data);
            
            if ($response->successful()) {
                $rawResponse = $response->json()['candidates'][0]['content']['parts'][0]['text'];
                
                // Intentar decodificar JSON
                $analysisResult = json_decode($rawResponse, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning('Respuesta de Gemini no es JSON válido, intentando limpiar: ' . $rawResponse);
                    // Intentar limpiar la respuesta y extraer JSON
                    $analysisResult = $this->extractJsonFromResponse($rawResponse);
                }
                
                if (!$analysisResult) {
                    throw new \Exception('No se pudo obtener análisis válido del audio');
                }
                
                Log::info('Análisis de audio completado exitosamente para: ' . basename($fullPath));
                return $analysisResult;
                
            } else {
                Log::error('Error en respuesta de API Gemini para audio: ' . $response->body());
                throw new \Exception('Error en análisis de audio: ' . $response->status());
            }
            
        } catch (\Exception $e) {
            Log::error('Error al analizar audio con Gemini: ' . $e->getMessage());
            
            // Retornar estructura básica en caso de error
            return [
                'transcripcion' => '',
                'error' => $e->getMessage(),
                'analisis_emocional' => [
                    'felicidad' => 0,
                    'tristeza' => 0,
                    'ansiedad' => 0,
                    'enojo' => 0,
                    'miedo' => 0
                ],
                'metricas_prosodicas' => [
                    'velocidad_habla' => 'no_disponible',
                    'pausas_significativas' => 0,
                    'titubeos' => 0,
                    'energia_vocal' => 0
                ],
                'indicadores_psicologicos' => [
                    'nivel_estres' => 0,
                    'coherencia_emocional' => 0,
                    'autenticidad' => 0
                ]
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
    private function getAudioMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'mp4' => 'audio/mp4',
            'wav' => 'audio/wav',
            'webm' => 'audio/webm',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4'
        ];
        
        return $mimeTypes[$extension] ?? 'audio/mpeg';
    }

    /**
     * Intenta extraer JSON válido de una respuesta que puede contener texto adicional
     */
    private function extractJsonFromResponse(string $response): ?array
    {
        // Buscar el primer { y el último } para extraer el JSON
        $startPos = strpos($response, '{');
        $endPos = strrpos($response, '}');
        
        if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
            $jsonString = substr($response, $startPos, $endPos - $startPos + 1);
            $decoded = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
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
        $prompt .= "      \"competencies_demonstrated\": [\"lista de competencias mostradas\"],\n";
        $prompt .= "      \"key_themes\": [\"temas principales identificados\"],\n";
        $prompt .= "      \"confidence_score\": 0.85,\n";
        $prompt .= "      \"depth_of_response\": \"superficial|moderate|deep\",\n";
        $prompt .= "      \"authenticity_indicators\": \"Indicadores de autenticidad\"\n";
        $prompt .= "    }\n";
        $prompt .= "  },\n";
        $prompt .= "  \"soft_skills_analysis\": {\n";
        $prompt .= "    \"communication_skills\": {\n";
        $prompt .= "      \"level\": \"alto|medio|bajo\",\n";
        $prompt .= "      \"evidence\": [\"evidencia específica\"],\n";
        $prompt .= "      \"score\": 0.85\n";
        $prompt .= "    },\n";
        $prompt .= "    \"critical_thinking\": {\n";
        $prompt .= "      \"level\": \"alto|medio|bajo\",\n";
        $prompt .= "      \"evidence\": [\"evidencia específica\"],\n";
        $prompt .= "      \"score\": 0.75\n";
        $prompt .= "    },\n";
        $prompt .= "    \"self_awareness\": {\n";
        $prompt .= "      \"level\": \"alto|medio|bajo\",\n";
        $prompt .= "      \"evidence\": [\"evidencia específica\"],\n";
        $prompt .= "      \"score\": 0.80\n";
        $prompt .= "    },\n";
        $prompt .= "    \"problem_solving\": {\n";
        $prompt .= "      \"level\": \"alto|medio|bajo\",\n";
        $prompt .= "      \"evidence\": [\"evidencia específica\"],\n";
        $prompt .= "      \"score\": 0.70\n";
        $prompt .= "    }\n";
        $prompt .= "  },\n";
        $prompt .= "  \"content_analysis\": {\n";
        $prompt .= "    \"overall_coherence\": \"Evaluación de coherencia general\",\n";
        $prompt .= "    \"language_proficiency\": \"alto|medio|bajo\",\n";
        $prompt .= "    \"emotional_intelligence_indicators\": [\"indicadores identificados\"],\n";
        $prompt .= "    \"professional_maturity\": \"alto|medio|bajo\"\n";
        $prompt .= "  },\n";
        $prompt .= "  \"thematic_analysis\": {\n";
        $prompt .= "    \"main_themes\": [\"tema1\", \"tema2\", \"tema3\"],\n";
        $prompt .= "    \"career_orientation\": \"descripción de orientación profesional\",\n";
        $prompt .= "    \"values_expressed\": [\"valor1\", \"valor2\"],\n";
        $prompt .= "    \"motivation_drivers\": [\"motivación1\", \"motivación2\"]\n";
        $prompt .= "  },\n";
        $prompt .= "  \"summary\": \"Resumen ejecutivo del análisis\",\n";
        $prompt .= "  \"confidence_scores\": {\n";
        $prompt .= "    \"overall_analysis\": 0.85,\n";
        $prompt .= "    \"soft_skills_assessment\": 0.80,\n";
        $prompt .= "    \"content_reliability\": 0.90\n";
        $prompt .= "  }\n";
        $prompt .= "}\n";
        $prompt .= "```\n\n";

        $prompt .= "Analiza cuidadosamente:\n";
        $prompt .= "1. La calidad y profundidad de las respuestas\n";
        $prompt .= "2. Las competencias y habilidades blandas demostradas\n";
        $prompt .= "3. Los patrones de pensamiento y comunicación\n";
        $prompt .= "4. La coherencia y autenticidad de las respuestas\n";
        $prompt .= "5. Los valores y motivaciones expresados\n\n";
        
        $prompt .= "Responde ÚNICAMENTE con el JSON solicitado, sin texto adicional.\n";

        return $prompt;
    }

    /**
     * Process the AI response for text analysis
     */
    private function processTextAnalysisResponse(string $aiResponse, array $textResponses): array
    {
        // Try to decode JSON response
        $analysisResult = json_decode($aiResponse, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Respuesta de IA para análisis de texto no es JSON válido, intentando extraer');
            $analysisResult = $this->extractJsonFromResponse($aiResponse);
        }
        
        if (!$analysisResult) {
            // Fallback to basic analysis if AI response fails
            return $this->generateFallbackTextAnalysis($textResponses);
        }
        
        return $analysisResult;
    }

    /**
     * Generate fallback analysis if AI processing fails
     */
    private function generateFallbackTextAnalysis(array $textResponses): array
    {
        $interpretations = [];
        $totalWords = 0;
        
        foreach ($textResponses as $questionId => $responseData) {
            $wordCount = $responseData['word_count'] ?? 0;
            $totalWords += $wordCount;
            
            $interpretations[$questionId] = [
                'content_analysis' => 'Análisis básico: Respuesta de ' . $wordCount . ' palabras',
                'competencies_demonstrated' => ['comunicación_escrita'],
                'key_themes' => ['respuesta_personal'],
                'confidence_score' => 0.5,
                'depth_of_response' => $wordCount > 50 ? 'moderate' : 'superficial',
                'authenticity_indicators' => 'Evaluación automática básica'
            ];
        }

        return [
            'interpretations' => $interpretations,
            'soft_skills_analysis' => [
                'communication_skills' => [
                    'level' => 'medio',
                    'evidence' => ['Respuestas textuales proporcionadas'],
                    'score' => 0.6
                ]
            ],
            'content_analysis' => [
                'overall_coherence' => 'Análisis básico completado',
                'language_proficiency' => 'medio',
                'emotional_intelligence_indicators' => [],
                'professional_maturity' => 'medio'
            ],
            'thematic_analysis' => [
                'main_themes' => ['comunicación', 'expresión_personal'],
                'career_orientation' => 'No determinado en análisis básico',
                'values_expressed' => [],
                'motivation_drivers' => []
            ],
            'summary' => "Análisis básico de {$totalWords} palabras en " . count($textResponses) . " respuestas",
            'confidence_scores' => [
                'overall_analysis' => 0.5,
                'soft_skills_assessment' => 0.4,
                'content_reliability' => 0.6
            ]
        ];
    }

} 
