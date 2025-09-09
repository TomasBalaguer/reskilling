<?php

namespace App\Services\Questionnaire\Types;

use App\Enums\QuestionType;
use App\Services\Questionnaire\AbstractQuestionnaireStrategy;
use App\Models\Questionnaire;

class TextResponseStrategy extends AbstractQuestionnaireStrategy
{
    public function buildStructure(Questionnaire $questionnaire): array
    {
        if ($questionnaire->structure) {
            return $questionnaire->structure;
        }

        return [
            'metadata' => [
                'evaluation_type' => 'qualitative',
                'response_format' => 'text_input',
                'scoring_method' => 'ai_analysis',
                'requires_ai_processing' => true,
                'min_word_count' => 10,
                'max_word_count' => 500
            ],
            'sections' => [
                [
                    'id' => 'text_response_questions',
                    'title' => $questionnaire->name,
                    'description' => $questionnaire->description,
                    'instructions' => [
                        'Lee cada pregunta cuidadosamente.',
                        'Responde con tus propias palabras.',
                        'Se honesto y reflexivo en tus respuestas.',
                        'No hay respuestas correctas o incorrectas.',
                        'Trata de escribir al menos 10 palabras por respuesta.'
                    ],
                    'questions' => $this->transformQuestionsToStructure($questionnaire->questions ?? []),
                    'response_type' => 'text_input'
                ]
            ]
        ];
    }

    public function calculateScores(array $processedResponses, array $respondentData = []): array
    {
        $responseAnalysis = [];
        $textAnalytics = [
            'total_word_count' => 0,
            'total_character_count' => 0,
            'avg_response_length' => 0,
            'complexity_score' => 0
        ];

        foreach ($processedResponses as $questionId => $response) {
            $textResponse = $response['processed_response']['text_response'] ?? '';
            $wordCount = $response['processed_response']['word_count'] ?? 0;
            $charCount = $response['processed_response']['character_count'] ?? 0;

            $textAnalytics['total_word_count'] += $wordCount;
            $textAnalytics['total_character_count'] += $charCount;

            $responseAnalysis[$questionId] = [
                'question_id' => $questionId,
                'text_response' => $textResponse,
                'word_count' => $wordCount,
                'character_count' => $charCount,
                'complexity_indicators' => $this->analyzeTextComplexity($textResponse),
                'sentiment' => $this->detectSentiment($textResponse),
                'key_themes' => $this->extractKeyThemes($textResponse)
            ];
        }

        $questionCount = count($processedResponses);
        if ($questionCount > 0) {
            $textAnalytics['avg_response_length'] = round($textAnalytics['total_word_count'] / $questionCount, 1);
        }

        return [
            'scoring_type' => 'TEXT_RESPONSE',
            'questionnaire_name' => 'Cuestionario de Respuesta Libre',
            'response_type' => 'text_analysis',
            'text_analytics' => $textAnalytics,
            'response_analysis' => $responseAnalysis,
            'content_quality_indicators' => $this->calculateContentQuality($responseAnalysis),
            'ai_processing_required' => true,
            'processing_status' => 'completed_basic_analysis',
            'respondent_data' => $respondentData,
            'summary' => $this->generateSummary($textAnalytics, $questionCount, $responseAnalysis)
        ];
    }

    public function requiresAIProcessing(): bool
    {
        return true;
    }

    public function getSupportedQuestionTypes(): array
    {
        return [
            QuestionType::TEXT_INPUT->value,
            QuestionType::TEXTAREA->value,
            QuestionType::ESSAY->value
        ];
    }

    protected function getEstimatedDuration(): int
    {
        return 25; // 25 minutes for text response questionnaire
    }

    private function transformQuestionsToStructure(array $questions): array
    {
        $transformedQuestions = [];
        
        foreach ($questions as $id => $questionData) {
            $text = is_array($questionData) ? $questionData['text'] : $questionData;
            $inputType = is_array($questionData) ? ($questionData['input_type'] ?? 'textarea') : 'textarea';
            $minWords = is_array($questionData) ? ($questionData['min_words'] ?? 10) : 10;
            $maxWords = is_array($questionData) ? ($questionData['max_words'] ?? 500) : 500;
            
            $transformedQuestions[] = [
                'id' => $id,
                'text' => $text,
                'type' => $inputType,
                'required' => true,
                'order' => (int) str_replace(['q', 'question_'], '', $id),
                'input_type' => $inputType,
                'min_words' => $minWords,
                'max_words' => $maxWords,
                'placeholder' => 'Escribe tu respuesta aquí...',
                'validation_rules' => ['required', 'string', 'min:10', 'max:2000']
            ];
        }

        return $transformedQuestions;
    }

