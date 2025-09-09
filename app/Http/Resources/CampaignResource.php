<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
            'status' => $this->status,
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'logo_url' => $this->company->logo_url,
                'email' => $this->company->email,
                'phone' => $this->company->phone,
            ],
            'active_period' => [
                'from' => $this->active_from,
                'until' => $this->active_until,
                'is_active' => $this->isActive(),
                'days_remaining' => $this->daysRemaining()
            ],
            'participation' => [
                'max_responses' => $this->max_responses,
                'current_responses' => $this->responses_count ?? 0,
                'completion_percentage' => $this->getCompletionPercentage(),
                'has_capacity' => $this->hasCapacity()
            ],
            'access' => [
                'public_link_enabled' => !empty($this->public_link_code),
                'email_invitations_enabled' => $this->email_invitations_enabled ?? true,
                'requires_security_code' => !empty($this->public_link_code)
            ],
            'questionnaires' => QuestionnaireResource::collection($this->whenLoaded('questionnaires')),
            'metadata' => [
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'estimated_duration' => $this->getEstimatedDuration(),
                'questionnaire_count' => $this->questionnaires_count ?? $this->questionnaires?->count() ?? 0
            ]
        ];
    }

    /**
     * Check if campaign is currently active
     */
    private function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->active_from <= now() && 
               $this->active_until >= now();
    }

    /**
     * Calculate days remaining
     */
    private function daysRemaining(): int
    {
        if (!$this->active_until) return 0;
        
        $diff = now()->diffInDays($this->active_until, false);
        return max(0, (int) $diff);
    }

    /**
     * Calculate completion percentage
     */
    private function getCompletionPercentage(): float
    {
        if (!$this->max_responses || $this->max_responses <= 0) return 0;
        
        $currentResponses = $this->responses_count ?? 0;
        return round(($currentResponses / $this->max_responses) * 100, 2);
    }

    /**
     * Check if campaign has capacity for more responses
     */
    private function hasCapacity(): bool
    {
        if (!$this->max_responses) return true;
        
        $currentResponses = $this->responses_count ?? 0;
        return $currentResponses < $this->max_responses;
    }

    /**
     * Get estimated duration for all questionnaires
     */
    private function getEstimatedDuration(): int
    {
        if (!$this->relationLoaded('questionnaires')) {
            return 0;
        }

        return $this->questionnaires->sum('estimated_duration_minutes') ?? 0;
    }
}