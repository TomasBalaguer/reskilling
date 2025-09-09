<?php

namespace App\Enums;

enum QuestionType: string
{
    case AUDIO_RESPONSE = 'audio_response';
    case MULTIPLE_CHOICE = 'multiple_choice';
    case SINGLE_CHOICE = 'single_choice';
    case TEXT_INPUT = 'text_input';
    case TEXTAREA = 'textarea';
    case LIKERT_SCALE = 'likert_scale';
    case NUMERIC_SCALE = 'numeric_scale';
    case YES_NO = 'yes_no';
    case DROPDOWN = 'dropdown';
    case CHECKBOX = 'checkbox';
    case RADIO = 'radio';
    case SLIDER = 'slider';
    case DATE_PICKER = 'date_picker';
    case TIME_PICKER = 'time_picker';
    case FILE_UPLOAD = 'file_upload';

    public function getDisplayName(): string
    {
        return match($this) {
            self::AUDIO_RESPONSE => 'Respuesta de Audio',
            self::MULTIPLE_CHOICE => 'Selección Múltiple',
            self::SINGLE_CHOICE => 'Opción Única',
            self::TEXT_INPUT => 'Entrada de Texto Corto',
            self::TEXTAREA => 'Entrada de Texto Largo',
            self::LIKERT_SCALE => 'Escala Likert',
            self::NUMERIC_SCALE => 'Escala Numérica',
            self::YES_NO => 'Sí/No',
            self::DROPDOWN => 'Lista Desplegable',
            self::CHECKBOX => 'Casillas de Verificación',
            self::RADIO => 'Botones de Radio',
            self::SLIDER => 'Control Deslizante',
            self::DATE_PICKER => 'Selector de Fecha',
            self::TIME_PICKER => 'Selector de Hora',
            self::FILE_UPLOAD => 'Subida de Archivo',
        };
    }

    public function getValidationRules(): array
    {
        return match($this) {
            self::AUDIO_RESPONSE => ['required', 'file', 'mimes:mp3,wav,m4a,aac', 'max:51200'],
            self::MULTIPLE_CHOICE => ['required', 'array', 'min:1'],
            self::SINGLE_CHOICE => ['required', 'string'],
            self::TEXT_INPUT => ['required', 'string', 'max:255'],
            self::TEXTAREA => ['required', 'string', 'max:2000'],
            self::LIKERT_SCALE => ['required', 'integer', 'between:1,7'],
            self::NUMERIC_SCALE => ['required', 'numeric'],
            self::YES_NO => ['required', 'boolean'],
            self::DROPDOWN => ['required', 'string'],
            self::CHECKBOX => ['sometimes', 'array'],
            self::RADIO => ['required', 'string'],
            self::SLIDER => ['required', 'numeric'],
            self::DATE_PICKER => ['required', 'date'],
            self::TIME_PICKER => ['required', 'date_format:H:i'],
            self::FILE_UPLOAD => ['required', 'file'],
        };
    }

    public function requiresOptions(): bool
    {
        return match($this) {
            self::MULTIPLE_CHOICE,
            self::SINGLE_CHOICE,
            self::DROPDOWN,
            self::CHECKBOX,
            self::RADIO,
            self::LIKERT_SCALE => true,
            default => false,
        };
    }

    public function isScaleBased(): bool
    {
        return match($this) {
            self::LIKERT_SCALE,
            self::NUMERIC_SCALE,
            self::SLIDER => true,
            default => false,
        };
    }
}