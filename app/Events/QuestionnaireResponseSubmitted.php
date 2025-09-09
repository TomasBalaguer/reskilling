<?php

namespace App\Events;

use App\Models\CampaignResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionnaireResponseSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CampaignResponse $response,
        public array $processedData = [],
        public bool $requiresAIProcessing = false
    ) {}

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'campaign:' . $this->response->campaign_id,
            'questionnaire:' . $this->response->questionnaire_id ?? 'unknown',
            'response:' . $this->response->id,
        ];
    }

    /**
     * Determine if AI processing is required for this response
     */
    public function requiresAIProcessing(): bool
    {
        return $this->requiresAIProcessing;
    }

    /**
     * Get response metadata
     */
    public function getMetadata(): array
    {
        return [
            'response_id' => $this->response->id,
            'campaign_id' => $this->response->campaign_id,
            'questionnaire_id' => $this->response->questionnaire_id,
            'questionnaire_type' => $this->response->questionnaire?->questionnaire_type?->value,
            'submitted_at' => now(),
            'requires_ai_processing' => $this->requiresAIProcessing,
            'processing_priority' => $this->getProcessingPriority()
        ];
    }

    /**
     * Get processing priority based on questionnaire type
     */
    private function getProcessingPriority(): string
    {
        $questionnaireType = $this->response->questionnaire?->questionnaire_type?->value;
        
        return match($questionnaireType) {
            'REFLECTIVE_QUESTIONS' => 'high',    // Audio processing is time-sensitive
            'TEXT_RESPONSE' => 'medium',         // AI analysis but less urgent
            'PERSONALITY_ASSESSMENT' => 'medium',
            default => 'low'                     // Statistical processing only
        };
    }
}