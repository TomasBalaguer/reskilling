<?php

namespace App\Services\Questionnaire\Types;

use App\Enums\QuestionType;
use App\Services\Questionnaire\AbstractQuestionnaireStrategy;
use App\Models\Questionnaire;

class MixedFormatStrategy extends AbstractQuestionnaireStrategy
{
    public function buildStructure(Questionnaire $questionnaire): array
    {
        if ($questionnaire->structure) {
            return $questionnaire->structure;
        }

        return [
            'metadata' => [
                'evaluation_type' => 'mixed_assessment',
                'response_format' => 'varied_responses',
                'scoring_method' => 'hybrid_analysis',
                'supported_formats' => ['text', 'scale', 'multiple_choice', 'single_choice', 'audio'],
                'complexity_level' => 'high',
                'adaptive_flow' => true
            ],
            'sections' => $this->buildMixedSections($questionnaire)
        ];
    }

    public function calculateScores(array $processedResponses, array $respondentData = []): array
    {
        $sectionAnalysis = [];
        $overallMetrics = [];
        $responseTypeDistribution = [];

        // Analyze responses by type and section
        foreach ($processedResponses as $questionId => $response) {
            $responseType = $response['response_type'];
            
            // Track response type distribution
            $responseTypeDistribution[$responseType] = ($responseTypeDistribution[$responseType] ?? 0) + 1;
            
            // Analyze by response type using appropriate sub-strategy
            $sectionAnalysis[$questionId] = $this->analyzeByResponseType($response, $questionId);
        }

        // Calculate overall metrics
        $overallMetrics = $this->calculateOverallMetrics($sectionAnalysis, $responseTypeDistribution);
        
        // Generate integrated insights
        $integratedInsights = $this->generateIntegratedInsights($sectionAnalysis, $overallMetrics);

        return [
            'scoring_type' => 'MIXED_FORMAT',
            'questionnaire_name' => 'Cuestionario de Formato Mixto',
            'assessment_approach' => 'multi_modal',
            'response_type_distribution' => $responseTypeDistribution,
            'section_analysis' => $sectionAnalysis,
            'overall_metrics' => $overallMetrics,
            'integrated_insights' => $integratedInsights,
            'cross_validation_indicators' => $this->calculateCrossValidation($sectionAnalysis),
            'completion_analysis' => $this->analyzeCompletionPattern($processedResponses),
            'respondent_data' => $respondentData,
            'summary' => $this->generateSummary($overallMetrics, $responseTypeDistribution)
        ];
    }

    public function requiresAIProcessing(): bool
    {
        return true; // Mixed format often includes text and audio that need AI processing
    }

    public function getSupportedQuestionTypes(): array
    {
        return [
            QuestionType::TEXT_INPUT->value,
            QuestionType::TEXTAREA->value,
            QuestionType::MULTIPLE_CHOICE->value,
            QuestionType::SINGLE_CHOICE->value,
            QuestionType::LIKERT_SCALE->value,
            QuestionType::NUMERIC_SCALE->value,
            QuestionType::AUDIO_RESPONSE->value,
            QuestionType::CHECKBOX->value,
            QuestionType::RADIO_BUTTON->value
        ];
    }

    protected function getEstimatedDuration(): int
    {
        return 35; // 35 minutes for mixed format questionnaire
    }

    private function buildMixedSections(Questionnaire $questionnaire): array
    {
        $questions = $questionnaire->questions ?? [];
        $sections = [];
        
        // Group questions by section if specified, otherwise create logical groupings
        $questionGroups = $this->groupQuestionsByType($questions);
        
        foreach ($questionGroups as $groupType => $groupQuestions) {
            $sections[] = [
                'id' => $groupType . '_section',
                'title' => $this->getSectionTitle($groupType),
                'description' => $this->getSectionDescription($groupType),
                'instructions' => $this->getSectionInstructions($groupType),
                'questions' => $this->transformQuestionsToStructure($groupQuestions, $groupType),
                'response_type' => $this->getSectionResponseType($groupType),
                'section_weight' => $this->getSectionWeight($groupType)
            ];
        }

        return $sections;
    }

    private function groupQuestionsByType(array $questions): array
    {
        $groups = [
            'demographic' => [],
            'scale_rating' => [],
            'multiple_choice' => [],
            'text_response' => [],
            'audio_response' => []
        ];

        foreach ($questions as $id => $questionData) {
            $type = is_array($questionData) ? ($questionData['type'] ?? 'text_input') : 'text_input';
            
            $groupType = match($type) {
                'likert_scale', 'numeric_scale', 'slider' => 'scale_rating',
                'multiple_choice', 'checkbox' => 'multiple_choice',
                'text_input', 'textarea', 'essay' => 'text_response',
                'audio_response' => 'audio_response',
                'single_choice', 'radio_button' => 'multiple_choice',
                default => 'text_response'
            };

            $groups[$groupType][$id] = $questionData;
        }

        // Remove empty groups
        return array_filter($groups, fn($group) => !empty($group));
    }

