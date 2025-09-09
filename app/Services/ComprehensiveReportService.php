<?php

namespace App\Services;

use App\Models\CampaignResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComprehensiveReportService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = config('services.google.api_key');
        $this->model = config('services.google.model', 'gemini-1.5-flash');
    }

    /**
     * Genera un reporte comprehensivo detallado para una respuesta
     */
    public function generateComprehensiveReport(CampaignResponse $response): array
    {
        try {
            // Recopilar todos los datos disponibles
            $reportData = $this->gatherReportData($response);

            // Generar el reporte usando IA
            $comprehensiveAnalysis = $this->generateAIComprehensiveReport($reportData);

            // Estructurar el reporte final
            $finalReport = $this->structureFinalReport($comprehensiveAnalysis, $response);

            Log::info('Comprehensive report generated successfully', [
                'response_id' => $response->id,
                'report_sections' => count($finalReport['sections'] ?? [])
            ]);

            return $finalReport;

        } catch (\Exception $e) {
            Log::error('Error generating comprehensive report', [
                'response_id' => $response->id,
                'error' => $e->getMessage()
            ]);

            return $this->generateFallbackReport($response);
        }
    }

    /**
     * Recopila todos los datos necesarios para el reporte
     */
    private function gatherReportData(CampaignResponse $response): array
    {
        // Decode responses field if it's a string
        $responses = is_string($response->responses) ? json_decode($response->responses, true) : $response->responses;
        
        // Extract transcriptions from responses field
        $transcriptions = [];
        if (!empty($responses)) {
            foreach ($responses as $questionId => $questionResponse) {
                if (isset($questionResponse['transcription_text'])) {
                    $transcriptions[$questionId] = $questionResponse['transcription_text'];
                }
            }
        }
        
        // If no transcriptions found in responses, try the transcriptions field
        if (empty($transcriptions)) {
            $transcriptions = is_string($response->transcriptions) ? json_decode($response->transcriptions, true) : $response->transcriptions;
        }
        
        // Decode other JSON fields properly
        $aiAnalysis = is_string($response->ai_analysis) ? json_decode($response->ai_analysis, true) : $response->ai_analysis;
        $prosodicAnalysis = is_string($response->prosodic_analysis) ? json_decode($response->prosodic_analysis, true) : $response->prosodic_analysis;
        $questionnaireScores = is_string($response->questionnaire_scores) ? json_decode($response->questionnaire_scores, true) : $response->questionnaire_scores;
        $rawResponses = is_string($response->raw_responses) ? json_decode($response->raw_responses, true) : $response->raw_responses;
        $processedResponses = is_string($response->processed_responses) ? json_decode($response->processed_responses, true) : $response->processed_responses;
        
        $data = [
            'respondent_info' => [
                'name' => $response->respondent_name,
                'email' => $response->respondent_email,
                'age' => $response->respondent_age,
                'additional_info' => $response->respondent_additional_info
            ],
            'campaign_info' => [
                'name' => $response->campaign->name,
                'company' => $response->campaign->company->name,
                'questionnaire_type' => $response->questionnaire?->questionnaire_type?->value
            ],
            'responses' => [
                'raw_responses' => $rawResponses,
                'processed_responses' => $processedResponses,
                'full_responses' => $responses  // Include full responses data
            ],
            'ai_analysis' => $aiAnalysis,
            'transcriptions' => $transcriptions,
            'prosodic_analysis' => $prosodicAnalysis,
            'questionnaire_scores' => $questionnaireScores
        ];

        return $data;
    }

    /**
     * Genera el análisis comprehensivo usando IA con el formato específico solicitado
     */
    private function generateAIComprehensiveReport(array $reportData): string
    {
        $prompt = $this->buildComprehensiveReportPrompt($reportData);

        try {
            $response = Http::timeout(120)->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 8192,
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Error al generar el reporte';
            }

            throw new \Exception('API response not successful: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Error calling AI for comprehensive report', [
                'error' => $e->getMessage()
            ]);
            
            return $this->generateBasicReport($reportData);
        }
    }

    /**
     * Construye el prompt específico para el reporte comprehensivo
     */
    private function buildComprehensiveReportPrompt(array $reportData): string
    {
        $respondentName = $reportData['respondent_info']['name'];
        
        $prompt = "# REPORTE PSICOLÓGICO PROFESIONAL PARA {$respondentName}\n\n";
        
        $prompt .= "Eres un psicólogo especialista en Habilidades Blandas y Personalidad. Necesito que generes un informe lo más detallado posible de esta persona, utilizando el contenido de sus respuestas y análisis prosódico disponible.\n\n";
        
        $prompt .= "**IMPORTANTE**: El informe debe estar escrito en tono profesional, descriptivo y teniendo en cuenta que lo lee directamente el candidato. El candidato es una persona que quiere conocer en profundidad todas sus habilidades como punto de partida para desarrollar algunas.\n\n";

        // Añadir información del candidato
        $prompt .= "## INFORMACIÓN DEL CANDIDATO\n";
        $prompt .= "- **Nombre**: {$respondentName}\n";
        if ($reportData['respondent_info']['age']) {
            $prompt .= "- **Edad**: {$reportData['respondent_info']['age']} años\n";
        }
        $prompt .= "- **Empresa**: {$reportData['campaign_info']['company']}\n";
        $prompt .= "- **Tipo de evaluación**: {$reportData['campaign_info']['questionnaire_type']}\n\n";

        // Map question IDs to actual questions for reflective questions
        $questionTexts = [
            'reflective_questions_q1' => 'Si pudieras mandarle un mensaje a vos mismo/a hace unos años, ¿qué le dirías? Imaginate que estás hablando con vos mismo/a cuando recién empezabas a estudiar o trabajar.',
            'reflective_questions_q2' => 'Imaginate que te contratan para el trabajo de tus sueños pero tenés que mudarte a otro país donde no conocés a nadie. ¿Cómo te sentirías y qué harías?',
            'reflective_questions_q3' => 'Si tuvieras que elegir entre un trabajo que paga muy bien pero no te gusta, y otro que te apasiona pero paga menos, ¿cuál elegirías y por qué?',
            'reflective_questions_q4' => 'Contame sobre alguna vez que hayas tenido que tomar una decisión difícil. ¿Cómo la tomaste y qué aprendiste de esa experiencia?',
            'reflective_questions_q5' => '¿Cómo reaccionás cuando las cosas no salen como las planeaste? Dame un ejemplo de alguna situación así.',
            'reflective_questions_q6' => 'Si tuvieras recursos ilimitados para crear un proyecto que ayude a otros, ¿qué harías y por qué?',
            'reflective_questions_q7' => '¿Qué es lo que más te motiva en la vida? ¿Cómo mantenés esa motivación cuando enfrentás obstáculos?'
        ];

        // Añadir transcripciones con las preguntas completas
        if (!empty($reportData['transcriptions'])) {
            $prompt .= "## RESPUESTAS TRANSCRITAS DEL CANDIDATO\n\n";
            foreach ($reportData['transcriptions'] as $questionId => $transcription) {
                $questionText = $questionTexts[$questionId] ?? "Pregunta {$questionId}";
                $prompt .= "**Pregunta**: {$questionText}\n";
                $prompt .= "**Respuesta**: {$transcription}\n\n";
            }
        }

        // Si las transcripciones están en full_responses, extraerlas de ahí
        if (empty($reportData['transcriptions']) && !empty($reportData['responses']['full_responses'])) {
            $prompt .= "## RESPUESTAS TRANSCRITAS DEL CANDIDATO\n\n";
            foreach ($reportData['responses']['full_responses'] as $questionId => $questionData) {
                if (isset($questionData['transcription_text'])) {
                    $questionText = $questionTexts[$questionId] ?? "Pregunta {$questionId}";
                    $prompt .= "**Pregunta**: {$questionText}\n";
                    $prompt .= "**Respuesta**: {$questionData['transcription_text']}\n\n";
                }
            }
        }

        // Añadir análisis prosódico si está disponible
        if (!empty($reportData['prosodic_analysis'])) {
            $prompt .= "## ANÁLISIS PROSÓDICO DE LAS RESPUESTAS\n\n";
            foreach ($reportData['prosodic_analysis'] as $questionId => $analysis) {
                $prompt .= "**{$questionId}**:\n";
                if (is_array($analysis)) {
                    foreach ($analysis as $key => $value) {
                        $prompt .= "- {$key}: {$value}\n";
                    }
                }
                $prompt .= "\n";
            }
        }

        // Añadir análisis de IA previo si está disponible
        if (!empty($reportData['ai_analysis'])) {
            $prompt .= "## ANÁLISIS EMOCIONAL Y DE PERSONALIDAD\n";
            if (isset($reportData['ai_analysis']['emotional_analysis'])) {
                $prompt .= json_encode($reportData['ai_analysis']['emotional_analysis'], JSON_PRETTY_PRINT) . "\n\n";
            }
            if (isset($reportData['ai_analysis']['soft_skills_analysis'])) {
                $prompt .= "### Análisis de Habilidades Blandas:\n";
                $prompt .= json_encode($reportData['ai_analysis']['soft_skills_analysis'], JSON_PRETTY_PRINT) . "\n\n";
            }
        }

        $prompt .= "## FORMATO REQUERIDO DEL REPORTE\n\n";
        $prompt .= "Genera un reporte con la siguiente estructura EXACTA:\n\n";
        
        $prompt .= "### RESUMEN DESCRIPTIVO DE PERSONALIDAD\n";
        $prompt .= "[Descripción detallada de la personalidad del candidato]\n\n";
        
        $prompt .= "### ANÁLISIS DETALLADO DE COMPETENCIAS\n\n";
        $prompt .= "Para cada competencia, proporciona un puntaje del 1 al 10 (siendo 10 muy alto y 1 muy pobre) + descripción detallada. Puedes usar frases del candidato como ejemplos.\n\n";
        
        $competencias = [
            'Perseverancia',
            'Resiliencia', 
            'Pensamiento Crítico y Adaptabilidad',
            'Regulación Emocional',
            'Responsabilidad',
            'Autoconocimiento',
            'Manejo del Estrés',
            'Asertividad',
            'Habilidad para Construir Relaciones',
            'Creatividad',
            'Empatía',
            'Capacidad de Influencia y Comunicación',
            'Capacidad y Estilo de Liderazgo',
            'Curiosidad y Capacidad de Aprendizaje',
            'Tolerancia a la Frustración'
        ];
        
        foreach ($competencias as $index => $competencia) {
            $prompt .= ($index + 1) . ". **{$competencia}**: [Puntaje/10] - [Descripción detallada]\n\n";
        }
        
        $prompt .= "### PUNTOS FUERTES\n";
        $prompt .= "[Las competencias con más puntaje y explicación]\n\n";
        
        $prompt .= "### ÁREAS A DESARROLLAR\n";
        $prompt .= "[Mínimo 3-4 competencias donde se observaron debilidades]\n\n";
        
        $prompt .= "### PROPUESTA DE RE-SKILLING PERSONALIZADA\n";
        $prompt .= "[Recomendaciones específicas para desarrollar los puntos débiles]\n\n";
        
        $prompt .= "**IMPORTANTE**: Utiliza un lenguaje profesional pero accesible, sé específico en las observaciones y proporciona ejemplos concretos basados en las respuestas del candidato.";

        return $prompt;
    }

    /**
     * Estructura el reporte final en formato organizado
     */
    private function structureFinalReport(string $aiReport, CampaignResponse $response): array
    {
        return [
            'generated_at' => now(),
            'response_id' => $response->id,
            'respondent_name' => $response->respondent_name,
            'campaign_info' => [
                'name' => $response->campaign->name,
                'company' => $response->campaign->company->name
            ],
            'report_type' => 'comprehensive_professional',
            'content' => $aiReport,
            'sections' => $this->parseReportSections($aiReport),
            'metadata' => [
                'word_count' => str_word_count($aiReport),
                'processing_version' => '1.0',
                'ai_model' => $this->model
            ]
        ];
    }

    /**
     * Parsea las secciones del reporte para facilitar la visualización
     */
    private function parseReportSections(string $report): array
    {
        $sections = [];
        
        // Dividir por títulos de sección (###)
        $parts = preg_split('/###\s*/', $report, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($parts as $part) {
            $lines = explode("\n", trim($part), 2);
            $title = trim($lines[0] ?? '');
            $content = trim($lines[1] ?? '');
            
            if ($title && $content) {
                $sections[] = [
                    'title' => $title,
                    'content' => $content
                ];
            }
        }
        
        return $sections;
    }

    /**
     * Genera un reporte básico si falla la IA
     */
    private function generateBasicReport(array $reportData): string
    {
        $name = $reportData['respondent_info']['name'];
        
        $report = "# REPORTE BÁSICO PARA {$name}\n\n";
        $report .= "### RESUMEN DESCRIPTIVO DE PERSONALIDAD\n";
        $report .= "Basado en las respuestas proporcionadas, {$name} muestra características que requieren un análisis más detallado.\n\n";
        
        $report .= "### ANÁLISIS DETALLADO DE COMPETENCIAS\n";
        $report .= "Análisis detallado no disponible temporalmente. Por favor, intente regenerar el reporte.\n\n";
        
        $report .= "### PUNTOS FUERTES\n";
        $report .= "Requiere análisis adicional.\n\n";
        
        $report .= "### ÁREAS A DESARROLLAR\n";
        $report .= "Requiere análisis adicional.\n\n";
        
        $report .= "### PROPUESTA DE RE-SKILLING PERSONALIZADA\n";
        $report .= "Se recomienda volver a generar este reporte cuando el sistema esté completamente disponible.\n\n";
        
        return $report;
    }

    /**
     * Genera un reporte de fallback si hay error completo
     */
    private function generateFallbackReport(CampaignResponse $response): array
    {
        return [
            'generated_at' => now(),
            'response_id' => $response->id,
            'respondent_name' => $response->respondent_name,
            'campaign_info' => [
                'name' => $response->campaign->name,
                'company' => $response->campaign->company->name
            ],
            'report_type' => 'fallback',
            'content' => 'Error generando reporte. Por favor intente nuevamente.',
            'sections' => [],
            'metadata' => [
                'error' => true,
                'processing_version' => '1.0'
            ]
        ];
    }
}