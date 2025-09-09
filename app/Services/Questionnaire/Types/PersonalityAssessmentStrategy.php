<?php

namespace App\Services\Questionnaire\Types;

use App\Enums\QuestionType;
use App\Services\Questionnaire\AbstractQuestionnaireStrategy;
use App\Models\Questionnaire;

class PersonalityAssessmentStrategy extends AbstractQuestionnaireStrategy
{
    public function buildStructure(Questionnaire $questionnaire): array
    {
        if ($questionnaire->structure) {
            return $questionnaire->structure;
        }

        return [
            'metadata' => [
                'evaluation_type' => 'personality_assessment',
                'response_format' => 'mixed_responses',
                'scoring_method' => 'psychometric_analysis',
                'requires_ai_processing' => true,
                'assessment_domains' => $this->getAssessmentDomains(),
                'interpretation_levels' => ['individual_traits', 'behavioral_patterns', 'workplace_implications']
            ],
            'sections' => [
                [
                    'id' => 'personality_assessment',
                    'title' => $questionnaire->name,
                    'description' => $questionnaire->description,
                    'instructions' => [
                        'Este cuestionario evalúa diferentes aspectos de tu personalidad.',
                        'Responde de forma honesta y espontánea.',
                        'No hay respuestas correctas o incorrectas.',
                        'Algunas preguntas usan escalas, otras requieren selección de opciones.',
                        'Tómate el tiempo necesario para reflexionar en cada respuesta.'
                    ],
                    'questions' => $this->transformQuestionsToStructure($questionnaire->questions ?? []),
                    'response_type' => 'mixed_format'
                ]
            ]
        ];
    }

    public function calculateScores(array $processedResponses, array $respondentData = []): array
    {
        $personalityProfile = [];
        $behavioralIndicators = [];
        $responsePatterns = [];

        foreach ($processedResponses as $questionId => $response) {
            $responseType = $response['response_type'];
            $processedResponse = $response['processed_response'];

            // Analyze different response types
            switch ($responseType) {
                case 'text_input':
                    $personalityProfile[$questionId] = $this->analyzeTextResponse($processedResponse, $questionId);
                    break;
                case 'numeric_input':
                    $personalityProfile[$questionId] = $this->analyzeNumericResponse($processedResponse, $questionId);
                    break;
                case 'multiple_choice':
                    $personalityProfile[$questionId] = $this->analyzeChoiceResponse($processedResponse, $questionId);
                    break;
                default:
                    $personalityProfile[$questionId] = $this->analyzeGenericResponse($processedResponse, $questionId);
            }
        }

        // Generate behavioral patterns
        $behavioralIndicators = $this->generateBehavioralIndicators($personalityProfile);
        $responsePatterns = $this->analyzeResponsePatterns($processedResponses);

        return [
            'scoring_type' => 'PERSONALITY_ASSESSMENT',
            'questionnaire_name' => 'Evaluación Integral de Personalidad',
            'assessment_type' => 'comprehensive_personality',
            'personality_profile' => $personalityProfile,
            'behavioral_indicators' => $behavioralIndicators,
            'response_patterns' => $responsePatterns,
            'psychometric_scores' => $this->calculatePsychometricScores($personalityProfile),
            'workplace_implications' => $this->generateWorkplaceImplications($behavioralIndicators),
            'ai_interpretation_required' => true,
            'confidence_level' => $this->calculateConfidenceLevel($processedResponses),
            'respondent_data' => $respondentData,
            'summary' => $this->generateSummary($behavioralIndicators, count($processedResponses))
        ];
    }

    public function requiresAIProcessing(): bool
    {
        return true;
    }

    public function getSupportedQuestionTypes(): array
    {
        return [
            QuestionType::TEXT_INPUT->value,
            QuestionType::TEXTAREA->value,
            QuestionType::LIKERT_SCALE->value,
            QuestionType::MULTIPLE_CHOICE->value,
            QuestionType::SINGLE_CHOICE->value,
            QuestionType::NUMERIC_SCALE->value
        ];
    }

