<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for Sacks Sentence Completion Test - Short Version (SSCT-Short)
 *
 * This processor uses the same logic as the full SACKS questionnaire
 * but adapted for a shorter version with fewer questions
 */
class SacksShortQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'SACKS_SHORT';

    /**
     * Category groupings for questions in the short version
     * This will be updated based on which questions are included in the short version
     *
     * @var array
     */
    protected $categoryGroups = [
        'I' => [
            'name' => 'ACTITUD FRENTE A LA MADRE',
            'questions' => [11], // q11: "Mi madre"
        ],
        'II' => [
            'name' => 'ACTITUD FRENTE AL PADRE',
            'questions' => [31], // q31: "Mi padre"
        ],
        'III' => [
            'name' => 'ACTITUD FRENTE A LA UNIDAD DE LA FAMILIA',
            'questions' => [4, 27], // q4: "Al regresar a mi casa", q27: "Mi casa"
        ],
        'IV' => [
            'name' => 'ACTITUD HACIA EL SEXO CONTRARIO',
            'questions' => [7, 35], // q7: "Los hombres", q35: "Las mujeres"
        ],
        'V' => [
            'name' => 'ACTITUD HACIA LAS RELACIONES HETEROSEXUALES',
            'questions' => [23], // q23: "La pareja"
        ],
        'VI' => [
            'name' => 'ACTITUD HACIA LOS AMIGOS Y CONOCIDOS',
            'questions' => [1, 10, 17], // q1: "Me gusta", q10: "La gente", q17: "La otra gente"
        ],
        'VII' => [
            'name' => 'ACTITUD FRENTE A LOS SUPERIORES EN EL TRABAJO O EN LA ESCUELA',
            'questions' => [], // No hay preguntas específicas en esta versión corta
        ],
        'VIII' => [
            'name' => 'ACTITUD HACIA LAS PERSONAS SUPERVISADAS',
            'questions' => [], // No hay preguntas específicas en esta versión corta
        ],
        'IX' => [
            'name' => 'ACTITUD HACIA LOS COMPAÑEROS EN LA ESCUELA Y EL TRABAJO',
            'questions' => [], // No hay preguntas específicas en esta versión corta
        ],
        'X' => [
            'name' => 'TEMORES',
            'questions' => [9, 13, 16, 26, 29, 34], // q9: "Lo que me molesta", q13: "Mi mayor temor", q16: "Mis nervios", q26: "Lo que me duele", q29: "Mi única preocupación", q34: "Mi mayor ansiedad"
        ],
        'XI' => [
            'name' => 'SENTIMIENTOS DE CULPA',
            'questions' => [5, 19], // q5: "Lamento", q19: "Yo fracasé"
        ],
        'XII' => [
            'name' => 'ACTITUD HACIA LAS PROPIAS HABILIDADES (AUTOCONCEPTO)',
            'questions' => [8, 12, 14, 18, 20, 24, 25, 28, 32, 33], // q8: "Lo mejor", q12: "Yo siento", q14: "No puedo", q18: "Yo sufro", q20: "Mi mente", q24: "Yo estoy mejor cuando", q25: "Algunas veces", q28: "Yo soy muy", q32: "Yo en secreto", q33: "Yo"
        ],
        'XIII' => [
            'name' => 'ACTITUD HACIA EL PASADO',
            'questions' => [2, 6, 15], // q2: "La época más feliz", q6: "Al acostarme", q15: "Cuando era chico/a"
        ],
        'XIV' => [
            'name' => 'ACTITUD HACIA EL FUTURO',
            'questions' => [21], // q21: "El futuro"
        ],
        'XV' => [
            'name' => 'METAS',
            'questions' => [3, 22, 30], // q3: "Quisiera saber", q22: "Necesito", q30: "Deseo"
        ],
    ];

    /**
     * Calculate scores and interpretations based on questionnaire responses
     * Uses the same logic as the full SACKS questionnaire
     *
     * @param  array  $responses  Raw responses from the questionnaire
     * @param  array  $patientData  Patient demographic data (age, gender, etc.)
     * @param  array  $result  Optional existing result to extend
     * @return array Processed results with scores, interpretations, and summaries
     */
    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        // Add patient data to results
        $result = $this->addPatientData($result, $patientData);

        // Verify we have sufficient responses
        if (! isset($responses['frases_incompletas_short']) || empty($responses['frases_incompletas_short'])) {
            $result['summary'] = 'No se proporcionaron respuestas suficientes para el Test de Frases Incompletas de Sacks - Versión Corta.';

            return $result;
        }

        // Group responses by category (same logic as full SACKS)
        $groupedResponses = $this->groupResponsesByCategory($responses['frases_incompletas_short']);

        // Store results
        $result['grouped_responses'] = $groupedResponses;
        $result['scoring_type'] = 'SACKS_SHORT';
        $result['questionnaire_name'] = 'Test de Frases Incompletas de Sacks - Versión Corta';

        return $result;
    }

    /**
     * Group responses by their categories
     * Uses the same logic as the full SACKS questionnaire
     */
    private function groupResponsesByCategory(array $responses): array
    {
        $grouped = [];

        foreach ($this->categoryGroups as $categoryNum => $category) {
            $categoryResponses = [];

            // Process questions assigned to this category
            if (! empty($category['questions'])) {
                foreach ($category['questions'] as $number) {
                    $qKey = 'q'.$number;
                    if (isset($responses[$qKey])) {
                        $categoryResponses[] = [
                            'number' => $number,
                            'question' => $responses[$qKey]['question'],
                            'answer' => $responses[$qKey]['answer'],
                        ];
                    }
                }
            }

            // Only add categories that have responses
            if (! empty($categoryResponses)) {
                $grouped[$categoryNum] = [
                    'name' => $category['name'],
                    'responses' => $categoryResponses,
                ];
            }
        }

        return $grouped;
    }

    /**
     * Get the category groups configuration
     */
    public function getCategoryGroups(): array
    {
        return $this->categoryGroups;
    }

    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     * Uses the same format as the full SACKS questionnaire
     *
     * @param  array  $results  Results from the calculateScores method
     * @return string Formatted prompt section
     */
    public function buildPromptSection(array $results): string
    {
        if (empty($results['grouped_responses'])) {
            return 'No hay suficientes respuestas para generar un análisis del Test de Frases Incompletas de Sacks - Versión Corta.';
        }

        $prompt = "DEVUELVE TU INTERPRETACION DEL CUESTIONARIO SACKS - VERSIÓN CORTA:\n\n";

        // Add scoring scale explanation (same as full SACKS)
        $prompt .= "ESCALA DE INTERPRETACIÓN:\n";
        $prompt .= "2 - Severamente alterado, aparenta requerir ayuda terapéutica en el manejo de los conflictos emocionales en ésta área.\n";
        $prompt .= "1 - Medianamente alterado, Tiene conflictos emocionales en esta área.\n";
        $prompt .= "0 - No hay alteración significativa en esta área.\n";
        $prompt .= "X - Incierto, No hay suficiente evidencia.\n\n";

        // Add responses by category (same logic as full SACKS)
        foreach ($results['grouped_responses'] as $categoryNum => $category) {
            $prompt .= "{$categoryNum}.- {$category['name']}. Puntaje_____\n\n";

            foreach ($category['responses'] as $response) {
                $prompt .= "{$response['number']}- {$response['question']}\n";
                $prompt .= "Respuesta: {$response['answer']}\n";
            }

            $prompt .= "\nSUMARIO INTERPRETATIVO:\n";
            $prompt .= "[Aquí debes escribir un breve análisis interpretativo de las respuestas de esta categoría]\n\n";
        }

        // Add general summary section (same as full SACKS)
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
     * Uses the same instructions as the full SACKS questionnaire
     *
     * @return string Formatted instructions
     */
    public function getInstructions(): string
    {
        $defaultInstructions = "Por favor, realiza un análisis detallado del Test de Frases Incompletas de Sacks - Versión Corta siguiendo exactamente este formato:\n\n".
               "1. Para cada categoría presente:\n".
               "   - Asigna un puntaje (2: Severamente alterado, 1: Medianamente alterado, 0: Sin alteración, X: Incierto)\n".
               "   - Proporciona un sumario interpretativo específico analizando las respuestas de esa categoría\n".
               "   - Identifica patrones, conflictos y significados subyacentes\n\n".
               "2. En el Sumario General:\n".
               "   - Lista las áreas principales de conflicto, especificando el nivel de alteración\n".
               "   - Explica cómo se interrelacionan las diferentes actitudes\n".
               "   - Describe la estructura de la personalidad considerando:\n".
               "     * Respuesta a impulsos internos y estímulos externos\n".
               "     * Nivel de ajuste emocional\n".
               "     * Grado de madurez psicológica\n".
               "     * Contacto con la realidad\n".
               "     * Mecanismos de expresión de conflictos\n\n".
               "3. Mantén un estilo clínico profesional, objetivo y basado en la evidencia proporcionada por las respuestas.\n\n".
               'NOTA: Esta es una versión corta del cuestionario, por lo que el análisis debe adaptarse al número reducido de preguntas disponibles.';

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
}
