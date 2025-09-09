<?php

namespace App\Services\Questionnaire\Types;

use App\Enums\QuestionType;
use App\Services\Questionnaire\AbstractQuestionnaireStrategy;
use App\Models\Questionnaire;

class BigFiveStrategy extends AbstractQuestionnaireStrategy
{
    public function buildStructure(Questionnaire $questionnaire): array
    {
        if ($questionnaire->structure) {
            return $questionnaire->structure;
        }

        return [
            'metadata' => [
                'evaluation_type' => 'personality_assessment',
                'response_format' => 'likert_scale',
                'scoring_method' => 'big_five_dimensions',
                'personality_model' => 'big_five',
                'dimensions' => $this->getBigFiveDimensions(),
                'scale_type' => 'likert_5_point',
                'reverse_scoring' => true
            ],
            'sections' => [
                [
                    'id' => 'big_five_assessment',
                    'title' => 'Evaluación Big Five de Personalidad',
                    'description' => 'Este cuestionario evalúa los cinco factores principales de personalidad según el modelo Big Five.',
                    'instructions' => [
                        'Lee cada afirmación cuidadosamente.',
                        'Indica qué tan bien cada afirmación te describe.',
                        'Usa la escala de 1 (muy en desacuerdo) a 5 (muy de acuerdo).',
                        'No hay respuestas correctas o incorrectas.',
                        'Responde de forma honesta y espontánea.'
                    ],
                    'questions' => $this->transformQuestionsToStructure($questionnaire->questions ?? []),
                    'response_type' => 'likert_scale',
                    'scale_definition' => $this->getScaleDefinition()
                ]
            ]
        ];
    }

    public function calculateScores(array $processedResponses, array $respondentData = []): array
    {
        $rawScores = [];
        $dimensionScores = [];
        
        // Extract raw scores
        foreach ($processedResponses as $questionId => $response) {
            $rawScores[$questionId] = (int) ($response['processed_response']['numeric_value'] ?? 0);
        }

        // Calculate dimension scores
        $dimensionScores = $this->calculateBigFiveScores($rawScores, $questionnaire ?? null);
        
        // Generate personality profile
        $personalityProfile = $this->generatePersonalityProfile($dimensionScores);
        
        // Calculate additional insights
        $personalityInsights = $this->generatePersonalityInsights($dimensionScores);

        return [
            'scoring_type' => 'BIG_FIVE',
            'questionnaire_name' => 'Big Five Personality Assessment',
            'personality_model' => 'big_five',
            'raw_scores' => $rawScores,
            'dimension_scores' => $dimensionScores,
            'personality_profile' => $personalityProfile,
            'personality_insights' => $personalityInsights,
            'statistical_summary' => $this->calculateStatisticalSummary($dimensionScores),
            'comparative_analysis' => $this->generateComparativeAnalysis($dimensionScores),
            'respondent_data' => $respondentData,
            'summary' => $this->generateSummary($dimensionScores, $personalityProfile)
        ];
    }

    public function requiresAIProcessing(): bool
    {
        return false; // Big Five uses statistical scoring, not AI
    }

    public function getSupportedQuestionTypes(): array
    {
        return [
            QuestionType::LIKERT_SCALE->value
        ];
    }

    protected function getEstimatedDuration(): int
    {
        return 18; // 18 minutes for Big Five assessment
    }

    private function getBigFiveDimensions(): array
    {
        return [
            'openness' => [
                'name' => 'Apertura a la Experiencia',
                'description' => 'Curiosidad intelectual, creatividad, preferencia por la variedad',
                'facets' => ['fantasía', 'estética', 'sentimientos', 'acciones', 'ideas', 'valores']
            ],
            'conscientiousness' => [
                'name' => 'Responsabilidad',
                'description' => 'Organización, persistencia, control de impulsos',
                'facets' => ['competencia', 'orden', 'sentido_del_deber', 'necesidad_de_logro', 'autodisciplina', 'deliberación']
            ],
            'extraversion' => [
                'name' => 'Extraversión',
                'description' => 'Sociabilidad, asertividad, nivel de actividad',
                'facets' => ['cordialidad', 'sociabilidad', 'asertividad', 'actividad', 'búsqueda_de_emociones', 'emociones_positivas']
            ],
            'agreeableness' => [
                'name' => 'Amabilidad',
                'description' => 'Compasión, cooperación, confianza en otros',
                'facets' => ['confianza', 'franqueza', 'altruismo', 'actitud_conciliadora', 'modestia', 'sensibilidad_a_los_demás']
            ],
            'neuroticism' => [
                'name' => 'Neuroticismo',
                'description' => 'Tendencia a experimentar emociones negativas',
                'facets' => ['ansiedad', 'hostilidad', 'depresión', 'ansiedad_social', 'impulsividad', 'vulnerabilidad']
            ]
        ];
    }

