<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for Bienestar y Energía Personal questionnaire
 */
class BienestarQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'BIENESTAR';

    /**
     * Definition of dimensions and their questions
     */
    protected $dimensions = [
        'entorno_estilo_vida' => [
            'name' => 'Entorno y Estilo de Vida',
            'questions' => ['q1', 'q2', 'q3', 'q4']
        ],
        'salud_fisica_energia' => [
            'name' => 'Salud Física y Energía',
            'questions' => ['q5', 'q6', 'q7', 'q8', 'q9']
        ],
        'regulacion_emocional' => [
            'name' => 'Regulación Emocional y Estrés',
            'questions' => ['q10', 'q11', 'q12', 'q13', 'q14']
        ],
        'relaciones_vida_social' => [
            'name' => 'Relaciones y Vida Social',
            'questions' => ['q15', 'q16', 'q17', 'q18', 'q19']
        ],
        'trabajo_proposito' => [
            'name' => 'Trabajo y Propósito',
            'questions' => ['q20', 'q21', 'q22', 'q23', 'q24']
        ],
        'tecnologia_carga_digital' => [
            'name' => 'Tecnología y Carga Digital',
            'questions' => ['q25', 'q26', 'q27', 'q28']
        ],
        'diversion_recreacion' => [
            'name' => 'Diversión y Recreación',
            'questions' => ['q29', 'q30', 'q31']
        ],
        'finanzas_seguridad' => [
            'name' => 'Finanzas y Seguridad',
            'questions' => ['q32', 'q33']
        ],
        'desarrollo_personal' => [
            'name' => 'Desarrollo Personal y Autoconfianza',
            'questions' => ['q34', 'q35', 'q36', 'q37']
        ],
        'habitos_bienestar' => [
            'name' => 'Hábitos y Bienestar General',
            'questions' => ['q38', 'q39', 'q40', 'q41', 'q42', 'q43']
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
        
        // Initialize scores array
        $scores = [];
        $totalScore = 0;
        $totalQuestions = 0;

        // Process each dimension
        foreach ($this->dimensions as $dimensionKey => $dimension) {
            $sum = 0;
            $validResponses = 0;

            // Calculate score for each question in the dimension
            foreach ($dimension['questions'] as $questionId) {
                if (isset($responses[$dimensionKey][$questionId]['answer'])) {
                    $answer = $responses[$dimensionKey][$questionId]['answer'];
                    $sum += $answer;
                    $validResponses++;
                    $totalScore += $answer;
                    $totalQuestions++;
                }
            }

            // Calculate average score for the dimension
            $averageScore = $validResponses > 0 ? round($sum / $validResponses, 2) : 0;
            
            // Store dimension scores
            $scores[$dimensionKey] = [
                'name' => $dimension['name'],
                'score' => $averageScore,
                'interpretation' => $this->interpretScore($averageScore),
                'recommendations' => $this->getRecommendations($dimensionKey, $averageScore)
            ];
        }

        // Calculate overall score
        $overallScore = $totalQuestions > 0 ? round($totalScore / $totalQuestions, 2) : 0;

        // Generate summary and recommendations
        $summary = $this->generateSummary($scores, $overallScore);
        $recommendations = $this->generateOverallRecommendations($scores);

        // Prepare final result
        $result['scores'] = $scores;
        $result['overall_score'] = $overallScore;
        $result['overall_interpretation'] = $this->interpretScore($overallScore);
        $result['summary'] = $summary;
        $result['recommendations'] = $recommendations;
        $result['scoring_type'] = 'BIENESTAR';
        $result['questionnaire_name'] = 'Cuestionario de Bienestar y Energía Personal';

        return $result;
    }

    /**
     * Interpret a score based on the 1-10 scale
     */
    private function interpretScore(float $score): string
    {
        if ($score >= 9) {
            return 'Excelente';
        } elseif ($score >= 7) {
            return 'Bueno';
        } elseif ($score >= 5) {
            return 'Regular';
        } elseif ($score >= 3) {
            return 'Bajo';
        } else {
            return 'Crítico';
        }
    }

    /**
     * Get specific recommendations based on dimension and score
     */
    private function getRecommendations(string $dimension, float $score): array
    {
        $recommendations = [];

        if ($score < 7) {
            switch ($dimension) {
                case 'entorno_estilo_vida':
                    $recommendations[] = 'Considera mejorar aspectos de tu entorno físico que puedan estar afectando tu bienestar.';
                    $recommendations[] = 'Busca formas de conectar más con la naturaleza y optimizar tu espacio vital.';
                    break;
                case 'salud_fisica_energia':
                    $recommendations[] = 'Establece una rutina de ejercicio regular adaptada a tus necesidades.';
                    $recommendations[] = 'Revisa tus hábitos de sueño y alimentación para mejorar tus niveles de energía.';
                    break;
                case 'regulacion_emocional':
                    $recommendations[] = 'Considera practicar técnicas de manejo del estrés como la meditación o respiración consciente.';
                    $recommendations[] = 'Busca apoyo profesional si sientes que necesitas herramientas adicionales para manejar tus emociones.';
                    break;
                case 'relaciones_vida_social':
                    $recommendations[] = 'Dedica tiempo de calidad a fortalecer tus relaciones personales.';
                    $recommendations[] = 'Practica la comunicación asertiva y busca actividades sociales que te agraden.';
                    break;
                case 'trabajo_proposito':
                    $recommendations[] = 'Establece límites claros entre tu vida laboral y personal.';
                    $recommendations[] = 'Identifica aspectos de tu trabajo que podrías mejorar o rediseñar.';
                    break;
                case 'tecnologia_carga_digital':
                    $recommendations[] = 'Establece horarios específicos para el uso de dispositivos digitales.';
                    $recommendations[] = 'Practica la desconexión digital periódicamente.';
                    break;
                case 'diversion_recreacion':
                    $recommendations[] = 'Programa actividades recreativas regularmente.';
                    $recommendations[] = 'Explora nuevos hobbies o retoma antiguos intereses.';
                    break;
                case 'finanzas_seguridad':
                    $recommendations[] = 'Considera crear un presupuesto y plan financiero.';
                    $recommendations[] = 'Busca asesoría financiera si es necesario.';
                    break;
                case 'desarrollo_personal':
                    $recommendations[] = 'Establece metas personales realistas y alcanzables.';
                    $recommendations[] = 'Trabaja en el desarrollo de tu autoestima y confianza.';
                    break;
                case 'habitos_bienestar':
                    $recommendations[] = 'Revisa y ajusta tus hábitos diarios para promover un mayor bienestar.';
                    $recommendations[] = 'Considera establecer rutinas saludables más estructuradas.';
                    break;
            }
        }

        return $recommendations;
    }

    /**
     * Generate a summary based on all scores
     */
    private function generateSummary(array $scores, float $overallScore): string
    {
        $strengths = [];
        $improvements = [];

        foreach ($scores as $dimension) {
            if ($dimension['score'] >= 8) {
                $strengths[] = $dimension['name'];
            } elseif ($dimension['score'] < 6) {
                $improvements[] = $dimension['name'];
            }
        }

        $summary = "Tu nivel general de bienestar es " . strtolower($this->interpretScore($overallScore)) . 
                  " (${overallScore}/10). ";

        if (!empty($strengths)) {
            $summary .= "Tus áreas más fuertes son: " . implode(", ", $strengths) . ". ";
        }

        if (!empty($improvements)) {
            $summary .= "Las áreas que podrían beneficiarse de mayor atención son: " . 
                       implode(", ", $improvements) . ".";
        }

        return $summary;
    }

    /**
     * Generate overall recommendations based on all scores
     */
    private function generateOverallRecommendations(array $scores): array
    {
        $recommendations = [];
        $lowScoreCount = 0;

        foreach ($scores as $dimension) {
            if ($dimension['score'] < 6) {
                $lowScoreCount++;
            }
        }

        if ($lowScoreCount >= 3) {
            $recommendations[] = "Se recomienda una revisión integral de tus hábitos y rutinas de bienestar.";
            $recommendations[] = "Considera buscar apoyo profesional para desarrollar estrategias de mejora en las áreas identificadas.";
        }

        return $recommendations;
    }

    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     */
    public function buildPromptSection(array $questionnaireResults): string
    {
        $promptSection = "";
        // Añadir información demográfica si está disponible
        if (isset($questionnaireResults['patient_data'])) {
            $promptSection .= "### Información demográfica relevante:\n";
            
            if (isset($questionnaireResults['patient_data']['age']) && $questionnaireResults['patient_data']['age'] !== 'No disponible') {
                $promptSection .= "- **Edad**: " . $questionnaireResults['patient_data']['age'] . " años\n";
            }
            
            if (isset($questionnaireResults['patient_data']['gender']) && $questionnaireResults['patient_data']['gender'] !== 'No especificado') {
                $promptSection .= "- **Género**: " . $questionnaireResults['patient_data']['gender'] . "\n";
            }
            
            $promptSection .= "\n";
        }
        // Añadir promedio global de bienestar
        if (isset($questionnaireResults['overall_score'])) {
            $promptSection .= "### Nivel General de Bienestar:\n";
            $promptSection .= "- **Promedio global**: " . $questionnaireResults['overall_score'] . "/10\n\n";
            // Categorización del nivel de bienestar
            $level = "Moderado";
            if ($questionnaireResults['overall_score'] >= 8) {
                $level = "Alto";
            } elseif ($questionnaireResults['overall_score'] < 5) {
                $level = "Bajo";
            }
            
            $promptSection .= "- **Categoría**: Nivel " . $level . " de bienestar general\n\n";
        }
        
        // Añadir puntuaciones por dimensión
        if (isset($questionnaireResults['scores']) && !empty($questionnaireResults['scores'])) {
            $promptSection .= "### Dimensiones evaluadas:\n";
            
            foreach ($questionnaireResults['scores'] as $dimensionKey => $dimension) {
                $promptSection .= "#### " . ($dimension['name'] ?? ucfirst(str_replace('_', ' ', $dimensionKey))) . ":\n";
                $promptSection .= "- **Promedio**: " . $dimension['score'] . "/10 \n";
                
                // Categorización por nivel
                $level = "Moderado";
                if ($dimension['score'] >= 8) {
                    $level = "Alto";
                } elseif ($dimension['score'] <= 4) {
                    $level = "Bajo";
                }
                
                $promptSection .= "- **Nivel**: " . $level . "\n";
                
                // Añadir interpretación de la dimensión
                if (isset($questionnaireResults['interpretations'][$dimensionKey])) {
                    $promptSection .= "- **Interpretación**: " . $questionnaireResults['interpretations'][$dimensionKey] . "\n";
                }
                
                $promptSection .= "\n";
            }
        }
        
        // Añadir fortalezas identificadas
        if (isset($questionnaireResults['strengths']) && !empty($questionnaireResults['strengths'])) {
            $promptSection .= "### Fortalezas identificadas:\n";
            foreach ($questionnaireResults['strengths'] as $strength) {
                $promptSection .= "- " . $strength . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Añadir áreas de mejora identificadas
        if (isset($questionnaireResults['improvement_areas']) && !empty($questionnaireResults['improvement_areas'])) {
            $promptSection .= "### Áreas de atención prioritaria:\n";
            foreach ($questionnaireResults['improvement_areas'] as $area) {
                $promptSection .= "- " . $area . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Añadir interpretaciones clínicas
        if (isset($questionnaireResults['clinical_interpretations']) && !empty($questionnaireResults['clinical_interpretations'])) {
            $promptSection .= "### Interpretaciones clínicas específicas:\n";
            foreach ($questionnaireResults['clinical_interpretations'] as $interpretation) {
                $promptSection .= "- " . $interpretation . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Añadir resumen global
        if (isset($questionnaireResults['summary']) && !empty($questionnaireResults['summary'])) {
            $promptSection .= "### Resumen global del Cuestionario de Bienestar:\n";
            $promptSection .= $questionnaireResults['summary'] . "\n\n";
        }
        
        return $promptSection;
    }

    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     */
    public function getInstructions(): string
    {
        $defaultInstructions = "Basándote en los resultados del Cuestionario de Bienestar y Energía Personal, proporciona:\n\n" .
               "1. Una interpretación clínica detallada de los resultados considerando la edad y género del paciente.\n" .
               "2. Análisis de las fortalezas y áreas de mejora identificadas.\n" .
               "3. Recomendaciones específicas para mejorar el bienestar y la energía personal, adaptadas a las características demográficas del paciente.\n" .
               "4. Consideraciones sobre la importancia de estos resultados para la salud mental y física, tomando en cuenta factores como la etapa vital y roles sociales.\n" .
               "5. Relación entre las diferentes dimensiones de bienestar evaluadas y cómo se influyen mutuamente en este caso particular.\n" .
               "6. Evaluación del impacto del estrés y las estrategias de afrontamiento en el bienestar y la energía personal del paciente.\n";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 