<?php

namespace App\Services\Questionnaire;

use App\Enums\QuestionnaireType;
use App\Models\Questionnaire;
use App\Services\AIInterpretationService;
use App\Services\Questionnaire\Types\ReflectiveQuestionsStrategy;
use Illuminate\Support\Facades\Log;

class QuestionnaireStrategyFactory
{
    protected AIInterpretationService $aiService;
    protected array $strategies = [];

    public function __construct(AIInterpretationService $aiService)
    {
        $this->aiService = $aiService;
        $this->registerDefaultStrategies();
    }

    /**
     * Get strategy instance for questionnaire
     */
    public function getStrategy(Questionnaire $questionnaire): QuestionnaireStrategyInterface
    {
        // Handle case where questionnaire_type is already an enum instance
        $questionnaireType = $questionnaire->questionnaire_type;
        
        if ($questionnaireType instanceof QuestionnaireType) {
            $type = $questionnaireType;
        } else {
            // Try to parse from string value
            $type = QuestionnaireType::tryFrom($questionnaireType ?? $questionnaire->scoring_type);
            
            if (!$type) {
                Log::warning("Unknown questionnaire type: {$questionnaire->scoring_type}");
                $type = QuestionnaireType::TEXT_RESPONSE; // fallback
            }
        }

        return $this->getStrategyByType($type);
    }

    /**
     * Get strategy by questionnaire type enum
     */
    public function getStrategyByType(QuestionnaireType $type): QuestionnaireStrategyInterface
    {
        $strategyClass = $type->getStrategyClass();
        
        // Return cached instance if available
        if (isset($this->strategies[$strategyClass])) {
            return $this->strategies[$strategyClass];
        }

        // Create new instance
        if (class_exists($strategyClass)) {
            $this->strategies[$strategyClass] = new $strategyClass($this->aiService);
            return $this->strategies[$strategyClass];
        }

        // Fallback to base strategy
        Log::warning("Strategy class not found: {$strategyClass}. Using fallback.");
        return $this->getFallbackStrategy();
    }

    /**
     * Register a custom strategy
     */
    public function registerStrategy(QuestionnaireType $type, QuestionnaireStrategyInterface $strategy): void
    {
        $strategyClass = $type->getStrategyClass();
        $this->strategies[$strategyClass] = $strategy;
    }

    /**
     * Get all available questionnaire types
     */
    public function getAvailableTypes(): array
    {
        return array_map(
            fn(QuestionnaireType $type) => [
                'value' => $type->value,
                'display_name' => $type->getDisplayName(),
                'response_format' => $type->getResponseFormat(),
                'is_audio_based' => $type->isAudioBased(),
                'requires_ai_processing' => $type->requiresAIProcessing(),
            ],
            QuestionnaireType::cases()
        );
    }

    /**
     * Check if strategy exists for type
     */
    public function hasStrategy(QuestionnaireType $type): bool
    {
        $strategyClass = $type->getStrategyClass();
        return class_exists($strategyClass) || isset($this->strategies[$strategyClass]);
    }

    /**
     * Register default strategies
     */
    protected function registerDefaultStrategies(): void
    {
        // Strategies will be auto-loaded when needed
        // This method can be used to pre-register specific instances if needed
    }

    /**
     * Get fallback strategy for unknown types
     */
    protected function getFallbackStrategy(): QuestionnaireStrategyInterface
    {
        // Return a basic text response strategy as fallback
        return new class($this->aiService) extends AbstractQuestionnaireStrategy {
            public function buildStructure(Questionnaire $questionnaire): array
            {
                return [
                    'sections' => [
                        [
                            'id' => 'questions',
                            'title' => $questionnaire->name,
                            'questions' => array_map(
                                fn($question, $id) => [
                                    'id' => $id,
                                    'text' => $question,
                                    'type' => 'text_input',
                                    'required' => true
                                ],
                                $questionnaire->questions ?? [],
                                array_keys($questionnaire->questions ?? [])
                            )
                        ]
                    ]
                ];
            }

            public function calculateScores(array $processedResponses, array $respondentData = []): array
            {
                return [
                    'scoring_type' => 'FALLBACK',
                    'processed_responses' => $processedResponses,
                    'respondent_data' => $respondentData
                ];
            }

            public function requiresAIProcessing(): bool
            {
                return false;
            }

            public function getSupportedQuestionTypes(): array
            {
                return ['text_input', 'textarea'];
            }

            protected function getEstimatedDuration(): int
            {
                return 15; // 15 minutes default
            }
        };
    }
}