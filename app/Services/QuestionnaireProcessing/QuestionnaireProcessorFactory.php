<?php

namespace App\Services\QuestionnaireProcessing;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;

class QuestionnaireProcessorFactory
{
    /**
     * Mapping of questionnaire types to their processor classes
     *
     * @var array
     */
    protected $processorMap = [
        'STAI' => StaiQuestionnaireProcessor::class,
        'SCL90' => Scl90QuestionnaireProcessor::class,
        'STAXI' => StaxiQuestionnaireProcessor::class,
        'IVA' => IvaQuestionnaireProcessor::class,
        'JAS' => JasQuestionnaireProcessor::class,
        'CRI' => CriQuestionnaireProcessor::class,
        'HOLMES_RAHE' => HolmesRaheQuestionnaireProcessor::class,
        'OSI' => OsiQuestionnaireProcessor::class,
        'BIENESTAR' => BienestarQuestionnaireProcessor::class,
        'BIG_FIVE' => BigFiveQuestionnaireProcessor::class,
        'BECK' => BeckQuestionnaireProcessor::class,
        'SACKS' => SacksQuestionnaireProcessor::class,
        'SACKS_SHORT' => SacksShortQuestionnaireProcessor::class,
        'BAI' => BaiQuestionnaireProcessor::class,
        'TDAH' => TdahQuestionnaireProcessor::class,
        'TDAH2' => Tdah2QuestionnaireProcessor::class,
        'TDAH3' => Tdah3QuestionnaireProcessor::class,
        'REFLECTIVE_QUESTIONS' => ReflectiveQuestionsProcessor::class,
        // Additional questionnaire types can be added here
        // Add other questionnaire types here as they are implemented
        // etc.
    ];

    /**
     * Get a processor for the specified questionnaire type
     *
     * @param  string  $type  Questionnaire type (e.g., 'STAI', 'SCL90')
     */
    public function getProcessor(string $type): ?QuestionnaireProcessorInterface
    {
        $type = strtoupper($type);

        try {
            // If we have a specific processor for this type, return it
            if (isset($this->processorMap[$type])) {
                return app($this->processorMap[$type]);
            }

            // Use base processor as fallback
            Log::info("No dedicated processor found for questionnaire type: {$type}. Using base processor.");
            $baseProcessor = app(BaseQuestionnaireProcessor::class);

            // Set the type property dynamically
            $reflection = new \ReflectionClass($baseProcessor);
            $property = $reflection->getProperty('type');
            $property->setAccessible(true);
            $property->setValue($baseProcessor, $type);

            return $baseProcessor;
        } catch (BindingResolutionException $e) {
            Log::error("Error resolving processor for questionnaire type {$type}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Register a new processor for a questionnaire type
     *
     * @param  string  $type  Questionnaire type
     * @param  string  $processorClass  Fully qualified class name of the processor
     */
    public function registerProcessor(string $type, string $processorClass): void
    {
        $type = strtoupper($type);
        $this->processorMap[$type] = $processorClass;
    }

    /**
     * Get all registered processor types
     *
     * @return array List of questionnaire types with registered processors
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->processorMap);
    }

    /**
     * Check if a processor is registered for the given type
     *
     * @param  string  $type  Questionnaire type
     */
    public function hasProcessor(string $type): bool
    {
        return isset($this->processorMap[strtoupper($type)]);
    }
}
