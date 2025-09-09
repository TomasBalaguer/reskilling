<?php

namespace App\Services\Questionnaire\Types;

use App\Enums\QuestionType;
use App\Services\Questionnaire\AbstractQuestionnaireStrategy;
use App\Models\Questionnaire;

class ReflectiveQuestionsStrategy extends AbstractQuestionnaireStrategy
{
    /**
     * Build the questionnaire structure for frontend consumption
     */
    public function buildStructure(Questionnaire $questionnaire): array
    {
        // Get structure from database if available, otherwise build from questions
        if ($questionnaire->structure) {
            return $questionnaire->structure;
        }

        return [
            'metadata' => [
                'evaluation_type' => 'qualitative',
                'response_format' => 'audio_with_transcription',
                'skill_dimensions' => $this->getSkillDimensions(),
                'target_age_range' => '18-35',
                'competencies_evaluated' => 15,
                'ai_processing' => true,
                'prosodic_analysis' => true
            ],
            'sections' => [
                [
                    'id' => 'reflective_questions',
                    'title' => 'Preguntas Reflexivas sobre Habilidades Blandas',
                    'description' => 'Responde cada pregunta con sinceridad y reflexión. Tómate el tiempo que necesites para pensar en tus experiencias personales. Graba tu respuesta de audio para cada pregunta.',
                    // 'instructions' => $this->getInstructions(),
                    'questions' => $this->transformQuestionsToStructure($questionnaire->questions ?? []),
                    'response_type' => 'audio_response'
                ]
            ]
        ];
    }

    /**
     * Calculate scores and perform initial analysis
     */
    public function calculateScores(array $processedResponses, array $respondentData = []): array
    {
        return [
            'scoring_type' => 'REFLECTIVE_QUESTIONS',
            'questionnaire_name' => 'Preguntas Reflexivas',
            'response_type' => 'audio_response',
            'respondent_data' => $respondentData,
            'transcriptions' => $this->extractTranscriptions($processedResponses),
            'audio_analysis' => $this->extractAudioAnalysis($processedResponses),
            'analysis_indicators' => $this->calculateAnalysisIndicators($processedResponses),
            'soft_skills_analysis' => $this->initializeSoftSkillsStructure(),
            'summary' => $this->generateSummary($processedResponses, $respondentData),
        ];
    }

    /**
     * Determine if responses require AI processing
     */
    public function requiresAIProcessing(): bool
    {
        return true;
    }

    /**
     * Get supported question types for this strategy
     */
    public function getSupportedQuestionTypes(): array
    {
        return [
            QuestionType::AUDIO_RESPONSE->value
        ];
    }

    /**
     * Get estimated duration
     */
    protected function getEstimatedDuration(): int
    {
        return 35; // 35 minutes estimated for 7 reflective audio questions
    }

    /**
     * Get skill dimensions for reflective questions
     */
    private function getSkillDimensions(): array
    {
        return [
            'autoconocimiento' => 'Capacidad de reflexión sobre la propia identidad, valores y crecimiento personal',
            'resiliencia' => 'Habilidad para recuperarse de setbacks y aprender de las dificultades',
            'pensamiento_critico' => 'Capacidad de análisis, evaluación de opciones y toma de decisiones fundamentadas',
            'liderazgo' => 'Habilidades para manejar conflictos, facilitar colaboración y guiar equipos',
            'creatividad' => 'Capacidad de encontrar soluciones innovadoras y pensar de manera no convencional',
            'motivacion' => 'Claridad sobre objetivos personales, impulso hacia el logro y perseverancia',
            'empatia' => 'Capacidad de comprender y responder a las emociones y perspectivas de otros'
        ];
    }

    /**
     * Get instructions for reflective questions
     */
    private function getInstructions(): array
    {
        return [
            'Encuentra un lugar tranquilo donde puedas hablar sin interrupciones.',
            'Lee cada pregunta cuidadosamente antes de comenzar a grabar.',
            'Tómate unos momentos para reflexionar antes de responder.',
            'Habla con naturalidad, como si estuvieras conversando con un amigo.',
            'Puedes pausar y retomar la grabación si necesitas tiempo para pensar.',
            'No hay respuestas correctas o incorrectas, solo comparte tu experiencia personal.',
            'Si no tienes una experiencia específica para alguna pregunta, explica cómo crees que reaccionarías.'
        ];
    }

