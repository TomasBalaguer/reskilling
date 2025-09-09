<?php

namespace App\Services\Questionnaire\Types;

use App\Enums\QuestionType;
use App\Services\Questionnaire\AbstractQuestionnaireStrategy;
use App\Models\Questionnaire;

class MultipleChoiceStrategy extends AbstractQuestionnaireStrategy
{
    public function buildStructure(Questionnaire $questionnaire): array
    {
        if ($questionnaire->structure) {
            return $questionnaire->structure;
        }

        return [
            'metadata' => [
                'evaluation_type' => 'quantitative',
                'response_format' => 'multiple_selection',
                'scoring_method' => 'weighted_sum',
                'allows_multiple_answers' => true,
                'randomize_options' => false
            ],
            'sections' => [
                [
                    'id' => 'multiple_choice_questions',
                    'title' => $questionnaire->name,
                    'description' => $questionnaire->description,
                    'instructions' => [
                        'Lee cada pregunta cuidadosamente.',
                        'Puedes seleccionar múltiples opciones en cada pregunta.',
                        'Asegúrate de seleccionar todas las opciones que consideres apropiadas.',
                        'No hay límite de tiempo, tómate el tiempo que necesites.'
                    ],
                    'questions' => $this->transformQuestionsToStructure($questionnaire->questions ?? []),
                    'response_type' => 'multiple_selection'
                ]
            ]
        ];
    }

    public function calculateScores(array $processedResponses, array $respondentData = []): array
    {
        $totalScore = 0;
        $maxPossibleScore = 0;
        $categoryScores = [];
        $responseDetails = [];

        foreach ($processedResponses as $questionId => $response) {
            $selectedOptions = $response['processed_response']['selected_options'] ?? [];
            $questionScore = 0;
            $maxQuestionScore = 0;

            // Calculate score based on weighted selections
            foreach ($selectedOptions as $option) {
                if (is_array($option) && isset($option['value'], $option['weight'])) {
                    $questionScore += $option['weight'];
                    $maxQuestionScore += $option['weight'];
                } elseif (is_string($option)) {
                    $questionScore += 1; // Default weight
                    $maxQuestionScore += 1;
                }
            }

            $totalScore += $questionScore;
            $maxPossibleScore += $maxQuestionScore;

            $responseDetails[$questionId] = [
                'question_id' => $questionId,
                'selected_options' => $selectedOptions,
                'question_score' => $questionScore,
                'max_question_score' => $maxQuestionScore,
                'completion_percentage' => $maxQuestionScore > 0 ? ($questionScore / $maxQuestionScore) * 100 : 0
            ];
        }

        return [
            'scoring_type' => 'MULTIPLE_CHOICE',
            'questionnaire_name' => 'Cuestionario de Selección Múltiple',
            'total_score' => $totalScore,
            'max_possible_score' => $maxPossibleScore,
            'completion_percentage' => $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0,
            'response_details' => $responseDetails,
            'category_scores' => $categoryScores,
            'respondent_data' => $respondentData,
            'summary' => $this->generateSummary($totalScore, $maxPossibleScore, count($processedResponses))
        ];
    }

    public function requiresAIProcessing(): bool
    {
        return false;
    }

    public function getSupportedQuestionTypes(): array
    {
        return [
            QuestionType::MULTIPLE_CHOICE->value,
            QuestionType::CHECKBOX->value
        ];
    }

    protected function getEstimatedDuration(): int
    {
        return 20; // 20 minutes for multiple choice questionnaire
    }

    private function transformQuestionsToStructure(array $questions): array
    {
        $transformedQuestions = [];
        
        foreach ($questions as $id => $questionData) {
            $text = is_array($questionData) ? $questionData['text'] : $questionData;
            $options = is_array($questionData) ? ($questionData['options'] ?? []) : [];
            
            $transformedQuestions[] = [
                'id' => $id,
                'text' => $text,
                'type' => 'multiple_choice',
                'required' => true,
                'order' => (int) str_replace(['q', 'question_'], '', $id),
                'options' => $this->formatOptions($options),
                'min_selections' => 1,
                'max_selections' => null, // No limit
                'validation_rules' => ['required', 'array', 'min:1']
            ];
        }

        return $transformedQuestions;
    }

    private function formatOptions(array $options): array
    {
        $formattedOptions = [];
        
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                $formattedOptions[] = [
                    'value' => $option['value'] ?? $key,
                    'label' => $option['label'] ?? $option['text'] ?? $key,
                    'weight' => $option['weight'] ?? 1
                ];
            } else {
                $formattedOptions[] = [
                    'value' => $key,
                    'label' => $option,
                    'weight' => 1
                ];
            }
        }

        return $formattedOptions;
    }

    private function generateSummary(int $totalScore, int $maxScore, int $questionCount): string
    {
        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 1) : 0;
        
        return "Cuestionario de selección múltiple completado. " .
               "Puntuación: {$totalScore}/{$maxScore} ({$percentage}%). " .
               "Respondidas {$questionCount} preguntas con opciones múltiples.";
    }
}