<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CampaignInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'email',
        'name',
        'token',
        'status',
        'sent_at',
        'opened_at',
        'completed_at',
        'expires_at',
        'metadata'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime', 
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
        });
    }

    // Relationships
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignResponse()
    {
        return $this->hasOne(CampaignResponse::class, 'invitation_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Methods
    public function markAsOpened()
    {
        $this->update([
            'status' => 'opened',
            'opened_at' => now()
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getAccessUrl(): string
    {
        return url("/invitation/{$this->token}");
    }
}