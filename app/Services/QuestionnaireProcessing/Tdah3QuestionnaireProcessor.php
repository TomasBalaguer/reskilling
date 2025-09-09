<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

class Tdah3QuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    protected $type = 'TDAH3';

    protected array $dimensions = [
        'control_inhibitorio' => [
            'name' => 'Control Inhibitorio e Impulsividad',
            'questions' => [23, 29, 36, 43, 50, 58, 64, 70, 73]
        ],
        'memoria_trabajo' => [
            'name' => 'Memoria de Trabajo',
            'questions' => [10, 17, 35, 41, 46, 56, 67]
        ],
        'planificacion' => [
            'name' => 'Planificación y Organización',
            'questions' => [3, 7, 15, 21, 30, 31, 34, 49, 52, 53, 54, 60, 63, 66, 71, 75]
        ],
        'flexibilidad' => [
            'name' => 'Flexibilidad Cognitiva',
            'questions' => [8, 22, 24, 32, 44, 61, 68]
        ],
        'regulacion_emocional' => [
            'name' => 'Regulación Emocional',
            'questions' => [1, 12, 13, 19, 28, 33, 37, 42, 51, 57, 59, 69, 72]
        ],
        'iniciativa' => [
            'name' => 'Iniciativa y Motivación',
            'questions' => [6, 9, 14, 20, 25, 26, 39, 45, 48, 62, 74]
        ]
    ];

    protected array $scoreMap = [
        'NUNCA' => 0,
        'A_VECES' => 1,
        'FRECUENTEMENTE' => 2
    ];

    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        $scores = [];
        $totalScore = 0;

        // Calculate scores for each dimension
        foreach ($this->dimensions as $dimensionKey => $dimension) {
            $dimensionScore = 0;
            $answeredQuestions = 0;

            foreach ($dimension['questions'] as $questionNumber) {
                $questionId = 'q' . $questionNumber;
                if (isset($responses['funciones_ejecutivas'][$questionId])) {
                    $answer = $responses['funciones_ejecutivas'][$questionId]['answer'];
                    $dimensionScore += $this->scoreMap[$answer];
                    $answeredQuestions++;
                }
            }

            $scores[$dimensionKey] = [
                'name' => $dimension['name'],
                'score' => $dimensionScore,
                'max_score' => count($dimension['questions']) * 2,
                'interpretation' => $this->interpretDimensionScore($dimensionScore),
                'questions_answered' => $answeredQuestions
            ];

            $totalScore += $dimensionScore;
        }

        return [
            'scores' => $scores,
            'total_score' => $totalScore,
            'max_total_score' => 150, // 75 questions * 2 points max
            'interpretations' => $this->interpretScores($scores),
            'clinical_interpretations' => $this->generateClinicalInterpretations($scores),
            'summary' => $this->generateSummary($scores),
            'scoring_type' => 'TDAH3',
            'questionnaire_name' => 'Cuestionario de Funciones Ejecutivas',
            'responses' => $responses
        ];
    }

    protected function interpretDimensionScore(int $score): string
    {
        if ($score <= 5) {
            return 'Funcionamiento dentro de lo esperado';
        } elseif ($score <= 10) {
            return 'Dificultades moderadas';
        } else {
            return 'Dificultades significativas';
        }
    }

    protected function interpretScores(array $scores): array
    {
        $interpretations = [];
        $dimensionsWithDifficulties = 0;

        foreach ($scores as $dimensionKey => $dimension) {
            $interpretations[$dimensionKey] = [
                'score' => $dimension['score'],
                'interpretation' => $dimension['interpretation'],
                'percentage' => ($dimension['score'] / $dimension['max_score']) * 100
            ];

            if ($dimension['score'] > 10) {
                $dimensionsWithDifficulties++;
            }
        }

        return [
            'individual' => $interpretations,
            'overall' => $this->generateOverallInterpretation($dimensionsWithDifficulties)
        ];
    }

    protected function generateOverallInterpretation(int $dimensionsWithDifficulties): string
    {
        if ($dimensionsWithDifficulties >= 3) {
            return 'Se observan dificultades significativas en múltiples funciones ejecutivas, lo que sugiere un perfil compatible con TDAH.';
        } elseif ($dimensionsWithDifficulties >= 1) {
            return 'Se observan dificultades moderadas en algunas funciones ejecutivas específicas.';
        } else {
            return 'Las funciones ejecutivas se encuentran dentro de los rangos esperados.';
        }
    }

    protected function generateClinicalInterpretations(array $scores): array
    {
        $interpretations = [];
        $tdahRelatedDimensions = ['control_inhibitorio', 'memoria_trabajo', 'planificacion', 'regulacion_emocional'];
        $tdahRelatedScore = 0;
        $tdahRelatedMax = 0;

        foreach ($scores as $dimensionKey => $dimension) {
            $interpretations[$dimensionKey] = [
                'name' => $dimension['name'],
                'score' => $dimension['score'],
                'max_score' => $dimension['max_score'],
                'interpretation' => $dimension['interpretation'],
                'percentage' => ($dimension['score'] / $dimension['max_score']) * 100
            ];

            if (in_array($dimensionKey, $tdahRelatedDimensions)) {
                $tdahRelatedScore += $dimension['score'];
                $tdahRelatedMax += $dimension['max_score'];
            }
        }

        $tdahRelatedPercentage = ($tdahRelatedScore / $tdahRelatedMax) * 100;

        return [
            'dimensions' => $interpretations,
            'tdah_related' => [
                'score' => $tdahRelatedScore,
                'max_score' => $tdahRelatedMax,
                'percentage' => $tdahRelatedPercentage,
                'interpretation' => $this->interpretTDAHRelatedScore($tdahRelatedPercentage)
            ]
        ];
    }

    protected function interpretTDAHRelatedScore(float $percentage): string
    {
        if ($percentage >= 70) {
            return 'Alto nivel de dificultades en funciones ejecutivas relacionadas con TDAH';
        } elseif ($percentage >= 40) {
            return 'Nivel moderado de dificultades en funciones ejecutivas relacionadas con TDAH';
        } else {
            return 'Bajo nivel de dificultades en funciones ejecutivas relacionadas con TDAH';
        }
    }

    protected function generateSummary(array $scores): string
    {
        $summary = "Resumen del Cuestionario de Funciones Ejecutivas:\n\n";
        
        foreach ($scores as $dimension) {
            $summary .= "{$dimension['name']}:\n";
            $summary .= "- Puntuación: {$dimension['score']} de {$dimension['max_score']}\n";
            $summary .= "- Interpretación: {$dimension['interpretation']}\n\n";
        }

        return $summary;
    }

    public function buildPromptSection(array $questionnaireResults): string
    {
        
        $scores = $questionnaireResults['scores'];
        $prompt = "Análisis del Cuestionario de Funciones Ejecutivas:\n\n";

        foreach ($scores as $dimension) {
            $prompt .= "{$dimension['name']}:\n";
            $prompt .= "- Puntuación: {$dimension['score']} de {$dimension['max_score']}\n";
            $prompt .= "- Interpretación: {$dimension['interpretation']}\n\n";
        }

        try {
            $prompt .= "Respuestas Detalladas:\n\n";
            foreach ($questionnaireResults['responses']['funciones_ejecutivas'] as $questionId => $response) {
                $prompt .= "{$questionId}:\n";
                $prompt .= "- Pregunta: {$response['question']}\n";
                $prompt .= "- Respuesta: {$response['answer']}\n\n";
            }
        } catch (\Exception $e) {
            Log::error('Error al construir el prompt: ' . $e->getMessage());
        }
        
        return $prompt;
    }

    public function getInstructions(): string
    {
        $defaultInstructions = "Analiza el cuestionario de funciones ejecutivas considerando:\n\n" .
               "1. Dimensiones evaluadas:\n" .
               "   - Control Inhibitorio e Impulsividad\n" .
               "   - Memoria de Trabajo\n" .
               "   - Planificación y Organización\n" .
               "   - Flexibilidad Cognitiva\n" .
               "   - Regulación Emocional\n" .
               "   - Iniciativa y Motivación\n\n" .
               "2. Interpretación de puntuaciones:\n" .
               "   - 0-5 puntos: Funcionamiento dentro de lo esperado\n" .
               "   - 6-10 puntos: Dificultades moderadas\n" .
               "   - 11+ puntos: Dificultades significativas\n\n" .
               "3. Relación con TDAH:\n" .
               "   - Evaluar especialmente Control Inhibitorio, Memoria de Trabajo, Planificación y Regulación Emocional\n" .
               "   - Considerar el patrón general de dificultades\n" .
               "   - Identificar áreas específicas más afectadas\n\n" .
               "4. Recomendaciones:\n" .
               "   - Sugerir estrategias específicas para las áreas con mayor dificultad\n" .
               "   - Proponer intervenciones basadas en el perfil de funciones ejecutivas\n" .
               "   - Considerar la necesidad de evaluación adicional si hay múltiples áreas afectadas";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
}

