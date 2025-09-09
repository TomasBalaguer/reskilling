<?php

namespace App\Models;

use App\Enums\QuestionnaireType;
use App\Services\Questionnaire\QuestionnaireStrategyFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Questionnaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'scoring_type',
        'questionnaire_type',
        'questions',
        'structure',
        'metadata',
        'configuration',
        'max_duration_minutes',
        'estimated_duration_minutes',
        'settings',
        'is_active',
        'version'
    ];

    protected $casts = [
        'questions' => 'array',
        'structure' => 'array',
        'metadata' => 'array', 
        'configuration' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'max_duration_minutes' => 'integer',
        'estimated_duration_minutes' => 'integer',
        'version' => 'integer',
        'questionnaire_type' => QuestionnaireType::class
    ];

    // Atributos virtuales para la serialización
    protected $is_completed = false;
    protected $detail_id = null;

    // Getters y setters para los atributos virtuales
    public function getIsCompletedAttribute()
    {
        return $this->is_completed;
    }

    public function setIsCompletedAttribute($value)
    {
        $this->is_completed = $value;
    }

    public function getDetailIdAttribute()
    {
        return $this->detail_id;
    }

    public function setDetailIdAttribute($value)
    {
        $this->detail_id = $value;
    }

    /**
     * Scope para obtener solo cuestionarios activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtener todas las secciones del cuestionario
     */
    public function getSections(): array
    {
        return $this->structure['sections'] ?? [];
    }

    /**
     * Obtener una sección específica por ID
     */
    public function getSection(string $sectionId): ?array
    {
        return collect($this->getSections())
            ->firstWhere('id', $sectionId);
    }

    /**
     * Obtener todas las preguntas de una sección
     */
    public function getSectionQuestions(string $sectionId): array
    {
        $section = $this->getSection($sectionId);
        return $section['questions'] ?? [];
    }

    /**
     * Validar si una respuesta es válida para una pregunta
     */
    public function isValidResponse(string $sectionId, string $questionId, $response): bool
    {
        $section = $this->getSection($sectionId);
        if (!$section) return false;

        // Verificar si la pregunta existe
        $questionExists = collect($section['questions'])
            ->contains('id', $questionId);
        if (!$questionExists) return false;

        // Si es de opción múltiple, verificar que la respuesta sea válida
        if ($section['response_type'] === 'single_choice') {
            return collect($section['options'])
                ->contains('value', $response);
        }

        return true;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The categories that belong to the questionnaire.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_questionnaire');
    }

    public function questionnairePrompts(): HasOne
    {
        return $this->hasOne(QuestionnairePrompt::class);
    }

    // New relationships for campaign system
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_questionnaires')
                   ->withPivot(['order', 'is_required'])
                   ->orderBy('campaign_questionnaires.order');
    }

    public function prompts()
    {
        return $this->hasMany(QuestionnairePrompt::class);
    }

    public function activePrompt()
    {
        return $this->hasOne(QuestionnairePrompt::class)->where('is_active', true);
    }

    protected $appends = ['is_completed', 'detail_id'];

    /**
     * Get strategy instance for this questionnaire
     */
    public function getStrategy(): \App\Services\Questionnaire\QuestionnaireStrategyInterface
    {
        return app(QuestionnaireStrategyFactory::class)->getStrategy($this);
    }

    /**
     * Get questionnaire type enum
     */
    public function getQuestionnaireType(): QuestionnaireType
    {
        return $this->questionnaire_type ?: QuestionnaireType::tryFrom($this->scoring_type) ?: QuestionnaireType::TEXT_RESPONSE;
    }

    /**
     * Build structure using strategy
     */
    public function buildStructure(): array
    {
        if ($this->structure) {
            return $this->structure;
        }

        return $this->getStrategy()->buildStructure($this);
    }

    /**
     * Get enhanced metadata
     */
    public function getEnhancedMetadata(): array
    {
        $baseMetadata = $this->metadata ?: [];
        $strategyMetadata = $this->getStrategy()->getMetadata();
        
        return array_merge($baseMetadata, $strategyMetadata, [
            'questionnaire_type' => $this->getQuestionnaireType()->value,
            'display_name' => $this->getQuestionnaireType()->getDisplayName(),
            'response_format' => $this->getQuestionnaireType()->getResponseFormat(),
            'is_audio_based' => $this->getQuestionnaireType()->isAudioBased(),
            'requires_ai_processing' => $this->getQuestionnaireType()->requiresAIProcessing(),
        ]);
    }

    /**
     * Validate responses using strategy
     */
    public function validateResponses(array $responses): array
    {
        return $this->getStrategy()->validateResponses($responses, $this);
    }

    /**
     * Process responses using strategy
     */
    public function processResponses(array $rawResponses): array
    {
        return $this->getStrategy()->processResponses($rawResponses, $this);
    }

    /**
     * Calculate scores using strategy
     */
    public function calculateScores(array $processedResponses, array $respondentData = []): array
    {
        return $this->getStrategy()->calculateScores($processedResponses, $respondentData);
    }

    /**
     * Check if questionnaire requires AI processing
     */
    public function requiresAIProcessing(): bool
    {
        return $this->getStrategy()->requiresAIProcessing();
    }

    /**
     * Scope for questionnaires of specific type
     */
    public function scopeOfType($query, QuestionnaireType $type)
    {
        return $query->where('questionnaire_type', $type->value)
                    ->orWhere('scoring_type', $type->value);
    }

    /**
     * Scope for audio-based questionnaires
     */
    public function scopeAudioBased($query)
    {
        return $query->where('questionnaire_type', QuestionnaireType::REFLECTIVE_QUESTIONS->value);
    }

    /**
     * Scope for AI-processable questionnaires
     */
    public function scopeRequiresAI($query)
    {
        $aiTypes = collect(QuestionnaireType::cases())
            ->filter(fn($type) => $type->requiresAIProcessing())
            ->map(fn($type) => $type->value)
            ->toArray();

        return $query->whereIn('questionnaire_type', $aiTypes)
                    ->orWhereIn('scoring_type', $aiTypes);
    }
} 