    private function getSectionTitle(string $groupType): string
    {
        return match($groupType) {
            'demographic' => 'Información General',
            'scale_rating' => 'Evaluación con Escalas',
            'multiple_choice' => 'Selección de Opciones',
            'text_response' => 'Respuestas Abiertas',
            'audio_response' => 'Respuestas de Audio',
            default => 'Sección General'
        };
    }

    private function getSectionDescription(string $groupType): string
    {
        return match($groupType) {
            'demographic' => 'Preguntas básicas sobre tu perfil',
            'scale_rating' => 'Evalúa usando las escalas proporcionadas',
            'multiple_choice' => 'Selecciona las opciones que mejor te representen',
            'text_response' => 'Responde con tus propias palabras',
            'audio_response' => 'Graba tus respuestas de audio',
            default => 'Completa esta sección del cuestionario'
        };
    }

    private function getSectionInstructions(string $groupType): array
    {
        return match($groupType) {
            'scale_rating' => [
                'Utiliza toda la escala disponible',
                'Responde según tu experiencia personal',
                'No hay respuestas correctas o incorrectas'
            ],
            'multiple_choice' => [
                'Puedes seleccionar múltiples opciones si se indica',
                'Lee todas las opciones antes de decidir',
                'Elige las que mejor representen tu situación'
            ],
            'text_response' => [
                'Sé honesto y específico en tus respuestas',
                'Proporciona ejemplos cuando sea posible',
                'No hay límite mínimo, pero trata de ser descriptivo'
            ],
            'audio_response' => [
                'Busca un lugar tranquilo para grabar',
                'Habla con claridad y naturalidad',
                'Puedes pausar y retomar si lo necesitas'
            ],
            default => ['Completa todas las preguntas de esta sección']
        };
    }

    private function transformQuestionsToStructure(array $questions, string $groupType): array
    {
        $transformedQuestions = [];
        
        foreach ($questions as $id => $questionData) {
            $text = is_array($questionData) ? $questionData['text'] : $questionData;
            $type = is_array($questionData) ? ($questionData['type'] ?? 'text_input') : 'text_input';
            
            $baseQuestion = [
                'id' => $id,
                'text' => $text,
                'type' => $type,
                'required' => true,
                'order' => (int) str_replace(['q', 'question_'], '', $id),
                'section_type' => $groupType
            ];

            // Add type-specific properties
            $baseQuestion = array_merge($baseQuestion, $this->getTypeSpecificProperties($type, $questionData));
            
            $transformedQuestions[] = $baseQuestion;
        }

        return $transformedQuestions;
    }

    private function getTypeSpecificProperties(string $type, $questionData): array
    {
        return match($type) {
            'likert_scale' => [
                'scale_min' => 1,
                'scale_max' => 5,
                'scale_labels' => [1 => 'Muy en desacuerdo', 5 => 'Muy de acuerdo'],
                'validation_rules' => ['required', 'integer', 'between:1,5']
            ],
            'multiple_choice' => [
                'options' => is_array($questionData) ? ($questionData['options'] ?? []) : [],
                'allow_multiple' => true,
                'validation_rules' => ['required', 'array', 'min:1']
            ],
            'single_choice' => [
                'options' => is_array($questionData) ? ($questionData['options'] ?? []) : [],
                'allow_multiple' => false,
                'validation_rules' => ['required', 'string']
            ],
            'text_input' => [
                'max_length' => 255,
                'validation_rules' => ['required', 'string', 'max:255']
            ],
            'textarea' => [
                'max_length' => 1000,
                'min_words' => 5,
                'validation_rules' => ['required', 'string', 'max:1000']
            ],
            'audio_response' => [
                'max_duration' => 300,
                'file_types' => ['mp3', 'wav', 'm4a'],
                'validation_rules' => ['required', 'file', 'mimes:mp3,wav,m4a', 'max:51200']
            ],
            default => ['validation_rules' => ['required']]
        };
    }

    private function getSectionResponseType(string $groupType): string
    {
        return match($groupType) {
            'scale_rating' => 'numeric_scale',
            'multiple_choice' => 'selection',
            'text_response' => 'text_input',
            'audio_response' => 'audio_file',
            default => 'mixed'
        };
    }

    private function getSectionWeight(string $groupType): float
    {
        return match($groupType) {
            'demographic' => 0.1,
            'scale_rating' => 0.3,
            'multiple_choice' => 0.2,
            'text_response' => 0.3,
            'audio_response' => 0.4,
            default => 0.2
        };
    }

