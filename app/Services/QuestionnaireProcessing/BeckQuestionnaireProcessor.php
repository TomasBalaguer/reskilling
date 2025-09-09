<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for Beck Depression Inventory-II (BDI-II) questionnaire
 */
class BeckQuestionnaireProcessor extends BaseQuestionnaireProcessor
{

    use HasQuestionnairePrompt;
    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'BECK';
    
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
        if (!isset($responses['sintomas_depresion']) || empty($responses['sintomas_depresion'])) {
            $result['summary'] = 'No se proporcionaron respuestas suficientes para el BDI-II.';
            return $result;
        }

        // Initialize scores array
        $scores = [
            'total' => [
                'name' => 'Puntuación Total',
                'description' => 'Suma total de todos los ítems del BDI-II',
                'raw_score' => 0
            ],
            'cognitive_affective' => [
                'name' => 'Dimensión Cognitivo-Afectiva',
                'description' => 'Ítems 1-13: tristeza, pesimismo, fracaso, pérdida de placer, culpa, castigo, autodesprecio, autocrítica, pensamientos suicidas, llanto, agitación, pérdida de interés, indecisión',
                'raw_score' => 0
            ],
            'somatic' => [
                'name' => 'Dimensión Somática',
                'description' => 'Ítems 14-21: cambios en autovaloración, pérdida de energía, cambios en el sueño, irritabilidad, cambios en el apetito, dificultad de concentración, cansancio, pérdida de interés en el sexo',
                'raw_score' => 0
            ]
        ];

        // Calculate raw scores
        $totalScore = 0;
        $cognitiveAffectiveScore = 0;
        $somaticScore = 0;

        foreach ($responses['sintomas_depresion'] as $qNum => $qData) {
            $questionNum = (int)substr($qNum, 1);
            $answer = $qData['answer'];
            
            // Handle special cases for sleep and appetite changes
            if ($questionNum == 16 || $questionNum == 18) {
                // Extract numeric value from answers like "1a" or "1b"
                $numericValue = (int)substr($answer, 0, 1);
                $totalScore += $numericValue;
                
                if ($questionNum <= 13) {
                    $cognitiveAffectiveScore += $numericValue;
                } else {
                    $somaticScore += $numericValue;
                }
            } else {
                // Convert string answer to integer
                $numericValue = (int)$answer;
                $totalScore += $numericValue;
                
                if ($questionNum <= 13) {
                    $cognitiveAffectiveScore += $numericValue;
                } else {
                    $somaticScore += $numericValue;
                }
            }
        }

        // Store raw scores
        $scores['total']['raw_score'] = $totalScore;
        $scores['cognitive_affective']['raw_score'] = $cognitiveAffectiveScore;
        $scores['somatic']['raw_score'] = $somaticScore;

        // Generate severity level interpretation
        $severityLevel = $this->getSeverityLevel($totalScore);
        
        // Generate interpretations
        $interpretations = [
            'severity' => $severityLevel,
            'dimensions' => [
                'cognitive_affective' => $this->interpretDimensionScore($cognitiveAffectiveScore, 'cognitive_affective'),
                'somatic' => $this->interpretDimensionScore($somaticScore, 'somatic')
            ]
        ];

        // Generate clinical interpretations
        $clinicalInterpretations = $this->generateClinicalInterpretations($scores, $responses);

        // Generate summary
        $summary = $this->generateBeckSummary($scores, $interpretations);

        // Store results
        $result['scores'] = $scores;
        $result['interpretations'] = $interpretations;
        $result['clinical_interpretations'] = $clinicalInterpretations;
        $result['summary'] = $summary;
        $result['scoring_type'] = 'BECK';
        $result['questionnaire_name'] = 'Inventario de Depresión de Beck (BDI-II)';

