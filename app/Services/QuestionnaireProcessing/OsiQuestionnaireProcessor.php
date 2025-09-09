<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for OSI (Occupational Stress Inventory) questionnaire
 */
class OsiQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'OSI';

    /**
     * Score mappings for different response types
     */
    protected $scoreMappings = [
        'NUNCA' => 1,
        'A_VECES' => 2,
        'A_MENUDO' => 3,
        'MUY_A_MENUDO' => 4,
        'SIEMPRE' => 5,
        'ALGUNAS_VECES' => 2  // Alias for A_VECES
    ];

    /**
     * Definition of scales and subscales
     */
    protected $scales = [
        'orq' => [
            'name' => 'Cuestionario de Roles Ocupacionales (ORQ)',
            'subscales' => [
                'sobrecarga_ocupacional' => [
                    'name' => 'Sobrecarga ocupacional (RO)',
                    'items' => ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9', 'q10'],
                    'inverse_items' => ['q5', 'q6']
                ],
                'insuficiencia_rol' => [
                    'name' => 'Insuficiencia del rol (RI)',
                    'items' => ['q11', 'q12', 'q13', 'q14', 'q15', 'q16', 'q17', 'q18', 'q19', 'q20'],
                    'inverse_items' => ['q11', 'q12', 'q14', 'q15', 'q16', 'q17', 'q19']
                ],
                'ambiguedad_rol' => [
                    'name' => 'Ambigüedad del rol (RA)',
                    'items' => ['q21', 'q22', 'q23', 'q24', 'q25', 'q26', 'q27', 'q28', 'q29', 'q30'],
                    'inverse_items' => ['q21', 'q22', 'q24', 'q25', 'q27', 'q28', 'q29', 'q30']
                ],
                'fronteras_rol' => [
                    'name' => 'Fronteras del rol (RB)',
                    'items' => ['q31', 'q32', 'q33', 'q34', 'q35', 'q36', 'q37', 'q38', 'q39', 'q40'],
                    'inverse_items' => ['q34', 'q35', 'q37', 'q38', 'q40']
                ],
                'responsabilidad' => [
                    'name' => 'Responsabilidad (R)',
                    'items' => ['q41', 'q42', 'q43', 'q44', 'q45', 'q46', 'q47', 'q48', 'q49', 'q50'],
                    'inverse_items' => ['q50']
                ],
                'ambiente_fisico' => [
                    'name' => 'Ambiente físico (PE)',
                    'items' => ['q51', 'q52', 'q53', 'q54', 'q55', 'q56', 'q57', 'q58', 'q59', 'q60']
                ]
            ]
        ],
        'psq' => [
            'name' => 'Cuestionario de Tensión Personal (PSQ)',
            'subscales' => [
                'tension_vocacional' => [
                    'name' => 'Tensión vocacional (VS)',
                    'items' => ['q61', 'q62', 'q63', 'q64', 'q65', 'q66', 'q67', 'q68', 'q69', 'q70'],
                    'inverse_items' => ['q66', 'q68', 'q69']
                ],
                'tension_psicologica' => [
                    'name' => 'Tensión psicológica (PSY)',
                    'items' => ['q71', 'q72', 'q73', 'q74', 'q75'],
                    'inverse_items' => ['q74']
                ],
                'tension_interpersonal' => [
                    'name' => 'Tensión interpersonal (IS)',
                    'items' => ['q76', 'q77', 'q78', 'q79', 'q80']
                ],
                'tension_fisica' => [
                    'name' => 'Tensión física (PHS)',
                    'items' => ['q81', 'q82', 'q83', 'q84', 'q85']
                ]
            ]
        ],
        'prq' => [
            'name' => 'Cuestionario de Recursos Personales (PRQ)',
            'subscales' => [
                'recreacion' => [
                    'name' => 'Recreación (RE)',
                    'items' => ['q86', 'q87', 'q88', 'q89', 'q90', 'q91', 'q92', 'q93', 'q94', 'q95']
                ],
                'autocuidado' => [
                    'name' => 'Autocuidado (SC)',
                    'items' => ['q96', 'q97', 'q98', 'q99', 'q100', 'q101', 'q102', 'q103', 'q104', 'q105']
                ],
                'soporte_social' => [
                    'name' => 'Soporte Social (SS)',
                    'items' => ['q106', 'q107', 'q108', 'q109', 'q110']
                ],
                'afrontamiento_racional' => [
                    'name' => 'Afrontamiento racional/cognitivo (RC)',
                    'items' => ['q111', 'q112', 'q113', 'q114', 'q115']
                ]
            ]
        ]
    ];

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
        // Initialize scores array
        $scores = [];
        $rawScores = [];

        // Process each main scale (ORQ, PSQ, PRQ)
        foreach ($this->scales as $scaleKey => $scale) {
            $scores[$scaleKey] = [
                'name' => $scale['name'],
                'subscales' => []
            ];

            // Process each subscale
            foreach ($scale['subscales'] as $subscaleKey => $subscale) {
                $sum = 0;
                $validItems = 0;

                // Calculate raw score for each item in the subscale
                foreach ($subscale['items'] as $item) {
                    $section = $this->determineSection($item);
                    if (isset($responses[$section][$item]['answer'])) {
                        $answer = $responses[$section][$item]['answer'];
                        $score = $this->scoreMappings[$answer] ?? 0;

                        // Invert score if necessary
                        if (isset($subscale['inverse_items']) && in_array($item, $subscale['inverse_items'])) {
                            $score = 6 - $score; // Invert 1-5 scale to 5-1
                        }

                        $sum += $score;
                        $validItems++;
                    }
                }

                // Store raw score
                $rawScores[$subscaleKey] = $sum;

                // Calculate percentile based on gender
                $percentile = $this->calculatePercentile($sum, $this->getPercentileRanges($patientData['gender'] ?? null));

                $scores[$scaleKey]['subscales'][$subscaleKey] = [
                    'name' => $subscale['name'],
                    'raw_score' => $sum,
                    'percentile' => $percentile,
                    'interpretation' => $this->interpretSubscale($subscaleKey, $percentile)
                ];
            }
        }

        // Generate interpretations and clinical implications
        $interpretations = $this->generateInterpretations($scores);
        $clinicalImplications = $this->generateClinicalImplications($scores);

        // Generate summary
        $summary = $this->generateSummary($scores);

        // Prepare final result
        $result['scores'] = $scores;
        $result['interpretations'] = $interpretations;
        $result['clinical_implications'] = $clinicalImplications;
        $result['summary'] = $summary;
        $result['scoring_type'] = 'OSI';
        $result['questionnaire_name'] = 'Inventario de Estrés Ocupacional (OSI)';

        return $result;
    }

    /**
     * Determine which section an item belongs to
     */
    private function determineSection($item): string
    {
        $itemNumber = intval(substr($item, 1));
        if ($itemNumber <= 60) {
            return 'primera_seccion';
        } elseif ($itemNumber <= 75) {
            return 'segunda_seccion';
        } else {
            return 'tercera_seccion';
        }
    }

    /**
     * Calculate the percentile for a given raw score
     *
     * @param int $rawScore The raw score to convert
     * @param array $percentileRanges Array of percentile ranges for the specific scale
     * @return int The calculated percentile
     */
    private function calculatePercentile(int $rawScore, array $percentileRanges): int
    {
        // Initialize ranges with the input array
        $ranges = $percentileRanges;
        $subscaleKey = array_key_first($ranges);
        
        // If we received the full array of subscales, get the specific subscale's ranges
        if ($subscaleKey !== null && isset($ranges[$subscaleKey]) && is_array($ranges[$subscaleKey])) {
            $ranges = $ranges[$subscaleKey];
        }

        // Sort percentiles in ascending order
        $percentiles = array_keys($ranges);
        sort($percentiles);
        
        // If score is less than or equal to the lowest percentile score
        if ($rawScore <= $ranges[$percentiles[0]]) {
            return (int) $percentiles[0];
        }

        // If score is greater than or equal to the highest percentile score
        if ($rawScore >= $ranges[end($percentiles)]) {
            return (int) end($percentiles);
        }

        // Find the two closest percentile points and interpolate
        for ($i = 0; $i < count($percentiles) - 1; $i++) {
            $lowerPercentile = (int) $percentiles[$i];
            $upperPercentile = (int) $percentiles[$i + 1];
            
            $lowerScore = $ranges[$percentiles[$i]];
            $upperScore = $ranges[$percentiles[$i + 1]];
            
            if ($rawScore > $lowerScore && $rawScore <= $upperScore) {
                // Linear interpolation formula: 
                // percentile = p1 + (score - s1) * (p2 - p1) / (s2 - s1)
                return (int) round($lowerPercentile + 
                    ($rawScore - $lowerScore) * 
                    ($upperPercentile - $lowerPercentile) / 
                    ($upperScore - $lowerScore)
                );
            }
        }

        // Fallback (should never reach here if data is valid)
        return 50;
    }

    /**
     * Get the appropriate percentile ranges based on gender
     *
     * @param string|null $gender The gender ('M', 'F', or null for general)
     * @return array The appropriate percentile ranges
     */
    private function getPercentileRanges(?string $gender): array
    {
        return match (strtoupper($gender)) {
            'M' => $this->getMalePercentileRanges(),
            'F' => $this->getFemalePercentileRanges(),
            default => $this->getGeneralPercentileRanges(),
        };
    }

    /**
     * Interpret subscale score based on percentile
     */
    private function interpretSubscale($subscale, $percentile): string
    {
        if ($percentile >= 98) {
            return 'Muy alto';
        } elseif ($percentile > 75) {
            return 'Alto';
        } elseif ($percentile >= 25) {
            return 'Normal';
        } elseif ($percentile > 2) {
            return 'Bajo';
        } else {
            return 'Muy bajo';
        }
    }

    /**
     * Generate interpretations based on scores
     */
    private function generateInterpretations($scores): array
    {
        $interpretations = [];

        foreach ($scores as $scaleKey => $scale) {
            foreach ($scale['subscales'] as $subscaleKey => $subscale) {
                $interpretations[$subscaleKey] = $this->getDetailedInterpretation($subscaleKey, $subscale['percentile']);
            }
        }

        return $interpretations;
    }

    /**
     * Get detailed interpretation for a subscale
     */
    private function getDetailedInterpretation($subscale, $percentile): string
    {
        $interpretationMap = [
            'sobrecarga_ocupacional' => [
                'high' => 'Alta sobrecarga ocupacional, indicando exceso de demandas laborales y dificultad para manejarlas.',
                'normal' => 'Nivel normal de carga laboral, con adecuado manejo de las demandas del trabajo.',
                'low' => 'Baja sobrecarga ocupacional, sugiriendo un buen balance entre demandas y recursos.'
            ],
            // Add interpretations for other subscales...
        ];

        if ($percentile > 75) {
            return $interpretationMap[$subscale]['high'] ?? 'Nivel alto';
        } elseif ($percentile >= 25) {
            return $interpretationMap[$subscale]['normal'] ?? 'Nivel normal';
        } else {
            return $interpretationMap[$subscale]['low'] ?? 'Nivel bajo';
        }
    }

    /**
     * Generate clinical implications based on scores
     */
    private function generateClinicalImplications($scores): array
    {
        $implications = [];

        // Add clinical implications based on score patterns
        // Example:
        if ($this->hasHighStressPattern($scores)) {
            $implications[] = 'El patrón de puntuaciones sugiere un alto nivel de estrés ocupacional que requiere intervención.';
        }

        return $implications;
    }

    /**
     * Check if scores indicate a high stress pattern
     */
    private function hasHighStressPattern($scores): bool
    {
        $highStressCount = 0;
        foreach ($scores as $scale) {
            foreach ($scale['subscales'] as $subscale) {
                if ($subscale['percentile'] > 75) {
                    $highStressCount++;
                }
            }
        }

        return $highStressCount >= 3;
    }

    /**
     * Generate summary based on scores
     */
    private function generateSummary($scores): string
    {
        // Analyze overall pattern
        $stressLevel = $this->analyzeStressLevel($scores);
        $resourceLevel = $this->analyzeResourceLevel($scores);

        return "El evaluado presenta un nivel de estrés ocupacional {$stressLevel} con un nivel {$resourceLevel} de recursos de afrontamiento. " .
               $this->getSummaryRecommendation($stressLevel, $resourceLevel);
    }

    /**
     * Analyze overall stress level
     */
    private function analyzeStressLevel($scores): string
    {
        $stressScores = array_merge(
            $scores['orq']['subscales'] ?? [],
            $scores['psq']['subscales'] ?? []
        );

        $highStressCount = 0;
        foreach ($stressScores as $score) {
            if ($score['percentile'] > 75) {
                $highStressCount++;
            }
        }

        if ($highStressCount >= 4) {
            return 'alto';
        } elseif ($highStressCount >= 2) {
            return 'moderado';
        } else {
            return 'bajo';
        }
    }

    /**
     * Analyze resource level
     */
    private function analyzeResourceLevel($scores): string
    {
        $resourceScores = $scores['prq']['subscales'] ?? [];
        $highResourceCount = 0;

        foreach ($resourceScores as $score) {
            if ($score['percentile'] > 75) {
                $highResourceCount++;
            }
        }

        if ($highResourceCount >= 3) {
            return 'alto';
        } elseif ($highResourceCount >= 1) {
            return 'moderado';
        } else {
            return 'bajo';
        }
    }

    /**
     * Get summary recommendation based on stress and resource levels
     */
    private function getSummaryRecommendation($stressLevel, $resourceLevel): string
    {
        if ($stressLevel === 'alto' && $resourceLevel === 'bajo') {
            return 'Se recomienda intervención inmediata para desarrollar recursos de afrontamiento y reducir estresores.';
        } elseif ($stressLevel === 'alto' && $resourceLevel === 'moderado') {
            return 'Se sugiere fortalecer los recursos de afrontamiento existentes y desarrollar estrategias adicionales.';
        } elseif ($stressLevel === 'moderado' && $resourceLevel === 'bajo') {
            return 'Se recomienda desarrollar recursos de afrontamiento preventivos.';
        } else {
            return 'Se sugiere mantener y potenciar las estrategias actuales de manejo del estrés.';
        }
    }

    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     */
    public function buildPromptSection(array $questionnaireResults): string
    {
        $promptSection = "";
        // Añadir estructura de dominios del OSI
        if (isset($questionnaireResults['scores']) && !empty($questionnaireResults['scores'])) {
            // Sección ORQ - Estrés Ocupacional
            if (isset($questionnaireResults['scores']['orq'])) {
                $promptSection .= "### 1. Cuestionario de Roles Ocupacionales (ORQ):\n";
                $promptSection .= "Mide estresores relacionados con el trabajo y el ambiente laboral.\n\n";
                foreach ($questionnaireResults['scores']['orq']['subscales'] as $scale => $score) {
                    if (is_array($score)) {
                        $promptSection .= "- **" . ($score['name'] ?? $scale) . "**: " . $score['raw_score'];
                        if (isset($questionnaireResults['percentiles'][$scale])) {
                            $promptSection .= " (Percentil " . $questionnaireResults['percentiles'][$scale] . ")";
                        }
                        if (isset($questionnaireResults['interpretations']['orq'][$scale])) {
                            $promptSection .= " - " . $questionnaireResults['interpretations']['orq'][$scale];
                        }
                        $promptSection .= "\n";
                    }
                }
                $promptSection .= "\n";
            }
            
            // Sección PSQ - Tensión Psicológica
            if (isset($questionnaireResults['scores']['psq'])) {
                $promptSection .= "### 2. Cuestionario de Tensión Personal (PSQ):\n";
                $promptSection .= "Mide la tensión o strain experimentada por la persona.\n\n";
                
                foreach ($questionnaireResults['scores']['psq']['subscales'] as $scale => $score) {
                    if (is_array($score)) {
                        $promptSection .= "- **" . ($score['name'] ?? $scale) . "**: " . $score['raw_score'];
                        if (isset($questionnaireResults['percentiles'][$scale])) {
                            $promptSection .= " (Percentil " . $questionnaireResults['percentiles'][$scale] . ")";
                        }
                        if (isset($questionnaireResults['interpretations']['psq'][$scale])) {
                            $promptSection .= " - " . $questionnaireResults['interpretations']['psq'][$scale];
                        }
                        $promptSection .= "\n";
                    }
                }
                $promptSection .= "\n";
            }
            
            // Sección PRQ - Recursos de Afrontamiento
            if (isset($questionnaireResults['scores']['prq'])) {
                $promptSection .= "### 3. Cuestionario de Recursos Personales (PRQ):\n";
                $promptSection .= "Mide recursos que ayudan a moderar los efectos del estrés y la tensión.\n\n";
                
                foreach ($questionnaireResults['scores']['prq']['subscales'] as $scale => $score) {
                    if (is_array($score)) {
                        $promptSection .= "- **" . ($score['name'] ?? $scale) . "**: " . $score['raw_score'];
                        if (isset($questionnaireResults['percentiles'][$scale])) {
                            $promptSection .= " (Percentil " . $questionnaireResults['percentiles'][$scale] . ")";
                        }
                        if (isset($questionnaireResults['interpretations']['prq'][$scale])) {
                            $promptSection .= " - " . $questionnaireResults['interpretations']['prq'][$scale];
                        }
                        $promptSection .= "\n";
                    }
                }
                $promptSection .= "\n";
            }
        }
        
        // Añadir interpretaciones clínicas específicas del OSI
        if (isset($questionnaireResults['clinical_interpretations']) && !empty($questionnaireResults['clinical_interpretations'])) {
            $promptSection .= "### Interpretaciones clínicas específicas:\n";
            foreach ($questionnaireResults['clinical_interpretations'] as $interpretation) {
                $promptSection .= "- " . $interpretation . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Añadir resumen global
        if (isset($questionnaireResults['summary']) && !empty($questionnaireResults['summary'])) {
            $promptSection .= "### Resumen global del OSI:\n";
            $promptSection .= $questionnaireResults['summary'] . "\n\n";
        }
        
        return $promptSection;
    }

    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     */
    public function getInstructions(): string
    {
        $defaultInstructions = "Basándote en los resultados del Inventario de Estrés Ocupacional (OSI), proporciona:\n\n" .
                "1. Una interpretación clínica detallada que integre los tres dominios principales (estrés ocupacional, tensión psicológica y recursos de afrontamiento).\n" .
                "2. Análisis de la interacción entre estresores laborales y los recursos que posee el individuo para afrontarlos.\n" .
                "3. Identificación de áreas críticas donde hay desequilibrio entre demandas y recursos.\n" .
                "4. Evaluación del impacto del estrés ocupacional en la vida personal, incluyendo salud física, bienestar emocional y relaciones interpersonales.\n" .
                "5. Recomendaciones específicas para intervenciones orientadas a:\n" .
                "   - Reducir estresores ocupacionales significativos.\n" .
                "   - Manejar la tensión psicológica resultante.\n" .
                "   - Fortalecer recursos de afrontamiento deficitarios.\n" .
                "6. Pronóstico sobre el riesgo de problemas relacionados con el estrés laboral.\n";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }

    /**
     * Get general percentile ranges (mixed gender population)
     */
    private function getGeneralPercentileRanges(): array
    {
        return [
            'sobrecarga_ocupacional' => [
                1 => 11, 5 => 14, 15 => 17, 25 => 19, 35 => 21, 50 => 25,
                65 => 29, 75 => 31, 85 => 34, 95 => 37, 99 => 45
            ],
            'insuficiencia_rol' => [
                1 => 13, 5 => 16, 15 => 20, 25 => 23, 35 => 26, 50 => 30,
                65 => 33, 75 => 36, 85 => 38, 95 => 45, 99 => 49
            ],
            'ambiguedad_rol' => [
                1 => 10, 5 => 14, 15 => 16, 25 => 18, 35 => 21, 50 => 23,
                65 => 26, 75 => 27, 85 => 29, 95 => 34, 99 => 40
            ],
            'fronteras_rol' => [
                1 => 12, 5 => 14, 15 => 18, 25 => 20, 35 => 22, 50 => 25,
                65 => 27, 75 => 30, 85 => 33, 95 => 37, 99 => 43
            ],
            'responsabilidad' => [
                1 => 12, 5 => 16, 15 => 19, 25 => 21, 35 => 22, 50 => 25,
                65 => 27, 75 => 30, 85 => 32, 95 => 34, 99 => 40
            ],
            'ambiente_fisico' => [
                1 => 10, 5 => 10, 15 => 11, 25 => 13, 35 => 13, 50 => 15,
                65 => 18, 75 => 20, 85 => 23, 95 => 30, 99 => 43
            ],
            'tension_vocacional' => [
                1 => 11, 5 => 13, 15 => 15, 25 => 16, 35 => 17, 50 => 20,
                65 => 21, 75 => 22, 85 => 24, 95 => 27, 99 => 33
            ],
            'tension_psicologica' => [
                1 => 12, 5 => 14, 15 => 16, 25 => 19, 35 => 21, 50 => 23,
                65 => 25, 75 => 27, 85 => 29, 95 => 35, 99 => 40
            ],
            'tension_interpersonal' => [
                1 => 14, 5 => 17, 15 => 20, 25 => 21, 35 => 22, 50 => 24,
                65 => 27, 75 => 28, 85 => 30, 95 => 33, 99 => 37
            ],
            'tension_fisica' => [
                1 => 10, 5 => 13, 15 => 15, 25 => 17, 35 => 19, 50 => 22,
                65 => 25, 75 => 27, 85 => 31, 95 => 37, 99 => 44
            ],
            'recreacion' => [
                1 => 12, 5 => 17, 15 => 20, 25 => 22, 35 => 23, 50 => 25,
                65 => 28, 75 => 30, 85 => 32, 95 => 36, 99 => 41
            ],
            'autocuidado' => [
                1 => 11, 5 => 14, 15 => 18, 25 => 19, 35 => 20, 50 => 22,
                65 => 24, 75 => 26, 85 => 29, 95 => 33, 99 => 38
            ],
            'soporte_social' => [
                1 => 19, 5 => 27, 15 => 34, 25 => 37, 35 => 39, 50 => 41,
                65 => 43, 75 => 45, 85 => 46, 95 => 48, 99 => 50
            ],
            'afrontamiento_racional' => [
                1 => 18, 5 => 22, 15 => 27, 25 => 29, 35 => 31, 50 => 33,
                65 => 36, 75 => 38, 85 => 40, 95 => 42, 99 => 47
            ]
        ];
    }

    /**
     * Get female-specific percentile ranges
     */
    private function getFemalePercentileRanges(): array
    {
        return [
            'sobrecarga_ocupacional' => [
                1 => 12, 5 => 14, 15 => 17, 25 => 18, 35 => 20, 50 => 25,
                65 => 29, 75 => 31, 85 => 34, 95 => 39, 99 => 45
            ],
            'insuficiencia_rol' => [
                1 => 10, 5 => 16, 15 => 19, 25 => 23, 35 => 26, 50 => 30,
                65 => 34, 75 => 37, 85 => 39, 95 => 45, 99 => 49
            ],
            'ambiguedad_rol' => [
                1 => 11, 5 => 14, 15 => 16, 25 => 18, 35 => 20, 50 => 23,
                65 => 25, 75 => 27, 85 => 29, 95 => 36, 99 => 41
            ],
            'fronteras_rol' => [
                1 => 10, 5 => 15, 15 => 18, 25 => 20, 35 => 22, 50 => 25,
                65 => 27, 75 => 29, 85 => 32, 95 => 37, 99 => 42
            ],
            'responsabilidad' => [
                1 => 10, 5 => 15, 15 => 18, 25 => 20, 35 => 22, 50 => 25,
                65 => 27, 75 => 29, 85 => 32, 95 => 37, 99 => 42
            ],
            'ambiente_fisico' => [
                1 => 10, 5 => 10, 15 => 11, 25 => 13, 35 => 13, 50 => 15,
                65 => 17, 75 => 19, 85 => 22, 95 => 28, 99 => 39
            ],
            'tension_vocacional' => [
                1 => 11, 5 => 13, 15 => 15, 25 => 16, 35 => 17, 50 => 20,
                65 => 21, 75 => 22, 85 => 24, 95 => 27, 99 => 33
            ],
            'tension_psicologica' => [
                1 => 12, 5 => 14, 15 => 17, 25 => 19, 35 => 21, 50 => 23,
                65 => 26, 75 => 27, 85 => 30, 95 => 36, 99 => 40
            ],
            'tension_interpersonal' => [
                1 => 13, 5 => 17, 15 => 19, 25 => 21, 35 => 22, 50 => 25,
                65 => 28, 75 => 29, 85 => 30, 95 => 33, 99 => 39
            ],
            'tension_fisica' => [
                1 => 10, 5 => 13, 15 => 16, 25 => 19, 35 => 21, 50 => 23,
                65 => 27, 75 => 29, 85 => 32, 95 => 38, 99 => 44
            ],
            'recreacion' => [
                1 => 12, 5 => 16, 15 => 19, 25 => 21, 35 => 23, 50 => 24,
                65 => 26, 75 => 28, 85 => 31, 95 => 37, 99 => 43
            ],
            'autocuidado' => [
                1 => 12, 5 => 15, 15 => 18, 25 => 19, 35 => 20, 50 => 22,
                65 => 24, 75 => 26, 85 => 29, 95 => 33, 99 => 38
            ],
            'soporte_social' => [
                1 => 19, 5 => 30, 15 => 34, 25 => 37, 35 => 39, 50 => 41,
                65 => 43, 75 => 44, 85 => 46, 95 => 48, 99 => 50
            ],
            'afrontamiento_racional' => [
                1 => 15, 5 => 21, 15 => 26, 25 => 28, 35 => 30, 50 => 32,
                65 => 35, 75 => 37, 85 => 39, 95 => 42, 99 => 46
            ]
        ];
    }

    /**
     * Get male-specific percentile ranges
     */
    private function getMalePercentileRanges(): array
    {
        return [
            'sobrecarga_ocupacional' => [
                1 => 10, 5 => 13, 15 => 18, 25 => 20, 35 => 23, 50 => 26,
                65 => 28, 75 => 31, 85 => 34, 95 => 36, 99 => 43
            ],
            'insuficiencia_rol' => [
                1 => 13, 5 => 16, 15 => 21, 25 => 22, 35 => 25, 50 => 30,
                65 => 32, 75 => 35, 85 => 38, 95 => 45, 99 => 47
            ],
            'ambiguedad_rol' => [
                1 => 10, 5 => 15, 15 => 18, 25 => 20, 35 => 22, 50 => 24,
                65 => 26, 75 => 27, 85 => 29, 95 => 34, 99 => 40
            ],
            'fronteras_rol' => [
                1 => 12, 5 => 14, 15 => 17, 25 => 20, 35 => 21, 50 => 25,
                65 => 27, 75 => 30, 85 => 33, 95 => 39, 99 => 48
            ],
            'responsabilidad' => [
                1 => 12, 5 => 16, 15 => 19, 25 => 21, 35 => 23, 50 => 26,
                65 => 29, 75 => 31, 85 => 32, 95 => 37, 99 => 40
            ],
            'ambiente_fisico' => [
                1 => 10, 5 => 10, 15 => 11, 25 => 12, 35 => 14, 50 => 16,
                65 => 18, 75 => 22, 85 => 25, 95 => 30, 99 => 43
            ],
            'tension_vocacional' => [
                1 => 11, 5 => 12, 15 => 14, 25 => 16, 35 => 17, 50 => 19,
                65 => 21, 75 => 22, 85 => 24, 95 => 28, 99 => 40
            ],
            'tension_psicologica' => [
                1 => 13, 5 => 14, 15 => 15, 25 => 17, 35 => 20, 50 => 22,
                65 => 24, 75 => 26, 85 => 29, 95 => 33, 99 => 42
            ],
            'tension_interpersonal' => [
                1 => 15, 5 => 18, 15 => 20, 25 => 21, 35 => 22, 50 => 24,
                65 => 26, 75 => 27, 85 => 28, 95 => 34, 99 => 37
            ],
            'tension_fisica' => [
                1 => 10, 5 => 12, 15 => 14, 25 => 16, 35 => 17, 50 => 20,
                65 => 22, 75 => 25, 85 => 27, 95 => 35, 99 => 42
            ],
            'recreacion' => [
                1 => 14, 5 => 18, 15 => 21, 25 => 22, 35 => 24, 50 => 26,
                65 => 29, 75 => 31, 85 => 33, 95 => 36, 99 => 38
            ],
            'autocuidado' => [
                1 => 10, 5 => 14, 15 => 17, 25 => 18, 35 => 20, 50 => 22,
                65 => 24, 75 => 26, 85 => 28, 95 => 33, 99 => 40
            ],
            'soporte_social' => [
                1 => 19, 5 => 27, 15 => 34, 25 => 37, 35 => 39, 50 => 41,
                65 => 43, 75 => 45, 85 => 46, 95 => 48, 99 => 50
            ],
            'afrontamiento_racional' => [
                1 => 19, 5 => 23, 15 => 28, 25 => 30, 35 => 32, 50 => 35,
                65 => 37, 75 => 39, 85 => 40, 95 => 43, 99 => 48
            ]
        ];
    }
} 