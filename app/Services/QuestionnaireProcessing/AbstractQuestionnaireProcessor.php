<?php

namespace App\Services\QuestionnaireProcessing;

abstract class AbstractQuestionnaireProcessor implements QuestionnaireProcessorInterface
{
    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type;
    
    /**
     * Get the name of this questionnaire type
     * 
     * @return string The questionnaire type identifier (e.g., 'STAI', 'SCL90')
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Add respondent data to the results array
     *
     * @param array $result The result array to modify
     * @param array $respondentData The respondent data to add
     * @return array The updated results
     */
    protected function addRespondentData(array $result, array $respondentData): array
    {
        if (!empty($respondentData)) {
            $result['respondent_data'] = $respondentData;
        }
        
        return $result;
    }

    /**
     * Add patient data to the results array (legacy compatibility)
     *
     * @param array $result The result array to modify
     * @param array $patientData The patient data to add
     * @return array The updated results
     */
    protected function addPatientData(array $result, array $patientData): array
    {
        return $this->addRespondentData($result, $patientData);
    }
    
    /**
     * Generate a clinical summary based on scores and interpretations
     *
     * @param array $scores The scores array
     * @param array $interpretations The interpretations array
     * @return string A formatted summary
     */
    protected function generateGenericSummary(array $scores, array $interpretations): string
    {
        $summary = "Este es un resumen de los resultados del cuestionario " . $this->getType() . ".\n\n";
        
        // Add overview of elevated scores
        $elevatedScores = [];
        foreach ($scores as $key => $score) {
            // If the score is an array with a 'clinical_significance' key that's true
            if (is_array($score) && isset($score['clinical_significance']) && $score['clinical_significance'] === true) {
                $elevatedScores[] = $score['name'] ?? ucfirst(str_replace('_', ' ', $key));
            }
        }
        
        if (!empty($elevatedScores)) {
            $summary .= "Se han detectado puntuaciones elevadas en: " . implode(", ", $elevatedScores) . ".\n\n";
        } else {
            $summary .= "No se han detectado puntuaciones clínicamente significativas.\n\n";
        }
        
        // Add any global interpretations
        if (isset($interpretations['global']) && !empty($interpretations['global'])) {
            $summary .= "Interpretación global: " . $interpretations['global'] . "\n\n";
        }
        
        // Add recommendations if available
        if (isset($interpretations['recommendations']) && !empty($interpretations['recommendations'])) {
            $summary .= "Recomendaciones: " . $interpretations['recommendations'] . "\n";
        }
        
        return $summary;
    }
    
    /**
     * Generic instructions template - override in child classes for specific questionnaires
     */
    public function getInstructions(): string
    {
        return "Basándote en los datos proporcionados, analiza los resultados del cuestionario " . $this->getType() . 
               " e interpreta su significado. Considera los patrones observados, las puntuaciones elevadas, " . 
               "y las posibles implicaciones para el desarrollo personal y profesional del respondente.";
    }
} 