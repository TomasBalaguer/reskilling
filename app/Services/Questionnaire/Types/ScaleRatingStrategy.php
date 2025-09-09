<?php

namespace App\Services\Questionnaire\Types;

use App\Enums\QuestionType;
use App\Services\Questionnaire\AbstractQuestionnaireStrategy;
use App\Models\Questionnaire;

class ScaleRatingStrategy extends AbstractQuestionnaireStrategy
{
    public function buildStructure(Questionnaire $questionnaire): array
    {
        if ($questionnaire->structure) {
            return $questionnaire->structure;
        }

        return [
            'metadata' => [
                'evaluation_type' => 'quantitative',
                'response_format' => 'numeric_scale',
                'scoring_method' => 'statistical_analysis',
                'scale_type' => 'likert',
                'scale_range' => [1, 7], // Default 1-7 scale
                'neutral_point' => 4
            ],
            'sections' => [
                [
                    'id' => 'scale_rating_questions',
                    'title' => $questionnaire->name,
                    'description' => $questionnaire->description,
                    'instructions' => [
                        'Para cada afirmación, selecciona el número que mejor represente tu nivel de acuerdo.',
                        'Utiliza toda la escala, desde 1 (totalmente en desacuerdo) hasta 7 (totalmente de acuerdo).',
                        'No hay respuestas correctas o incorrectas.',
                        'Responde según tu experiencia personal.'
                    ],
                    'questions' => $this->transformQuestionsToStructure($questionnaire->questions ?? []),
                    'response_type' => 'numeric_scale',
                    'scale_definition' => $this->getScaleDefinition()
                ]
            ]
        ];
    }

    public function calculateScores(array $processedResponses, array $respondentData = []): array
    {
        $scores = [];
        $statisticalAnalysis = [];
        $categoryScores = [];
        
        foreach ($processedResponses as $questionId => $response) {
            $numericValue = (int) ($response['processed_response']['numeric_value'] ?? 0);
            $scores[$questionId] = $numericValue;
        }

        $statisticalAnalysis = $this->calculateStatistics($scores);
        $categoryScores = $this->calculateCategoryScores($scores, $questionnaire ?? null);
        $profileAnalysis = $this->generateProfileAnalysis($scores, $statisticalAnalysis);

        return [
            'scoring_type' => 'SCALE_RATING',
            'questionnaire_name' => 'Cuestionario de Escala de Calificación',
            'scale_type' => 'likert_7_point',
            'raw_scores' => $scores,
            'statistical_analysis' => $statisticalAnalysis,
            'category_scores' => $categoryScores,
            'profile_analysis' => $profileAnalysis,
            'response_patterns' => $this->analyzeResponsePatterns($scores),
            'reliability_indicators' => $this->calculateReliability($scores),
            'respondent_data' => $respondentData,
            'summary' => $this->generateSummary($statisticalAnalysis, count($scores), $profileAnalysis)
        ];
    }

    public function requiresAIProcessing(): bool
    {
        return false;
    }

    public function getSupportedQuestionTypes(): array
    {
        return [
            QuestionType::LIKERT_SCALE->value,
            QuestionType::NUMERIC_SCALE->value,
            QuestionType::SLIDER->value
        ];
    }

    protected function getEstimatedDuration(): int
    {
        return 12; // 12 minutes for scale rating questionnaire
    }

    private function transformQuestionsToStructure(array $questions): array
    {
        $transformedQuestions = [];
        
        foreach ($questions as $id => $questionData) {
            $text = is_array($questionData) ? $questionData['text'] : $questionData;
            $scaleMin = is_array($questionData) ? ($questionData['scale_min'] ?? 1) : 1;
            $scaleMax = is_array($questionData) ? ($questionData['scale_max'] ?? 7) : 7;
            $category = is_array($questionData) ? ($questionData['category'] ?? 'general') : 'general';
            
            $transformedQuestions[] = [
                'id' => $id,
                'text' => $text,
                'type' => 'likert_scale',
                'required' => true,
                'order' => (int) str_replace(['q', 'question_'], '', $id),
                'scale_min' => $scaleMin,
                'scale_max' => $scaleMax,
                'scale_labels' => $this->getScaleLabels($scaleMin, $scaleMax),
                'category' => $category,
                'reverse_scored' => is_array($questionData) ? ($questionData['reverse_scored'] ?? false) : false,
                'validation_rules' => ['required', 'integer', "between:{$scaleMin},{$scaleMax}"]
            ];
        }

        return $transformedQuestions;
    }

