<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'questionnaire_id',
        'respondent_name',
        'respondent_email',
        'respondent_type',
        'respondent_age',
        'respondent_additional_info',
        'age',
        'gender',
        'occupation',
        'session_id',
        'responses',
        'raw_responses',
        'processed_responses',
        'interpretation',
        'ai_analysis',
        'transcriptions',
        'prosodic_analysis',
        'comprehensive_report',
        'questionnaire_scores',
        'processing_status',
        'processing_error',
        'ai_analysis_status',
        'ai_analysis_completed_at',
        'ai_analysis_failed_at',
        'analysis_summary',
        'completion_percentage',
        'access_type',
        'access_token',
        'audio_files',
        'duration_minutes',
        'started_at',
        'completed_at',
        'processing_started_at',
        'processing_completed_at',
        'report_generated_at',
    ];

    protected $casts = [
        'responses' => 'array',
        'raw_responses' => 'array',
        'processed_responses' => 'array',
        'respondent_additional_info' => 'array',
        'interpretation' => 'array',
        'ai_analysis' => 'array',
        'transcriptions' => 'array',
        'prosodic_analysis' => 'array',
        'analysis_summary' => 'array',
        'comprehensive_report' => 'array',
        'audio_files' => 'array',
        'questionnaire_scores' => 'array',
        'age' => 'integer',
        'respondent_age' => 'integer',
        'completion_percentage' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'ai_analysis_completed_at' => 'datetime',
        'ai_analysis_failed_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'report_generated_at' => 'datetime',
        'campaign_id' => 'integer',
        'questionnaire_id' => 'integer',
    ];

    // Relationships
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function questionnaire()
    {
        return $this->belongsTo(Questionnaire::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('processing_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('processing_status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('processing_status', 'processing');
    }

    public function scopeFailed($query)
    {
        return $query->where('processing_status', 'failed');
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('respondent_email', $email);
    }

    // Accessors
    public function getIsCompletedAttribute()
    {
        return $this->processing_status === 'completed';
    }

    public function getIsPendingAttribute()
    {
        return $this->processing_status === 'pending';
    }

    public function getIsProcessingAttribute()
    {
        return $this->processing_status === 'processing';
    }

    public function getIsFailedAttribute()
    {
        return $this->processing_status === 'failed';
    }

    public function getDurationMinutesAttribute()
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        
        return $this->started_at->diffInMinutes($this->completed_at);
    }

    public function getHasAudioResponsesAttribute()
    {
        if (!$this->responses) return false;
        
        foreach ($this->responses as $response) {
            if (isset($response['audio_file_path']) && !empty($response['audio_file_path'])) {
                return true;
            }
        }
        
        return false;
    }

    // Methods
    public function markAsStarted()
    {
        $this->update([
            'started_at' => now(),
            'processing_status' => 'processing'
        ]);
    }

    public function markAsCompleted($interpretation = null)
    {
        $updateData = [
            'completed_at' => now(),
            'processing_status' => 'completed'
        ];
        
        if ($interpretation) {
            $updateData['interpretation'] = $interpretation;
        }
        
        $this->update($updateData);
        
        // Increment campaign response count
        $this->campaign->incrementResponseCount();
    }

    public function markAsFailed($error = null)
    {
        $this->update([
            'processing_status' => 'failed',
            'processing_error' => $error
        ]);
    }

    public function getInterpretationSummary()
    {
        if (!$this->interpretation) return null;
        
        // Extract key metrics from interpretation
        return [
            'overall_score' => $this->interpretation['overall_score'] ?? null,
            'top_strengths' => $this->interpretation['top_strengths'] ?? [],
            'development_areas' => $this->interpretation['development_areas'] ?? [],
            'completion_date' => $this->completed_at ? $this->completed_at->format('Y-m-d H:i') : null,
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($response) {
            if (!$response->started_at) {
                $response->started_at = now();
            }
        });
    }
}