        return $result;
    }

    /**
     * Get severity level based on total score
     *
     * @param int $totalScore
     * @return array Severity level information
     */
    private function getSeverityLevel(int $totalScore): array
    {
        if ($totalScore <= 13) {
            return [
                'level' => 'Depresión mínima',
                'description' => 'Los síntomas depresivos son mínimos o ausentes.',
                'score_range' => '0-13'
            ];
        } elseif ($totalScore <= 18) {
            return [
                'level' => 'Depresión leve',
                'description' => 'Presencia de síntomas depresivos leves que requieren observación.',
                'score_range' => '14-18'
            ];
        } elseif ($totalScore <= 27) {
            return [
                'level' => 'Depresión moderada',
                'description' => 'Síntomas depresivos de intensidad moderada que pueden requerir atención clínica.',
                'score_range' => '19-27'
            ];
        } else {
            return [
                'level' => 'Depresión grave',
                'description' => 'Síntomas depresivos graves que requieren atención clínica inmediata.',
                'score_range' => '28-63'
            ];
        }
    }

    /**
     * Interpret dimension scores
     *
     * @param int $score
     * @param string $dimension
     * @return string
     */
    private function interpretDimensionScore(int $score, string $dimension): string
    {
        $interpretation = '';
        
        if ($dimension === 'cognitive_affective') {
            if ($score <= 7) {
                $interpretation = 'Manifestaciones cognitivo-afectivas mínimas';
            } elseif ($score <= 12) {
                $interpretation = 'Manifestaciones cognitivo-afectivas leves';
            } elseif ($score <= 18) {
                $interpretation = 'Manifestaciones cognitivo-afectivas moderadas';
            } else {
                $interpretation = 'Manifestaciones cognitivo-afectivas graves';
            }
        } else { // somatic
            if ($score <= 6) {
                $interpretation = 'Manifestaciones somáticas mínimas';
            } elseif ($score <= 9) {
                $interpretation = 'Manifestaciones somáticas leves';
            } elseif ($score <= 13) {
                $interpretation = 'Manifestaciones somáticas moderadas';
            } else {
                $interpretation = 'Manifestaciones somáticas graves';
            }
        }

        return $interpretation;
    }

    /**
     * Generate clinical interpretations based on specific item responses
     *
     * @param array $scores
     * @param array $responses
     * @return array
     */
    private function generateClinicalInterpretations(array $scores, array $responses): array
    {
        $clinicalInterpretations = [];

        // Check for suicidal ideation (Item 9)
        if (isset($responses['sintomas_depresion']['q9'])) {
            $suicidalScore = (int)$responses['sintomas_depresion']['q9']['answer'];
            if ($suicidalScore >= 1) {
                $clinicalInterpretations[] = 'ATENCIÓN: Presencia de ideación suicida que requiere evaluación clínica inmediata.';
            }
        }

        // Check for severe symptoms
        if ($scores['total']['raw_score'] >= 28) {
            $clinicalInterpretations[] = 'La severidad de los síntomas sugiere la necesidad de una evaluación clínica exhaustiva y posible intervención terapéutica.';
        }

        // Check cognitive symptoms
        if ($scores['cognitive_affective']['raw_score'] > 18) {
            $clinicalInterpretations[] = 'Presencia significativa de síntomas cognitivo-afectivos que pueden requerir atención específica en el tratamiento.';
        }

        // Check somatic symptoms
        if ($scores['somatic']['raw_score'] > 13) {
            $clinicalInterpretations[] = 'Manifestaciones somáticas significativas que pueden requerir consideración en el plan de tratamiento.';
        }

        return $clinicalInterpretations;
    }

    /**
     * Generate a summary of the BDI-II results
     *
     * @param array $scores
     * @param array $interpretations
     * @return string
     */
    private function generateBeckSummary(array $scores, array $interpretations): string
    {
        $summary = "El paciente presenta una " . strtolower($interpretations['severity']['level']) . 
                  " (puntuación total: " . $scores['total']['raw_score'] . "). ";

        $summary .= "En la dimensión cognitivo-afectiva, " . 
                   strtolower($interpretations['dimensions']['cognitive_affective']) . 
                   " (puntuación: " . $scores['cognitive_affective']['raw_score'] . "). ";

        $summary .= "En la dimensión somática, " . 
                   strtolower($interpretations['dimensions']['somatic']) . 
                   " (puntuación: " . $scores['somatic']['raw_score'] . "). ";

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
        $promptSection = "## Resultados del Inventario de Depresión de Beck (BDI-II)\n\n";

        // Add demographic information if available
        if (isset($results['patient_data'])) {
            $promptSection .= "### Información demográfica relevante:\n";
            
            if (isset($results['patient_data']['age'])) {
                $promptSection .= "- **Edad**: " . $results['patient_data']['age'] . " años\n";
            }
            
            if (isset($results['patient_data']['gender'])) {
                $promptSection .= "- **Género**: " . $results['patient_data']['gender'] . "\n";
            }
            
            $promptSection .= "\n";
        }

        // Add scores
        if (isset($results['scores'])) {
            $promptSection .= "### Puntuaciones:\n";
            foreach ($results['scores'] as $scaleId => $scale) {
                $promptSection .= "- **" . $scale['name'] . "**: " . $scale['raw_score'] . "\n";
                $promptSection .= "  - " . $scale['description'] . "\n\n";
            }
        }

        // Add severity interpretation
        if (isset($results['interpretations']['severity'])) {
            $severity = $results['interpretations']['severity'];
            $promptSection .= "### Nivel de Severidad:\n";
            $promptSection .= "- **Nivel**: " . $severity['level'] . "\n";
            $promptSection .= "- **Rango**: " . $severity['score_range'] . "\n";
            $promptSection .= "- **Descripción**: " . $severity['description'] . "\n\n";
        }

        // Add clinical interpretations
        if (isset($results['clinical_interpretations'])) {
            $promptSection .= "### Interpretaciones Clínicas:\n";
            foreach ($results['clinical_interpretations'] as $interpretation) {
                $promptSection .= "- " . $interpretation . "\n";
            }
            $promptSection .= "\n";
        }

        // Add summary
        if (isset($results['summary'])) {
            $promptSection .= "### Resumen:\n";
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
        $defaultInstructions = "Basándote en los resultados del Inventario de Depresión de Beck (BDI-II), proporciona:\n\n" .
               "1. Una interpretación detallada del nivel de severidad de los síntomas depresivos.\n" .
               "2. Análisis de las dimensiones cognitivo-afectiva y somática.\n" .
               "3. Identificación de áreas que requieren atención clínica inmediata.\n" .
               "4. Recomendaciones específicas basadas en el perfil de síntomas.\n" .
               "5. Consideración de factores de riesgo y aspectos que requieren seguimiento.";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 