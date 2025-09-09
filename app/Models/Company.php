<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Services\FileStorageService;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'subdomain',
        'logo_url',
        'logo_s3_path',
        'email',
        'phone',
        'max_campaigns',
        'max_responses_per_campaign',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'max_campaigns' => 'integer',
        'max_responses_per_campaign' => 'integer',
    ];

    // Relationships
    public function users()
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getTotalResponsesAttribute()
    {
        return $this->campaigns()->sum('responses_count');
    }

    public function getActiveCampaignsAttribute()
    {
        return $this->campaigns()->where('status', 'active')->count();
    }

    // Logo URL accessor
    public function getLogoUrlAttribute()
    {
        if ($this->logo_s3_path) {
            $fileStorage = new FileStorageService();
            return $fileStorage->getImageUrl($this->logo_s3_path);
        }
        
        return $this->attributes['logo_url'] ?? null;
    }
}