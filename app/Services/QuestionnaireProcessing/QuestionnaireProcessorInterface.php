<?php

namespace App\Services\QuestionnaireProcessing;

interface QuestionnaireProcessorInterface
{
    /**
     * Calculate scores and interpretations based on questionnaire responses
     *
     * @param array $responses Raw responses from the questionnaire
     * @param array $patientData Patient demographic data (age, gender, etc.)
     * @param array $result Optional existing result to extend
     * @return array Processed results with scores, interpretations, and summaries
     */
    public function calculateScores(array $responses, array $patientData = [], array $result = []): array;
    
    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     *
     * @param array $results Results from the calculateScores method
     * @return string Formatted prompt section
     */
    public function buildPromptSection(array $results): string;
    
    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     *
     * @return string Formatted instructions
     */
    public function getInstructions(): string;
    
    /**
     * Get the name of this questionnaire type
     * 
     * @return string The questionnaire type identifier (e.g., 'STAI', 'SCL90')
     */
    public function getType(): string;
} 