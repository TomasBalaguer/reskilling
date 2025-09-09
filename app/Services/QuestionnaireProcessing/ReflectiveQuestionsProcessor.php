<?php

namespace App\Services\QuestionnaireProcessing;

/**
 * Processor for Reflective Questions soft skills questionnaire
 * Handles audio response transcription analysis for soft skills evaluation
 */
class ReflectiveQuestionsProcessor extends BaseQuestionnaireProcessor
{
    protected $type = 'REFLECTIVE_QUESTIONS';
    
    /**
     * Soft skills dimensions evaluated by this questionnaire
     */
    protected array $skillDimensions = [
        'autoconocimiento' => 'Autoconocimiento',
        'resiliencia' => 'Resiliencia', 
        'pensamiento_critico' => 'Pensamiento crítico',
        'liderazgo' => 'Liderazgo',
        'creatividad' => 'Creatividad',
        'motivacion' => 'Motivación',
        'empatia' => 'Empatía'
    ];

    /**
     * Calculate scores and interpretations based on audio transcription analysis
     * For reflective questions, scores are derived from AI analysis of audio transcriptions
     *
     * @param array $responses Raw responses from the questionnaire (audio transcriptions)
     * @param array $patientData Patient demographic data (age, gender, etc.)
     * @param array $result Optional existing result to extend
     * @return array Processed results with scores, interpretations, and summaries
     */
    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        \Log::info('REFLECTIVE_QUESTIONS - calculateScores called with ' . count($responses) . ' responses');
        
        // Add respondent data to result
        $result = $this->addRespondentData($result, $patientData);
        
        // Initialize with type information
        $result['scoring_type'] = $this->getType();
        $result['questionnaire_name'] = 'Preguntas Reflexivas';
        $result['response_type'] = 'audio_response';
        
        // Process audio responses and transcriptions
        $result['transcriptions'] = $this->processAudioResponses($responses);
        
        // Try to get existing audio analysis from Gemini (may not be available if processing async)
        $result['audio_analysis'] = $this->getExistingAudioAnalysis($responses);
        \Log::info('REFLECTIVE_QUESTIONS - audio_analysis count: ' . count($result['audio_analysis']));
        
        // Initialize soft skills evaluation structure
        $result['soft_skills_analysis'] = $this->initializeSoftSkillsStructure();
        
        // Add analysis indicators for AI interpretation (now including audio metrics)
        $result['analysis_indicators'] = $this->extractAnalysisIndicators($responses, $result['audio_analysis']);
        
        // Generate summary
        $result['summary'] = $this->generateReflectiveQuestionsSummary($responses, $patientData);
        
