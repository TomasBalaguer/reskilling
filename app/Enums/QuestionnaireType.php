<?php

namespace App\Enums;

enum QuestionnaireType: string
{
    case REFLECTIVE_QUESTIONS = 'REFLECTIVE_QUESTIONS';
    case MULTIPLE_CHOICE = 'MULTIPLE_CHOICE';
    case SINGLE_CHOICE = 'SINGLE_CHOICE';
    case TEXT_RESPONSE = 'TEXT_RESPONSE';
    case SCALE_RATING = 'SCALE_RATING';
    case PERSONALITY_ASSESSMENT = 'PERSONALITY_ASSESSMENT';
    case BIG_FIVE = 'BIG_FIVE';
    case MIXED_FORMAT = 'MIXED_FORMAT';

    public function getDisplayName(): string
    {
        return match($this) {
            self::REFLECTIVE_QUESTIONS => 'Preguntas Reflexivas (Audio)',
            self::MULTIPLE_CHOICE => 'Selección Múltiple',
            self::SINGLE_CHOICE => 'Opción Única',
            self::TEXT_RESPONSE => 'Respuesta Libre de Texto',
            self::SCALE_RATING => 'Escala de Calificación',
            self::PERSONALITY_ASSESSMENT => 'Evaluación de Personalidad',
            self::BIG_FIVE => 'Big Five de Personalidad',
            self::MIXED_FORMAT => 'Formato Mixto',
        };
    }

    public function getResponseFormat(): string
    {
        return match($this) {
            self::REFLECTIVE_QUESTIONS => 'audio_with_transcription',
            self::MULTIPLE_CHOICE => 'multiple_selection',
            self::SINGLE_CHOICE => 'single_selection',
            self::TEXT_RESPONSE => 'text_input',
            self::SCALE_RATING => 'numeric_scale',
            self::PERSONALITY_ASSESSMENT => 'mixed_responses',
            self::BIG_FIVE => 'likert_scale',
            self::MIXED_FORMAT => 'varied_responses',
        };
    }

    public function isAudioBased(): bool
    {
        return match($this) {
            self::REFLECTIVE_QUESTIONS => true,
            default => false,
        };
    }

    public function requiresAIProcessing(): bool
    {
        return match($this) {
            self::REFLECTIVE_QUESTIONS, 
            self::TEXT_RESPONSE,
            self::PERSONALITY_ASSESSMENT => true,
            default => false,
        };
    }

    public function getStrategyClass(): string
    {
        return match($this) {
            self::REFLECTIVE_QUESTIONS => 'App\Services\Questionnaire\Types\ReflectiveQuestionsStrategy',
            self::MULTIPLE_CHOICE => 'App\Services\Questionnaire\Types\MultipleChoiceStrategy',
            self::SINGLE_CHOICE => 'App\Services\Questionnaire\Types\SingleChoiceStrategy',
            self::TEXT_RESPONSE => 'App\Services\Questionnaire\Types\TextResponseStrategy',
            self::SCALE_RATING => 'App\Services\Questionnaire\Types\ScaleRatingStrategy',
            self::PERSONALITY_ASSESSMENT => 'App\Services\Questionnaire\Types\PersonalityAssessmentStrategy',
            self::BIG_FIVE => 'App\Services\Questionnaire\Types\BigFiveStrategy',
            self::MIXED_FORMAT => 'App\Services\Questionnaire\Types\MixedFormatStrategy',
        };
    }
}