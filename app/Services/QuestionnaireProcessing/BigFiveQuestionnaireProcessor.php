<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for BIG FIVE (BFQ) personality questionnaire
 */
class BigFiveQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'BIG_FIVE';
    
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
        if (!isset($responses['personalidad']) || empty($responses['personalidad'])) {
            $result['summary'] = 'No se proporcionaron respuestas suficientes para el cuestionario BFQ.';
            return $result;
        }

        // Define scales and their items
        $scales = [
            // Dimensions
            'E' => [
                'name' => 'Energía (E)',
                'description' => 'Evalúa aspectos relacionados con la extraversión, energía, dinamismo y dominancia',
                'items' => ['q1', 'q7', 'q13', 'q19', 'q25', 'q31', 'q37', 'q39', 'q51', 'q53', 'q59', 'q61', 'q68', 'q71', 'q73', 'q78', 'q94', 'q99', 'q114', 'q121'],
                'inverted' => [7, 19, 31, 37, 51, 61, 71, 78, 99, 121]
            ],
            'A' => [
                'name' => 'Afabilidad (A)',
                'description' => 'Evalúa aspectos relacionados con la cooperación, empatía y cordialidad',
                'items' => ['q3', 'q10', 'q16', 'q22', 'q28', 'q34', 'q40', 'q48', 'q52', 'q64', 'q70', 'q74', 'q86', 'q93', 'q100', 'q108', 'q111', 'q126', 'q128', 'q130'],
                'inverted' => [16, 28, 40, 64, 70, 74, 100, 108, 128, 130]
            ],
            'T' => [
                'name' => 'Tesón (T)',
                'description' => 'Evalúa aspectos relacionados con la responsabilidad, perseverancia y orden',
                'items' => ['q2', 'q8', 'q14', 'q20', 'q26', 'q32', 'q38', 'q46', 'q49', 'q54', 'q57', 'q66', 'q75', 'q79', 'q82', 'q85', 'q96', 'q106', 'q107', 'q110'],
                'inverted' => [2, 14, 32, 38, 54, 66, 82, 85, 107, 110]
            ],
            'EE' => [
                'name' => 'Estabilidad Emocional (EE)',
                'description' => 'Evalúa aspectos relacionados con el control emocional y la ansiedad',
                'items' => ['q9', 'q15', 'q21', 'q27', 'q33', 'q43', 'q45', 'q50', 'q58', 'q62', 'q69', 'q76', 'q81', 'q83', 'q89', 'q91', 'q98', 'q102', 'q119', 'q122'],
                'inverted' => [15, 33, 45, 62, 69, 83, 98, 120]
            ],
            'AM' => [
                'name' => 'Apertura Mental (AM)',
                'description' => 'Evalúa aspectos relacionados con la apertura a la cultura y experiencias',
                'items' => ['q5', 'q11', 'q17', 'q23', 'q29', 'q35', 'q41', 'q47', 'q55', 'q56', 'q60', 'q67', 'q72', 'q77', 'q87', 'q90', 'q97', 'q105', 'q124', 'q131'],
                'inverted' => [11, 17, 35, 47, 55, 67, 77, 90, 124, 131]
            ],
            // Distortion scale
            'D' => [
                'name' => 'Distorsión (D)',
                'description' => 'Evalúa la tendencia a dar una imagen distorsionada de sí mismo',
                'items' => ['q6', 'q12', 'q18', 'q24', 'q30', 'q36', 'q42', 'q44', 'q80', 'q84', 'q92', 'q101', 'q113', 'q127'],
                'inverted' => []
            ]
        ];
        // Calculate raw scores
        $scores = [];
        foreach ($scales as $scaleId => $scale) {
            $sum = 0;
            $validItems = 0;
            foreach ($scale['items'] as $index => $item) {
                if (isset($responses['personalidad'][$item]['answer'])) {
                    $answer = $responses['personalidad'][$item]['answer'];
                    // Check if item should be inverted
                    if (in_array($index + 1, $scale['inverted'])) {
                        $answer = 6 - $answer; // Invert 1-5 to 5-1
                    }
                    
                    $sum += $answer;
                    $validItems++;
                }
            }
            $scores[$scaleId] = [
                'raw_score' => $sum,
                'item_count' => $validItems,
                'name' => $scale['name'],
                'description' => $scale['description']
            ];
        }
        // Convert raw scores to T scores using normative data
        $tScores = $this->convertToTScores($scores);
        foreach ($tScores as $scaleId => $tScore) {
            $scores[$scaleId]['t_score'] = $tScore;
        }
        // Generate interpretations
        $interpretations = $this->getBigFiveInterpretations($scores);
        // Generate clinical interpretations
        $clinicalInterpretations = $this->generateBigFiveClinicalInterpretations($scores);
        
        // Generate summary
        $summary = $this->generateBigFiveSummary($scores, $interpretations);

        // Prepare final result
        $result['scores'] = $scores;
        $result['interpretations'] = $interpretations;
        $result['clinical_interpretations'] = $clinicalInterpretations;
        $result['summary'] = $summary;
        $result['scoring_type'] = 'BIG_FIVE';
        $result['questionnaire_name'] = 'Cuestionario Big Five (BFQ)';

        return $result;
    }

    /**
     * Convert raw scores to T scores using normative data
     */
    private function convertToTScores($scores): array
    {
        // This would normally use normative tables. For now using a simple conversion
        // In a real implementation, this would use proper normative data based on age, gender, etc.
        $tScores = [];
        foreach ($scores as $scaleId => $score) {
            try {
                // Simple conversion formula - this should be replaced with actual normative data
                $mean = 50; // T score mean
                $sd = 10;   // T score standard deviation
                
                // Use the actual number of items that were answered
                $maxPossible = $score['item_count'] * 5; // Max possible raw score (5 points per item)
                $tScores[$scaleId] = round(($score['raw_score'] / $maxPossible) * 100);
                
                // Ensure T scores fall within typical range (20-80)
                $tScores[$scaleId] = max(20, min(80, $tScores[$scaleId]));
            } catch(\Exception $e) {
                // Log error and set default score
                $tScores[$scaleId] = 50; // Default to mean if there's an error
            }
        }
        
        return $tScores;
    }

    /**
     * Generate interpretations for Big Five scores
     */
    private function getBigFiveInterpretations($scores): array
    {
        $interpretations = [];
        
        foreach ($scores as $scaleId => $score) {
            $tScore = $score['t_score'];
            
            // Get interpretation based on T score ranges
            if ($tScore <= 35) {
                $level = "muy bajo";
            } elseif ($tScore <= 45) {
                $level = "bajo";
            } elseif ($tScore <= 55) {
                $level = "promedio";
            } elseif ($tScore <= 65) {
                $level = "alto";
            } else {
                $level = "muy alto";
            }
            
            // Scale-specific interpretations
            switch ($scaleId) {
                case 'E':
                    $interpretations[$scaleId] = "Nivel $level de energía y extraversión. " . 
                        ($tScore > 55 ? "Tendencia a ser dinámico, activo y dominante." : 
                         ($tScore < 45 ? "Tendencia a ser reservado, poco activo y sumiso." : 
                         "Niveles equilibrados de actividad y sociabilidad."));
                    break;
                    
                case 'A':
                    $interpretations[$scaleId] = "Nivel $level de afabilidad. " .
                        ($tScore > 55 ? "Tendencia a ser cooperativo, empático y cordial." :
                         ($tScore < 45 ? "Tendencia a ser poco cooperativo y distante." :
                         "Niveles equilibrados de cooperación y empatía."));
                    break;
                    
                case 'T':
                    $interpretations[$scaleId] = "Nivel $level de tesón. " .
                        ($tScore > 55 ? "Tendencia a ser meticuloso, ordenado y perseverante." :
                         ($tScore < 45 ? "Tendencia a ser poco meticuloso y poco perseverante." :
                         "Niveles equilibrados de orden y perseverancia."));
                    break;
                    
                case 'EE':
                    $interpretations[$scaleId] = "Nivel $level de estabilidad emocional. " .
                        ($tScore > 55 ? "Tendencia a ser emocionalmente estable y controlado." :
                         ($tScore < 45 ? "Tendencia a ser emocionalmente inestable y ansioso." :
                         "Niveles equilibrados de estabilidad emocional."));
                    break;
                    
                case 'AM':
                    $interpretations[$scaleId] = "Nivel $level de apertura mental. " .
                        ($tScore > 55 ? "Tendencia a ser abierto a nuevas experiencias y culturalmente interesado." :
                         ($tScore < 45 ? "Tendencia a ser conservador y poco abierto a nuevas experiencias." :
                         "Niveles equilibrados de apertura a la experiencia."));
                    break;
                    
                case 'D':
                    $interpretations[$scaleId] = "Nivel $level de distorsión. " .
                        ($tScore > 65 ? "Posible tendencia a presentar una imagen artificialmente positiva." :
                         ($tScore < 35 ? "Posible tendencia a presentar una imagen artificialmente negativa." :
                         "Nivel normal de distorsión en las respuestas."));
                    break;
            }
        }
        
        return $interpretations;
    }

    /**
     * Generate clinical interpretations for Big Five scores
     */
    private function generateBigFiveClinicalInterpretations($scores): array
    {
        $clinicalInterpretations = [];
        
        // Check for extreme scores
        foreach ($scores as $scaleId => $score) {
            if ($score['t_score'] >= 70 || $score['t_score'] <= 30) {
                switch ($scaleId) {
                    case 'E':
                        $clinicalInterpretations[] = $score['t_score'] >= 70 ?
                            "La elevada puntuación en Energía sugiere posible comportamiento dominante o impulsivo en situaciones sociales." :
                            "La baja puntuación en Energía puede indicar dificultades en situaciones que requieren interacción social o asertividad.";
                        break;
                        
                    case 'A':
                        $clinicalInterpretations[] = $score['t_score'] >= 70 ?
                            "La elevada puntuación en Afabilidad podría indicar dificultades para establecer límites o defender los propios intereses." :
                            "La baja puntuación en Afabilidad sugiere posibles dificultades en las relaciones interpersonales y trabajo en equipo.";
                        break;
                        
                    case 'T':
                        $clinicalInterpretations[] = $score['t_score'] >= 70 ?
                            "La elevada puntuación en Tesón podría indicar tendencias perfeccionistas o rigidez conductual." :
                            "La baja puntuación en Tesón sugiere posibles dificultades con la organización y cumplimiento de objetivos.";
                        break;
                        
                    case 'EE':
                        $clinicalInterpretations[] = $score['t_score'] >= 70 ?
                            "La elevada puntuación en Estabilidad Emocional podría indicar cierta insensibilidad o desconexión emocional." :
                            "La baja puntuación en Estabilidad Emocional sugiere vulnerabilidad al estrés y posibles dificultades de regulación emocional.";
                        break;
                        
                    case 'AM':
                        $clinicalInterpretations[] = $score['t_score'] >= 70 ?
                            "La elevada puntuación en Apertura Mental podría indicar dificultades para mantener rutinas o compromisos estables." :
                            "La baja puntuación en Apertura Mental sugiere posible rigidez cognitiva y resistencia al cambio.";
                        break;
                }
            }
        }

        // Check for distortion
        if ($scores['D']['t_score'] > 65) {
            $clinicalInterpretations[] = "La elevada puntuación en la escala de Distorsión sugiere una posible tendencia a presentar una imagen artificialmente favorable de sí mismo. Los resultados deben interpretarse con cautela.";
        } elseif ($scores['D']['t_score'] < 35) {
            $clinicalInterpretations[] = "La baja puntuación en la escala de Distorsión sugiere una posible tendencia a presentar una imagen artificialmente desfavorable de sí mismo. Los resultados deben interpretarse con cautela.";
        }

        return $clinicalInterpretations;
    }

    /**
     * Generate a summary for Big Five results
     */
    private function generateBigFiveSummary($scores, $interpretations): string
    {
        // Identify predominant characteristics
        $highScores = [];
        $lowScores = [];
        
        foreach ($scores as $scaleId => $score) {
            if ($scaleId != 'D') { // Exclude distortion scale from summary
                if ($score['t_score'] >= 60) {
                    $highScores[] = $score['name'];
                } elseif ($score['t_score'] <= 40) {
                    $lowScores[] = $score['name'];
                }
            }
        }

        $summary = "El perfil de personalidad muestra ";
        
        if (!empty($highScores)) {
            $summary .= "puntuaciones elevadas en " . implode(", ", $highScores);
        }
        
        if (!empty($highScores) && !empty($lowScores)) {
            $summary .= " y ";
        }
        
        if (!empty($lowScores)) {
            $summary .= "puntuaciones bajas en " . implode(", ", $lowScores);
        }
        
        if (empty($highScores) && empty($lowScores)) {
            $summary .= "un patrón general de puntuaciones dentro del rango promedio";
        }
        
        $summary .= ". ";

        // Add distortion interpretation
        if ($scores['D']['t_score'] > 65) {
            $summary .= "La elevada puntuación en Distorsión sugiere que estos resultados podrían estar influidos por una tendencia a presentar una imagen favorable de sí mismo.";
        } elseif ($scores['D']['t_score'] < 35) {
            $summary .= "La baja puntuación en Distorsión sugiere que estos resultados podrían estar influidos por una tendencia a presentar una imagen desfavorable de sí mismo.";
        } else {
            $summary .= "El nivel de Distorsión está dentro de límites normales, lo que sugiere que estos resultados son probablemente una representación válida de la personalidad del individuo.";
        }

        return $summary;
    }

    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     */
    public function buildPromptSection(array $results): string
    {
        $promptSection = "## Resultados del Cuestionario Big Five (BFQ)\n\n";
        
        // Add demographic information if available
        if (isset($results['patient_data'])) {
            $promptSection .= "### Información demográfica relevante:\n";
            
            if (isset($results['patient_data']['age'])) {
                $promptSection .= "- **Edad**: " . $results['patient_data']['age'] . " años\n";
            }
            
            if (isset($results['patient_data']['gender'])) {
                $promptSection .= "- **Género**: " . $results['patient_data']['gender'] . "\n";
            }
            
            $promptSection .= "\n";
        }
        // Add main dimensions
        if (isset($results['scores'])) {
            $promptSection .= "### Dimensiones principales:\n";
            
            $mainDimensions = ['E', 'A', 'T', 'EE', 'AM'];
            
            foreach ($mainDimensions as $dim) {
                if (isset($results['scores'][$dim])) {
                    $score = $results['scores'][$dim];
                    $promptSection .= "- **" . $score['name'] . "**: T = " . $score['t_score'] . "\n";
                    $promptSection .= "  - " . $score['description'] . "\n";
                    
                    if (isset($results['interpretations'][$dim])) {
                        $promptSection .= "  - **Interpretación**: " . $results['interpretations'][$dim] . "\n";
                    }
                    
                    $promptSection .= "\n";
                }
            }
        }
        
        // Add distortion scale
        if (isset($results['scores']['D'])) {
            $promptSection .= "### Escala de Distorsión:\n";
            $score = $results['scores']['D'];
            $promptSection .= "- **" . $score['name'] . "**: T = " . $score['t_score'] . "\n";
            $promptSection .= "  - " . $score['description'] . "\n";
            
            if (isset($results['interpretations']['D'])) {
                $promptSection .= "  - **Interpretación**: " . $results['interpretations']['D'] . "\n";
            }
            
            $promptSection .= "\n";
        }
        
        // Add clinical interpretations
        if (isset($results['clinical_interpretations']) && !empty($results['clinical_interpretations'])) {
            $promptSection .= "### Interpretaciones clínicas específicas:\n";
            foreach ($results['clinical_interpretations'] as $interpretation) {
                $promptSection .= "- " . $interpretation . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Add summary
        if (isset($results['summary'])) {
            $promptSection .= "### Resumen global del BFQ:\n";
            $promptSection .= $results['summary'] . "\n\n";
        }
        
        return $promptSection;
    }

    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     */
    public function getInstructions(): string
    {
        $defaultInstructions = "Basándote en los resultados del Cuestionario Big Five (BFQ), proporciona:\n\n" .
               "1. Una interpretación clínica detallada de los resultados considerando la edad y género del paciente.\n" .
               "2. Análisis de las fortalezas y áreas de mejora identificadas.\n" .
               "3. Recomendaciones específicas para mejorar el bienestar y la energía personal, adaptadas a las características demográficas del paciente.\n" .
               "4. Consideraciones sobre la importancia de estos resultados para la salud mental y física, tomando en cuenta factores como la etapa vital y roles sociales.\n" .
               "5. Relación entre las diferentes dimensiones de bienestar evaluadas y cómo se influyen mutuamente en este caso particular.\n";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 