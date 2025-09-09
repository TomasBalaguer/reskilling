<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for Sacks Sentence Completion Test (SSCT)
 */
class SacksQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'SACKS';

    /**
     * Category groupings for questions following the official SSCT format
     *
     * @var array
     */
    protected $categoryGroups = [
        'I' => [
            'name' => 'ACTITUD FRENTE A LA MADRE',
            'questions' => [14, 29, 44, 59]
        ],
        'II' => [
            'name' => 'ACTITUD FRENTE AL PADRE',
            'questions' => [1, 16, 31, 46]
        ],
        'III' => [
            'name' => 'ACTITUD FRENTE A LA UNIDAD DE LA FAMILIA',
            'questions' => [12, 27, 42, 57]
        ],
        'IV' => [
            'name' => 'ACTITUD HACIA EL SEXO CONTRARIO',
            'questions' => [10, 25, 40, 55]
        ],
        'V' => [
            'name' => 'ACTITUD HACIA LAS RELACIONES HETEROSEXUALES',
            'questions' => [11, 26, 41, 56]
        ],
        'VI' => [
            'name' => 'ACTITUD HACIA LOS AMIGOS Y CONOCIDOS',
            'questions' => [8, 23, 38, 53]
        ],
        'VII' => [
            'name' => 'ACTITUD FRENTE A LOS SUPERIORES EN EL TRABAJO O EN LA ESCUELA',
            'questions' => [6, 21, 36, 51]
        ],
        'VIII' => [
            'name' => 'ACTITUD HACIA LAS PERSONAS SUPERVISADAS',
            'questions' => [4, 19, 34, 48]
        ],
        'IX' => [
            'name' => 'ACTITUD HACIA LOS COMPAÑEROS EN LA ESCUELA Y EL TRABAJO',
            'questions' => [13, 28, 43, 58]
        ],
        'X' => [
            'name' => 'TEMORES',
            'questions' => [7, 22, 37, 52]
        ],
        'XI' => [
            'name' => 'SENTIMIENTOS DE CULPA',
            'questions' => [15, 30, 45, 60]
        ],
        'XII' => [
            'name' => 'ACTITUD HACIA LAS PROPIAS HABILIDADES',
            'questions' => [2, 17, 32, 47]
        ],
        'XIII' => [
            'name' => 'ACTITUD HACIA EL PASADO',
            'questions' => [9, 24, 39, 54]
        ],
        'XIV' => [
            'name' => 'ACTITUD HACIA EL FUTURO',
            'questions' => [5, 20, 35, 50]
        ],
        'XV' => [
            'name' => 'METAS',
            'questions' => [3, 18, 33, 49]
        ]
    ];

    /**
     * Calculate scores and interpretations based on questionnaire responses
     *
     * @param array $responses Raw responses from the questionnaire
     * @param array $patientData Patient demographic data (age, gender, etc.)
     * @param array $result Optional existing result to extend
     * @return array Processed results with scores, interpretations, and summaries
     */
    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        // Add patient data to results
        $result = $this->addPatientData($result, $patientData);

        // Verify we have sufficient responses
        if (!isset($responses['frases_incompletas']) || empty($responses['frases_incompletas'])) {
            $result['summary'] = 'No se proporcionaron respuestas suficientes para el Test de Frases Incompletas de Sacks.';
            return $result;
        }

        // Group responses by category
        $groupedResponses = $this->groupResponsesByCategory($responses['frases_incompletas']);
        
        // Store results
        $result['grouped_responses'] = $groupedResponses;
        $result['scoring_type'] = 'SACKS';
        $result['questionnaire_name'] = 'Test de Frases Incompletas de Sacks';

        return $result;
    }

    /**
     * Group responses by their categories
     *
     * @param array $responses
     * @return array
     */
    private function groupResponsesByCategory(array $responses): array
    {
        $grouped = [];

        foreach ($this->categoryGroups as $categoryNum => $category) {
            $categoryResponses = [];
            foreach ($category['questions'] as $number) {
                $qKey = 'q' . $number;
                if (isset($responses[$qKey])) {
                    $categoryResponses[] = [
                        'number' => $number,
                        'question' => $responses[$qKey]['question'],
                        'answer' => $responses[$qKey]['answer']
                    ];
                }
            }
            $grouped[$categoryNum] = [
                'name' => $category['name'],
                'responses' => $categoryResponses
            ];
        }

        return $grouped;
    }

    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     *
     * @param array $results Results from the calculateScores method
     * @return string Formatted prompt section
     */
    public function buildPromptSection(array $results): string
    {
        if (empty($results['grouped_responses'])) {
            return "No hay suficientes respuestas para generar un análisis del Test de Frases Incompletas de Sacks.";
        }

        $prompt = "DEVUELVE TU INTERPRETACION DEL CUESARIO SACKSTIONARIO SACKS:\n\n";

        // Add scoring scale explanation
        $prompt .= "ESCALA DE INTERPRETACIÓN:\n";
        $prompt .= "2 - Severamente alterado, aparenta requerir ayuda terapéutica en el manejo de los conflictos emocionales en ésta área.\n";
        $prompt .= "1 - Medianamente alterado, Tiene conflictos emocionales en esta área.\n";
        $prompt .= "0 - No hay alteración significativa en esta área.\n";
        $prompt .= "X - Incierto, No hay suficiente evidencia.\n\n";

        // Add responses by category
        foreach ($results['grouped_responses'] as $categoryNum => $category) {
            $prompt .= "{$categoryNum}.- {$category['name']}. Puntaje_____\n\n";
            
            foreach ($category['responses'] as $response) {
                $prompt .= "{$response['number']}- {$response['question']}\n";
                $prompt .= "Respuesta: {$response['answer']}\n";
            }
            
            $prompt .= "\nSUMARIO INTERPRETATIVO:\n";
            $prompt .= "[Aquí debes escribir un breve análisis interpretativo de las respuestas de esta categoría]\n\n";
        }

        // Add general summary section
        $prompt .= "SUMARIO GENERAL\n\n";
        $prompt .= "1.- Áreas principales de conflicto y alteración.\n";
        $prompt .= "[Enumera y describe las áreas que muestran mayor conflicto o alteración basándote en los puntajes asignados]\n\n";
        
        $prompt .= "2.- Interrelación entre las actitudes.\n";
        $prompt .= "[Describe cómo se relacionan entre sí las diferentes actitudes evaluadas]\n\n";
        
        $prompt .= "3.- Estructura de la personalidad\n\n";
        $prompt .= "A.- Extensión en la cual el sujeto responde a impulsos internos y estímulos externos\n";
        $prompt .= "[Analiza cómo el sujeto responde a impulsos internos vs. estímulos externos]\n\n";
        
        $prompt .= "B.- Ajuste emocional\n";
        $prompt .= "[Evalúa el nivel de ajuste emocional del sujeto]\n\n";
        
        $prompt .= "C.- Madurez\n";
        $prompt .= "[Evalúa el nivel de madurez psicológica]\n\n";
        
        $prompt .= "D.- Nivel de realidad\n";
        $prompt .= "[Analiza el contacto con la realidad y la adecuación de las respuestas]\n\n";
        
        $prompt .= "E.- Forma en que los conflictos son expresados\n";
        $prompt .= "[Describe los mecanismos y formas de expresión de conflictos]\n\n";



        return $prompt;
    }

    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     *
     * @return string Formatted instructions
     */
    public function getInstructions(): string
    {
        $defaultInstructions = "Por favor, realiza un análisis detallado del Test de Frases Incompletas de Sacks siguiendo exactamente este formato:\n\n" .
               "1. Para cada una de las 15 categorías:\n" .
               "   - Asigna un puntaje (2: Severamente alterado, 1: Medianamente alterado, 0: Sin alteración, X: Incierto)\n" .
               "   - Proporciona un sumario interpretativo específico analizando las respuestas de esa categoría\n" .
               "   - Identifica patrones, conflictos y significados subyacentes\n\n" .
               "2. En el Sumario General:\n" .
               "   - Lista las áreas principales de conflicto, especificando el nivel de alteración\n" .
               "   - Explica cómo se interrelacionan las diferentes actitudes\n" .
               "   - Describe la estructura de la personalidad considerando:\n" .
               "     * Respuesta a impulsos internos y estímulos externos\n" .
               "     * Nivel de ajuste emocional\n" .
               "     * Grado de madurez psicológica\n" .
               "     * Contacto con la realidad\n" .
               "     * Mecanismos de expresión de conflictos\n\n" .
               "3. Mantén un estilo clínico profesional, objetivo y basado en la evidencia proporcionada por las respuestas.";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 