    private function transformQuestionsToStructure(array $questions): array
    {
        $transformedQuestions = [];
        $dimensionMap = $this->getDimensionMap();
        
        foreach ($questions as $id => $questionData) {
            $text = is_array($questionData) ? $questionData['text'] : $questionData;
            $dimension = is_array($questionData) ? ($questionData['dimension'] ?? 'openness') : 'openness';
            $reverseScored = is_array($questionData) ? ($questionData['reverse_scored'] ?? false) : false;
            
            $transformedQuestions[] = [
                'id' => $id,
                'text' => $text,
                'type' => 'likert_scale',
                'required' => true,
                'order' => (int) str_replace(['q', 'question_'], '', $id),
                'scale_min' => 1,
                'scale_max' => 5,
                'scale_labels' => $this->getScaleDefinition(),
                'dimension' => $dimension,
                'reverse_scored' => $reverseScored,
                'validation_rules' => ['required', 'integer', 'between:1,5']
            ];
        }

        return $transformedQuestions;
    }

    private function getScaleDefinition(): array
    {
        return [
            1 => 'Muy en desacuerdo',
            2 => 'En desacuerdo',
            3 => 'Neutral',
            4 => 'De acuerdo',
            5 => 'Muy de acuerdo'
        ];
    }

    private function getDimensionMap(): array
    {
        // This would typically come from the questionnaire configuration
        // Mapping question IDs to Big Five dimensions
        return [
            'openness' => ['q1', 'q6', 'q11', 'q16', 'q21', 'q26', 'q31', 'q36'],
            'conscientiousness' => ['q2', 'q7', 'q12', 'q17', 'q22', 'q27', 'q32', 'q37'],
            'extraversion' => ['q3', 'q8', 'q13', 'q18', 'q23', 'q28', 'q33', 'q38'],
            'agreeableness' => ['q4', 'q9', 'q14', 'q19', 'q24', 'q29', 'q34', 'q39'],
            'neuroticism' => ['q5', 'q10', 'q15', 'q20', 'q25', 'q30', 'q35', 'q40']
        ];
    }

    private function calculateBigFiveScores(array $rawScores, ?Questionnaire $questionnaire): array
    {
        $dimensionMap = $this->getDimensionMap();
        $dimensionScores = [];
        $dimensions = $this->getBigFiveDimensions();

        foreach ($dimensionMap as $dimension => $questionIds) {
            $dimensionTotal = 0;
            $questionCount = 0;
            $responses = [];

            foreach ($questionIds as $questionId) {
                if (isset($rawScores[$questionId])) {
                    $score = $rawScores[$questionId];
                    
                    // Apply reverse scoring if needed (would come from question config)
                    // For demonstration, assume some questions are reverse scored
                    if ($this->isReverseScored($questionId)) {
                        $score = 6 - $score; // Reverse 1-5 scale
                    }
                    
                    $dimensionTotal += $score;
                    $questionCount++;
                    $responses[] = $score;
                }
            }

            if ($questionCount > 0) {
                $average = $dimensionTotal / $questionCount;
                $percentile = $this->convertToPercentile($average, $dimension);
                
                $dimensionScores[$dimension] = [
                    'name' => $dimensions[$dimension]['name'],
                    'raw_score' => $dimensionTotal,
                    'average_score' => round($average, 2),
                    'question_count' => $questionCount,
                    'percentile' => $percentile,
                    'level' => $this->interpretScore($percentile),
                    'description' => $dimensions[$dimension]['description'],
                    'individual_responses' => $responses
                ];
            }
        }

        return $dimensionScores;
    }

    private function isReverseScored(string $questionId): bool
    {
        // This would typically come from question configuration
        // For demonstration, assume certain patterns are reverse scored
        $reverseQuestions = ['q6', 'q16', 'q26', 'q7', 'q17', 'q27', 'q8', 'q18', 'q28'];
        return in_array($questionId, $reverseQuestions);
    }

