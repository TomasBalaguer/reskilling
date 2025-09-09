<?php

namespace App\Events;

use App\Models\CampaignResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AudioTranscriptionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CampaignResponse $response,
        public array $transcriptionData = [],
        public bool $success = true,
        public ?string $error = null
    ) {}

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'campaign:' . $this->response->campaign_id,
            'response:' . $this->response->id,
            'transcription:completed',
        ];
    }

    /**
     * Check if transcription was successful
     */
    public function isSuccessful(): bool
    {
        return $this->success && !empty($this->transcriptionData);
    }

    /**
     * Get transcription summary
     */
    public function getTranscriptionSummary(): array
    {
        if (!$this->isSuccessful()) {
            return [
                'status' => 'failed',
                'error' => $this->error,
                'audio_files' => 0,
                'transcriptions' => 0
            ];
        }

        $audioFiles = count($this->transcriptionData);
        $successfulTranscriptions = count(array_filter(
            $this->transcriptionData,
            fn($data) => !empty($data['transcription_text'])
        ));

        return [
            'status' => 'completed',
            'audio_files' => $audioFiles,
            'transcriptions' => $successfulTranscriptions,
            'success_rate' => $audioFiles > 0 ? ($successfulTranscriptions / $audioFiles) * 100 : 0,
            'total_duration' => array_sum(array_column($this->transcriptionData, 'duration')),
            'languages_detected' => array_unique(array_column($this->transcriptionData, 'language')),
        ];
    }

    /**
     * Get metadata for next processing stage
     */
    public function getProcessingMetadata(): array
    {
        return [
            'response_id' => $this->response->id,
            'campaign_id' => $this->response->campaign_id,
            'questionnaire_id' => $this->response->questionnaire_id,
            'transcription_status' => $this->success ? 'completed' : 'failed',
            'transcription_summary' => $this->getTranscriptionSummary(),
            'next_stage' => $this->success ? 'ai_analysis' : 'error_handling',
            'processed_at' => now()
        ];
    }
}