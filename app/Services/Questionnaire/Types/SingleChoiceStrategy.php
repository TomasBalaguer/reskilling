<?php

namespace App\Services\Questionnaire\Types;

use App\Enums\QuestionType;
use App\Services\Questionnaire\AbstractQuestionnaireStrategy;
use App\Models\Questionnaire;

class SingleChoiceStrategy extends AbstractQuestionnaireStrategy
{
    public function buildStructure(Questionnaire $questionnaire): array
    {
        if ($questionnaire->structure) {
            return $questionnaire->structure;
        }

        return [
            'metadata' => [
                'evaluation_type' => 'quantitative',
                'response_format' => 'single_selection',
                'scoring_method' => 'weighted_sum',
                'allows_multiple_answers' => false,
                'randomize_options' => false
            ],
            'sections' => [
                [
                    'id' => 'single_choice_questions',
                    'title' => $questionnaire->name,
                    'description' => $questionnaire->description,
                    'instructions' => [
                        'Lee cada pregunta cuidadosamente.',
                        'Selecciona una sola opción por pregunta.',
                        'Elige la opción que mejor represente tu respuesta.',
                        'No puedes cambiar tu respuesta una vez seleccionada.'
                    ],
                    'questions' => $this->transformQuestionsToStructure($questionnaire->questions ?? []),
                    'response_type' => 'single_selection'
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
            $selectedValue = $response['processed_response']['text_response'] ?? '';
            $questionScore = 0;
            $maxQuestionScore = 0;

            // Find the selected option and get its weight
            $questionData = $this->getQuestionData($questionId, $response);
            if ($questionData && isset($questionData['options'])) {
                foreach ($questionData['options'] as $option) {
                    $maxQuestionScore = max($maxQuestionScore, $option['weight'] ?? 1);
                    
                    if ($option['value'] === $selectedValue) {
                        $questionScore = $option['weight'] ?? 1;
                    }
                }
            } else {
                // Default scoring if no options defined
                $questionScore = !empty($selectedValue) ? 1 : 0;
                $maxQuestionScore = 1;
            }

            $totalScore += $questionScore;
            $maxPossibleScore += $maxQuestionScore;

            $responseDetails[$questionId] = [
                'question_id' => $questionId,
                'selected_option' => $selectedValue,
                'question_score' => $questionScore,
                'max_question_score' => $maxQuestionScore,
                'is_correct' => $questionScore === $maxQuestionScore
            ];
        }

        return [
            'scoring_type' => 'SINGLE_CHOICE',
            'questionnaire_name' => 'Cuestionario de Opción Única',
            'total_score' => $totalScore,
            'max_possible_score' => $maxPossibleScore,
            'completion_percentage' => $maxPossibleScore > 0 ? ($totalScore / $maxPossibleScore) * 100 : 0,
            'correct_answers' => count(array_filter($responseDetails, fn($r) => $r['is_correct'])),
            'total_questions' => count($responseDetails),
            'accuracy_percentage' => count($responseDetails) > 0 ? 
                (count(array_filter($responseDetails, fn($r) => $r['is_correct'])) / count($responseDetails)) * 100 : 0,
            'response_details' => $responseDetails,
            'category_scores' => $categoryScores,
            'respondent_data' => $respondentData,
            'summary' => $this->generateSummary($totalScore, $maxPossibleScore, count($processedResponses), $responseDetails)
        ];
    }

    public function requiresAIProcessing(): bool
    {
        return false;
    }

    public function getSupportedQuestionTypes(): array
    {
        return [
            QuestionType::SINGLE_CHOICE->value,
            QuestionType::RADIO_BUTTON->value
        ];
    }

    protected function getEstimatedDuration(): int
    {
        return 15; // 15 minutes for single choice questionnaire
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
                'type' => 'single_choice',
                'required' => true,
                'order' => (int) str_replace(['q', 'question_'], '', $id),
                'options' => $this->formatOptions($options),
                'validation_rules' => ['required', 'string']
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
                    'weight' => $option['weight'] ?? 1,
                    'is_correct' => $option['is_correct'] ?? false
                ];
            } else {
                $formattedOptions[] = [
                    'value' => $key,
                    'label' => $option,
                    'weight' => 1,
                    'is_correct' => false
                ];
            }
        }

        return $formattedOptions;
    }

    private function getQuestionData(string $questionId, array $response): ?array
    {
        // This would typically come from the questionnaire structure
        // For now, return null as we don't have access to the original structure here
        return null;
    }

    private function generateSummary(int $totalScore, int $maxScore, int $questionCount, array $responseDetails): string
    {
        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100, 1) : 0;
        $correctAnswers = count(array_filter($responseDetails, fn($r) => $r['is_correct']));
        $accuracy = $questionCount > 0 ? round(($correctAnswers / $questionCount) * 100, 1) : 0;
        
        return "Cuestionario de opción única completado. " .
               "Puntuación: {$totalScore}/{$maxScore} ({$percentage}%). " .
               "Respuestas correctas: {$correctAnswers}/{$questionCount} ({$accuracy}%).";
    }
}