    private function getScaleDefinition(): array
    {
        return [
            1 => 'Totalmente en desacuerdo',
            2 => 'Muy en desacuerdo',
            3 => 'En desacuerdo',
            4 => 'Neutral',
            5 => 'De acuerdo',
            6 => 'Muy de acuerdo',
            7 => 'Totalmente de acuerdo'
        ];
    }

    private function getScaleLabels(int $min, int $max): array
    {
        $labels = [];
        $range = $max - $min + 1;
        
        if ($range === 7) {
            return $this->getScaleDefinition();
        } elseif ($range === 5) {
            return [
                1 => 'Totalmente en desacuerdo',
                2 => 'En desacuerdo',
                3 => 'Neutral',
                4 => 'De acuerdo',
                5 => 'Totalmente de acuerdo'
            ];
        } else {
            // Generic labels for other ranges
            for ($i = $min; $i <= $max; $i++) {
                $labels[$i] = (string) $i;
            }
            $labels[$min] = 'Mínimo';
            $labels[$max] = 'Máximo';
        }

        return $labels;
    }

    private function calculateStatistics(array $scores): array
    {
        if (empty($scores)) {
            return [
                'mean' => 0,
                'median' => 0,
                'mode' => 0,
                'std_deviation' => 0,
                'variance' => 0,
                'min' => 0,
                'max' => 0,
                'range' => 0
            ];
        }

        $values = array_values($scores);
        $count = count($values);
        $sum = array_sum($values);
        $mean = $sum / $count;

        sort($values);
        $median = $count % 2 === 0 ? 
            ($values[$count/2 - 1] + $values[$count/2]) / 2 : 
            $values[floor($count/2)];

        // Calculate mode
        $valueFreq = array_count_values($values);
        $maxFreq = max($valueFreq);
        $modes = array_keys($valueFreq, $maxFreq);
        $mode = $modes[0];

        // Calculate standard deviation
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / $count;
        $stdDev = sqrt($variance);

        return [
            'mean' => round($mean, 2),
            'median' => $median,
            'mode' => $mode,
            'std_deviation' => round($stdDev, 2),
            'variance' => round($variance, 2),
            'min' => min($values),
            'max' => max($values),
            'range' => max($values) - min($values),
            'count' => $count
        ];
    }

    private function calculateCategoryScores(array $scores, ?Questionnaire $questionnaire): array
    {
        // This would typically use category information from the questionnaire structure
        // For now, return basic grouping
        return [
            'general' => [
                'average' => count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0,
                'questions' => array_keys($scores)
            ]
        ];
    }

    private function generateProfileAnalysis(array $scores, array $stats): array
    {
        $mean = $stats['mean'];
        $stdDev = $stats['std_deviation'];
        
        $profile = [
            'overall_tendency' => $this->interpretMean($mean),
            'response_consistency' => $this->interpretStandardDeviation($stdDev),
            'extremes_usage' => $this->analyzeExtremesUsage($scores),
            'central_tendency' => $this->analyzeCentralTendency($scores)
        ];

        return $profile;
    }

    private function interpretMean(float $mean): string
    {
        return match(true) {
            $mean >= 6 => 'Muy positivo',
            $mean >= 5 => 'Positivo',
            $mean >= 4 => 'Neutral-Positivo',
            $mean >= 3 => 'Neutral-Negativo',
            $mean >= 2 => 'Negativo',
            default => 'Muy negativo'
        };
    }

