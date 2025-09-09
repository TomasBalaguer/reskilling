<?php

namespace App\Services\Questionnaire;

use App\Models\Questionnaire;
use App\Models\CampaignResponse;

interface QuestionnaireStrategyInterface
{
    /**
     * Build the questionnaire structure for frontend consumption
     */
    public function buildStructure(Questionnaire $questionnaire): array;

    /**
     * Validate responses according to questionnaire type
     */
    public function validateResponses(array $responses, Questionnaire $questionnaire): array;

    /**
     * Process raw responses into structured format
     */
    public function processResponses(array $rawResponses, Questionnaire $questionnaire): array;

    /**
     * Calculate scores and perform initial analysis
     */
    public function calculateScores(array $processedResponses, array $respondentData = []): array;

    /**
     * Determine if responses require AI processing
     */
    public function requiresAIProcessing(): bool;

    /**
     * Generate AI interpretation if needed
     */
    public function generateAIInterpretation(CampaignResponse $response, array $processedData): ?array;

    /**
     * Get supported question types for this strategy
     */
    public function getSupportedQuestionTypes(): array;

    /**
     * Get metadata for this questionnaire type
     */
    public function getMetadata(): array;
}