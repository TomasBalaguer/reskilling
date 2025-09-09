<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for STAXI (State-Trait Anger Expression Inventory) questionnaire
 */
class StaxiQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'STAXI';
    
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
        if (empty($responses) || !isset($responses['current_state']) || !isset($responses['general_state']) || !isset($responses['angry_reactions'])) {
            $result['summary'] = 'No se proporcionaron respuestas suficientes para el cuestionario STAXI.';
            return $result;
        }
        
        // Definición de las escalas y subescalas del STAXI
        $scales = [
            'estado_enojo' => [
                'items' => ['q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9', 'q10'],
                'name' => 'Estado de enojo (S-Anger)',
                'description' => 'Mide la intensidad del sentimiento de enojo experimentado en un momento determinado'
            ],
            'rasgo_enojo' => [
                'items' => ['q11', 'q12', 'q13', 'q14', 'q15', 'q16', 'q17', 'q18', 'q19', 'q20'],
                'name' => 'Rasgo de enojo (T-Anger)',
                'description' => 'Mide la predisposición del sujeto a experimentar enojo'
            ],
            'temperamento_irritable' => [
                'items' => ['q11', 'q12', 'q13', 'q16'],
                'name' => 'Temperamento irritable (T-Anger/T)',
                'description' => 'Mide la predisposición general a experimentar y expresar enojo sin una provocación específica'
            ],
            'reaccion_enojo' => [
                'items' => ['q14', 'q15', 'q18', 'q20'],
                'name' => 'Reacción de enojo (T-Anger/R)',
                'description' => 'Mide la predisposición a expresar enojo cuando se es criticado o tratado injustamente'
            ],
            'enojo_hacia_afuera' => [
                'items' => ['q22', 'q27', 'q29', 'q32', 'q34', 'q39', 'q42', 'q43'],
                'name' => 'Enojo hacia afuera (AX/OUT)',
                'description' => 'Mide la frecuencia con la que un individuo expresa enojo hacia otras personas u objetos'
            ],
            'enojo_hacia_adentro' => [
                'items' => ['q23', 'q26', 'q30', 'q33', 'q36', 'q37', 'q41'],
                'name' => 'Enojo hacia adentro (AX/IN)',
                'description' => 'Mide la frecuencia con la que un individuo contiene o suprime los sentimientos de enojo'
            ],
            'control_enojo' => [
                'items' => ['q21', 'q24', 'q28', 'q31', 'q35', 'q38', 'q40', 'q44'],
                'name' => 'Control del enojo (AX/CON)',
                'description' => 'Mide la frecuencia con la que un individuo intenta controlar la expresión de su enojo'
            ]
        ];
        
        // Mapeo de valores de respuesta a puntajes numéricos
        $scoreMappings = [
            'current_state' => [
                'NO' => 1,
                'ALGO' => 2,
                'BASTANTE' => 3,
                'MUCHO' => 4
            ],
            'general_state' => [
                'CASI_NUNCA' => 1,
                'MUY_RARAMENTE' => 1,
                'A_VECES' => 2,
                'MUY_SEGUIDO' => 3,
                'SIEMPRE' => 4,
                'CASI_SIEMPRE' => 4
            ],
            'angry_reactions' => [
                'CASI_NUNCA' => 1,
                'MUY_RARAMENTE' => 1,
                'A_VECES' => 2,
                'MUY_SEGUIDO' => 3,
                'SIEMPRE' => 4,
                'CASI_SIEMPRE' => 4
            ]
        ];
        
        // Inicializar scores
        $scores = [];
        
        // Calcular puntajes para cada escala
        foreach ($scales as $scaleKey => $scale) {
            $sum = 0;
            $validItems = 0;
            
            foreach ($scale['items'] as $item) {
                // Determinar la sección correcta basada en el ítem
                $section = '';
                if (in_array($item, $scales['estado_enojo']['items'])) {
                    $section = 'current_state';
                } elseif (in_array($item, $scales['rasgo_enojo']['items'])) {
                    $section = 'general_state';
                } else {
                    $section = 'angry_reactions';
                }
                
                // Para ítems de control del enojo, invertir la puntuación si es necesario (menos para q21 que ya está en sentido positivo)
                $invertScore = false;
                if ($scaleKey === 'control_enojo' && $item !== 'q21') {
                    $invertScore = true;
                }
                
                if (isset($responses[$section][$item]['answer'])) {
                    $answer = $responses[$section][$item]['answer'];
                    $itemScore = $scoreMappings[$section][$answer] ?? 0;
                    
                    // Invertir puntuación si es necesario
                    if ($invertScore) {
                        $itemScore = 5 - $itemScore; // Invierte la escala 1-4 a 4-1
                    }
                    
                    $sum += $itemScore;
                    $validItems++;
                }
            }
            
            $scores[$scaleKey] = [
                'raw_score' => $sum,
                'item_count' => $validItems,
                'average' => $validItems > 0 ? round($sum / $validItems, 2) : 0,
                'name' => $scale['name'],
                'description' => $scale['description']
            ];
        }
        
        // Calcular la expresión de enojo (AX/EX)
        $axOut = $scores['enojo_hacia_afuera']['raw_score'] ?? 0;
        $axIn = $scores['enojo_hacia_adentro']['raw_score'] ?? 0;
        $axCon = $scores['control_enojo']['raw_score'] ?? 0;
        
        $axEx = $axIn + $axOut - $axCon + 16;
        
        $scores['expresion_enojo'] = [
            'raw_score' => $axEx,
            'name' => 'Expresión del enojo (AX/EX)',
            'description' => 'Índice general de la frecuencia con la cual el enojo es expresado, independientemente de la dirección',
            'formula' => 'AX/EX = AX/IN + AX/OUT - AX/CON + 16'
        ];
        
        // Generate interpretations
        $interpretations = $this->getStaxiInterpretations($scores);
        
        // Generate clinical interpretations
        $clinicalInterpretations = $this->generateStaxiClinicalInterpretations($scores);
        
        // Generate summary
        $summary = $this->generateStaxiSummary($scores, $interpretations);
        
        // Prepare final result
        $result['scores'] = $scores;
        $result['interpretations'] = $interpretations;
        $result['clinical_interpretations'] = $clinicalInterpretations;
        $result['summary'] = $summary;
        $result['scoring_type'] = 'STAXI';
        $result['questionnaire_name'] = 'Inventario de Expresión de Ira Estado-Rasgo (STAXI)';
        
        return $result;
    }
    
    /**
     * Generate interpretations for STAXI scores
     */
    private function getStaxiInterpretations($scores): array
    {
        $interpretations = [];
        
        foreach ($scores as $scaleKey => $score) {
            $rawScore = $score['raw_score'];
            $interpretation = '';
            
            // Estado de enojo (1-40)
            if ($scaleKey === 'estado_enojo') {
                if ($rawScore <= 10) {
                    $interpretation = 'Sin enojo significativo en el momento actual';
                } elseif ($rawScore <= 20) {
                    $interpretation = 'Nivel leve de enojo en el momento actual';
                } elseif ($rawScore <= 30) {
                    $interpretation = 'Nivel moderado de enojo en el momento actual';
                } else {
                    $interpretation = 'Nivel alto de enojo en el momento actual';
                }
            }
            // Rasgo de enojo (10-40)
            elseif ($scaleKey === 'rasgo_enojo') {
                if ($rawScore <= 15) {
                    $interpretation = 'Tendencia baja a experimentar enojo';
                } elseif ($rawScore <= 25) {
                    $interpretation = 'Tendencia moderada a experimentar enojo';
                } else {
                    $interpretation = 'Tendencia alta a experimentar enojo';
                }
            }
            // Temperamento irritable (4-16)
            elseif ($scaleKey === 'temperamento_irritable') {
                if ($rawScore <= 6) {
                    $interpretation = 'Temperamento poco irritable';
                } elseif ($rawScore <= 10) {
                    $interpretation = 'Temperamento moderadamente irritable';
                } else {
                    $interpretation = 'Temperamento muy irritable';
                }
            }
            // Reacción de enojo (4-16)
            elseif ($scaleKey === 'reaccion_enojo') {
                if ($rawScore <= 6) {
                    $interpretation = 'Baja reactividad a las críticas o al trato injusto';
                } elseif ($rawScore <= 10) {
                    $interpretation = 'Reactividad moderada a las críticas o al trato injusto';
                } else {
                    $interpretation = 'Alta reactividad a las críticas o al trato injusto';
                }
            }
            // Enojo hacia afuera (8-32)
            elseif ($scaleKey === 'enojo_hacia_afuera') {
                if ($rawScore <= 13) {
                    $interpretation = 'Raramente expresa enojo hacia otras personas u objetos';
                } elseif ($rawScore <= 20) {
                    $interpretation = 'Ocasionalmente expresa enojo hacia otras personas u objetos';
                } else {
                    $interpretation = 'Frecuentemente expresa enojo hacia otras personas u objetos';
                }
            }
            // Enojo hacia adentro (8-32)
            elseif ($scaleKey === 'enojo_hacia_adentro') {
                if ($rawScore <= 13) {
                    $interpretation = 'Raramente suprime sentimientos de enojo';
                } elseif ($rawScore <= 20) {
                    $interpretation = 'Ocasionalmente suprime sentimientos de enojo';
                } else {
                    $interpretation = 'Frecuentemente suprime sentimientos de enojo';
                }
            }
            // Control del enojo (8-32)
            elseif ($scaleKey === 'control_enojo') {
                if ($rawScore <= 13) {
                    $interpretation = 'Bajo control del enojo';
                } elseif ($rawScore <= 20) {
                    $interpretation = 'Control moderado del enojo';
                } else {
                    $interpretation = 'Alto control del enojo';
                }
            }
            // Expresión del enojo (0-72)
            elseif ($scaleKey === 'expresion_enojo') {
                if ($rawScore <= 24) {
                    $interpretation = 'Baja expresión general del enojo';
                } elseif ($rawScore <= 48) {
                    $interpretation = 'Expresión moderada del enojo';
                } else {
                    $interpretation = 'Alta expresión general del enojo';
                }
            }
            
            $interpretations[$scaleKey] = $interpretation;
        }
        
        return $interpretations;
    }
    
    /**
     * Generate clinical interpretations for STAXI scores
     */
    private function generateStaxiClinicalInterpretations($scores): array
    {
        $clinicalInterpretations = [];
        
        // 1. Si Estado > Rasgo: Posible reacción situacional
        if (($scores['estado_enojo']['raw_score'] / 10) > ($scores['rasgo_enojo']['raw_score'] / 10)) {
            $clinicalInterpretations[] = 'El nivel de enojo actual es superior al habitual, lo que sugiere una reacción a una situación específica reciente.';
        }
        
        // 2. Si Temperamento Alto y Control Alto: Posible represión
        if ($scores['temperamento_irritable']['raw_score'] > 10 && $scores['control_enojo']['raw_score'] > 20) {
            $clinicalInterpretations[] = 'Presenta un temperamento irritable con alto control del enojo, lo que puede indicar esfuerzo por reprimir sentimientos de enojo.';
        }
        
        // 3. Si Enojo Hacia Afuera Alto y Control Bajo: Riesgo de expresión inapropiada
        if ($scores['enojo_hacia_afuera']['raw_score'] > 20 && $scores['control_enojo']['raw_score'] < 13) {
            $clinicalInterpretations[] = 'Alta tendencia a expresar enojo hacia afuera con bajo control, lo que podría manifestarse en conductas agresivas o inapropiadas.';
        }
        
        // 4. Si Enojo Hacia Adentro Alto: Riesgo para salud mental/física
        if ($scores['enojo_hacia_adentro']['raw_score'] > 20) {
            $clinicalInterpretations[] = 'Tendencia a suprimir o internalizar el enojo, lo que podría contribuir a problemas de salud física o mental como ansiedad, depresión o psicosomatización.';
        }
        
        return $clinicalInterpretations;
    }
    
    /**
     * Genera un resumen interpretativo para el STAXI
     */
    private function generateStaxiSummary($scores, $interpretations): string
    {
        $estadoEnojo = $scores['estado_enojo']['raw_score'];
        $rasgoEnojo = $scores['rasgo_enojo']['raw_score'];
        $expresionEnojo = $scores['expresion_enojo']['raw_score'];
        
        // Niveles de riesgo basados en la puntuación de expresión de enojo
        if ($expresionEnojo > 48) {
            if ($estadoEnojo > 30 || $rasgoEnojo > 25) {
                return "Presenta un patrón de manejo del enojo de alto riesgo, caracterizado por alta intensidad y frecuencia de sentimientos de enojo, con tendencia a expresarlos de manera inadecuada. Este patrón se asocia con dificultades en las relaciones interpersonales y posible riesgo para la salud física y mental.";
            } else {
                return "Muestra una expresión inadecuada del enojo aunque no necesariamente experimenta enojo con alta intensidad o frecuencia. La manera de manejar el enojo podría generar problemas interpersonales.";
            }
        } elseif ($expresionEnojo > 24) {
            if ($scores['enojo_hacia_adentro']['raw_score'] > 20) {
                return "Presenta un patrón de internalización del enojo de nivel moderado. Tiende a suprimir sus sentimientos de enojo más que a expresarlos abiertamente, lo que podría contribuir a tensión interna.";
            } elseif ($scores['enojo_hacia_afuera']['raw_score'] > 20) {
                return "Muestra un patrón de externalización del enojo de nivel moderado. Tiende a expresar sus sentimientos de enojo hacia otras personas u objetos, lo que podría generar conflictos interpersonales ocasionales.";
            } else {
                return "Presenta un equilibrio moderado en la expresión de enojo, alternando entre expresión externa, internalización y control.";
            }
        } else {
            if ($scores['control_enojo']['raw_score'] > 20) {
                return "Muestra un patrón adaptativo en el manejo del enojo, caracterizado por un adecuado control de la expresión de sentimientos de enojo. Es probable que utilice estrategias efectivas para manejar situaciones frustrantes.";
            } else {
                return "Presenta bajos niveles de experiencia y expresión de enojo. Podría reflejar un estilo de afrontamiento calmado o posible supresión excesiva de sentimientos negativos.";
            }
        }
    }
    
    /**
     * Build the questionnaire-specific prompt section for AI interpretation
     *
     * @param array $results Results from the calculateScores method
     * @return string Formatted prompt section
     */
    public function buildPromptSection(array $questionnaireResults): string
    {
        $promptSection = "";
        // Añadir puntuaciones de escalas del STAXI
        if (isset($questionnaireResults['scores']) && !empty($questionnaireResults['scores'])) {
            $promptSection .= "### Escalas de experiencia y expresión del enojo:\n";
            
            // Organizar las escalas por categorías
            $categories = [
                'Experiencia del enojo' => ['estado_enojo', 'rasgo_enojo', 'temperamento_irritable', 'reaccion_enojo'],
                'Expresión del enojo' => ['enojo_hacia_afuera', 'enojo_hacia_adentro', 'control_enojo', 'expresion_enojo']
            ];
            
            foreach ($categories as $category => $scales) {
                $promptSection .= "#### $category:\n";
                
                foreach ($scales as $scale) {
                    if (isset($questionnaireResults['scores'][$scale])) {
                        $score = $questionnaireResults['scores'][$scale];
                        $promptSection .= "- **" . ($score['name'] ?? ucfirst(str_replace('_', ' ', $scale))) . "**: ";
                        $promptSection .= "Puntuación " . $score['raw_score'];
                        
                        if (isset($questionnaireResults['interpretations'][$scale])) {
                            $promptSection .= " - " . $questionnaireResults['interpretations'][$scale];
                        }
                        
                        $promptSection .= "\n";
                    }
                }
                $promptSection .= "\n";
            }
        }
        
        // Añadir interpretaciones clínicas específicas
        if (isset($questionnaireResults['clinical_interpretations']) && !empty($questionnaireResults['clinical_interpretations'])) {
            $promptSection .= "### Interpretaciones clínicas del STAXI:\n";
            foreach ($questionnaireResults['clinical_interpretations'] as $interpretation) {
                $promptSection .= "- " . $interpretation . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Añadir resumen global
        if (isset($questionnaireResults['summary']) && !empty($questionnaireResults['summary'])) {
            $promptSection .= "### Resumen global del STAXI:\n";
            $promptSection .= $questionnaireResults['summary'] . "\n\n";
        }
        
        return $promptSection;
    }
    
    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     *
     * @return string Formatted instructions
     */
    public function getInstructions(): string
    {
        $defaultInstructions = "Basándote en los resultados del STAXI, proporciona:\n\n" .
               "1. Una interpretación clínica sobre la experiencia, expresión y control del enojo.\n" .
               "2. Análisis de la relación entre el estado y rasgo del enojo.\n" .
               "3. Evaluación del estilo de expresión del enojo (internalización vs. externalización).\n" .
               "4. Implicaciones para el funcionamiento psicológico y las relaciones interpersonales.\n" .
               "5. Recomendaciones para la regulación del enojo y estrategias de intervención para mejorar el manejo emocional.\n";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 