        return $result;
    }
    
    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     *
     * @param array $results Results from the calculateScores method
     * @return string Formatted prompt section
     */
    public function buildPromptSection(array $results): string
    {
        \Log::info('REFLECTIVE_QUESTIONS - buildPromptSection called');
        \Log::info('REFLECTIVE_QUESTIONS - Results keys: ' . implode(', ', array_keys($results)));
        \Log::info('REFLECTIVE_QUESTIONS - Has audio_analysis: ' . (isset($results['audio_analysis']) ? 'YES (' . count($results['audio_analysis']) . ' items)' : 'NO'));
        
        $promptSection = "## Análisis Integrado de Preguntas Reflexivas - Habilidades Blandas\n\n";
        
        // Add integrated analysis with both transcriptions and audio analysis
        if (isset($results['audio_analysis']) && !empty($results['audio_analysis'])) {
            $promptSection .= "### Análisis Completo por Pregunta (Texto + Audio):\n\n";
            
            foreach ($results['audio_analysis'] as $questionId => $audioAnalysis) {
                $questionNumber = intval(substr($questionId, 1)); // Extraer número de q1, q2, etc.
                $questionText = $this->getQuestionText($questionId);
                
                $promptSection .= "**PREGUNTA $questionNumber:** $questionText\n\n";
                
                // Transcripción
                if (isset($audioAnalysis['transcripcion']) && !empty($audioAnalysis['transcripcion'])) {
                    $promptSection .= "**Respuesta transcrita:** " . $audioAnalysis['transcripcion'] . "\n\n";
                } else {
                    $promptSection .= "**Respuesta transcrita:** No disponible\n\n";
                }
                
                // Análisis emocional (si está disponible)
                if (isset($audioAnalysis['analisis_emocional'])) {
                    $emociones = $audioAnalysis['analisis_emocional'];
                    $promptSection .= "**Análisis emocional de la voz:**\n";
                    $promptSection .= "- Felicidad: " . ($emociones['felicidad'] ?? 0) . "\n";
                    $promptSection .= "- Tristeza: " . ($emociones['tristeza'] ?? 0) . "\n";
                    $promptSection .= "- Ansiedad: " . ($emociones['ansiedad'] ?? 0) . "\n";
                    $promptSection .= "- Enojo: " . ($emociones['enojo'] ?? 0) . "\n";
                    if (isset($emociones['emocion_dominante'])) {
                        $promptSection .= "- Emoción dominante: " . $emociones['emocion_dominante'] . "\n";
                    }
                    $promptSection .= "\n";
                } elseif (isset($audioAnalysis['status']) && $audioAnalysis['status'] === 'pending') {
                    $promptSection .= "**Análisis emocional de la voz:** En proceso (será completado en futuros análisis)\n\n";
                }
                
                // Métricas prosódicas
                if (isset($audioAnalysis['metricas_prosodicas'])) {
                    $metricas = $audioAnalysis['metricas_prosodicas'];
                    $promptSection .= "**Características del habla:**\n";
                    $promptSection .= "- Velocidad: " . ($metricas['velocidad_habla'] ?? 'no disponible') . "\n";
                    $promptSection .= "- Pausas significativas: " . ($metricas['pausas_significativas'] ?? 0) . "\n";
                    $promptSection .= "- Titubeos (eh, um, este...): " . ($metricas['titubeos'] ?? 0) . "\n";
                    $promptSection .= "- Energía vocal: " . ($metricas['energia_vocal'] ?? 0) . "\n";
                    $promptSection .= "- Claridad de dicción: " . ($metricas['claridad_diccion'] ?? 0) . "\n";
                    $promptSection .= "\n";
                }
                
                // Indicadores psicológicos
                if (isset($audioAnalysis['indicadores_psicologicos'])) {
                    $psico = $audioAnalysis['indicadores_psicologicos'];
                    $promptSection .= "**Indicadores psicológicos detectados:**\n";
                    $promptSection .= "- Nivel de estrés: " . ($psico['nivel_estres'] ?? 0) . "\n";
                    $promptSection .= "- Nivel de confianza: " . ($psico['nivel_confianza'] ?? 0) . "\n";
                    $promptSection .= "- Coherencia emocional: " . ($psico['coherencia_emocional'] ?? 0) . "\n";
                    $promptSection .= "- Autenticidad: " . ($psico['autenticidad'] ?? 0) . "\n";
                    $promptSection .= "- Nivel de reflexión: " . ($psico['nivel_reflexion'] ?? 0) . "\n";
                    $promptSection .= "\n";
                }
                
                // Observaciones adicionales
                if (isset($audioAnalysis['observaciones']) && !empty($audioAnalysis['observaciones'])) {
                    $promptSection .= "**Observaciones adicionales:** " . $audioAnalysis['observaciones'] . "\n\n";
                }
                
                $promptSection .= "---\n\n";
            }
        } else {
            // Fallback al formato anterior si no hay análisis de audio
            if (isset($results['transcriptions']) && !empty($results['transcriptions'])) {
                $promptSection .= "### Respuestas de Audio Transcritas:\n";
                foreach ($results['transcriptions'] as $index => $transcription) {
                    $questionNumber = $index + 1;
                    $promptSection .= "**Pregunta $questionNumber:** " . ($transcription['question_text'] ?? '') . "\n";
                    $promptSection .= "**Respuesta:** " . ($transcription['transcription'] ?? 'Sin transcripción disponible') . "\n";
                    
                    if (isset($transcription['duration'])) {
                        $promptSection .= "**Duración:** {$transcription['duration']} segundos\n";
                    }
                    
                    $promptSection .= "\n";
                }
            }
        }
        
        // Add aggregated analysis indicators
        if (isset($results['analysis_indicators'])) {
            $promptSection .= "### Resumen General del Análisis:\n";
            $indicators = $results['analysis_indicators'];
            $promptSection .= "- **Total de preguntas respondidas:** " . ($indicators['total_responses'] ?? 0) . "\n";
            $promptSection .= "- **Tiempo total de respuesta:** " . ($indicators['total_duration'] ?? 0) . " segundos\n";
            $promptSection .= "- **Promedio de duración por respuesta:** " . ($indicators['avg_duration'] ?? 0) . " segundos\n";
            
            // Add prosodics summary if available
            if (isset($indicators['prosodics_summary']) && !isset($indicators['prosodics_summary']['no_analysis'])) {
                $prosodics = $indicators['prosodics_summary'];
                $promptSection .= "\n**Resumen prosódico general:**\n";
                $promptSection .= "- Emoción dominante promedio: " . ($prosodics['dominant_emotion'] ?? 'no disponible') . "\n";
                $promptSection .= "- Nivel promedio de estrés: " . ($prosodics['avg_stress_level'] ?? 0) . "\n";
                $promptSection .= "- Nivel promedio de confianza: " . ($prosodics['avg_confidence_level'] ?? 0) . "\n";
                $promptSection .= "- Total de titubeos en todas las respuestas: " . ($prosodics['total_hesitations'] ?? 0) . "\n";
                $promptSection .= "- Total de pausas significativas: " . ($prosodics['total_significant_pauses'] ?? 0) . "\n";
            }
            
            $promptSection .= "\n";
        }
        
        // Add soft skills framework
        $promptSection .= "### Dimensiones de Habilidades Blandas a Evaluar:\n";
        foreach ($this->skillDimensions as $key => $name) {
            $promptSection .= "- **$name:** Evaluar integrando contenido textual Y indicadores prosódicos/emocionales\n";
        }
        $promptSection .= "\n";
        
        return $promptSection;
    }
    
    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     *
     * @return string Formatted instructions
     */
    public function getInstructions(): string
    {
        return "INFORME PSICOLÓGICO INTEGRAL DE HABILIDADES BLANDAS - ANÁLISIS TEXTO + PROSODIA\n\n" .
               
               "Necesito un informe lo más detallado posible de esta persona, utilizando el contenido de la respuesta + prosodia. " .
               "Como si fueses un psicólogo especialista en Habilidades Blandas y Personalidad. El informe tiene que estar escrito en tono " .
               "profesional, descriptivo y teniendo en cuenta que lo lee directamente el candidato. El candidato es una persona que quiere " .
               "conocer en profundidad todas sus habilidades como punto de partida para desarrollar algunas.\n\n" .
               
               "**ESTRUCTURA REQUERIDA DEL INFORME:**\n\n" .
               
               "## RESUMEN DESCRIPTIVO DE PERSONALIDAD\n" .
               "Una descripción integral de los patrones de personalidad observados, integrando tanto las respuestas verbales como los indicadores prosódicos y emocionales.\n\n" .
               
               "## ANÁLISIS DETALLADO DE COMPETENCIAS\n" .
               "Para cada competencia, proporciona un **puntaje del 1 al 10** (siendo 10 muy alto y 1 muy pobre) + **descripción detallada** " .
               "de esa competencia en esta persona. Puedes utilizar frases que usó para ejemplificar.\n\n" .
               
               "### 1. PERSEVERANCIA (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 2. RESILIENCIA (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 3. PENSAMIENTO CRÍTICO Y ADAPTABILIDAD (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 4. REGULACIÓN EMOCIONAL (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 5. RESPONSABILIDAD (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 6. AUTOCONOCIMIENTO (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 7. MANEJO DEL ESTRÉS (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 8. ASERTIVIDAD (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 9. HABILIDAD PARA CONSTRUIR RELACIONES (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 10. CREATIVIDAD (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 11. EMPATÍA (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 12. CAPACIDAD DE INFLUENCIA Y COMUNICACIÓN (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 13. CAPACIDAD Y ESTILO DE LIDERAZGO (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 14. CURIOSIDAD Y CAPACIDAD DE APRENDIZAJE (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 15. TOLERANCIA A LA FRUSTRACIÓN (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "## PUNTOS FUERTES\n" .
               "Las competencias con más puntaje y por qué se destacan.\n\n" .
               
               "## ÁREAS A DESARROLLAR\n" .
               "Mínimo 3 o 4 competencias donde se observaron los puntajes más bajos y por qué.\n\n" .
               
               "## PROPUESTA DE RE-SKILLING PERSONALIZADA\n" .
               "Proponer recomendaciones específicas y prácticas para desarrollar los puntos débiles identificados.\n\n" .
               
               "**METODOLOGÍA DE ANÁLISIS:**\n" .
               "- Integra SIEMPRE contenido textual + indicadores prosódicos (pausas, titubeos, energía vocal, emociones, etc.)\n" .
               "- Utiliza citas directas de las respuestas cuando sea relevante\n" .
               "- Correlaciona lo que dice vs. cómo lo dice para identificar autenticidad y coherencia\n" .
               "- Considera patrones emocionales y de estrés en la voz para evaluar regulación emocional y manejo del estrés\n" .
               "- Evalúa claridad de dicción y fluidez para competencias comunicacionales\n" .
               "- Tono profesional pero accesible para el candidato";
    }
    
    /**
     * Process audio responses and extract transcription data
     */
    private function processAudioResponses(array $responses): array
    {
        $transcriptions = [];
        
        foreach ($responses as $questionId => $response) {
            $transcription = [
                'question_id' => $questionId,
                'question_text' => $this->getQuestionText($questionId),
                'transcription' => $response['transcription_text'] ?? '',
                'duration' => $response['duration_seconds'] ?? null,
                'audio_file_path' => $response['audio_file_path'] ?? null
            ];
            
            $transcriptions[] = $transcription;
        }
        
        return $transcriptions;
    }
    
    /**
     * Initialize the soft skills evaluation structure
     */
    private function initializeSoftSkillsStructure(): array
    {
        $structure = [];
        
        foreach ($this->skillDimensions as $key => $name) {
            $structure[$key] = [
                'name' => $name,
                'level' => null, // To be filled by AI analysis
                'evidence' => [], // Specific quotes/examples
                'development_notes' => null
            ];
        }
        
        return $structure;
    }
    
    /**
     * Extract analysis indicators from responses, now including audio analysis metrics
     */
    private function extractAnalysisIndicators(array $responses, array $audioAnalysis = []): array
    {
        $totalResponses = count($responses);
        $totalDuration = 0;
        $responsesWithAudio = 0;
        
        // Métricas básicas de audio
        foreach ($responses as $response) {
            if (isset($response['duration_seconds']) && $response['duration_seconds'] > 0) {
                $totalDuration += $response['duration_seconds'];
                $responsesWithAudio++;
            } elseif (isset($response['audio_file_path']) && !empty($response['audio_file_path'])) {
                // Count responses with audio files even if duration is not available
                $responsesWithAudio++;
            }
        }
        
        $avgDuration = $responsesWithAudio > 0 ? round($totalDuration / $responsesWithAudio, 2) : 0;
        
        $indicators = [
            'total_responses' => $totalResponses,
            'responses_with_audio' => $responsesWithAudio,
            'total_duration' => $totalDuration,
            'avg_duration' => $avgDuration,
            'completion_rate' => $totalResponses > 0 ? round(($responsesWithAudio / $totalResponses) * 100, 2) : 0
        ];
        
        // Agregar métricas prosódicas agregadas si hay análisis de audio
        if (!empty($audioAnalysis)) {
            $prosodicsMetrics = $this->aggregateProsodicsMetrics($audioAnalysis);
            $indicators['prosodics_summary'] = $prosodicsMetrics;
        }
        
        return $indicators;
    }
    
    /**
     * Aggregate prosodic metrics from all audio analyses
     */
    private function aggregateProsodicsMetrics(array $audioAnalysis): array
    {
        $emotionTotals = [];
        $stressLevels = [];
        $confidenceLevels = [];
        $totalTitubeos = 0;
        $totalPausas = 0;
        $validAnalyses = 0;
        
        foreach ($audioAnalysis as $analysis) {
            if (isset($analysis['analisis_emocional'])) {
                $validAnalyses++;
                
                // Sumar emociones
                foreach ($analysis['analisis_emocional'] as $emotion => $score) {
                    if (is_numeric($score)) {
                        $emotionTotals[$emotion] = ($emotionTotals[$emotion] ?? 0) + $score;
                    }
                }
                
                // Recopilar métricas psicológicas
                if (isset($analysis['indicadores_psicologicos']['nivel_estres'])) {
                    $stressLevels[] = $analysis['indicadores_psicologicos']['nivel_estres'];
                }
                
                if (isset($analysis['indicadores_psicologicos']['nivel_confianza'])) {
                    $confidenceLevels[] = $analysis['indicadores_psicologicos']['nivel_confianza'];
                }
                
                // Sumar métricas prosódicas
                if (isset($analysis['metricas_prosodicas']['titubeos'])) {
                    $totalTitubeos += $analysis['metricas_prosodicas']['titubeos'];
                }
                
                if (isset($analysis['metricas_prosodicas']['pausas_significativas'])) {
                    $totalPausas += $analysis['metricas_prosodicas']['pausas_significativas'];
                }
            }
        }
        
        if ($validAnalyses === 0) {
            return ['no_analysis' => true];
        }
        
        // Calcular promedios
        $avgEmotions = [];
        foreach ($emotionTotals as $emotion => $total) {
            $avgEmotions[$emotion] = round($total / $validAnalyses, 3);
        }
        
        return [
            'valid_analyses' => $validAnalyses,
            'avg_emotions' => $avgEmotions,
            'avg_stress_level' => !empty($stressLevels) ? round(array_sum($stressLevels) / count($stressLevels), 3) : 0,
            'avg_confidence_level' => !empty($confidenceLevels) ? round(array_sum($confidenceLevels) / count($confidenceLevels), 3) : 0,
            'total_hesitations' => $totalTitubeos,
            'total_significant_pauses' => $totalPausas,
            'dominant_emotion' => !empty($avgEmotions) ? array_keys($avgEmotions, max($avgEmotions))[0] : 'no_disponible'
        ];
    }
    
    /**
     * Generate summary for reflective questions analysis
     */
    private function generateReflectiveQuestionsSummary(array $responses, array $patientData): string
    {
        $age = $patientData['age'] ?? null;
        $ageGroup = $this->getAgeGroup($age);
        
        $summary = "Este es un análisis de habilidades blandas basado en respuestas reflexivas de audio.\n\n";
        
        $totalResponses = count($responses);
        $responsesWithAudio = 0;
        
        foreach ($responses as $response) {
            if (isset($response['transcription_text']) && !empty(trim($response['transcription_text']))) {
                $responsesWithAudio++;
            }
        }
        
        $completionRate = $totalResponses > 0 ? round(($responsesWithAudio / $totalResponses) * 100, 2) : 0;
        
        $summary .= "Completitud del cuestionario: $completionRate% ($responsesWithAudio de $totalResponses preguntas respondidas)\n";
        $summary .= "Grupo etario: $ageGroup\n";
        $summary .= "Tipo de evaluación: Análisis cualitativo de respuestas reflexivas\n\n";
        
        $summary .= "Este cuestionario evalúa 7 dimensiones clave de habilidades blandas: " . 
                   implode(", ", array_values($this->skillDimensions)) . ".\n\n";
        
        $summary .= "La interpretación se basa en el análisis de las respuestas de audio transcritas, " .
                   "considerando la profundidad de reflexión, ejemplos concretos proporcionados, " .
                   "evidencia de aprendizaje y crecimiento personal.";
        
        return $summary;
    }
    
    /**
     * Get existing audio analysis from responses (processed by async job)
     * 
     * @param array $responses Response array with potential gemini_analysis
     * @return array Audio analysis results if available
     */
    private function getExistingAudioAnalysis(array $responses): array
    {
        \Log::info('REFLECTIVE_QUESTIONS - getExistingAudioAnalysis called with ' . count($responses) . ' responses');
        $audioAnalysis = [];
        
        foreach ($responses as $questionId => $response) {
            \Log::info("REFLECTIVE_QUESTIONS - Processing question: $questionId");
            
            // Check if we have transcription and/or analysis available
            $hasTranscription = !empty($response['transcription_text']);
            $hasGeminiAnalysis = isset($response['gemini_analysis']) && !empty($response['gemini_analysis']);
            $hasAudioFile = isset($response['audio_file_path']) && !empty($response['audio_file_path']);
            
            \Log::info("REFLECTIVE_QUESTIONS - $questionId status:", [
                'has_transcription' => $hasTranscription,
                'has_gemini_analysis' => $hasGeminiAnalysis,
                'has_audio_file' => $hasAudioFile,
                'transcription_text' => $hasTranscription ? substr($response['transcription_text'], 0, 50) . '...' : 'none',
                'audio_file_path' => $response['audio_file_path'] ?? 'none'
            ]);
            
            if ($hasGeminiAnalysis) {
                // Full analysis available - use the complete Gemini analysis
                $audioAnalysis[$questionId] = $response['gemini_analysis'];
                \Log::info("REFLECTIVE_QUESTIONS - $questionId: Using full Gemini analysis");
            } else if ($hasTranscription) {
                // Only transcription available - create minimal analysis structure
                $audioAnalysis[$questionId] = [
                    'transcripcion' => $response['transcription_text'],
                    'duracion_segundos' => $response['duration_seconds'] ?? null,
                    'status' => 'transcription_only',
                    'message' => 'Análisis prosódico pendiente'
                ];
                \Log::info("REFLECTIVE_QUESTIONS - $questionId: Using transcription only");
            } else if ($hasAudioFile) {
                // Audio file exists but no analysis ready yet
                $audioAnalysis[$questionId] = [
                    'status' => 'pending',
                    'transcripcion' => '',
                    'message' => 'Análisis de audio en proceso'
                ];
                \Log::info("REFLECTIVE_QUESTIONS - $questionId: Audio file exists, analysis pending");
            } else {
                \Log::info("REFLECTIVE_QUESTIONS - $questionId: No audio data available");
            }
        }
        
        \Log::info('REFLECTIVE_QUESTIONS - getExistingAudioAnalysis returning ' . count($audioAnalysis) . ' analyses');
        return $audioAnalysis;
    }
    
    /**
     * Get question text by ID (mapping to the 7 reflective questions)
     */
    private function getQuestionText(string $questionId): string
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
        
        return $questions[$questionId] ?? 'Pregunta no encontrada';
    }
}