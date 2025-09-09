<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for CRI (Coping Responses Inventory) questionnaire
 */
class CriQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'CRI';
    
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
        
        // Verificar que tengamos las respuestas necesarias
        if (!isset($responses['estrategias_afrontamiento']) || empty($responses['estrategias_afrontamiento'])) {
            $result['summary'] = 'No se proporcionaron respuestas suficientes para el CRI.';
            return $result;
        }

        // Definir las escalas según los ítems mostrados en la documentación
        $scales = [
            // Estrategias de Afrontamiento de Aproximación Cognitiva
            'analisis_logico' => [
                'items' => [1, 9, 17, 25, 33, 41],
                'name' => 'Análisis Lógico',
                'description' => 'Intentos cognitivos de comprender y prepararse mentalmente para enfrentar una situación estresante y sus consecuencias.'
            ],
            'reevaluacion_positiva' => [
                'items' => [2, 10, 18, 26, 34, 42],
                'name' => 'Reevaluación Positiva',
                'description' => 'Intentos cognitivos que apuntan a construir y reestructurar el problema en un sentido positivo, a la vez que se acepta la realidad de la situación.'
            ],
            
            // Estrategias de Afrontamiento de Aproximación Conductual
            'busqueda_orientacion' => [
                'items' => [3, 11, 19, 27, 35, 43],
                'name' => 'Búsqueda de Orientación y Apoyo',
                'description' => 'Intentos conductuales de buscar información, apoyo y orientación en otras personas.'
            ],
            'resolucion_problemas' => [
                'items' => [4, 12, 20, 28, 36, 44],
                'name' => 'Resolución de Problemas',
                'description' => 'Esfuerzos conductuales de realizar acciones concretas que conduzcan directamente a resolver el problema.'
            ],
            
            // Estrategias de Afrontamiento Evitativo Cognitivo
            'evitacion_cognitiva' => [
                'items' => [5, 13, 21, 29, 37, 45],
                'name' => 'Evitación Cognitiva',
                'description' => 'Esfuerzos cognitivos de evitar pensar en el problema.'
            ],
            'aceptacion_resignacion' => [
                'items' => [6, 14, 22, 30, 38, 46],
                'name' => 'Aceptación o Resignación',
                'description' => 'Intentos cognitivos de reaccionar al problema aceptándolo pasivamente, de modo resignado.'
            ],
            
            // Estrategias de Afrontamiento de Evitación conductual
            'busqueda_recompensas' => [
                'items' => [7, 15, 23, 31, 39, 47],
                'name' => 'Búsqueda de Recompensas Alternativas',
                'description' => 'Esfuerzos conductuales de involucrarse en actividades sustitutivas y crear nuevas fuentes de satisfacción que resulten distractoras.'
            ],
            'descarga_emocional' => [
                'items' => [8, 16, 24, 32, 40, 48],
                'name' => 'Descarga Emocional',
                'description' => 'Intentos conductuales de reducir la tensión por medio de la expresión de los sentimientos negativos, como llorar o gritar.'
            ]
        ];
        // Grupos de escalas
        $scaleGroups = [
            'aproximacion' => [
                'name' => 'Estrategias de Aproximación',
                'scales' => ['analisis_logico', 'reevaluacion_positiva', 'busqueda_orientacion', 'resolucion_problemas']
            ],
            'evitacion' => [
                'name' => 'Estrategias de Evitación',
                'scales' => ['evitacion_cognitiva', 'aceptacion_resignacion', 'busqueda_recompensas', 'descarga_emocional']
            ],
            'cognitivo' => [
                'name' => 'Estrategias Cognitivas',
                'scales' => ['analisis_logico', 'reevaluacion_positiva', 'evitacion_cognitiva', 'aceptacion_resignacion']
            ],
            'conductual' => [
                'name' => 'Estrategias Conductuales',
                'scales' => ['busqueda_orientacion', 'resolucion_problemas', 'busqueda_recompensas', 'descarga_emocional']
            ],
            'aproximacion_conductual' => [
                'name' => 'Estrategias de Aproximación Conductual',
                'scales' => ['busqueda_orientacion', 'resolucion_problemas']
            ],
            'aproximacion_cognitiva' => [
                'name' => 'Estrategias de Aproximación Cognitiva',
                'scales' => ['analisis_logico', 'reevaluacion_positiva']
            ],
            'evitacion_cognitiva' => [
                'name' => 'Estrategias de Evitación Cognitiva',
                'scales' => ['evitacion_cognitiva', 'aceptacion_resignacion']
            ],
            'evitacion_conductual' => [
                'name' => 'Estrategias de Evitación Conductual',
                'scales' => ['busqueda_recompensas', 'descarga_emocional']
            ]
        ];
        // Mapeo de respuestas de la Parte I (evaluación inicial)
        $evaluationResponses = [
            'N' => 0,     // No
            'GN' => 1,    // Generalmente No
            'GS' => 2,    // Generalmente Sí
            'S' => 3      // Sí
        ];
        
        // Calcular puntuaciones brutas (PB) para cada escala
        $scores = [];
        $interpretations = [];
        $globalScores = [];
        
        foreach ($scales as $scaleId => $scale) {
            $sum = 0;
            $validItems = 0;
            
            foreach ($scale['items'] as $itemNum) {
                $qKey = 'q' . $itemNum;
                if (isset($responses['estrategias_afrontamiento'][$qKey]['answer'])) {
                    // Las respuestas ya están en formato numérico 0-3
                    $sum += (int)$responses['estrategias_afrontamiento'][$qKey]['answer'];
                    $validItems++;
                }
            }
            
            // Calcular puntuación T usando la fórmula T = (PB - M) x 10 / DE + 50
            // Usamos los baremos de Mikulic & Crespi (2008)
            $rawScore = $sum;
            $mean = 0;
            $sd = 1;
            
            // Asignar Media y Desviación Estándar según la escala
            switch ($scaleId) {
                case 'analisis_logico':
                    $mean = 11.1;
                    $sd = 3.5;
                    break;
                case 'reevaluacion_positiva':
                    $mean = 11.1;
                    $sd = 3.8;
                    break;
                case 'busqueda_orientacion':
                    $mean = 9.6;
                    $sd = 3.5;
                    break;
                case 'resolucion_problemas':
                    $mean = 11.2;
                    $sd = 3.8;
                    break;
                case 'evitacion_cognitiva':
                    $mean = 8.8;
                    $sd = 4.0;
                    break;
                case 'aceptacion_resignacion':
                    $mean = 8.0;
                    $sd = 4.0;
                    break;
                case 'busqueda_recompensas':
                    $mean = 9.8;
                    $sd = 4.1;
                    break;
                case 'descarga_emocional':
                    $mean = 7.7;
                    $sd = 3.5;
                    break;
            }
            
            // Calcular puntuación T
            $tScore = ($rawScore - $mean) * 10 / $sd + 50;
            $tScore = round($tScore, 1);
            
            // Almacenar resultados
            $scores[$scaleId] = [
                'name' => $scale['name'],
                'description' => $scale['description'],
                'raw_score' => $rawScore,
                't_score' => $tScore
            ];
            
            // Generar interpretación basada en la puntuación T
            $interpretations[$scaleId] = $this->getCriScaleInterpretation($scaleId, $tScore);
        }
            // Calcular puntuaciones para los grupos de escalas
        foreach ($scaleGroups as $groupId => $group) {
            $rawSum = 0;
            $tSum = 0;
            $count = 0;
            
            foreach ($group['scales'] as $scaleId) {
                if (isset($scores[$scaleId])) {
                    $rawSum += $scores[$scaleId]['raw_score'];
                    $tSum += $scores[$scaleId]['t_score'];
                    $count++;
                }
            }
            
            if ($count > 0) {
                $rawAvg = round($rawSum / $count, 1);
                $tAvg = round($tSum / $count, 1);
                
                // Definimos Media y Desviación Estándar para escalas globales
                $mean = 0;
                $sd = 1;
                
                // Asignar Media y Desviación Estándar según el grupo
                switch ($groupId) {
                    case 'aproximacion':
                        $mean = 43.3;
                        $sd = 10.9;
                        break;
                    case 'evitacion':
                        $mean = 34.5;
                        $sd = 11.3;
                        break;
                    case 'cognitivo':
                        $mean = 28.0;
                        $sd = 8.2;
                        break;
                    case 'conductual':
                        $mean = 38.5;
                        $sd = 10.3;
                        break;
                    case 'aproximacion_conductual':
                        $mean = 20.9;
                        $sd = 6.1;
                        break;
                    case 'aproximacion_cognitiva':
                        $mean = 22.3;
                        $sd = 6.2;
                        break;
                    case 'evitacion_cognitiva':
                        $mean = 16.8;
                        $sd = 6.9;
                        break;
                    case 'evitacion_conductual':
                        $mean = 17.6;
                        $sd = 6.3;
                        break;
                }
                
                // Almacenar resultados de grupos
                $globalScores[$groupId] = [
                    'name' => $group['name'],
                    'raw_score' => $rawSum,
                    'raw_average' => $rawAvg,
                    't_score' => $tAvg
                ];
            }
        }
        
        // Almacenar resultados en el array de retorno
        $result['scores'] = $scores;
        $result['global_scores'] = $globalScores;
        $result['interpretations'] = $interpretations;
        
        // Generar resumen de los resultados más significativos
        $summary = $this->generateCriSummary($scores, $globalScores);
        $result['summary'] = $summary;
        
        return $result;
    }
    
    /**
     * Genera interpretación para cada escala del CRI
     * 
     * @param string $scale Identificador de la escala
     * @param float $tScore Puntuación T
     * @return string Interpretación de la escala
     */
    private function getCriScaleInterpretation($scale, $tScore): string
    {
        // Interpretación basada en la puntuación T
        if ($tScore <= 34) {
            $level = "Muy por debajo del promedio";
            $intensity = "muy baja";
        } elseif ($tScore <= 40) {
            $level = "Por debajo del promedio";
            $intensity = "baja";
        } elseif ($tScore <= 60) {
            $level = "Dentro del promedio";
            $intensity = "moderada";
        } elseif ($tScore <= 65) {
            $level = "Por encima del promedio";
            $intensity = "alta";
        } else {
            $level = "Muy por encima del promedio";
            $intensity = "muy alta";
        }
        
        // Interpretaciones específicas para cada escala
        $interpretations = [
            'analisis_logico' => [
                'low' => "Tendencia baja a aplicar el análisis lógico para comprender y prepararse mentalmente ante situaciones estresantes.",
                'avg' => "Capacidad promedio para aplicar el análisis lógico en la comprensión y preparación mental ante situaciones estresantes.",
                'high' => "Marcada tendencia a analizar lógicamente las situaciones estresantes, comprenderlas y prepararse mentalmente para afrontarlas."
            ],
            'reevaluacion_positiva' => [
                'low' => "Dificultad para reestructurar positivamente las situaciones problemáticas y encontrar aspectos positivos en ellas.",
                'avg' => "Capacidad moderada para reestructurar positivamente los problemas, encontrando aspectos favorables en la situación.",
                'high' => "Fuerte tendencia a reestructurar positivamente los problemas, construyendo una perspectiva favorable mientras se acepta la realidad."
            ],
            'busqueda_orientacion' => [
                'low' => "Poca inclinación a buscar información, consejo o apoyo en otras personas para afrontar situaciones difíciles.",
                'avg' => "Disposición moderada a buscar orientación, información o apoyo social cuando enfrenta situaciones estresantes.",
                'high' => "Marcada tendencia a buscar activamente orientación, apoyo emocional e información en otras personas ante situaciones difíciles."
            ],
            'resolucion_problemas' => [
                'low' => "Baja tendencia a emprender acciones concretas para resolver directamente las situaciones problemáticas.",
                'avg' => "Capacidad moderada para emprender acciones concretas encaminadas a resolver directamente los problemas.",
                'high' => "Fuerte disposición a emprender acciones concretas y efectivas dirigidas a resolver directamente las situaciones problemáticas."
            ],
            'evitacion_cognitiva' => [
                'low' => "Poca tendencia a evitar pensar en los problemas o situaciones estresantes.",
                'avg' => "Tendencia moderada a evitar pensar realísticamente sobre los problemas o situaciones estresantes.",
                'high' => "Marcada tendencia a evitar pensar en los problemas, utilizando la negación o distracción mental como mecanismo de afrontamiento."
            ],
            'aceptacion_resignacion' => [
                'low' => "Baja tendencia a aceptar pasivamente los problemas y resignarse ante ellos.",
                'avg' => "Moderada tendencia a aceptar los problemas de forma resignada, considerándolos como inevitables.",
                'high' => "Fuerte tendencia a aceptar pasivamente los problemas, con una actitud resignada ante la incapacidad percibida para resolverlos."
            ],
            'busqueda_recompensas' => [
                'low' => "Escasa tendencia a buscar actividades alternativas o nuevas fuentes de satisfacción como distracción.",
                'avg' => "Moderada tendencia a involucrarse en actividades sustitutivas y buscar nuevas fuentes de satisfacción.",
                'high' => "Marcada tendencia a involucrarse en actividades alternativas y crear nuevas fuentes de satisfacción que sirvan como distracción."
            ],
            'descarga_emocional' => [
                'low' => "Poca tendencia a expresar sentimientos negativos como forma de reducir la tensión.",
                'avg' => "Moderada tendencia a expresar sentimientos negativos como llanto o enojo para reducir la tensión.",
                'high' => "Fuerte tendencia a expresar abiertamente sentimientos negativos como forma de reducir la tensión emocional."
            ]
        ];
        
        // Seleccionar el tipo de interpretación basado en la puntuación
        $interpretationType = ($tScore <= 40) ? 'low' : (($tScore <= 60) ? 'avg' : 'high');
        
        // Construir la interpretación completa
        $interpretation = "Puntuación T: {$tScore} - {$level}. ";
        $interpretation .= "Muestra una utilización {$intensity} de esta estrategia. ";
        
        if (isset($interpretations[$scale][$interpretationType])) {
            $interpretation .= $interpretations[$scale][$interpretationType];
        }
        
        return $interpretation;
    }
    
    /**
     * Genera un resumen clínico de los resultados del CRI
     * 
     * @param array $scores Puntuaciones de las escalas individuales
     * @param array $globalScores Puntuaciones de los grupos de escalas
     * @return string Resumen clínico
     */
    private function generateCriSummary(array $scores, array $globalScores): string
    {
        // Identificar las estrategias más y menos utilizadas
        $highestScales = [];
        $lowestScales = [];
        
        foreach ($scores as $scaleId => $scoreData) {
            if ($scoreData['t_score'] >= 65) {
                $highestScales[] = $scoreData['name'];
            } elseif ($scoreData['t_score'] <= 40) {
                $lowestScales[] = $scoreData['name'];
            }
        }
        
        // Determinar el estilo predominante de afrontamiento
        $aproximacion = $globalScores['aproximacion']['t_score'] ?? 50;
        $evitacion = $globalScores['evitacion']['t_score'] ?? 50;
        $cognitivo = $globalScores['cognitivo']['t_score'] ?? 50;
        $conductual = $globalScores['conductual']['t_score'] ?? 50;
        
        $styleDescriptions = [];
        
        // Comparar aproximación vs evitación
        if ($aproximacion > $evitacion + 10) {
            $styleDescriptions[] = "predominantemente orientado a la aproximación";
        } elseif ($evitacion > $aproximacion + 10) {
            $styleDescriptions[] = "predominantemente orientado a la evitación";
        } else {
            $styleDescriptions[] = "con un balance entre estrategias de aproximación y evitación";
        }
        
        // Comparar cognitivo vs conductual
        if ($cognitivo > $conductual + 10) {
            $styleDescriptions[] = "con mayor uso de estrategias cognitivas que conductuales";
        } elseif ($conductual > $cognitivo + 10) {
            $styleDescriptions[] = "con mayor uso de estrategias conductuales que cognitivas";
        } else {
            $styleDescriptions[] = "con un balance entre estrategias cognitivas y conductuales";
        }
        
        // Construir el resumen
        $summary = "El perfil de afrontamiento es " . implode(" y ", $styleDescriptions) . ". ";
        
        if (!empty($highestScales)) {
            $summary .= "Las estrategias utilizadas con mayor frecuencia son: " . implode(", ", $highestScales) . ". ";
        }
        
        if (!empty($lowestScales)) {
            $summary .= "Las estrategias utilizadas con menor frecuencia son: " . implode(", ", $lowestScales) . ". ";
        }
        
        // Añadir evaluación general
        $summary .= "Este patrón de afrontamiento sugiere ";
        
        if ($aproximacion > 60 && $evitacion < 45) {
            $summary .= "un estilo activo y orientado a la resolución de problemas, lo que generalmente se asocia con mejores resultados en el manejo del estrés.";
        } elseif ($evitacion > 60 && $aproximacion < 45) {
            $summary .= "un estilo predominantemente evitativo, que puede ser adaptativo a corto plazo pero potencialmente problemático si se mantiene como estrategia principal a largo plazo.";
        } elseif ($aproximacion > 55 && $evitacion > 55) {
            $summary .= "un uso flexible de múltiples estrategias, lo que puede ser adaptativo en diferentes contextos y situaciones estresantes.";
        } elseif ($aproximacion < 45 && $evitacion < 45) {
            $summary .= "un repertorio limitado de estrategias de afrontamiento, lo que podría dificultar la adaptación a diferentes tipos de estresores.";
        } else {
            $summary .= "un perfil de afrontamiento mixto, con aspectos tanto adaptativos como potencialmente problemáticos dependiendo del contexto específico.";
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
        if (isset($questionnaireResults['problem_description']) && !empty($questionnaireResults['problem_description'])) {
            $promptSection .= "### Descripción del problema:\n";
            $promptSection .= $questionnaireResults['problem_description'] . "\n\n";
        }
        
        // Añadir evaluación del problema
        if (isset($questionnaireResults['problem_evaluation']) && !empty($questionnaireResults['problem_evaluation'])) {
            $promptSection .= "### Evaluación del problema:\n";
            
            $evaluationItems = [
                'q1' => 'La situación fue nueva',
                'q2' => 'La situación fue predecible',
                'q4' => 'La situación fue percibida como amenaza',
                'q5' => 'La situación fue percibida como desafío',
                'q9' => 'El problema se ha resuelto',
                'q10' => 'El resultado fue favorable'
            ];
            
            foreach ($evaluationItems as $qKey => $description) {
                if (isset($questionnaireResults['problem_evaluation'][$qKey])) {
                    $answer = $questionnaireResults['problem_evaluation'][$qKey]['answer'];
                    $promptSection .= "- " . $description . ": " . $answer . "\n";
                }
            }
            $promptSection .= "\n";
        }
        
        // Añadir puntuaciones de escalas específicas
        if (isset($questionnaireResults['scores']) && !empty($questionnaireResults['scores'])) {
            $promptSection .= "### Puntuaciones en estrategias de afrontamiento:\n";
            
            // Organizar las escalas por grupos para una mejor comprensión
            $scaleGroups = [
                'Estrategias de Aproximación Cognitiva' => ['analisis_logico', 'reevaluacion_positiva'],
                'Estrategias de Aproximación Conductual' => ['busqueda_orientacion', 'resolucion_problemas'],
                'Estrategias de Evitación Cognitiva' => ['evitacion_cognitiva', 'aceptacion_resignacion'],
                'Estrategias de Evitación Conductual' => ['busqueda_recompensas', 'descarga_emocional']
            ];
            
            foreach ($scaleGroups as $groupName => $scales) {
                $promptSection .= "#### " . $groupName . ":\n";
                
                foreach ($scales as $scaleId) {
                    if (isset($questionnaireResults['scores'][$scaleId])) {
                        $score = $questionnaireResults['scores'][$scaleId];
                        $promptSection .= "- **" . $score['name'] . "**: ";
                        $promptSection .= "Puntuación bruta " . $score['raw_score'] . ", ";
                        $promptSection .= "Puntuación T " . $score['t_score'];
                        
                        if (isset($questionnaireResults['interpretations'][$scaleId])) {
                            $promptSection .= "\n  - " . $questionnaireResults['interpretations'][$scaleId];
                        }
                        
                        $promptSection .= "\n";
                    }
                }
                $promptSection .= "\n";
            }
        }
        
        // Añadir puntuaciones globales
        if (isset($questionnaireResults['global_scores']) && !empty($questionnaireResults['global_scores'])) {
            $promptSection .= "### Puntuaciones globales:\n";
            
            $groupCategories = [
                'Según orientación' => ['aproximacion', 'evitacion'],
                'Según tipo de respuesta' => ['cognitivo', 'conductual'],
                'Según combinación' => ['aproximacion_cognitiva', 'aproximacion_conductual', 'evitacion_cognitiva', 'evitacion_conductual']
            ];
            
            foreach ($groupCategories as $categoryName => $groups) {
                $promptSection .= "#### " . $categoryName . ":\n";
                
                foreach ($groups as $groupId) {
                    if (isset($questionnaireResults['global_scores'][$groupId])) {
                        $score = $questionnaireResults['global_scores'][$groupId];
                        $promptSection .= "- **" . $score['name'] . "**: ";
                        $promptSection .= "Puntuación T " . $score['t_score'] . "\n";
                    }
                }
                $promptSection .= "\n";
            }
        }
        
        // Añadir interpretaciones clínicas
        if (isset($questionnaireResults['clinical_interpretations']) && !empty($questionnaireResults['clinical_interpretations'])) {
            $promptSection .= "### Interpretaciones clínicas:\n";
            
            foreach ($questionnaireResults['clinical_interpretations'] as $interpretation) {
                $promptSection .= "- " . $interpretation . "\n";
            }
            $promptSection .= "\n";
        }
        
        // Añadir resumen
        if (isset($questionnaireResults['summary']) && !empty($questionnaireResults['summary'])) {
            $promptSection .= "### Resumen global del CRI:\n";
            $promptSection .= $questionnaireResults['summary'] . "\n\n";
        }
        
        return $promptSection;
    }
    
    /**
     * Obtiene el nivel descriptivo de una puntuación T
     * 
     * @param float $tScore Puntuación T
     * @return string Descripción del nivel
     */
    private function getScoreLevel($tScore): string
    {
        if ($tScore <= 34) {
            return "Muy por debajo del promedio";
        } elseif ($tScore <= 40) {
            return "Por debajo del promedio";
        } elseif ($tScore <= 60) {
            return "Dentro del promedio";
        } elseif ($tScore <= 65) {
            return "Por encima del promedio";
        } else {
            return "Muy por encima del promedio";
        }
    }
    
    /**
     * Get AI-specific instructions for interpreting this questionnaire type
     *
     * @return string Formatted instructions
     */
    public function getInstructions(): string
    {
        $defaultInstructions = "Basándote en los resultados del Inventario de Respuestas de Afrontamiento (CRI), proporciona:\n\n" .
               "### Preguntas generales sobre afrontamiento y bienestar psicológico\n" .
               "¿Cómo se pueden interpretar las puntuaciones obtenidas en el cuestionario CRI en términos de estrategias de afrontamiento predominantes en este paciente?\n\n" .
               "¿Qué patrones de afrontamiento destacan en este perfil y cómo pueden influir en su bienestar emocional?\n\n" .
               "¿Existen factores de riesgo asociados a estas estrategias de afrontamiento que podrían indicar vulnerabilidad psicológica?\n\n" .
               "### Preguntas específicas sobre cada estrategia de afrontamiento\n" .
               "¿Qué indica la puntuación en \"Búsqueda de Orientación y Apoyo\" en términos de dependencia emocional o capacidad para gestionar problemas con ayuda externa?\n\n" .
               "¿Cómo puede afectar al bienestar psicológico la puntuación en \"Evitación Cognitiva\"? ¿Podría estar relacionado con mecanismos de negación o represión emocional?\n\n" .
               "¿Qué significa la puntuación en \"Resolución de Problemas\" y cómo influye en la capacidad del paciente para manejar situaciones difíciles?\n\n" .
               "¿De qué manera la puntuación en \"Descarga Emocional\" podría estar vinculada a problemas de regulación emocional o estrés?\n\n" .
               "¿Cómo influye la puntuación en \"Búsqueda de Recompensas Alternativas\" en la capacidad del paciente para encontrar nuevas formas de satisfacción y disfrute en la vida?\n\n" .
               "¿Qué efectos puede tener la puntuación en \"Aceptación o Resignación\" en la salud mental del paciente? ¿Podría estar relacionada con una actitud pasiva ante la vida o con resiliencia?\n\n" .
               "### Preguntas sobre intervención y recomendaciones\n" .
               "¿Qué estrategias terapéuticas podrían ser útiles para potenciar las fortalezas de este paciente en su estilo de afrontamiento?\n\n" .
               "¿Qué aspectos deberían trabajarse en terapia para mejorar las estrategias de afrontamiento menos adaptativas?\n\n" .
               "¿Cómo podría este perfil de afrontamiento influir en la respuesta del paciente a diferentes tipos de intervenciones psicológicas?\n\n" .
               "### Pregunta adicional sobre la interrelación de las estrategias\n" .
                "¿Cómo interactúan las diferentes estrategias de afrontamiento en el perfil del paciente y qué implicaciones tiene esta interacción en su capacidad para manejar el estrés y la adversidad?\n";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 