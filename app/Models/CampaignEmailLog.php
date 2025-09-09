<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignEmailLog extends Model
{
    protected $fillable = [
        'campaign_id',
        'campaign_invitation_id', 
        'recipient_email',
        'recipient_name',
        'type',
        'status',
        'error_message',
        'metadata',
        'queued_at',
        'sent_at',
        'failed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime', 
        'failed_at' => 'datetime'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(CampaignInvitation::class, 'campaign_invitation_id');
    }

    /**
     * Mark email as queued
     */
    public function markAsQueued(): self
    {
        $this->update([
            'status' => 'queued',
            'queued_at' => now()
        ]);
        return $this;
    }

    /**
     * Mark email as sent
     */
    public function markAsSent(): self
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
        return $this;
    }

    /**
     * Mark email as failed
     */
    public function markAsFailed(string $errorMessage, array $metadata = []): self
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'metadata' => array_merge($this->metadata ?? [], $metadata)
        ]);
        return $this;
    }
}
