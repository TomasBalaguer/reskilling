<?php

namespace App\Services\Questionnaire;

use App\Models\Questionnaire;
use App\Models\CampaignResponse;
use App\Services\AIInterpretationService;
use Illuminate\Support\Facades\Validator;

abstract class AbstractQuestionnaireStrategy implements QuestionnaireStrategyInterface
{
    protected AIInterpretationService $aiService;

    public function __construct(AIInterpretationService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Build the questionnaire structure for frontend consumption
     * Override in specific implementations
     */
    abstract public function buildStructure(Questionnaire $questionnaire): array;

    /**
     * Validate responses according to questionnaire type
     */
    public function validateResponses(array $responses, Questionnaire $questionnaire): array
    {
        $errors = [];
        $structure = $questionnaire->structure ?? $this->buildStructure($questionnaire);
        
        if (!isset($structure['sections'])) {
            return ['structure' => ['Invalid questionnaire structure']];
        }

        foreach ($structure['sections'] as $section) {
            if (!isset($section['questions'])) continue;
            
            foreach ($section['questions'] as $question) {
                $questionId = $question['id'];
                $questionType = $question['type'] ?? 'text_input';
                
                if ($question['required'] ?? false) {
                    if (!isset($responses[$questionId]) || empty($responses[$questionId])) {
                        $errors[$questionId] = ["La pregunta '{$question['text']}' es requerida"];
                        continue;
                    }
                }

                if (isset($responses[$questionId])) {
                    $validationErrors = $this->validateQuestionResponse(
                        $responses[$questionId], 
                        $question, 
                        $questionType
                    );
                    
                    if (!empty($validationErrors)) {
                        $errors[$questionId] = $validationErrors;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Process raw responses into structured format
     */
    public function processResponses(array $rawResponses, Questionnaire $questionnaire): array
    {
        $processedResponses = [];
        
        foreach ($rawResponses as $questionId => $response) {
            $processedResponses[$questionId] = [
                'question_id' => $questionId,
                'raw_response' => $response,
                'processed_response' => $this->processIndividualResponse($response, $questionId, $questionnaire),
                'response_type' => $this->getResponseType($response),
                'submitted_at' => now(),
                'metadata' => $this->extractResponseMetadata($response, $questionId)
            ];
        }

        return $processedResponses;
    }

    /**
     * Generate AI interpretation if needed - default implementation
     */
    public function generateAIInterpretation(CampaignResponse $response, array $processedData): ?array
    {
        if (!$this->requiresAIProcessing()) {
            return null;
        }

        return $this->aiService->generateInterpretation(
            $response,
            $response->campaign->questionnaires()->first(),
            $processedData
        );
    }

    /**
     * Get default metadata structure
     */
    public function getMetadata(): array
    {
        return [
            'version' => '1.0',
            'supported_question_types' => $this->getSupportedQuestionTypes(),
            'requires_ai_processing' => $this->requiresAIProcessing(),
            'estimated_duration' => $this->getEstimatedDuration(),
        ];
    }

    /**
     * Validate individual question response
     */
    protected function validateQuestionResponse($response, array $question, string $questionType): array
    {
        $rules = $this->getValidationRules($questionType, $question);
        
        $validator = Validator::make(
            ['response' => $response], 
            ['response' => $rules]
        );

        return $validator->fails() ? $validator->errors()->get('response') : [];
    }

    /**
     * Get validation rules for question type
     */
    protected function getValidationRules(string $questionType, array $question): array
    {
        $baseRules = match($questionType) {
            'audio_response' => ['required', 'array'],
            'multiple_choice' => ['required', 'array', 'min:1'],
            'single_choice' => ['required', 'string'],
            'text_input' => ['required', 'string', 'max:255'],
            'textarea' => ['required', 'string', 'max:2000'],
            'likert_scale' => ['required', 'integer'],
            'numeric_scale' => ['required', 'numeric'],
            default => ['required']
        };

        // Add question-specific rules
        if (isset($question['min_value']) && isset($question['max_value'])) {
            $baseRules[] = "between:{$question['min_value']},{$question['max_value']}";
        }

        return $baseRules;
    }

    /**
     * Process individual response based on type
     */
    protected function processIndividualResponse($response, string $questionId, Questionnaire $questionnaire)
    {
        if (is_array($response) && isset($response['audio_file'])) {
            return [
                'type' => 'audio',
                'audio_file_path' => $response['audio_file'],
                'duration' => $response['duration'] ?? null,
                'transcription_pending' => true
            ];
        }

        if (is_array($response)) {
            return [
                'type' => 'multiple_selection',
                'selected_options' => $response,
                'count' => count($response)
            ];
        }

        if (is_string($response)) {
            return [
                'type' => 'text',
                'text_response' => $response,
                'word_count' => str_word_count($response),
                'character_count' => strlen($response)
            ];
        }

        if (is_numeric($response)) {
            return [
                'type' => 'numeric',
                'numeric_value' => $response
            ];
        }

        return [
            'type' => 'unknown',
            'raw_value' => $response
        ];
    }

    /**
     * Determine response type
     */
    protected function getResponseType($response): string
    {
        if (is_array($response) && isset($response['audio_file'])) {
            return 'audio_response';
        }
        
        if (is_array($response)) {
            return 'multiple_choice';
        }
        
        if (is_string($response)) {
            return 'text_input';
        }
        
        if (is_numeric($response)) {
            return 'numeric_input';
        }
        
        return 'unknown';
    }

    /**
     * Extract response metadata
     */
    protected function extractResponseMetadata($response, string $questionId): array
    {
        $metadata = [
            'question_id' => $questionId,
            'response_size' => is_array($response) ? count($response) : strlen((string)$response)
        ];

        if (is_array($response) && isset($response['audio_file'])) {
            $metadata['audio_metadata'] = [
                'file_path' => $response['audio_file'],
                'duration' => $response['duration'] ?? null,
                'file_size' => $response['file_size'] ?? null
            ];
        }

        return $metadata;
    }

    /**
     * Get estimated duration for this questionnaire type
     */
    abstract protected function getEstimatedDuration(): int;
}