<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'max_responses',
        'responses_count',
        'active_from',
        'active_until',
        'public_link_enabled',
        'public_link_code',
        'access_type',
        'allow_public_access',
        'settings',
        'status',
    ];

    protected $casts = [
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'public_link_enabled' => 'boolean',
        'allow_public_access' => 'boolean',
        'settings' => 'array',
        'max_responses' => 'integer',
        'responses_count' => 'integer',
        'company_id' => 'integer',
    ];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function questionnaires()
    {
        return $this->belongsToMany(Questionnaire::class, 'campaign_questionnaires')
                   ->withPivot(['order', 'is_required'])
                   ->orderBy('campaign_questionnaires.order');
    }

    public function responses()
    {
        return $this->hasMany(CampaignResponse::class);
    }

    public function invitations()
    {
        return $this->hasMany(CampaignInvitation::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(CampaignEmailLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('active_from', '<=', now())
                    ->where('active_until', '>=', now());
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // Accessors
    public function getIsActiveAttribute()
    {
        return $this->status === 'active' &&
               $this->active_from <= now() &&
               $this->active_until >= now();
    }

    public function getCompletionRateAttribute()
    {
        return $this->max_responses > 0 ? 
               round(($this->responses_count / $this->max_responses) * 100, 2) : 0;
    }

    public function getPublicUrlAttribute()
    {
        return url("/campaign/{$this->code}");
    }

    public function getIsFullAttribute()
    {
        return $this->responses_count >= $this->max_responses;
    }

    // Methods
    public function generateUniqueCode()
    {
        do {
            $code = Str::random(8);
        } while (static::where('code', $code)->exists());
        
        $this->code = $code;
        return $code;
    }

    public function incrementResponseCount()
    {
        $this->increment('responses_count');
        
        // Check if campaign is now full
        if ($this->responses_count >= $this->max_responses) {
            $this->update(['status' => 'completed']);
        }
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (!$campaign->code) {
                $campaign->generateUniqueCode();
            }
        });
    }
}