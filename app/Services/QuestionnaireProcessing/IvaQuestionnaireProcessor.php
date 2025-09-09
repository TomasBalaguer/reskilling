<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for IVA (Inventario de Valoración y Afrontamiento) questionnaire
 */
class IvaQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'IVA';
    
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
        // Verificar que tengamos respuestas válidas
        if (!isset($responses['pensamientos_situacion']) || empty($responses['pensamientos_situacion'])) {
            $result['summary'] = 'No se proporcionaron respuestas suficientes para el cuestionario IVA.';
            return $result;
        }

        // Inicializar arrays para los puntajes
        $scores = [];
        $interpretations = [];

        // Definir las escalas del IVA según la documentación
        $scales = [];
        $scales['vsa'] = [
            'name' => 'Valoración de la situación como amenazante (VSA)',
            'items' => ['q1', 'q2', 'q3', 'q4', 'q5', 'q6'],
            'max_score' => 24,
            'description' => 'Evalúa la percepción de la situación como amenazante o peligrosa'
        ];
        
        $scales['vsd'] = [
            'name' => 'Valoración de la situación como desafío (VSD)',
            'items' => ['q7', 'q8', 'q9', 'q10', 'q11', 'q12'],
            'max_score' => 24,
            'description' => 'Evalúa la percepción de la situación como un reto o desafío'
        ];
        
        $scales['vsi'] = [
            'name' => 'Valoración de la situación como irrelevante (VSI)',
            'items' => ['q13', 'q14', 'q15'],
            'max_score' => 12,
            'description' => 'Evalúa la percepción de la situación como poco importante'
        ];
        
        $scales['acs'] = [
            'name' => 'Afrontamiento cognitivo dirigido a cambiar la situación (ACS)',
            'items' => ['q16', 'q17', 'q18', 'q19', 'q20', 'q21'],
            'max_score' => 24,
            'description' => 'Estrategias de pensamiento dirigidas a modificar la situación'
        ];
        
        $scales['ace'] = [
            'name' => 'Afrontamiento cognitivo dirigido a reducir la emoción (ACE)',
            'items' => ['q22', 'q23', 'q24', 'q25', 'q26', 'q27'],
            'max_score' => 24,
            'description' => 'Estrategias de pensamiento dirigidas a reducir el malestar emocional'
        ];
        
        $scales['acms'] = [
            'name' => 'Afrontamiento conductual motor dirigido a cambiar la situación (ACMS)',
            'items' => ['q28', 'q29', 'q30', 'q31', 'q32', 'q33'],
            'max_score' => 24,
            'description' => 'Acciones conductuales dirigidas a modificar la situación'
        ];
        
        $scales['acme'] = [
            'name' => 'Afrontamiento conductual motor dirigido a reducir la emoción (ACME)',
            'items' => ['q34', 'q35', 'q36', 'q37', 'q38', 'q39'],
            'max_score' => 24,
            'description' => 'Acciones conductuales dirigidas a reducir el malestar emocional'
        ];
        
        $scales['ap'] = [
            'name' => 'Afrontamiento pasivo (AP)',
            'items' => ['q40', 'q41'],
            'max_score' => 8,
            'description' => 'Estrategias de evitación pasiva o no-acción'
        ];
        
        $scales['ae'] = [
            'name' => 'Afrontamiento evitativo (AE)',
            'items' => ['q42'],
            'max_score' => 4,
            'description' => 'Estrategias de evitación activa o escape'
        ];

        // Calcular puntajes para cada escala
        foreach ($scales as $scaleId => $scale) {
            $total = 0;
            $count = 0;
            
            foreach ($scale['items'] as $item) {
                if (isset($responses['pensamientos_situacion'][$item]['answer'])) {
                    // Ajustar el puntaje (la escala original es 0-4, pero las respuestas son 1-5)
                    $answer = $responses['pensamientos_situacion'][$item]['answer'] - 1;
                    $total += $answer;
                    $count++;
                }
            }
            
            // Solo calcular si tenemos todas las respuestas
            if ($count == count($scale['items'])) {
                $scores[$scaleId] = [
                    'name' => $scale['name'],
                    'raw_score' => $total,
                    'max_score' => $scale['max_score'],
                    'percentage' => round(($total / $scale['max_score']) * 100, 1),
                    'description' => $scale['description']
                ];

                // Generar interpretación basada en el porcentaje
                $interpretations[$scaleId] = $this->getIvaScaleInterpretation($scaleId, $scores[$scaleId]['percentage']);
            }
        }
        // Extraer información del problema descrito
        $problemDescription = $responses['situacion']['problema']['answer'] ?? 'No especificado';

        // Crear grupos para una mejor organización de las escalas
        $result['scale_groups'] = [
            'valoracion_primaria_secundaria' => [
                'name' => 'Valoración Primaria y Secundaria',
                'scales' => ['vsa', 'vsd', 'vsi'],
                'description' => 'Evaluación cognitiva de la situación'
            ],
            'afrontamiento_cognitivo' => [
                'name' => 'Afrontamiento Cognitivo',
                'scales' => ['acs', 'ace'],
                'description' => 'Estrategias cognitivas de afrontamiento'
            ],
            'afrontamiento_conductual' => [
                'name' => 'Afrontamiento Conductual',
                'scales' => ['acms', 'acme', 'ap', 'ae'],
                'description' => 'Estrategias conductuales de afrontamiento'
            ]
        ];

        // Guardar los resultados
        $result['scores'] = $scores;
        $result['interpretations'] = $interpretations;
        $result['situacion_problema'] = $problemDescription;
        
        // Generar interpretación clínica
        $result['clinical_interpretations'] = $this->generateIvaClinicalInterpretation($scores, $problemDescription);
        
        // Generar resumen
        $result['summary'] = $this->generateIvaSummary($scores, $interpretations);

        // Asignar el tipo de evaluación
        $result['scoring_type'] = 'IVA';
        $result['questionnaire_name'] = 'Inventario de Valoración y Afrontamiento';

        return $result;
    }
    
    /**
     * Genera una interpretación para cada escala del IVA
     */
    private function getIvaScaleInterpretation($scale, $percentage): string
    {
        // Umbrales: Bajo (0-33%), Medio (34-66%), Alto (67-100%)
        if ($percentage < 33) {
            $level = 'bajo';
        } elseif ($percentage < 67) {
            $level = 'medio';
        } else {
            $level = 'alto';
        }

        // Interpretaciones específicas por escala y nivel
        $interpretations = [
            'vsa' => [
                'bajo' => 'Percibe la situación como poco amenazante, no la evalúa como peligrosa o con consecuencias negativas significativas.',
                'medio' => 'Percibe algunos aspectos amenazantes en la situación, aunque no de forma intensa o generalizada.',
                'alto' => 'Percibe la situación como altamente amenazante, con posibles consecuencias negativas importantes para su bienestar.'
            ],
            'vsd' => [
                'bajo' => 'No interpreta la situación como un reto o desafío personal que pueda suponer crecimiento.',
                'medio' => 'Considera algunos aspectos de la situación como oportunidades de crecimiento o aprendizaje.',
                'alto' => 'Interpreta claramente la situación como un desafío positivo que puede aportar crecimiento personal.'
            ],
            'vsi' => [
                'bajo' => 'Considera que la situación es relevante y tiene importancia en su vida.',
                'medio' => 'Atribuye una importancia moderada a la situación, ni la minimiza ni la sobrevalora.',
                'alto' => 'Tiende a minimizar la importancia de la situación, considerándola poco relevante en su vida.'
            ],
            'acs' => [
                'bajo' => 'Utiliza poco las estrategias cognitivas orientadas a modificar la situación o el problema.',
                'medio' => 'Emplea moderadamente estrategias de pensamiento para analizar y cambiar aspectos de la situación.',
                'alto' => 'Utiliza intensamente estrategias cognitivas dirigidas a analizar y cambiar la situación problemática.'
            ],
            'ace' => [
                'bajo' => 'Aplica pocas estrategias cognitivas orientadas a regular sus emociones ante la situación.',
                'medio' => 'Utiliza moderadamente estrategias mentales para gestionar las emociones generadas por la situación.',
                'alto' => 'Emplea intensamente estrategias de pensamiento para reducir el impacto emocional de la situación.'
            ],
            'acms' => [
                'bajo' => 'Realiza pocas acciones concretas dirigidas a modificar o resolver la situación problemática.',
                'medio' => 'Lleva a cabo algunas acciones orientadas a cambiar aspectos de la situación problemática.',
                'alto' => 'Implementa numerosas acciones dirigidas específicamente a modificar o resolver la situación.'
            ],
            'acme' => [
                'bajo' => 'Realiza pocas conductas orientadas a reducir su malestar emocional ante la situación.',
                'medio' => 'Lleva a cabo algunas acciones para gestionar y reducir el malestar emocional generado.',
                'alto' => 'Implementa numerosas conductas específicas para reducir su tensión y malestar emocional.'
            ],
            'ap' => [
                'bajo' => 'No suele adoptar una actitud pasiva o de inacción ante la situación problemática.',
                'medio' => 'Ocasionalmente adopta una actitud pasiva o de no-acción ante ciertos aspectos de la situación.',
                'alto' => 'Tiende a adoptar una actitud predominantemente pasiva ante la situación, evitando actuar.'
            ],
            'ae' => [
                'bajo' => 'No suele utilizar estrategias de evitación o escape ante la situación problemática.',
                'medio' => 'Ocasionalmente utiliza estrategias de evitación ante ciertos aspectos de la situación.',
                'alto' => 'Tiende a evitar activamente o escapar de la situación problemática y lo relacionado con ella.'
            ]
        ];

        return $interpretations[$scale][$level] ?? 'Interpretación no disponible.';
    }

    /**
     * Genera una interpretación clínica global para los resultados del IVA
     */
    private function generateIvaClinicalInterpretation($scores, $problemDescription): array
    {
        $interpretations = [];
        
        // 1. Valoración primaria (VSA, VSD, VSI)
        $vsaScore = $scores['vsa']['percentage'] ?? 0;
        $vsdScore = $scores['vsd']['percentage'] ?? 0;
        $vsiScore = $scores['vsi']['percentage'] ?? 0;

        // Determinar el patrón predominante de valoración
        if ($vsaScore > $vsdScore && $vsaScore > $vsiScore) {
            $interpretations[] = "El patrón de valoración predominante es de amenaza, lo que sugiere una tendencia a percibir las situaciones estresantes como peligrosas o potencialmente dañinas. Este tipo de valoración suele asociarse con emociones de ansiedad, miedo o preocupación.";
        } elseif ($vsdScore > $vsaScore && $vsdScore > $vsiScore) {
            $interpretations[] = "El patrón de valoración predominante es de desafío, lo que indica una tendencia a interpretar las situaciones estresantes como oportunidades para el crecimiento personal o el aprendizaje. Este tipo de valoración suele asociarse con emociones positivas como entusiasmo o determinación, a pesar del estrés.";
        } elseif ($vsiScore > $vsaScore && $vsiScore > $vsdScore) {
            $interpretations[] = "El patrón de valoración predominante es de irrelevancia, lo que sugiere una tendencia a minimizar la importancia de las situaciones estresantes. Si bien esto puede proteger temporalmente del malestar, podría dificultar la implementación de estrategias de afrontamiento efectivas.";
        } else {
            $interpretations[] = "No se observa un patrón de valoración claramente predominante, lo que puede indicar una evaluación mixta o ambivalente de la situación estresante.";
        }

        // 2. Estrategias de afrontamiento cognitivo vs. conductual
        $cognitiveAvg = (($scores['acs']['percentage'] ?? 0) + ($scores['ace']['percentage'] ?? 0)) / 2;
        $behavioralAvg = (($scores['acms']['percentage'] ?? 0) + ($scores['acme']['percentage'] ?? 0)) / 2;

        if ($cognitiveAvg > $behavioralAvg + 15) {
            $interpretations[] = "Predomina el uso de estrategias de afrontamiento cognitivas sobre las conductuales, lo que sugiere una tendencia a procesar y manejar el estrés principalmente a través del pensamiento y la reevaluación, antes que mediante acciones concretas.";
        } elseif ($behavioralAvg > $cognitiveAvg + 15) {
            $interpretations[] = "Predomina el uso de estrategias de afrontamiento conductuales sobre las cognitivas, lo que indica una preferencia por las acciones concretas para manejar el estrés, por encima del procesamiento mental o la reevaluación.";
        } else {
            $interpretations[] = "Se observa un equilibrio entre las estrategias de afrontamiento cognitivas y conductuales, lo que sugiere un repertorio flexible de recursos para manejar situaciones estresantes.";
        }

        // 3. Orientación del afrontamiento: hacia el problema vs. hacia la emoción
        $problemFocusedAvg = (($scores['acs']['percentage'] ?? 0) + ($scores['acms']['percentage'] ?? 0)) / 2;
        $emotionFocusedAvg = (($scores['ace']['percentage'] ?? 0) + ($scores['acme']['percentage'] ?? 0)) / 2;

        if ($problemFocusedAvg > $emotionFocusedAvg + 15) {
            $interpretations[] = "El afrontamiento está principalmente orientado a cambiar la situación problemática, más que a regular las emociones asociadas. Este enfoque suele ser adaptativo cuando la situación es modificable.";
        } elseif ($emotionFocusedAvg > $problemFocusedAvg + 15) {
            $interpretations[] = "El afrontamiento está principalmente orientado a regular las emociones generadas por la situación, más que a modificar el problema en sí. Este enfoque puede ser adaptativo cuando la situación no es fácilmente modificable.";
        } else {
            $interpretations[] = "Se observa un equilibrio entre estrategias orientadas al problema y a la emoción, lo que sugiere flexibilidad para ajustar el afrontamiento según las demandas específicas de la situación.";
        }

        // 4. Estrategias de evitación
        $avoidanceScore = (($scores['ap']['percentage'] ?? 0) + ($scores['ae']['percentage'] ?? 0)) / 2;

        if ($avoidanceScore > 60) {
            $interpretations[] = "Presenta un uso significativo de estrategias de evitación (pasivas y activas), lo que podría ser problemático a largo plazo si impide abordar efectivamente las situaciones estresantes.";
        } elseif ($avoidanceScore > 30) {
            $interpretations[] = "Utiliza moderadamente estrategias de evitación, lo que puede ser adaptativo para situaciones específicas, aunque su uso persistente podría limitar el desarrollo de recursos de afrontamiento más efectivos.";
        } else {
            $interpretations[] = "Muestra poco uso de estrategias de evitación, prefiriendo afrontar activamente las situaciones estresantes, lo que generalmente se asocia con mejores resultados a largo plazo.";
        }

        // 5. Interpretación específica basada en la descripción del problema
        if (!empty($problemDescription) && $problemDescription != 'No especificado') {
            $interpretations[] = "En relación al problema descrito ('$problemDescription'), el patrón de afrontamiento observado sugiere una tendencia a " . 
                ($problemFocusedAvg > $emotionFocusedAvg ? 
                    "abordar directamente las fuentes de estrés mediante acciones concretas o reevaluaciones cognitivas." : 
                    "gestionar principalmente el impacto emocional de la situación, regulando las respuestas internas ante el estrés.");
        }

        return $interpretations;
    }

    /**
     * Genera un resumen para los resultados del IVA
     */
    private function generateIvaSummary($scores, $interpretations): string
    {
        // Determinar patrones predominantes
        $valueType = '';
        $vsaScore = $scores['vsa']['percentage'] ?? 0;
        $vsdScore = $scores['vsd']['percentage'] ?? 0;
        $vsiScore = $scores['vsi']['percentage'] ?? 0;

        if ($vsaScore > $vsdScore && $vsaScore > $vsiScore) {
            $valueType = "amenaza";
        } elseif ($vsdScore > $vsaScore && $vsdScore > $vsiScore) {
            $valueType = "desafío";
        } elseif ($vsiScore > $vsaScore && $vsiScore > $vsdScore) {
            $valueType = "irrelevancia";
        } else {
            $valueType = "mixto";
        }

        // Determinar estrategias predominantes
        $problemFocusedAvg = (($scores['acs']['percentage'] ?? 0) + ($scores['acms']['percentage'] ?? 0)) / 2;
        $emotionFocusedAvg = (($scores['ace']['percentage'] ?? 0) + ($scores['acme']['percentage'] ?? 0)) / 2;
        $cognitiveAvg = (($scores['acs']['percentage'] ?? 0) + ($scores['ace']['percentage'] ?? 0)) / 2;
        $behavioralAvg = (($scores['acms']['percentage'] ?? 0) + ($scores['acme']['percentage'] ?? 0)) / 2;
        $avoidanceScore = (($scores['ap']['percentage'] ?? 0) + ($scores['ae']['percentage'] ?? 0)) / 2;

        $focusType = $problemFocusedAvg > $emotionFocusedAvg ? "orientado al problema" : "orientado a la emoción";
        $strategyType = $cognitiveAvg > $behavioralAvg ? "predominantemente cognitivo" : "predominantemente conductual";
        $avoidanceType = $avoidanceScore > 50 ? "con tendencia evitativa significativa" : "con baja evitación";

        // Construir resumen
        $summary = "El análisis del IVA revela un patrón de valoración de tipo $valueType, con un estilo de afrontamiento $focusType y $strategyType, $avoidanceType. ";
        
        // Agregar comentario sobre adaptabilidad
        if ($valueType == "desafío" && $avoidanceScore < 40 && ($problemFocusedAvg > 50 || $emotionFocusedAvg > 50)) {
            $summary .= "Este perfil sugiere un afrontamiento generalmente adaptativo, con recursos para manejar el estrés de forma constructiva.";
        } elseif ($valueType == "amenaza" && $avoidanceScore > 60) {
            $summary .= "Este perfil sugiere potenciales dificultades en el manejo del estrés, con un enfoque principalmente defensivo.";
        } else {
            $summary .= "Este perfil muestra un patrón mixto de recursos de afrontamiento, con aspectos tanto adaptativos como potencialmente problemáticos según el contexto.";
        }

        return $summary;
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
        
        // Añadir la descripción del problema
        if (isset($questionnaireResults['situacion_problema']) && !empty($questionnaireResults['situacion_problema'])) {
            $promptSection .= "### Descripción del problema:\n";
            $promptSection .= $questionnaireResults['situacion_problema'] . "\n\n";
        }
        
        // Organizar resultados por grupos de escalas
        if (isset($questionnaireResults['scale_groups']) && !empty($questionnaireResults['scale_groups'])) {
            foreach ($questionnaireResults['scale_groups'] as $groupId => $group) {
                $promptSection .= "### " . $group['name'] . ":\n";
                if (!empty($group['description'])) {
                    $promptSection .= $group['description'] . "\n\n";
                }
                
                foreach ($group['scales'] as $scaleId) {
                    if (isset($questionnaireResults['scores'][$scaleId])) {
                        $score = $questionnaireResults['scores'][$scaleId];
                        $promptSection .= "- **" . ($score['name'] ?? ucfirst(str_replace('_', ' ', $scaleId))) . "**: ";
                        $promptSection .= "Puntuación " . $score['raw_score'] . " (";
                        $promptSection .= $score['percentage'] . "% del máximo)";
                        
                        if (isset($questionnaireResults['interpretations'][$scaleId])) {
                            $promptSection .= "\n  - " . $questionnaireResults['interpretations'][$scaleId];
                        }
                        
                        $promptSection .= "\n";
                    }
                }
                $promptSection .= "\n";
            }
        } 
        // Si no hay grupos, mostrar escalas directamente
        elseif (isset($questionnaireResults['scores']) && !empty($questionnaireResults['scores'])) {
            $promptSection .= "### Escalas de Valoración y Afrontamiento:\n";
            
            // Escalas de valoración
            $promptSection .= "#### Valoración cognitiva:\n";
            $valuationScales = ['vsa', 'vsd', 'vsi'];
            foreach ($valuationScales as $scaleId) {
                if (isset($questionnaireResults['scores'][$scaleId])) {
                    $score = $questionnaireResults['scores'][$scaleId];
                    $promptSection .= "- **" . ($score['name'] ?? ucfirst(str_replace('_', ' ', $scaleId))) . "**: ";
                    $promptSection .= "Puntuación " . $score['raw_score'] . " (";
                    $promptSection .= $score['percentage'] . "% del máximo)";
                    
                    if (isset($questionnaireResults['interpretations'][$scaleId])) {
                        $promptSection .= "\n  - " . $questionnaireResults['interpretations'][$scaleId];
                    }
                    
                    $promptSection .= "\n";
                }
            }
            $promptSection .= "\n";
            
            // Escalas de afrontamiento cognitivo
            $promptSection .= "#### Afrontamiento cognitivo:\n";
            $cognitiveScales = ['acs', 'ace'];
            foreach ($cognitiveScales as $scaleId) {
                if (isset($questionnaireResults['scores'][$scaleId])) {
                    $score = $questionnaireResults['scores'][$scaleId];
                    $promptSection .= "- **" . ($score['name'] ?? ucfirst(str_replace('_', ' ', $scaleId))) . "**: ";
                    $promptSection .= "Puntuación " . $score['raw_score'] . " (";
                    $promptSection .= $score['percentage'] . "% del máximo)";
                    
                    if (isset($questionnaireResults['interpretations'][$scaleId])) {
                        $promptSection .= "\n  - " . $questionnaireResults['interpretations'][$scaleId];
                    }
                    
                    $promptSection .= "\n";
                }
            }
            $promptSection .= "\n";
            
            // Escalas de afrontamiento conductual
            $promptSection .= "#### Afrontamiento conductual:\n";
            $behavioralScales = ['acms', 'acme', 'ap', 'ae'];
            foreach ($behavioralScales as $scaleId) {
                if (isset($questionnaireResults['scores'][$scaleId])) {
                    $score = $questionnaireResults['scores'][$scaleId];
                    $promptSection .= "- **" . ($score['name'] ?? ucfirst(str_replace('_', ' ', $scaleId))) . "**: ";
                    $promptSection .= "Puntuación " . $score['raw_score'] . " (";
                    $promptSection .= $score['percentage'] . "% del máximo)";
                    
                    if (isset($questionnaireResults['interpretations'][$scaleId])) {
                        $promptSection .= "\n  - " . $questionnaireResults['interpretations'][$scaleId];
                    }
                    
                    $promptSection .= "\n";
                }
            }
            $promptSection .= "\n";
        }
        
        // Añadir interpretaciones clínicas específicas
        if (isset($questionnaireResults['clinical_interpretations']) && !empty($questionnaireResults['clinical_interpretations'])) {
            $promptSection .= "### Interpretaciones clínicas específicas:\n";
            foreach ($questionnaireResults['clinical_interpretations'] as $interpretation) {
                $promptSection .= "- " . $interpretation . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Añadir resumen global
        if (isset($questionnaireResults['summary']) && !empty($questionnaireResults['summary'])) {
            $promptSection .= "### Resumen global del IVA:\n";
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
        $defaultInstructions = "Basándote en los resultados del Inventario de Valoración y Afrontamiento (IVA), proporciona:\n\n" .
               "1. Una interpretación clínica sobre el estilo de valoración cognitiva de situaciones estresantes.\n" .
               "2. Análisis de las estrategias de afrontamiento preferidas, distinguiendo entre:\n" .
               "   - Estrategias cognitivas vs. conductuales.\n" .
               "   - Estrategias orientadas al problema vs. orientadas a la emoción.\n" .
               "3. Evaluación de la adaptabilidad del estilo de afrontamiento en relación al problema descrito.\n" .
               "4. Identificación de posibles áreas de mejora en el repertorio de afrontamiento.\n" .
               "5. Recomendaciones para desarrollar estrategias más efectivas según el contexto específico.\n" .
               "6. Análisis del impacto del estilo de afrontamiento en el bienestar psicológico y la salud general.\n";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 