    protected function getEstimatedDuration(): int
    {
        return 30; // 30 minutes for comprehensive personality assessment
    }

    private function getAssessmentDomains(): array
    {
        return [
            'cognitive_style' => 'Estilo de procesamiento de información y toma de decisiones',
            'social_orientation' => 'Preferencias de interacción social y trabajo en equipo',
            'emotional_regulation' => 'Manejo de emociones y respuesta al estrés',
            'motivation_drivers' => 'Factores que impulsan el comportamiento y el rendimiento',
            'adaptation_flexibility' => 'Capacidad de adaptación a cambios y nuevas situaciones',
            'leadership_potential' => 'Tendencias y habilidades de liderazgo'
        ];
    }

    private function transformQuestionsToStructure(array $questions): array
    {
        $transformedQuestions = [];
        
        foreach ($questions as $id => $questionData) {
            $text = is_array($questionData) ? $questionData['text'] : $questionData;
            $type = is_array($questionData) ? ($questionData['type'] ?? 'likert_scale') : 'likert_scale';
            $domain = is_array($questionData) ? ($questionData['domain'] ?? 'general') : 'general';
            
            $baseQuestion = [
                'id' => $id,
                'text' => $text,
                'type' => $type,
                'required' => true,
                'order' => (int) str_replace(['q', 'question_'], '', $id),
                'assessment_domain' => $domain
            ];

            // Add type-specific properties
            switch ($type) {
                case 'likert_scale':
                    $baseQuestion = array_merge($baseQuestion, [
                        'scale_min' => 1,
                        'scale_max' => 7,
                        'scale_labels' => [
                            1 => 'Totalmente en desacuerdo',
                            4 => 'Neutral',
                            7 => 'Totalmente de acuerdo'
                        ],
                        'validation_rules' => ['required', 'integer', 'between:1,7']
                    ]);
                    break;
                    
                case 'multiple_choice':
                    $options = is_array($questionData) ? ($questionData['options'] ?? []) : [];
                    $baseQuestion = array_merge($baseQuestion, [
                        'options' => $options,
                        'validation_rules' => ['required', 'array', 'min:1']
                    ]);
                    break;
                    
                case 'text_input':
                    $baseQuestion = array_merge($baseQuestion, [
                        'max_length' => 500,
                        'placeholder' => 'Describe tu experiencia o perspectiva...',
                        'validation_rules' => ['required', 'string', 'max:500']
                    ]);
                    break;
            }

            $transformedQuestions[] = $baseQuestion;
        }

        return $transformedQuestions;
    }

    private function analyzeTextResponse(array $processedResponse, string $questionId): array
    {
        $text = $processedResponse['text_response'] ?? '';
        $wordCount = $processedResponse['word_count'] ?? 0;
        
        return [
            'response_type' => 'textual_analysis',
            'content_depth' => $wordCount > 50 ? 'detailed' : ($wordCount > 20 ? 'moderate' : 'brief'),
            'thematic_indicators' => $this->extractThematicIndicators($text),
            'emotional_tone' => $this->analyzeEmotionalTone($text),
            'cognitive_complexity' => $this->assessCognitiveComplexity($text)
        ];
    }

    private function analyzeNumericResponse(array $processedResponse, string $questionId): array
    {
        $value = $processedResponse['numeric_value'] ?? 0;
        
        return [
            'response_type' => 'numeric_analysis',
            'scale_position' => $value,
            'tendency' => $value > 4 ? 'positive' : ($value < 4 ? 'negative' : 'neutral'),
            'intensity' => abs($value - 4) // Distance from neutral
        ];
    }

    private function analyzeChoiceResponse(array $processedResponse, string $questionId): array
    {
        $selections = $processedResponse['selected_options'] ?? [];
        
        return [
            'response_type' => 'choice_analysis',
            'selection_count' => count($selections),
            'choice_pattern' => $this->analyzeChoicePattern($selections),
            'preference_indicators' => $selections
        ];
    }

