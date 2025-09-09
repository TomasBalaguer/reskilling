<?php

namespace App\Services\QuestionnaireProcessing;

/**
 * Base questionnaire processor with common utility methods
 */
class BaseQuestionnaireProcessor extends AbstractQuestionnaireProcessor
{
    /**
     * Calculate scores and interpretations based on questionnaire responses
     * Basic implementation that should be overridden by specific questionnaire processors
     *
     * @param array $responses Raw responses from the questionnaire
     * @param array $patientData Patient demographic data (age, gender, etc.)
     * @param array $result Optional existing result to extend
     * @return array Processed results with scores, interpretations, and summaries
     */
    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        // Add patient data to result
        $result = $this->addPatientData($result, $patientData);
        
        // Initialize with type information
        $result['scoring_type'] = $this->getType();
        
        // Add a default summary
        $result['summary'] = "This is a base implementation. Please override this method in a specific questionnaire processor.";
        
        return $result;
    }
    
    /**
     * Build a prompt section for AI interpretation
     * Basic implementation that should be overridden by specific questionnaire processors
     *
     * @param array $results Results from the calculateScores method
     * @return string Formatted prompt section
     */
    public function buildPromptSection(array $results): string
    {
        $promptSection = "## " . ($results['questionnaire_name'] ?? $this->getType()) . " Results\n\n";
        
        // Add summary if available
        if (isset($results['summary'])) {
            $promptSection .= "Summary: " . $results['summary'] . "\n\n";
        }
        
        // Add scores if available
        if (isset($results['scores']) && is_array($results['scores'])) {
            $promptSection .= "### Scores:\n";
            foreach ($results['scores'] as $key => $score) {
                if (is_array($score)) {
                    $scoreName = $score['name'] ?? ucfirst(str_replace('_', ' ', $key));
                    $scoreValue = $score['raw_score'] ?? ($score['value'] ?? 'N/A');
                    $percentile = $score['percentile'] ?? 'N/A';
                    
                    $promptSection .= "- $scoreName: $scoreValue (Percentile: $percentile)\n";
                } else {
                    $promptSection .= "- " . ucfirst(str_replace('_', ' ', $key)) . ": $score\n";
                }
            }
            $promptSection .= "\n";
        }
        
        // Add interpretations if available
        if (isset($results['interpretations']) && is_array($results['interpretations'])) {
            $promptSection .= "### Interpretations:\n";
            foreach ($results['interpretations'] as $key => $interpretation) {
                if (is_string($interpretation)) {
                    $promptSection .= "- " . ucfirst(str_replace('_', ' ', $key)) . ": $interpretation\n";
                }
            }
            $promptSection .= "\n";
        }
        
        return $promptSection;
    }
    
    /**
     * Get normalized gender value for looking up appropriate norms
     * 
     * @param string|null $gender The gender input
     * @return string Normalized gender code ('M' or 'F')
     */
    protected function normalizeGender(?string $gender): string
    {
        if (!$gender) {
            return 'M'; // Default to male if gender not provided
        }
        
        $gender = strtolower(trim($gender));
        
        if (in_array($gender, ['m', 'masculino', 'male', 'hombre', 'varon', 'v'])) {
            return 'M';
        }
        
        if (in_array($gender, ['f', 'femenino', 'female', 'mujer'])) {
            return 'F';
        }
        
        return 'M'; // Default to male for other values
    }
    
    /**
     * Get appropriate age group for normative comparisons
     * 
     * @param int|null $age The age in years
     * @return string Age group identifier
     */
    protected function getAgeGroup(?int $age): string
    {
        if (!$age) {
            return 'adulto'; // Default
        }
        
        if ($age < 13) {
            return 'niño';
        } elseif ($age < 18) {
            return 'adolescente';
        } elseif ($age < 60) {
            return 'adulto';
        } else {
            return 'adulto mayor';
        }
    }
} 