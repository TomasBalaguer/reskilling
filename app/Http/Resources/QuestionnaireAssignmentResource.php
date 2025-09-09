<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for questionnaire assignments in the format expected by the frontend
 * This matches the structure previously defined for campaign questionnaire responses
 */
class QuestionnaireAssignmentResource extends JsonResource
{
    protected $accessType;
    protected $accessToken;
    protected $respondentData;

    /**
     * Create a new resource instance.
     */
    public function __construct($resource, string $accessType = 'public_link', string $accessToken = '', array $respondentData = [])
    {
        parent::__construct($resource);
        $this->accessType = $accessType;
        $this->accessToken = $accessToken;
        $this->respondentData = $respondentData;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'data' => [
                'assignment' => $this->formatAssignment(),
                'respondent' => $this->formatRespondent(),
                'questionnaires' => $this->formatQuestionnaires()
            ],
            'message' => 'Questionnaires retrieved successfully.'
        ];
    }

    /**
     * Format assignment information
     */
    private function formatAssignment(): array
    {
        // For campaigns accessed by code
        if ($this->resource instanceof \App\Models\Campaign) {
            return [
                'id' => $this->resource->id,
                'campaign' => [
                    'id' => $this->resource->id,
                    'name' => $this->resource->name,
                    'description' => $this->resource->description,
                    'code' => $this->resource->code,
                    'company' => [
                        'id' => $this->resource->company->id,
                        'name' => $this->resource->company->name,
                        'logo_url' => $this->resource->company->logo_url,
                        'email' => $this->resource->company->email,
                        'phone' => $this->resource->company->phone,
                    ]
                ],
                'expires_at' => $this->resource->active_until,
                'is_completed' => false,
                'access_token' => $this->accessToken,
                'access_type' => $this->accessType
            ];
        }

        // For invitation-based access
        if ($this->resource instanceof \App\Models\CampaignInvitation) {
            return [
                'id' => $this->resource->id,
                'campaign' => [
                    'id' => $this->resource->campaign->id,
                    'name' => $this->resource->campaign->name,
                    'description' => $this->resource->campaign->description,
                    'code' => $this->resource->campaign->code,
                    'company' => [
                        'id' => $this->resource->campaign->company->id,
                        'name' => $this->resource->campaign->company->name,
                        'logo_url' => $this->resource->campaign->company->logo_url,
                        'email' => $this->resource->campaign->company->email,
                        'phone' => $this->resource->campaign->company->phone,
                    ]
                ],
                'expires_at' => $this->resource->expires_at ?? $this->resource->campaign->active_until,
                'is_completed' => $this->resource->isCompleted(),
                'access_token' => $this->accessToken,
                'access_type' => $this->accessType
            ];
        }

        return [];
    }

    /**
     * Format respondent information
     */
    private function formatRespondent(): array
    {
        if ($this->accessType === 'email_invitation' && $this->resource instanceof \App\Models\CampaignInvitation) {
            return [
                'type' => 'invited_candidate',
                'name' => $this->resource->name,
                'relationship' => 'Candidato Invitado',
                'email' => $this->resource->email
            ];
        }

        return [
            'type' => 'candidate',
            'name' => $this->respondentData['name'] ?? null,
            'relationship' => 'Candidato',
            'email' => $this->respondentData['email'] ?? null
        ];
    }

    /**
     * Format questionnaires for frontend compatibility
     */
    private function formatQuestionnaires(): array
    {
        $questionnaires = $this->getQuestionnaires();
        
        return $questionnaires->map(function ($questionnaire) {
            return [
                'id' => $questionnaire->id,
                'name' => $questionnaire->name,
                'code' => $questionnaire->getQuestionnaireType()->value,
                'description' => $questionnaire->description,
                'structure' => $questionnaire->buildStructure(),
                'scoring_type' => $questionnaire->scoring_type,
                'questionnaire_type' => $questionnaire->getQuestionnaireType()->value,
                'metadata' => $questionnaire->getEnhancedMetadata(),
                'is_active' => $questionnaire->is_active,
                'created_at' => $questionnaire->created_at,
                'updated_at' => $questionnaire->updated_at,
                'deleted_at' => null,
                'category_id' => 1, // Default category for soft skills
                'respondent_type' => $this->getRespondentType(),
                'respondent_name' => $this->getRespondentName(),
                'is_completed' => $this->getQuestionnaireCompletionStatus($questionnaire),
                'detail_id' => $this->getDetailId($questionnaire)
            ];
        })->toArray();
    }

    /**
     * Get questionnaires collection
     */
    private function getQuestionnaires()
    {
        if ($this->resource instanceof \App\Models\Campaign) {
            return $this->resource->questionnaires;
        }

        if ($this->resource instanceof \App\Models\CampaignInvitation) {
            return $this->resource->campaign->questionnaires;
        }

        return collect();
    }

    /**
     * Get respondent type
     */
    private function getRespondentType(): string
    {
        return $this->accessType === 'email_invitation' ? 'invited_candidate' : 'candidate';
    }

    /**
     * Get respondent name
     */
    private function getRespondentName(): ?string
    {
        if ($this->accessType === 'email_invitation' && $this->resource instanceof \App\Models\CampaignInvitation) {
            return $this->resource->name;
        }

        return $this->respondentData['name'] ?? null;
    }

    /**
     * Get questionnaire completion status
     */
    private function getQuestionnaireCompletionStatus($questionnaire): bool
    {
        if ($this->resource instanceof \App\Models\CampaignInvitation) {
            // Check if this specific questionnaire has been completed
            $response = $this->resource->campaignResponse()
                ->where('questionnaire_id', $questionnaire->id)
                ->where('processing_status', 'completed')
                ->first();
                
            return $response !== null;
        }

        return false; // For public access, we can't pre-determine completion
    }

    /**
     * Get detail ID for tracking response progress
     */
    private function getDetailId($questionnaire): ?int
    {
        if ($this->resource instanceof \App\Models\CampaignInvitation) {
            $response = $this->resource->campaignResponse()
                ->where('questionnaire_id', $questionnaire->id)
                ->first();
                
            return $response?->id;
        }

        return null;
    }

    /**
     * Create resource for campaign access
     */
    public static function forCampaign(\App\Models\Campaign $campaign, string $accessToken = ''): self
    {
        return new self($campaign, 'public_link', $accessToken);
    }

    /**
     * Create resource for invitation access
     */
    public static function forInvitation(\App\Models\CampaignInvitation $invitation): self
    {
        return new self($invitation, 'email_invitation', $invitation->token);
    }
}