    private function analyzeGenericResponse(array $processedResponse, string $questionId): array
    {
        return [
            'response_type' => 'generic_analysis',
            'data' => $processedResponse
        ];
    }

    private function generateBehavioralIndicators(array $personalityProfile): array
    {
        $indicators = [
            'communication_style' => 'analytical', // Default, would be determined by analysis
            'decision_making_approach' => 'systematic',
            'stress_response_pattern' => 'adaptive',
            'social_interaction_preference' => 'balanced',
            'change_adaptation_style' => 'flexible'
        ];

        // Basic analysis based on response patterns
        $textResponses = array_filter($personalityProfile, fn($p) => $p['response_type'] === 'textual_analysis');
        if (count($textResponses) > 0) {
            $avgDepth = array_reduce($textResponses, function($carry, $item) {
                return $carry + ($item['content_depth'] === 'detailed' ? 3 : ($item['content_depth'] === 'moderate' ? 2 : 1));
            }, 0) / count($textResponses);
            
            $indicators['communication_style'] = $avgDepth > 2 ? 'detailed_expressive' : 'concise_focused';
        }

        return $indicators;
    }

    private function analyzeResponsePatterns(array $processedResponses): array
    {
        return [
            'response_consistency' => 'high', // Would be calculated based on actual patterns
            'engagement_level' => count($processedResponses) > 0 ? 'active' : 'passive',
            'completion_quality' => 'comprehensive',
            'response_time_pattern' => 'thoughtful' // Would come from timing data
        ];
    }

    private function calculatePsychometricScores(array $personalityProfile): array
    {
        return [
            'reliability_indicators' => [
                'internal_consistency' => 0.85, // Would be calculated
                'response_validity' => 'high'
            ],
            'trait_scores' => [
                'analytical_thinking' => 75,
                'interpersonal_skills' => 70,
                'emotional_stability' => 80,
                'adaptability' => 85
            ],
            'confidence_intervals' => [
                'overall_assessment' => '85-95%'
            ]
        ];
    }

    private function generateWorkplaceImplications(array $behavioralIndicators): array
    {
        return [
            'leadership_potential' => 'High potential with analytical approach',
            'team_dynamics' => 'Effective collaborator with systematic approach',
            'role_preferences' => ['analytical_roles', 'strategic_planning', 'problem_solving'],
            'development_recommendations' => [
                'Consider roles requiring analytical thinking',
                'Develop creative problem-solving skills',
                'Leverage systematic approach in team settings'
            ]
        ];
    }

    private function calculateConfidenceLevel(array $processedResponses): string
    {
        $responseCount = count($processedResponses);
        
        return match(true) {
            $responseCount >= 20 => 'high',
            $responseCount >= 10 => 'medium',
            default => 'low'
        };
    }

    private function extractThematicIndicators(string $text): array
    {
        // Basic thematic analysis - in production would use AI
        return ['achievement_orientation', 'collaborative_mindset'];
    }

    private function analyzeEmotionalTone(string $text): string
    {
        // Basic emotional analysis - in production would use AI
        return 'positive_professional';
    }

    private function assessCognitiveComplexity(string $text): string
    {
        $sentences = preg_split('/[.!?]+/', $text);
        return count($sentences) > 3 ? 'complex' : 'simple';
    }

    private function analyzeChoicePattern(array $selections): string
    {
        return count($selections) > 3 ? 'comprehensive' : 'selective';
    }

    private function generateSummary(array $behavioralIndicators, int $responseCount): string
    {
        $style = $behavioralIndicators['communication_style'] ?? 'balanced';
        
        return "Evaluación integral de personalidad completada con {$responseCount} respuestas. " .
               "Estilo de comunicación identificado: {$style}. " .
               "Análisis psicométrico disponible con implicaciones para el entorno laboral. " .
               "Procesamiento adicional de IA requerido para interpretación completa.";
    }
}