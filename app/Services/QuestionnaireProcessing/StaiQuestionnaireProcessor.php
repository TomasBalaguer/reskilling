<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

class StaiQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'STAI';
    
    /**
     * Calculate scores and interpretations based on questionnaire responses
     *
     * @param array $responses Raw responses from the questionnaire
     * @param array $patientData Patient demographic data (age, gender, etc.)
     * @param array $result Optional existing result to extend
     * @return array Processed results with scores, interpretations, and summaries
     */
    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        // Add patient data to results
        $result = $this->addPatientData($result, $patientData);
        // Verify we have sufficient responses

        if (empty($responses) || !isset($responses['estado']) || !isset($responses['rasgo'])) {
            $result['summary'] = 'No se proporcionaron respuestas suficientes para el cuestionario STAI.';
            return $result;
        }
        // Initialize scores and interpretations arrays
        $scores = [];
        $interpretations = [];
        $clinicalInterpretations = [];
        
        // Define direct and reverse items for each scale
        $stateItems = [
            'direct' => ['e3', 'e4', 'e6', 'e7', 'e9', 'e12', 'e13', 'e14', 'e17', 'e18'],
            'reverse' => ['e1', 'e2', 'e5', 'e8', 'e10', 'e11', 'e15', 'e16', 'e19', 'e20']
        ];
        
        $traitItems = [
            'direct' => ['r22', 'r23', 'r24', 'r25', 'r28', 'r29', 'r31', 'r32', 'r34', 'r35', 'r37', 'r38', 'r40'],
            'reverse' => ['r21', 'r26', 'r27', 'r30', 'r33', 'r36', 'r39']
        ];
        
        // Calculate State Anxiety (A/E) raw score
        $stateScore = 0;
        foreach ($stateItems['direct'] as $item) {
            if (isset($responses['estado'][$item]['answer'])) {
                $stateScore += $responses['estado'][$item]['answer'];
            }
        }
        foreach ($stateItems['reverse'] as $item) {
            if (isset($responses['estado'][$item]['answer'])) {
                // Reverse scoring (items are on a 0-3 scale where 0=Not at all, 3=Very much so)
                $stateScore += (4 - $responses['estado'][$item]['answer']);
            }
        }
        
        // Calculate Trait Anxiety (A/R) raw score
        $traitScore = 0;
        foreach ($traitItems['direct'] as $item) {
            if (isset($responses['rasgo'][$item]['answer'])) {
                $traitScore += $responses['rasgo'][$item]['answer'];
            }
        }
        
        foreach ($traitItems['reverse'] as $item) {
            if (isset($responses['rasgo'][$item]['answer'])) {
                // Reverse scoring (items are on a 0-3 scale where 0=Almost never, 3=Almost always)
                $traitScore += (4 - $responses['rasgo'][$item]['answer']);
            }
        }
        // Calculate percentiles based on normative data
        $gender = isset($patientData['gender']) ? strtolower($patientData['gender']) : 'desconocido';
        $gender = ($gender == 'masculino' || $gender == 'male' || $gender == 'm') ? 'M' : 'F';
        
        $age = isset($patientData['age']) ? (int)$patientData['age'] : 0;
        $ageGroup = 'adulto'; // Default
        // Determine age group
        if ($age < 18) {
            $ageGroup = 'adolescente';
        } elseif ($age >= 60) {
            $ageGroup = 'adulto mayor';
        }
        
        // Store demographic data used for interpretation
        $result['demographic_used'] = [
            'gender' => $gender,
            'age_group' => $ageGroup
        ];
        // Get percentiles from normative tables
        $statePercentile = $this->calculatePercentile($stateScore, $gender, $ageGroup, 'state');
        $traitPercentile = $this->calculatePercentile($traitScore, $gender, $ageGroup, 'trait');
        // Store scores
        $scores['ansiedad_estado'] = [
            'name' => 'Ansiedad Estado (A/E)',
            'raw_score' => $stateScore,
            'percentile' => $statePercentile,
            'description' => 'Evalúa cómo se siente el sujeto en el momento actual; determina los niveles de ansiedad transitorios experimentados en una situación puntual'
        ];
        
        $scores['ansiedad_rasgo'] = [
            'name' => 'Ansiedad Rasgo (A/R)',
            'raw_score' => $traitScore,
            'percentile' => $traitPercentile,
            'description' => 'Evalúa cómo se siente el sujeto habitualmente; determina los niveles de ansiedad como característica relativamente estable de personalidad'
        ];
        // Generate interpretations
        $interpretations['ansiedad_estado'] = $this->getStaiInterpretation($statePercentile, 'state');
        $interpretations['ansiedad_rasgo'] = $this->getStaiInterpretation($traitPercentile, 'trait');
        // Generate clinical interpretations
        $clinicalInterpretations = $this->generateStaiClinicalInterpretation($scores, $interpretations);
        
        // Generate summary
        $summary = $this->generateStaiSummary($scores, $interpretations);
        
        // Add all data to result
        $result['scores'] = $scores;
        $result['interpretations'] = $interpretations;
        $result['clinical_interpretations'] = $clinicalInterpretations;
        $result['summary'] = $summary;
        $result['scoring_type'] = 'STAI';
        $result['questionnaire_name'] = 'Cuestionario de Ansiedad Estado-Rasgo';
        
        return $result;
    }
    
    /**
     * Calculate percentile based on normative data
     */
    private function calculatePercentile($score, $gender, $ageGroup, $type)
    {
        // Simplified percentile tables for demonstration
        // In a real application, these would be more comprehensive and possibly stored in a database
        $percentileTables = [
            'M' => [ // Male
                'adulto' => [
                    'state' => [
                        5 => 0, 10 => 2, 15 => 5, 20 => 10, 25 => 23, 30 => 39,
                        35 => 56, 40 => 74, 45 => 85, 50 => 93, 55 => 97, 60 => 99
                    ],
                    'trait' => [
                        5 => 0, 10 => 1, 15 => 3, 20 => 8, 25 => 20, 30 => 36,
                        35 => 54, 40 => 72, 45 => 85, 50 => 93, 55 => 97, 60 => 99
                    ]
                ],
                'adulto mayor' => [
                    'state' => [
                        5 => 0, 10 => 1, 15 => 3, 20 => 8, 25 => 18, 30 => 34,
                        35 => 50, 40 => 69, 45 => 83, 50 => 91, 55 => 96, 60 => 99
                    ],
                    'trait' => [
                        5 => 0, 10 => 0, 15 => 2, 20 => 6, 25 => 15, 30 => 31,
                        35 => 48, 40 => 65, 45 => 80, 50 => 90, 55 => 95, 60 => 99
                    ]
                ]
            ],
            'F' => [ // Female
                'adulto' => [
                    'state' => [
                        5 => 0, 10 => 0, 15 => 1, 20 => 4, 25 => 12, 30 => 26,
                        35 => 45, 40 => 65, 45 => 80, 50 => 90, 55 => 96, 60 => 99
                    ],
                    'trait' => [
                        5 => 0, 10 => 0, 15 => 0, 20 => 2, 25 => 10, 30 => 25,
                        35 => 43, 40 => 63, 45 => 78, 50 => 88, 55 => 95, 60 => 99
                    ]
                ],
                'adulto mayor' => [
                    'state' => [
                        5 => 0, 10 => 0, 15 => 0, 20 => 2, 25 => 7, 30 => 18,
                        35 => 36, 40 => 56, 45 => 75, 50 => 87, 55 => 94, 60 => 98
                    ],
                    'trait' => [
                        5 => 0, 10 => 0, 15 => 0, 20 => 0, 25 => 5, 30 => 15,
                        35 => 30, 40 => 50, 45 => 70, 50 => 85, 55 => 93, 60 => 98
                    ]
                ]
            ]
        ];
        
        // Fallback to adult if age group not found
        if (!isset($percentileTables[$gender][$ageGroup])) {
            $ageGroup = 'adulto';
        }
        
        // Fallback to female if gender not found
        if (!isset($percentileTables[$gender])) {
            $gender = 'F';
        }
        
        $table = $percentileTables[$gender][$ageGroup][$type];
        
        // Find closest percentile
        $percentile = 0;
        foreach ($table as $scoreThreshold => $percentileValue) {
            if ($score <= $scoreThreshold) {
                return $percentileValue;
            }
            $percentile = $percentileValue;
        }
        
        // If higher than all thresholds, return the highest percentile
        return 99;
    }
    
    /**
     * Get interpretation based on percentile
     */
    private function getStaiInterpretation($percentile, $type)
    {
        if ($percentile < 25) {
            $level = "bajo";
            if ($type == 'state') {
                return "Nivel bajo de ansiedad situacional. En el momento de la evaluación, el sujeto se encuentra tranquilo y con pocos síntomas de tensión o aprensión.";
            } else {
                return "Nivel bajo de ansiedad como rasgo. El sujeto tiende a mantenerse tranquilo y sereno ante la mayoría de situaciones.";
            }
        } elseif ($percentile < 75) {
            $level = "medio";
            if ($type == 'state') {
                return "Nivel moderado de ansiedad situacional. El sujeto muestra síntomas de tensión y aprensión dentro de lo esperable para la situación actual.";
            } else {
                return "Nivel moderado de ansiedad como rasgo. El sujeto suele experimentar cierta tensión ante situaciones estresantes, pero dentro de los parámetros normales.";
            }
        } else {
            $level = "alto";
            if ($type == 'state') {
                return "Nivel elevado de ansiedad situacional. En el momento de la evaluación, el sujeto presenta marcados síntomas de tensión, aprensión o nerviosismo.";
            } else {
                return "Nivel elevado de ansiedad como rasgo. El sujeto tiende a percibir muchas situaciones como amenazantes y mantiene una predisposición a experimentar ansiedad de forma frecuente e intensa.";
            }
        }
    }
    
    /**
     * Generate clinical interpretations based on state and trait anxiety scores
     */
    private function generateStaiClinicalInterpretation($scores, $interpretations)
    {
        $clinicalInterpretations = [];
        
        // Extract percentiles for easier comparison
        $statePercentile = $scores['ansiedad_estado']['percentile'];
        $traitPercentile = $scores['ansiedad_rasgo']['percentile'];
        
        // Clinical significance threshold (75th percentile)
        $significanceThreshold = 75;
        
        // Check if both state and trait anxiety are elevated
        if ($statePercentile >= $significanceThreshold && $traitPercentile >= $significanceThreshold) {
            $clinicalInterpretations[] = "Los niveles elevados tanto en ansiedad estado como en ansiedad rasgo sugieren una vulnerabilidad ansiosa significativa que se manifiesta también en el momento actual, indicando posible necesidad de intervención.";
        }
        
        // Check for state/trait discrepancies
        if ($statePercentile >= $significanceThreshold && $traitPercentile < 50) {
            $clinicalInterpretations[] = "La elevada ansiedad estado con una ansiedad rasgo en rango normal/bajo sugiere una reacción ansiosa a una situación específica actual, que no es habitual en el patrón general del sujeto.";
        }
        
        if ($statePercentile < 50 && $traitPercentile >= $significanceThreshold) {
            $clinicalInterpretations[] = "La elevada ansiedad rasgo con una ansiedad estado en rango normal/bajo podría indicar: 1) buenas estrategias de afrontamiento momentáneas, 2) posible negación de la ansiedad, o 3) un entorno actualmente seguro para una persona habitualmente ansiosa.";
        }
        
        // Add specific clinical considerations based on symptom patterns
        if ($statePercentile >= 85) {
            $clinicalInterpretations[] = "El nivel muy elevado de ansiedad estado (percentil " . $statePercentile . ") podría interferir con el funcionamiento cognitivo y la toma de decisiones en el momento actual.";
        }
        
        if ($traitPercentile >= 85) {
            $clinicalInterpretations[] = "El nivel muy elevado de ansiedad rasgo (percentil " . $traitPercentile . ") sugiere una tendencia estable a experimentar ansiedad, que podría relacionarse con trastornos de ansiedad generalizada o rasgos ansiosos de personalidad.";
        }
        
        // Add note about relation between scales if there's a large discrepancy
        if (abs($statePercentile - $traitPercentile) >= 30) {
            $clinicalInterpretations[] = "La discrepancia significativa entre ansiedad estado y rasgo (diferencia de " . abs($statePercentile - $traitPercentile) . " percentiles) indica una fluctuación importante entre el nivel habitual de ansiedad y el momento actual, sugiriendo un factor situacional relevante.";
        }
        
        // If no significant findings, add a general note
        if (empty($clinicalInterpretations)) {
            if ($statePercentile < $significanceThreshold && $traitPercentile < $significanceThreshold) {
                $clinicalInterpretations[] = "Los niveles de ansiedad, tanto estado como rasgo, se encuentran dentro de rangos que no sugieren significación clínica.";
            } else {
                $clinicalInterpretations[] = "El patrón de ansiedad observado muestra algunas elevaciones que merecen seguimiento, aunque sin claros indicadores de patología ansiosa significativa en este momento.";
            }
        }
        
        return $clinicalInterpretations;
    }
    
    /**
     * Generate summary of STAI results
     */
    private function generateStaiSummary($scores, $interpretations)
    {
        // Extract key information
        $stateScore = $scores['ansiedad_estado']['raw_score'];
        $statePercentile = $scores['ansiedad_estado']['percentile'];
        $traitScore = $scores['ansiedad_rasgo']['raw_score'];
        $traitPercentile = $scores['ansiedad_rasgo']['percentile'];
        
        // Determine overall anxiety levels
        $stateLevel = $statePercentile < 25 ? "bajo" : ($statePercentile < 75 ? "moderado" : "elevado");
        $traitLevel = $traitPercentile < 25 ? "bajo" : ($traitPercentile < 75 ? "moderado" : "elevado");
        
        // Generate summary
        $summary = "El cuestionario STAI muestra un nivel {$stateLevel} de ansiedad estado (puntuación directa: {$stateScore}, percentil: {$statePercentile}), ";
        $summary .= "indicando que en el momento de la evaluación el sujeto " . ($stateLevel == "bajo" ? "se encontraba tranquilo con escasos síntomas de tensión" : 
                                                                                ($stateLevel == "moderado" ? "presentaba síntomas moderados de tensión" : 
                                                                                "experimentaba una marcada tensión, nerviosismo o aprensión"));
        
        $summary .= ". Respecto a la ansiedad rasgo, muestra un nivel {$traitLevel} (puntuación directa: {$traitScore}, percentil: {$traitPercentile}), ";
        $summary .= "lo que sugiere una tendencia " . ($traitLevel == "bajo" ? "poco frecuente a experimentar estados ansiosos en su vida cotidiana" : 
                                                    ($traitLevel == "moderado" ? "moderada a experimentar estados ansiosos ante situaciones percibidas como amenazantes" : 
                                                    "elevada a percibir diversas situaciones como amenazantes y a responder a ellas con estados de ansiedad"));
        
        // Add note about discrepancy if relevant
        if (abs($statePercentile - $traitPercentile) > 30) {
            $discrepancyDirection = $statePercentile > $traitPercentile ? 
                "la ansiedad actual supera significativamente su nivel habitual, sugiriendo una reacción a circunstancias específicas del momento" : 
                "la ansiedad actual es notablemente menor que su nivel habitual, lo que podría indicar un momento de calma relativa";
            
            $summary .= ". Destaca la discrepancia entre ansiedad estado y rasgo, donde {$discrepancyDirection}";
        }
        
        $summary .= ".";
        
        return $summary;
    }
    
    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     *
     * @param array $results Results from the calculateScores method
     * @return string Formatted prompt section
     */
    public function buildPromptSection(array $results): string
    {
        $promptSection = "";
        
        // Añadir información demográfica si está disponible
        if (isset($results['patient_data'])) {
            $promptSection .= "### Información demográfica relevante:\n";
            
            if (isset($results['patient_data']['age']) && $results['patient_data']['age'] !== 'No disponible') {
                $promptSection .= "- **Edad**: " . $results['patient_data']['age'] . " años\n";
            }
            
            if (isset($results['patient_data']['gender']) && $results['patient_data']['gender'] !== 'No especificado') {
                $promptSection .= "- **Género**: " . $results['patient_data']['gender'] . "\n";
            }
            
            if (isset($results['demographic_used'])) {
                $promptSection .= "- **Normativa utilizada**: ";
                $promptSection .= "Baremos para " . $results['demographic_used']['age_group'] . " ";
                $promptSection .= "(" . ($results['demographic_used']['gender'] === 'M' ? 'masculinos' : 'femeninos') . ")\n";
            }
            
            $promptSection .= "\n";
        }
        
        // Añadir puntuaciones de escalas del STAI
        if (isset($results['scores']) && !empty($results['scores'])) {
            $promptSection .= "### Puntuaciones de ansiedad:\n";
            
            // Ansiedad Estado
            if (isset($results['scores']['ansiedad_estado'])) {
                $score = $results['scores']['ansiedad_estado'];
                $promptSection .= "- **" . ($score['name'] ?? 'Ansiedad Estado (A/E)') . "**: ";
                $promptSection .= "Puntuación directa: " . $score['raw_score'];
                
                if (isset($score['percentile'])) {
                    $promptSection .= " (Percentil " . $score['percentile'] . ")";
                }
                
                $promptSection .= "\n";
                $promptSection .= "  - " . ($score['description'] ?? 'Mide el nivel actual de tensión y aprensión') . "\n";
                
                if (isset($results['interpretations']['ansiedad_estado'])) {
                    $promptSection .= "  - **Interpretación**: " . $results['interpretations']['ansiedad_estado'] . "\n";
                }
                
                $promptSection .= "\n";
            }
            
            // Ansiedad Rasgo
            if (isset($results['scores']['ansiedad_rasgo'])) {
                $score = $results['scores']['ansiedad_rasgo'];
                $promptSection .= "- **" . ($score['name'] ?? 'Ansiedad Rasgo (A/R)') . "**: ";
                $promptSection .= "Puntuación directa: " . $score['raw_score'];
                
                if (isset($score['percentile'])) {
                    $promptSection .= " (Percentil " . $score['percentile'] . ")";
                }
                
                $promptSection .= "\n";
                $promptSection .= "  - " . ($score['description'] ?? 'Mide la propensión ansiosa relativamente estable') . "\n";
                
                if (isset($results['interpretations']['ansiedad_rasgo'])) {
                    $promptSection .= "  - **Interpretación**: " . $results['interpretations']['ansiedad_rasgo'] . "\n";
                }
                
                $promptSection .= "\n";
            }
        }
        
        // Añadir interpretaciones clínicas específicas
        if (isset($results['clinical_interpretations']) && !empty($results['clinical_interpretations'])) {
            $promptSection .= "### Interpretaciones clínicas específicas:\n";
            foreach ($results['clinical_interpretations'] as $interpretation) {
                $promptSection .= "- " . $interpretation . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Añadir resumen global
        if (isset($results['summary']) && !empty($results['summary'])) {
            $promptSection .= "### Resumen global del STAI:\n";
            $promptSection .= $results['summary'] . "\n\n";
        }
        
        return $promptSection;
    }
    
    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     *
     * @return string Formatted instructions
     */
    public function getInstructions(): string
    {
        $defaultInstructions = "Basándote en los resultados del Cuestionario de Ansiedad Estado-Rasgo (STAI), proporciona:\n\n" .
               "1. Una interpretación clínica detallada de los niveles de ansiedad estado (situacional) y ansiedad rasgo (disposicional).\n" .
               "2. Análisis de la relación entre ambas escalas y su significado para el funcionamiento actual del paciente.\n" .
               "3. Evaluación de la posible interferencia de la ansiedad en diferentes áreas de funcionamiento (cognitivo, social, laboral).\n" .
               "4. Consideración de factores que podrían explicar el patrón observado, incluyendo posibles estresores actuales.\n" .
               "5. Recomendaciones específicas para el manejo de la ansiedad adaptadas al perfil del paciente.\n" .
               "6. Evaluación de la posible relación con otros trastornos psicológicos si el nivel de ansiedad es clínicamente significativo.";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 