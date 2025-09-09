# Questionnaire Processing System

This system provides a standardized way to process and interpret various psychological questionnaires. It follows a modular architecture that allows for easy extension to support new questionnaire types.

## Architecture

The system is built around the following components:

1. **QuestionnaireProcessorInterface**: Defines the contract that all questionnaire processors must implement.
2. **AbstractQuestionnaireProcessor**: Provides a base implementation of common methods.
3. **BaseQuestionnaireProcessor**: Extends the abstract class with utility methods and fallback implementations.
4. **Specific Processors**: Individual processors for each questionnaire type (e.g., `StaiQuestionnaireProcessor`).
5. **QuestionnaireProcessorFactory**: Central access point for obtaining processor instances.

## Using the System

### Basic Usage

```php
// Get a processor for a specific questionnaire type
$factory = app(\App\Services\QuestionnaireProcessing\QuestionnaireProcessorFactory::class);
$processor = $factory->getProcessor('STAI'); // Returns StaiQuestionnaireProcessor

// Process questionnaire responses
$results = $processor->calculateScores($responses, $patientData);

// Get a formatted prompt section for AI interpretation
$promptSection = $processor->buildPromptSection($results);

// Get specific instructions for interpreting this questionnaire
$instructions = $processor->getInstructions();
```

### Integration with QuestionnaireAssignmentController

The `QuestionnaireAssignmentController` has been updated to use the processor system:

```php
private function calculateQuestionnaireMeaning($questionnaire, $responses, $patientData = []): array
{
    // Initialize result with basic information
    $result = [
        'questionnaire_id' => $questionnaire->id,
        'questionnaire_name' => $questionnaire->name,
        'scoring_type' => $questionnaire->scoring_type,
    ];

    // Try to use the processor factory first
    try {
        $factory = app(\App\Services\QuestionnaireProcessing\QuestionnaireProcessorFactory::class);
        $processor = $factory->getProcessor($questionnaire->scoring_type);

        if ($processor) {
            return $processor->calculateScores($responses, $patientData, $result);
        }
    } catch (\Exception $e) {
        \Log::error("Error using questionnaire processor: " . $e->getMessage());
        // Fall back to legacy implementation
    }

    // Fall back to legacy implementation if processor is not available
    // ...
}
```

## Extending the System

### Creating a New Questionnaire Processor

1. Create a new class that extends `BaseQuestionnaireProcessor`:

```php
<?php

namespace App\Services\QuestionnaireProcessing;

class NewQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    /**
     * The questionnaire type identifier
     */
    protected $type = 'NEW_TYPE';

    /**
     * Calculate scores and interpretations
     */
    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        // Add patient data to results
        $result = $this->addPatientData($result, $patientData);

        // Process responses
        // ...

        // Add scores, interpretations, and summary to result
        $result['scores'] = $scores;
        $result['interpretations'] = $interpretations;
        $result['summary'] = $summary;

        return $result;
    }

    /**
     * Custom instructions for AI interpretation
     */
    public function getInstructions(): string
    {
        return "Specific instructions for interpreting this questionnaire...";
    }
}
```

2. Register the new processor in the `QuestionnaireServiceProvider`:

```php
$this->app->bind(NewQuestionnaireProcessor::class, function ($app) {
    return new NewQuestionnaireProcessor();
});
```

3. Add the new processor to the `QuestionnaireProcessorFactory` map:

```php
protected $processorMap = [
    'STAI' => StaiQuestionnaireProcessor::class,
    'NEW_TYPE' => NewQuestionnaireProcessor::class,
];
```

## Best Practices

1. **Normalization**: Use the `normalizeGender()` and `getAgeGroup()` methods from `BaseQuestionnaireProcessor` to standardize demographic data.
2. **Patient Data**: Always call `$this->addPatientData($result, $patientData)` to ensure consistent formatting.
3. **Result Structure**: Follow the standard result structure:
    - `scores`: Raw scores and derived metrics
    - `interpretations`: Text interpretations of individual scales
    - `summary`: Overall interpretation in natural language
    - `demographic_used`: Demographic data used for interpretation
4. **Error Handling**: Validate input data and provide meaningful fallbacks when data is missing.

## Available Processors

1. **StaiQuestionnaireProcessor**: Processes the State-Trait Anxiety Inventory (STAI) questionnaire.
    - Calculates state and trait anxiety scores
    - Provides percentiles based on normative data
    - Interprets anxiety levels with clinical thresholds
2. **Scl90QuestionnaireProcessor**: Processes the Symptom Checklist-90-Revised (SCL-90-R) questionnaire.

    - Calculates scores for nine primary symptom dimensions
    - Provides three global indices of distress (GSI, PST, PSDI)
    - Computes T-scores based on gender-specific normative data
    - Interprets clinical significance of symptoms and response patterns

3. **StaxiQuestionnaireProcessor**: Processes the State-Trait Anger Expression Inventory (STAXI) questionnaire.

    - Calculates state and trait anger scores
    - Measures anger expression styles (anger-in, anger-out, anger control)
    - Computes anger expression index
    - Provides clinical interpretations of anger patterns and associated risks

4. **IvaQuestionnaireProcessor**: Processes the Inventario de Valoración y Afrontamiento (IVA) questionnaire.

    - Assesses cognitive appraisal patterns (threat, challenge, irrelevance)
    - Measures cognitive and behavioral coping strategies
    - Evaluates problem-focused and emotion-focused coping styles
    - Analyzes avoidance patterns and their adaptability
    - Provides interpretations based on the specific stressful situation described

5. **JasQuestionnaireProcessor**: Processes the Jenkins Activity Survey (JAS) questionnaire.

    - Assesses Type A behavior pattern associated with coronary risk
    - Measures four key dimensions: global Type A, speed/impatience, job involvement, and hard-driving/competitive behavior
    - Calculates coronary risk level based on behavior patterns
    - Provides clinical interpretations of behavior patterns and recommendations for behavioral modifications

6. **CriQuestionnaireProcessor**: Processes the Coping Responses Inventory (CRI) questionnaire.

    - Assesses eight different coping strategies divided into approach and avoidance categories
    - Evaluates both cognitive and behavioral coping responses
    - Calculates standardized T-scores for each coping scale
    - Provides comparative analysis of approach vs. avoidance and cognitive vs. behavioral strategies
    - Generates clinical interpretations of coping patterns and their adaptability to different stressors

7. **HolmesRaheQuestionnaireProcessor**: Processes the Holmes and Rahe Social Readjustment Rating Scale.
    - Calculates total Life Change Units (LCU) based on reported stressful life events
    - Assesses risk level for developing stress-related health problems
    - Categorizes stressors into family, work, health, economic, legal, and personal domains
    - Identifies predominant stress categories to guide intervention planning
    - Provides specific clinical interpretations and recommendations based on stress exposure level