    private function convertToPercentile(float $average, string $dimension): int
    {
        // Simplified percentile conversion based on normal distribution
        // In practice, this would use normative data
        $normalized = ($average - 1) / 4; // Convert 1-5 scale to 0-1
        
        return match(true) {
            $normalized >= 0.9 => 95,
            $normalized >= 0.8 => 85,
            $normalized >= 0.7 => 75,
            $normalized >= 0.6 => 65,
            $normalized >= 0.4 => 50,
            $normalized >= 0.3 => 35,
            $normalized >= 0.2 => 25,
            $normalized >= 0.1 => 15,
            default => 5
        };
    }

    private function interpretScore(int $percentile): string
    {
        return match(true) {
            $percentile >= 80 => 'Muy Alto',
            $percentile >= 60 => 'Alto',
            $percentile >= 40 => 'Promedio',
            $percentile >= 20 => 'Bajo',
            default => 'Muy Bajo'
        };
    }

    private function generatePersonalityProfile(array $dimensionScores): array
    {
        $profile = [
            'dominant_traits' => [],
            'balanced_traits' => [],
            'lower_traits' => [],
            'personality_type' => $this->determinePersonalityType($dimensionScores)
        ];

        foreach ($dimensionScores as $dimension => $data) {
            $level = $data['level'];
            
            if (in_array($level, ['Muy Alto', 'Alto'])) {
                $profile['dominant_traits'][] = [
                    'dimension' => $dimension,
                    'name' => $data['name'],
                    'level' => $level,
                    'percentile' => $data['percentile']
                ];
            } elseif ($level === 'Promedio') {
                $profile['balanced_traits'][] = [
                    'dimension' => $dimension,
                    'name' => $data['name'],
                    'level' => $level,
                    'percentile' => $data['percentile']
                ];
            } else {
                $profile['lower_traits'][] = [
                    'dimension' => $dimension,
                    'name' => $data['name'],
                    'level' => $level,
                    'percentile' => $data['percentile']
                ];
            }
        }

        return $profile;
    }

    private function determinePersonalityType(array $dimensionScores): array
    {
        // Simplified personality type determination
        $highTraits = [];
        
        foreach ($dimensionScores as $dimension => $data) {
            if ($data['percentile'] >= 60) {
                $highTraits[] = $dimension;
            }
        }

        return [
            'primary_traits' => $highTraits,
            'type_description' => $this->getTypeDescription($highTraits)
        ];
    }

    private function getTypeDescription(array $highTraits): string
    {
        // Simplified type descriptions based on high traits
        if (in_array('extraversion', $highTraits) && in_array('conscientiousness', $highTraits)) {
            return 'Líder natural con tendencia a la organización y sociabilidad';
        } elseif (in_array('openness', $highTraits) && in_array('agreeableness', $highTraits)) {
            return 'Creativo y empático, orientado hacia la colaboración';
        } elseif (in_array('conscientiousness', $highTraits) && in_array('agreeableness', $highTraits)) {
            return 'Confiable y cooperativo, excelente trabajo en equipo';
        } else {
            return 'Perfil de personalidad equilibrado con fortalezas particulares';
        }
    }

    private function generatePersonalityInsights(array $dimensionScores): array
    {
        return [
            'strengths' => $this->identifyStrengths($dimensionScores),
            'development_areas' => $this->identifyDevelopmentAreas($dimensionScores),
            'work_preferences' => $this->identifyWorkPreferences($dimensionScores),
            'communication_style' => $this->identifyCommunicationStyle($dimensionScores)
        ];
    }

    private function identifyStrengths(array $dimensionScores): array
    {
        $strengths = [];
        
        foreach ($dimensionScores as $dimension => $data) {
            if ($data['percentile'] >= 70) {
                $strengths[] = [
                    'trait' => $data['name'],
                    'strength_description' => $this->getStrengthDescription($dimension, $data['percentile'])
                ];
            }
        }

        return $strengths;
    }

    private function identifyDevelopmentAreas(array $dimensionScores): array
    {
        $developmentAreas = [];
        
        foreach ($dimensionScores as $dimension => $data) {
            if ($data['percentile'] <= 30) {
                $developmentAreas[] = [
                    'trait' => $data['name'],
                    'development_suggestion' => $this->getDevelopmentSuggestion($dimension, $data['percentile'])
                ];
            }
        }

        return $developmentAreas;
    }