    private function interpretStandardDeviation(float $stdDev): string
    {
        return match(true) {
            $stdDev <= 0.5 => 'Muy consistente',
            $stdDev <= 1.0 => 'Consistente',
            $stdDev <= 1.5 => 'Moderadamente consistente',
            $stdDev <= 2.0 => 'Variable',
            default => 'Muy variable'
        };
    }

    private function analyzeExtremesUsage(array $scores): array
    {
        $extremes = array_filter($scores, fn($score) => $score <= 2 || $score >= 6);
        $extremeUsage = count($scores) > 0 ? (count($extremes) / count($scores)) * 100 : 0;

        return [
            'extreme_responses_count' => count($extremes),
            'extreme_usage_percentage' => round($extremeUsage, 1),
            'tendency' => $extremeUsage > 50 ? 'Alto uso de extremos' : 
                         ($extremeUsage > 25 ? 'Uso moderado de extremos' : 'Uso conservador de extremos')
        ];
    }

    private function analyzeCentralTendency(array $scores): array
    {
        $central = array_filter($scores, fn($score) => $score >= 3 && $score <= 5);
        $centralUsage = count($scores) > 0 ? (count($central) / count($scores)) * 100 : 0;

        return [
            'central_responses_count' => count($central),
            'central_usage_percentage' => round($centralUsage, 1),
            'tendency' => $centralUsage > 60 ? 'Fuerte tendencia central' : 
                         ($centralUsage > 30 ? 'Tendencia central moderada' : 'Baja tendencia central')
        ];
    }

    private function analyzeResponsePatterns(array $scores): array
    {
        return [
            'acquiescence_bias' => $this->checkAcquiescenceBias($scores),
            'response_range_used' => max($scores) - min($scores),
            'midpoint_avoidance' => $this->checkMidpointAvoidance($scores)
        ];
    }

    private function checkAcquiescenceBias(array $scores): array
    {
        $positiveResponses = array_filter($scores, fn($score) => $score > 4);
        $positivePercentage = count($scores) > 0 ? (count($positiveResponses) / count($scores)) * 100 : 0;

        return [
            'positive_response_rate' => round($positivePercentage, 1),
            'bias_indication' => $positivePercentage > 70 ? 'Posible sesgo de aquiescencia' : 
                               ($positivePercentage < 30 ? 'Posible sesgo negativo' : 'Sin sesgo aparente')
        ];
    }

    private function checkMidpointAvoidance(array $scores): array
    {
        $midpointResponses = array_filter($scores, fn($score) => $score === 4);
        $midpointPercentage = count($scores) > 0 ? (count($midpointResponses) / count($scores)) * 100 : 0;

        return [
            'midpoint_usage_rate' => round($midpointPercentage, 1),
            'avoidance_indication' => $midpointPercentage < 10 ? 'Posible evitación del punto medio' : 
                                    ($midpointPercentage > 30 ? 'Alto uso del punto medio' : 'Uso normal del punto medio')
        ];
    }

    private function calculateReliability(array $scores): array
    {
        // Basic reliability indicators
        $stats = $this->calculateStatistics($scores);
        
        return [
            'internal_consistency' => 'N/A', // Would require Cronbach's alpha calculation
            'response_stability' => $stats['std_deviation'] < 1.5 ? 'Alta' : 
                                   ($stats['std_deviation'] < 2.5 ? 'Media' : 'Baja'),
            'data_quality' => count($scores) > 10 ? 'Suficiente' : 'Limitada'
        ];
    }

    private function generateSummary(array $stats, int $questionCount, array $profile): string
    {
        $mean = $stats['mean'];
        $tendency = $profile['overall_tendency'];
        $consistency = $profile['response_consistency'];

        return "Cuestionario de escala completado con {$questionCount} preguntas. " .
               "Puntuación promedio: {$mean}/7 ({$tendency}). " .
               "Patrón de respuesta: {$consistency}. " .
               "Análisis estadístico disponible para interpretación detallada.";
    }
}