    private function analyzeTextComplexity(string $text): array
    {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count($text, 1);
        $avgWordsPerSentence = count($sentences) > 0 ? count($words) / count($sentences) : 0;
        
        return [
            'sentence_count' => count($sentences),
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'unique_words' => count(array_unique(array_map('strtolower', $words))),
            'vocabulary_diversity' => count($words) > 0 ? count(array_unique(array_map('strtolower', $words))) / count($words) : 0
        ];
    }

    private function detectSentiment(string $text): array
    {
        // Basic sentiment analysis - in production this would use AI service
        $positiveWords = ['bueno', 'excelente', 'positivo', 'feliz', 'satisfecho', 'lograr', 'éxito'];
        $negativeWords = ['malo', 'terrible', 'negativo', 'triste', 'frustrado', 'fallar', 'error'];
        
        $words = str_word_count(strtolower($text), 1);
        $positiveCount = count(array_intersect($words, $positiveWords));
        $negativeCount = count(array_intersect($words, $negativeWords));
        
        $sentiment = 'neutral';
        if ($positiveCount > $negativeCount) {
            $sentiment = 'positive';
        } elseif ($negativeCount > $positiveCount) {
            $sentiment = 'negative';
        }

        return [
            'overall_sentiment' => $sentiment,
            'positive_indicators' => $positiveCount,
            'negative_indicators' => $negativeCount,
            'confidence' => 'low' // Basic analysis has low confidence
        ];
    }

    private function extractKeyThemes(string $text): array
    {
        // Basic theme extraction - in production this would use AI service
        $themes = [
            'liderazgo' => ['líder', 'liderazgo', 'dirigir', 'equipo', 'gestión'],
            'comunicación' => ['comunicar', 'hablar', 'explicar', 'discutir', 'presentar'],
            'problema_resolución' => ['problema', 'solución', 'resolver', 'analizar', 'decidir'],
            'trabajo_equipo' => ['equipo', 'colaborar', 'cooperar', 'juntos', 'grupo']
        ];

        $detectedThemes = [];
        $words = str_word_count(strtolower($text), 1);

        foreach ($themes as $theme => $keywords) {
            $matches = count(array_intersect($words, $keywords));
            if ($matches > 0) {
                $detectedThemes[] = [
                    'theme' => $theme,
                    'relevance_score' => $matches,
                    'matched_keywords' => array_intersect($words, $keywords)
                ];
            }
        }

        return $detectedThemes;
    }

    private function calculateContentQuality(array $responseAnalysis): array
    {
        $totalResponses = count($responseAnalysis);
        $qualityMetrics = [
            'completeness_score' => 0,
            'depth_score' => 0,
            'coherence_score' => 0
        ];

        foreach ($responseAnalysis as $response) {
            // Completeness based on word count
            $wordCount = $response['word_count'];
            $completeness = min(100, ($wordCount / 50) * 100); // 50 words = 100% completeness
            
            // Depth based on sentence structure
            $complexity = $response['complexity_indicators'];
            $depth = min(100, ($complexity['sentence_count'] * 20)); // 5 sentences = 100% depth
            
            // Coherence based on vocabulary diversity
            $coherence = ($complexity['vocabulary_diversity'] ?? 0) * 100;

            $qualityMetrics['completeness_score'] += $completeness;
            $qualityMetrics['depth_score'] += $depth;
            $qualityMetrics['coherence_score'] += $coherence;
        }

        if ($totalResponses > 0) {
            $qualityMetrics['completeness_score'] = round($qualityMetrics['completeness_score'] / $totalResponses, 1);
            $qualityMetrics['depth_score'] = round($qualityMetrics['depth_score'] / $totalResponses, 1);
            $qualityMetrics['coherence_score'] = round($qualityMetrics['coherence_score'] / $totalResponses, 1);
        }

        $qualityMetrics['overall_quality_score'] = round(
            ($qualityMetrics['completeness_score'] + $qualityMetrics['depth_score'] + $qualityMetrics['coherence_score']) / 3, 1
        );

        return $qualityMetrics;
    }

    private function generateSummary(array $textAnalytics, int $questionCount, array $responseAnalysis): string
    {
        $avgLength = $textAnalytics['avg_response_length'];
        $totalWords = $textAnalytics['total_word_count'];
        
        $engagementLevel = match(true) {
            $avgLength >= 100 => 'Alto',
            $avgLength >= 50 => 'Medio',
            $avgLength >= 25 => 'Básico',
            default => 'Mínimo'
        };

        return "Cuestionario de respuesta libre completado. " .
               "Respondidas {$questionCount} preguntas con {$totalWords} palabras totales. " .
               "Promedio de {$avgLength} palabras por respuesta. " .
               "Nivel de engagement: {$engagementLevel}. " .
               "Procesamiento adicional de IA disponible para análisis profundo.";
    }
}