    private function identifyWorkPreferences(array $dimensionScores): array
    {
        $preferences = [];
        
        // Based on Big Five research correlations
        if (($dimensionScores['extraversion']['percentile'] ?? 0) >= 60) {
            $preferences[] = 'Trabajo en equipo y interacción social';
        }
        
        if (($dimensionScores['conscientiousness']['percentile'] ?? 0) >= 60) {
            $preferences[] = 'Entornos estructurados y orientados a objetivos';
        }
        
        if (($dimensionScores['openness']['percentile'] ?? 0) >= 60) {
            $preferences[] = 'Proyectos creativos e innovadores';
        }

        return $preferences;
    }

    private function identifyCommunicationStyle(array $dimensionScores): string
    {
        $extraversion = $dimensionScores['extraversion']['percentile'] ?? 50;
        $agreeableness = $dimensionScores['agreeableness']['percentile'] ?? 50;
        
        if ($extraversion >= 60 && $agreeableness >= 60) {
            return 'Comunicador expresivo y colaborativo';
        } elseif ($extraversion >= 60) {
            return 'Comunicador directo y asertivo';
        } elseif ($agreeableness >= 60) {
            return 'Comunicador diplomático y considerado';
        } else {
            return 'Comunicador reflexivo y selectivo';
        }
    }

    private function getStrengthDescription(string $dimension, int $percentile): string
    {
        return match($dimension) {
            'openness' => 'Excelente capacidad para generar ideas innovadoras y adaptarse a cambios',
            'conscientiousness' => 'Altamente confiable, organizado y orientado al logro de objetivos',
            'extraversion' => 'Energético, sociable y cómodo liderando e interactuando con otros',
            'agreeableness' => 'Empático, cooperativo y excelente para construir relaciones',
            'neuroticism' => 'Estable emocionalmente y resistente al estrés',
            default => 'Fortaleza significativa en esta dimensión de personalidad'
        };
    }

    private function getDevelopmentSuggestion(string $dimension, int $percentile): string
    {
        return match($dimension) {
            'openness' => 'Considera explorar nuevas experiencias y enfoques creativos',
            'conscientiousness' => 'Trabaja en desarrollar rutinas y sistemas de organización',
            'extraversion' => 'Practica habilidades de networking y comunicación grupal',
            'agreeableness' => 'Desarrolla habilidades de colaboración y resolución de conflictos',
            'neuroticism' => 'Considera técnicas de manejo del estrés y regulación emocional',
            default => 'Área de oportunidad para desarrollo personal'
        };
    }

    private function calculateStatisticalSummary(array $dimensionScores): array
    {
        $percentiles = array_column($dimensionScores, 'percentile');
        
        if (empty($percentiles)) {
            return ['mean_percentile' => 0, 'variability' => 0, 'profile_consistency' => 'N/A'];
        }

        $mean = array_sum($percentiles) / count($percentiles);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $percentiles)) / count($percentiles);
        $stdDev = sqrt($variance);

        return [
            'mean_percentile' => round($mean, 1),
            'variability' => round($stdDev, 1),
            'profile_consistency' => $stdDev < 15 ? 'Alta' : ($stdDev < 25 ? 'Media' : 'Baja')
        ];
    }

    private function generateComparativeAnalysis(array $dimensionScores): array
    {
        $sortedDimensions = $dimensionScores;
        uasort($sortedDimensions, fn($a, $b) => $b['percentile'] <=> $a['percentile']);

        return [
            'highest_dimension' => array_key_first($sortedDimensions),
            'lowest_dimension' => array_key_last($sortedDimensions),
            'dimension_ranking' => array_keys($sortedDimensions)
        ];
    }

    private function generateSummary(array $dimensionScores, array $profile): string
    {
        $dominantTraits = count($profile['dominant_traits']);
        $personalityType = $profile['personality_type']['type_description'];
        
        return "Evaluación Big Five completada. " .
               "Perfil de personalidad con {$dominantTraits} rasgos dominantes. " .
               "Tipo: {$personalityType}. " .
               "Análisis detallado disponible por dimensión de personalidad.";
    }
}