<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

class BaiQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    protected $type = 'BAI';

    /**
     * Mapping of answer values to scores
     */
    protected $scoreMapping = [
        'No' => 0,
        'Leve' => 1,
        'Moderado' => 2,
        'Bastante' => 3
    ];

    /**
     * Interpretation ranges for BAI scores
     */
    protected $interpretationRanges = [
        [
            'min' => 0,
            'max' => 21,
            'label' => 'Ansiedad muy baja',
            'description' => 'Los síntomas de ansiedad son mínimos o inexistentes.'
        ],
        [
            'min' => 22,
            'max' => 35,
            'label' => 'Ansiedad moderada',
            'description' => 'Hay presencia de síntomas de ansiedad que requieren atención.'
        ],
        [
            'min' => 36,
            'max' => 63,
            'label' => 'Ansiedad severa',
            'description' => 'Los síntomas de ansiedad son graves y requieren atención inmediata.'
        ]
    ];

    /**
     * Calculate scores based on questionnaire responses
     */
    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        // Add patient data to results
        $result = $this->addPatientData($result, $patientData);

        if (!isset($responses['sintomas_ansiedad'])) {
            throw new \InvalidArgumentException('Las respuestas no contienen la sección de síntomas de ansiedad');
        }

        $totalScore = 0;
        $symptomScores = [];
        $symptomsByIntensity = [
            'Bastante' => [],
            'Moderado' => [],
            'Leve' => [],
            'No' => []
        ];

        foreach ($responses['sintomas_ansiedad'] as $questionId => $response) {
            $score = $this->scoreMapping[$response['answer']];
            $totalScore += $score;
            
            $symptomScores[$questionId] = [
                'question' => $response['question'],
                'answer' => $response['answer'],
                'score' => $score
            ];

            // Group symptoms by intensity for analysis
            $symptomsByIntensity[$response['answer']][] = $response['question'];
        }

        // Get interpretation based on total score
        $interpretation = $this->getInterpretation($totalScore);

        // Prepare the results array
        $result['scores'] = [
            'total_score' => $totalScore,
            'interpretation' => $interpretation,
            'symptom_scores' => $symptomScores,
            'symptoms_by_intensity' => $symptomsByIntensity
        ];

        return $result;
    }

    /**
     * Get interpretation based on total score
     */
    protected function getInterpretation(int $totalScore): array
    {
        foreach ($this->interpretationRanges as $range) {
            if ($totalScore >= $range['min'] && $totalScore <= $range['max']) {
                return $range;
            }
        }

        // If score is higher than any range, return the highest severity
        return end($this->interpretationRanges);
    }

    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     */
    public function buildPromptSection(array $results): string
    {
        if (!isset($results['scores'])) {
            return "No hay suficientes datos para generar una interpretación del BAI.";
        }

        $scores = $results['scores'];
        $prompt = "INVENTARIO DE ANSIEDAD DE BECK (BAI)\n\n";

        // Add demographic information if available
        if (isset($results['patient_data'])) {
            $prompt .= "Datos del Paciente:\n";
            foreach ($results['patient_data'] as $key => $value) {
                $prompt .= ucfirst($key) . ": " . $value . "\n";
            }
            $prompt .= "\n";
        }

        // Add total score and interpretation
        $prompt .= "Puntuación Total: " . $scores['total_score'] . "\n";
        $prompt .= "Nivel de Ansiedad: " . $scores['interpretation']['label'] . "\n";
        $prompt .= "Interpretación: " . $scores['interpretation']['description'] . "\n\n";

        // Add symptom analysis by intensity
        $prompt .= "ANÁLISIS DE SÍNTOMAS POR INTENSIDAD:\n\n";
        foreach ($scores['symptoms_by_intensity'] as $intensity => $symptoms) {
            if (!empty($symptoms)) {
                $prompt .= "$intensity:\n";
                foreach ($symptoms as $symptom) {
                    $prompt .= "- $symptom\n";
                }
                $prompt .= "\n";
            }
        }
        return $prompt;
    }

    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     */
    public function getInstructions(): string
    {
        $defaultInstructions = "Por favor, analiza los resultados del Inventario de Ansiedad de Beck (BAI) considerando:\n\n" .
               "1. La puntuación total y su interpretación según los rangos establecidos:\n" .
               "   - 0-21: Ansiedad muy baja\n" .
               "   - 22-35: Ansiedad moderada\n" .
               "   - 36 o más: Ansiedad severa\n\n" .
               "2. La distribución de síntomas por intensidad\n" .
               "3. Los patrones específicos de síntomas físicos y cognitivos\n" .
               "4. El impacto potencial en el funcionamiento diario\n" .
               "5. Las recomendaciones terapéuticas apropiadas según el nivel de severidad";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 