    /**
     * Transform questions to match frontend structure
     */
    private function transformQuestionsToStructure(array $questions): array
    {
        $skillFocusMap = [
            'q1' => 'Autoconocimiento',
            'q2' => 'Resiliencia', 
            'q3' => 'Pensamiento crítico',
            'q4' => 'Liderazgo',
            'q5' => 'Creatividad',
            'q6' => 'Motivación',
            'q7' => 'Empatía'
        ];

        $maxDurationMap = [
            'q1' => 180, 'q2' => 240, 'q3' => 240,
            'q4' => 300, 'q5' => 240, 'q6' => 240, 'q7' => 300
        ];

        $transformedQuestions = [];
        foreach ($questions as $id => $text) {
            $transformedQuestions[] = [
                'id' => $id,
                'text' => $text,
                'type' => 'audio_response',
                'required' => true,
                'order' => (int) str_replace('q', '', $id),
                'skill_focus' => $skillFocusMap[$id] ?? 'General',
                'max_duration' => $maxDurationMap[$id] ?? 180,
                'validation_rules' => ['required', 'file', 'mimes:mp3,wav,m4a,aac', 'max:51200']
            ];
        }

        return $transformedQuestions;
    }

    /**
     * Extract transcriptions from processed responses
     */
    private function extractTranscriptions(array $processedResponses): array
    {
        $transcriptions = [];
        
        foreach ($processedResponses as $questionId => $response) {
            if (isset($response['processed_response']['transcription_text'])) {
                $transcriptions[$questionId] = [
                    'question_id' => $questionId,
                    'transcription' => $response['processed_response']['transcription_text'],
                    'duration' => $response['processed_response']['duration'] ?? null
                ];
            }
        }
        
        return $transcriptions;
    }

    /**
     * Extract audio analysis from processed responses
     */
    private function extractAudioAnalysis(array $processedResponses): array
    {
        $audioAnalysis = [];
        
        foreach ($processedResponses as $questionId => $response) {
            if (isset($response['processed_response']['gemini_analysis'])) {
                $audioAnalysis[$questionId] = $response['processed_response']['gemini_analysis'];
            } elseif (isset($response['processed_response']['transcription_text'])) {
                $audioAnalysis[$questionId] = [
                    'transcripcion' => $response['processed_response']['transcription_text'],
                    'status' => 'transcription_only'
                ];
            }
        }
        
        return $audioAnalysis;
    }

    /**
     * Calculate analysis indicators
     */
    private function calculateAnalysisIndicators(array $processedResponses): array
    {
        $totalResponses = count($processedResponses);
        $totalDuration = 0;
        $responsesWithAudio = 0;

        foreach ($processedResponses as $response) {
            if (isset($response['processed_response']['duration'])) {
                $totalDuration += $response['processed_response']['duration'];
                $responsesWithAudio++;
            } elseif (isset($response['processed_response']['audio_file_path'])) {
                $responsesWithAudio++;
            }
        }

        $avgDuration = $responsesWithAudio > 0 ? round($totalDuration / $responsesWithAudio, 2) : 0;

        return [
            'total_responses' => $totalResponses,
            'responses_with_audio' => $responsesWithAudio,
            'total_duration' => $totalDuration,
            'avg_duration' => $avgDuration,
            'completion_rate' => $totalResponses > 0 ? round(($responsesWithAudio / $totalResponses) * 100, 2) : 0
        ];
    }

    /**
     * Initialize soft skills evaluation structure
     */
    private function initializeSoftSkillsStructure(): array
    {
        $structure = [];
        
        foreach ($this->getSkillDimensions() as $key => $name) {
            $structure[$key] = [
                'name' => $name,
                'level' => null,
                'evidence' => [],
                'development_notes' => null
            ];
        }
        
        return $structure;
    }

    /**
     * Generate summary
     */
    private function generateSummary(array $processedResponses, array $respondentData): string
    {
        $completionRate = $this->calculateAnalysisIndicators($processedResponses)['completion_rate'];
        $ageGroup = isset($respondentData['age']) ? $this->getAgeGroup($respondentData['age']) : 'No especificado';
        
        return "Análisis de habilidades blandas basado en respuestas reflexivas de audio. " .
               "Completitud: {$completionRate}%. Grupo etario: {$ageGroup}. " .
               "Evalúa 7 dimensiones clave mediante análisis de contenido y prosodia.";
    }

    /**
     * Get age group classification
     */
    private function getAgeGroup(?int $age): string
    {
        if (!$age) return 'No especificado';
        
        return match(true) {
            $age < 25 => 'Joven adulto (18-24)',
            $age < 35 => 'Adulto joven (25-34)',
            $age < 45 => 'Adulto (35-44)',
            default => 'Adulto maduro (45+)'
        };
    }
}