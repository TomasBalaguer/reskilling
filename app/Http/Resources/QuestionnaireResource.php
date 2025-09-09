<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionnaireResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->getQuestionnaireType()->value,
            'questionnaire_type' => $this->getQuestionnaireType()->value,
            'scoring_type' => $this->scoring_type,
            'structure' => $this->buildStructure(),
            'metadata' => $this->getEnhancedMetadata(),
            'status' => [
                'is_active' => $this->is_active,
                'is_completed' => $this->when(isset($this->is_completed), $this->is_completed),
                'completion_status' => $this->getCompletionStatus()
            ],
            'timing' => [
                'estimated_duration_minutes' => $this->estimated_duration_minutes,
                'max_duration_minutes' => $this->max_duration_minutes,
                'estimated_duration_display' => $this->getEstimatedDurationDisplay()
            ],
            'processing' => [
                'requires_ai_processing' => $this->requiresAIProcessing(),
                'is_audio_based' => $this->getQuestionnaireType()->isAudioBased(),
                'response_format' => $this->getQuestionnaireType()->getResponseFormat(),
                'processing_complexity' => $this->getProcessingComplexity()
            ],
            'questions_summary' => [
                'total_questions' => $this->getTotalQuestions(),
                'sections_count' => $this->getSectionsCount(),
                'question_types' => $this->getQuestionTypes()
            ],
            'respondent_info' => [
                'respondent_type' => $this->when(isset($this->respondent_type), $this->respondent_type),
                'respondent_name' => $this->when(isset($this->respondent_name), $this->respondent_name),
                'detail_id' => $this->when(isset($this->detail_id), $this->detail_id)
            ],
            'timestamps' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'version' => $this->version ?? 1
            ]
        ];
    }

    /**
     * Get completion status
     */
    private function getCompletionStatus(): string
    {
        if (isset($this->is_completed) && $this->is_completed) {
            return 'completed';
        }
        
        if (isset($this->detail_id) && $this->detail_id) {
            return 'in_progress';
        }
        
        return 'not_started';
    }

    /**
     * Get estimated duration display
     */
    private function getEstimatedDurationDisplay(): string
    {
        $minutes = $this->estimated_duration_minutes ?? 0;
        
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;
            
            if ($remainingMinutes === 0) {
                return $hours . ' hora' . ($hours > 1 ? 's' : '');
            } else {
                return $hours . 'h ' . $remainingMinutes . 'min';
            }
        }
        
        return $minutes . ' minuto' . ($minutes !== 1 ? 's' : '');
    }

    /**
     * Get processing complexity level
     */
    private function getProcessingComplexity(): string
    {
        $questionnaireType = $this->getQuestionnaireType();
        
        return match($questionnaireType->value) {
            'REFLECTIVE_QUESTIONS' => 'high',      // Audio + AI processing
            'TEXT_RESPONSE', 'PERSONALITY_ASSESSMENT' => 'medium', // AI text analysis
            'MIXED_FORMAT' => 'medium',            // Multiple processing types
            'BIG_FIVE' => 'low',                   // Statistical only
            default => 'low'                       // Simple scoring
        };
    }

    /**
     * Get total number of questions
     */
    private function getTotalQuestions(): int
    {
        $structure = $this->buildStructure();
        $totalQuestions = 0;

        if (isset($structure['sections'])) {
            foreach ($structure['sections'] as $section) {
                if (isset($section['questions'])) {
                    $totalQuestions += count($section['questions']);
                }
            }
        }

        return $totalQuestions;
    }

    /**
     * Get number of sections
     */
    private function getSectionsCount(): int
    {
        $structure = $this->buildStructure();
        return count($structure['sections'] ?? []);
    }

    /**
     * Get question types present in questionnaire
     */
    private function getQuestionTypes(): array
    {
        $structure = $this->buildStructure();
        $questionTypes = [];

        if (isset($structure['sections'])) {
            foreach ($structure['sections'] as $section) {
                if (isset($section['questions'])) {
                    foreach ($section['questions'] as $question) {
                        $type = $question['type'] ?? 'text_input';
                        if (!in_array($type, $questionTypes)) {
                            $questionTypes[] = $type;
                        }
                    }
                }
            }
        }

        return $questionTypes;
    }
}