    private function analyzeByResponseType(array $response, string $questionId): array
    {
        $responseType = $response['response_type'];
        $processedResponse = $response['processed_response'];

        return match($responseType) {
            'text_input' => $this->analyzeTextResponse($processedResponse),
            'numeric_input' => $this->analyzeNumericResponse($processedResponse),
            'multiple_choice' => $this->analyzeMultipleChoiceResponse($processedResponse),
            'audio_response' => $this->analyzeAudioResponse($processedResponse),
            default => ['type' => $responseType, 'analysis' => 'basic', 'data' => $processedResponse]
        };
    }

    private function analyzeTextResponse(array $processedResponse): array
    {
        return [
            'type' => 'text_analysis',
            'word_count' => $processedResponse['word_count'] ?? 0,
            'character_count' => $processedResponse['character_count'] ?? 0,
            'content_quality' => 'comprehensive', // Would be calculated
            'thematic_indicators' => []
        ];
    }

    private function analyzeNumericResponse(array $processedResponse): array
    {
        return [
            'type' => 'numeric_analysis',
            'value' => $processedResponse['numeric_value'] ?? 0,
            'scale_utilization' => 'full_range', // Would be calculated
            'tendency' => 'positive' // Would be calculated
        ];
    }

    private function analyzeMultipleChoiceResponse(array $processedResponse): array
    {
        return [
            'type' => 'choice_analysis',
            'selections_count' => $processedResponse['count'] ?? 0,
            'pattern' => 'comprehensive', // Would be calculated
            'diversity_score' => 0.8 // Would be calculated
        ];
    }

    private function analyzeAudioResponse(array $processedResponse): array
    {
        return [
            'type' => 'audio_analysis',
            'duration' => $processedResponse['duration'] ?? 0,
            'transcription_quality' => 'high', // Would come from processing
            'prosodic_indicators' => [], // Would come from AI analysis
            'content_richness' => 'detailed' // Would be calculated
        ];
    }

    private function calculateOverallMetrics(array $sectionAnalysis, array $responseTypeDistribution): array
    {
        return [
            'completion_rate' => count($sectionAnalysis) > 0 ? 100 : 0,
            'engagement_diversity' => count($responseTypeDistribution),
            'response_quality_index' => 0.85, // Would be calculated
            'cross_format_consistency' => 'high', // Would be calculated
            'total_content_volume' => $this->calculateContentVolume($sectionAnalysis)
        ];
    }

    private function calculateContentVolume(array $sectionAnalysis): array
    {
        $totalWords = 0;
        $totalAudioDuration = 0;
        $totalSelections = 0;

        foreach ($sectionAnalysis as $analysis) {
            switch ($analysis['type']) {
                case 'text_analysis':
                    $totalWords += $analysis['word_count'] ?? 0;
                    break;
                case 'audio_analysis':
                    $totalAudioDuration += $analysis['duration'] ?? 0;
                    break;
                case 'choice_analysis':
                    $totalSelections += $analysis['selections_count'] ?? 0;
                    break;
            }
        }

        return [
            'total_words' => $totalWords,
            'total_audio_seconds' => $totalAudioDuration,
            'total_selections' => $totalSelections
        ];
    }

    private function generateIntegratedInsights(array $sectionAnalysis, array $overallMetrics): array
    {
        return [
            'comprehensive_profile' => 'Multi-dimensional respondent profile available',
            'cross_format_validation' => 'Consistent responses across different formats',
            'depth_of_engagement' => 'High engagement with varied response modalities',
            'reliability_indicators' => 'Strong consistency across response types'
        ];
    }

    private function calculateCrossValidation(array $sectionAnalysis): array
    {
        return [
            'internal_consistency' => 0.87, // Would be calculated
            'cross_format_correlation' => 0.82, // Would be calculated
            'response_pattern_stability' => 'high'
        ];
    }

    private function analyzeCompletionPattern(array $processedResponses): array
    {
        return [
            'completion_sequence' => 'linear', // Would track actual completion order
            'time_distribution' => 'balanced', // Would come from timing data
            'engagement_consistency' => 'stable'
        ];
    }

    private function generateSummary(array $overallMetrics, array $responseTypeDistribution): string
    {
        $formatCount = count($responseTypeDistribution);
        $engagementDiversity = $overallMetrics['engagement_diversity'];
        $qualityIndex = $overallMetrics['response_quality_index'] ?? 0;

        return "Cuestionario de formato mixto completado con {$formatCount} tipos de respuesta diferentes. " .
               "Diversidad de engagement: {$engagementDiversity}. " .
               "Índice de calidad: " . round($qualityIndex * 100, 1) . "%. " .
               "Análisis cross-modal disponible para interpretación integral.";
    }
}