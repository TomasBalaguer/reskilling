<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for Holmes-Rahe Social Readjustment Rating Scale questionnaire
 */
class HolmesRaheQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'HOLMES_RAHE';
    
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
        if (!isset($responses['eventos_vitales']) || empty($responses['eventos_vitales'])) {
            $result['summary'] = 'No se proporcionaron respuestas suficientes para la escala Holmes-Rahe.';
            return $result;
        }

        // Definir los valores de UCV (Unidades de Cambio Vital) para cada evento
        $ucvValues = [
            'q1' => 100, // Muerte del cónyuge
            'q2' => 73,  // Divorcio
            'q3' => 65,  // Separación
            'q4' => 63,  // Privación de la libertad
            'q5' => 63,  // Muerte de un familiar próximo
            'q6' => 53,  // Enfermedad o incapacidad, graves
            'q7' => 50,  // Matrimonio
            'q8' => 47,  // Perder el empleo
            'q9' => 45,  // Reconciliación de la pareja
            'q10' => 45, // Jubilación
            'q11' => 44, // Enfermedad de un pariente cercano
            'q12' => 40, // Embarazo
            'q13' => 39, // Problemas sexuales
            'q14' => 39, // Llegada de un nuevo miembro a la familia
            'q15' => 39, // Cambios importantes en el trabajo
            'q16' => 38, // Cambios importantes a nivel económico
            'q17' => 37, // Muerte de un amigo íntimo
            'q18' => 36, // Cambiar de empleo
            'q19' => 35, // Discusiones con la pareja (cambio significativo)
            'q20' => 31, // Pedir una hipoteca de alto valor
            'q21' => 30, // Hacer efectivo un préstamo
            'q22' => 29, // Cambio de responsabilidades en el trabajo
            'q23' => 29, // Un hijo/a abandona el hogar
            'q24' => 29, // Problemas con la ley
            'q25' => 28, // Logros personales excepcionales
            'q26' => 26, // La pareja comienza o deja de trabajar
            'q27' => 26, // Se inicia o se termina el ciclo de escolarización
            'q28' => 25, // Cambios importantes en las condiciones de vida
            'q29' => 24, // Cambio en los hábitos personales
            'q30' => 23, // Problemas con el jefe
            'q31' => 20, // Cambio en el horario o condiciones de trabajo
            'q32' => 20, // Cambio de residencia
            'q33' => 20, // Cambio a una escuela nueva
            'q34' => 19, // Cambio en la forma o frecuencia de las diversiones
            'q35' => 19, // Cambio en la frecuencia de las actividades religiosas
            'q36' => 18, // Cambio en las actividades sociales
            'q37' => 17, // Pedir una hipoteca o préstamo menor
            'q38' => 16, // Cambios en los hábitos del sueño
            'q39' => 15, // Cambios en el número de reuniones familiares
            'q40' => 15, // Cambio en los hábitos alimentarios
            'q41' => 15, // Vacaciones
            'q42' => 12, // Navidades
            'q43' => 11  // Infracciones menores de la ley
        ];

        // Inicializar puntuación total
        $totalScore = 0;
        $selectedEvents = [];

        // Calcular la puntuación total sumando los valores UCV de los eventos marcados como true
        foreach ($responses['eventos_vitales'] as $questionId => $response) {
            if (isset($response['answer']) && $response['answer'] === true && isset($ucvValues[$questionId])) {
                $totalScore += $ucvValues[$questionId];
                $selectedEvents[$questionId] = [
                    'event' => $response['question'],
                    'value' => $ucvValues[$questionId]
                ];
            }
        }

        // Determinar el nivel de riesgo basado en la puntuación total
        $riskLevel = '';
        $interpretation = '';
        
        if ($totalScore < 150) {
            $riskLevel = 'Bajo';
            $interpretation = 'Riesgo bajo de desarrollar problemas de salud relacionados con el estrés. La puntuación sugiere que la cantidad de cambios vitales experimentados durante el último año se encuentra dentro de límites manejables para la mayoría de las personas.';
        } elseif ($totalScore < 300) {
            $riskLevel = 'Moderado';
            $interpretation = 'Riesgo moderado (33%) de desarrollar problemas de salud relacionados con el estrés en los próximos 2 años. Se recomienda prestar atención a las estrategias de manejo del estrés y buscar apoyo según sea necesario.';
        } else {
            $riskLevel = 'Alto';
            $interpretation = 'Riesgo alto (50% o más) de desarrollar problemas de salud relacionados con el estrés en los próximos 2 años. Se recomienda enfáticamente buscar apoyo profesional para implementar estrategias efectivas de manejo del estrés.';
        }

        // Generar interpretaciones clínicas
        $clinicalInterpretations = $this->generateHolmesRaheClinicalInterpretation($totalScore, $selectedEvents);
        // Guardar resultados
        $result['scores'] = [
            'total_ucv' => [
                'name' => 'Total de Unidades de Cambio Vital (UCV)',
                'raw_score' => $totalScore,
                'description' => 'Suma total de las unidades de cambio vital de los eventos seleccionados'
            ]
        ];
        
        $result['selected_events'] = $selectedEvents;
        $result['risk_level'] = $riskLevel;
        $result['interpretation'] = $interpretation;
        $result['clinical_interpretations'] = $clinicalInterpretations;
        
        // Generar resumen
        $result['summary'] = "Puntuación total: $totalScore UCV. Nivel de riesgo: $riskLevel. " . 
                            substr($interpretation, 0, strpos($interpretation, '.') + 1);
        
        return $result;
    }
    
    /**
     * Genera interpretaciones clínicas específicas para la escala Holmes-Rahe
     * 
     * @param int $totalScore Puntuación total de UCV
     * @param array $selectedEvents Eventos seleccionados por el paciente
     * @return array Interpretaciones clínicas
     */
    private function generateHolmesRaheClinicalInterpretation($totalScore, $selectedEvents): array
    {
        $interpretations = [];
        
        // Interpretación general basada en el puntaje
        if ($totalScore < 150) {
            $interpretations[] = "La exposición a eventos vitales estresantes durante el último año ha sido relativamente baja, lo que sugiere una capacidad adecuada para mantener la estabilidad psicosocial.";
        } elseif ($totalScore < 300) {
            $interpretations[] = "La exposición a eventos vitales estresantes durante el último año ha sido moderada, lo que podría estar requiriendo un esfuerzo significativo de adaptación y afrontamiento.";
        } else {
            $interpretations[] = "La exposición a eventos vitales estresantes durante el último año ha sido alta, lo que representa una considerable carga de adaptación que podría estar superando los recursos de afrontamiento disponibles.";
        }
        
        // Analizar el tipo de eventos experimentados
        $eventCategories = [
            'familiares' => ['q1', 'q2', 'q3', 'q5', 'q7', 'q9', 'q11', 'q12', 'q14', 'q19', 'q23'],
            'laborales' => ['q8', 'q10', 'q15', 'q18', 'q22', 'q26', 'q30', 'q31'],
            'salud' => ['q6', 'q11', 'q13'],
            'economicos' => ['q16', 'q20', 'q21', 'q37'],
            'legales' => ['q4', 'q24', 'q43'],
            'cambios_personales' => ['q25', 'q27', 'q28', 'q29', 'q32', 'q33', 'q34', 'q35', 'q36', 'q38', 'q39', 'q40', 'q41', 'q42']
        ];
        
        $categoryScores = array_fill_keys(array_keys($eventCategories), 0);
        $categoryEvents = array_fill_keys(array_keys($eventCategories), 0);
        
        foreach ($selectedEvents as $questionId => $eventData) {
            foreach ($eventCategories as $category => $questions) {
                if (in_array($questionId, $questions)) {
                    $categoryScores[$category] += $eventData['value'];
                    $categoryEvents[$category]++;
                }
            }
        }
        
        // Identificar categorías prominentes (más de 1 evento o más de 40 puntos)
        $prominentCategories = [];
        foreach ($categoryScores as $category => $score) {
            if ($score > 40 || $categoryEvents[$category] > 1) {
                $prominentCategories[$category] = [
                    'score' => $score,
                    'events' => $categoryEvents[$category]
                ];
            }
        }
        
        // Generar interpretaciones específicas por categoría
        if (!empty($prominentCategories)) {
            arsort($prominentCategories);
            $topCategory = key($prominentCategories);
            
            switch ($topCategory) {
                case 'familiares':
                    $interpretations[] = "Los eventos estresantes más significativos están relacionados con cambios en la estructura y dinámica familiar. Estos cambios suelen requerir ajustes importantes en los roles y relaciones interpersonales, y podrían estar generando tensiones en el sistema familiar.";
                    break;
                case 'laborales':
                    $interpretations[] = "Los cambios más significativos están relacionados con el ámbito laboral. Las transiciones en el entorno de trabajo pueden generar incertidumbre sobre la estabilidad económica y profesional, afectando la sensación de seguridad y autoeficacia.";
                    break;
                case 'salud':
                    $interpretations[] = "Los eventos relacionados con la salud personal o de familiares cercanos constituyen una importante fuente de estrés. Este tipo de situaciones suelen generar preocupación, incertidumbre y pueden requerir adaptaciones significativas en la vida cotidiana.";
                    break;
                case 'economicos':
                    $interpretations[] = "Los cambios económicos representan una fuente importante de estrés. Las preocupaciones financieras suelen tener un efecto generalizado en diversas áreas de la vida y pueden generar sensación de inseguridad sobre el futuro.";
                    break;
                case 'legales':
                    $interpretations[] = "Los eventos relacionados con aspectos legales o judiciales constituyen una fuente significativa de estrés. Estas situaciones suelen implicar procesos prolongados de incertidumbre y pueden afectar múltiples áreas de la vida.";
                    break;
                case 'cambios_personales':
                    $interpretations[] = "Los cambios en hábitos y rutinas personales, aunque individualmente pueden parecer menos intensos, en conjunto representan una importante carga de adaptación que requiere ajustes constantes en la vida cotidiana.";
                    break;
            }
        }
        
        // Agregar recomendaciones basadas en el nivel de riesgo
        if ($totalScore < 150) {
            $interpretations[] = "Recomendación: Mantener las estrategias de afrontamiento actuales, fortaleciendo factores protectores como el apoyo social, actividades placenteras y hábitos saludables.";
        } elseif ($totalScore < 300) {
            $interpretations[] = "Recomendación: Implementar estrategias específicas de manejo del estrés, como técnicas de relajación, actividad física regular, organización del tiempo y fortalecimiento de la red de apoyo social. Considerar consulta con profesionales de salud mental si aparecen síntomas de estrés persistentes.";
        } else {
            $interpretations[] = "Recomendación: Buscar apoyo profesional para desarrollar un plan estructurado de manejo del estrés. Priorizar el autocuidado, establecer límites saludables, y considerar ajustes temporales en responsabilidades cuando sea posible. Estar atento a señales de alarma como cambios en el sueño, apetito, estado de ánimo o funcionamiento general.";
        }
        
        return $interpretations;
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
        
        // Añadir puntuación total UCV
        if (isset($questionnaireResults['scores']['total_ucv'])) {
            $score = $questionnaireResults['scores']['total_ucv'];
            $promptSection .= "### Puntuación total de Unidades de Cambio Vital (UCV):\n";
            $promptSection .= "- **" . $score['name'] . "**: " . $score['raw_score'] . "\n";
            $promptSection .= "- **Descripción**: " . $score['description'] . "\n\n";
        }
        
        // Añadir nivel de riesgo
        if (isset($questionnaireResults['risk_level']) && !empty($questionnaireResults['risk_level'])) {
            $promptSection .= "### Nivel de riesgo:\n";
            $promptSection .= "- **Nivel**: " . $questionnaireResults['risk_level'] . "\n";
            
            if (isset($questionnaireResults['interpretation']) && !empty($questionnaireResults['interpretation'])) {
                $promptSection .= "- **Interpretación**: " . $questionnaireResults['interpretation'] . "\n\n";
            }
        }
        
        // Añadir eventos seleccionados (ordenados por valor UCV)
        if (isset($questionnaireResults['selected_events']) && !empty($questionnaireResults['selected_events'])) {
            $promptSection .= "### Eventos vitales seleccionados:\n";
            
            // Ordenar eventos por valor UCV (de mayor a menor)
            $events = $questionnaireResults['selected_events'];
            uasort($events, function($a, $b) {
                return $b['value'] <=> $a['value'];
            });
            
            foreach ($events as $questionId => $eventData) {
                $promptSection .= "- **" . $eventData['event'] . "** (UCV: " . $eventData['value'] . ")\n";
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
            $promptSection .= "### Resumen global de Holmes-Rahe:\n";
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
        $defaultInstructions = "Analiza los resultados de la Escala de Reajuste Psicosocial de Holmes y Rahe considerando la cantidad, tipo e intensidad " .
               "de los eventos vitales experimentados por el paciente durante el último año. Evalúa el nivel de riesgo para desarrollar " .
               "problemas de salud relacionados con el estrés y la carga adaptativa acumulada. " .
               "Identifica las áreas de vida más afectadas por los cambios y cómo podrían estar interactuando entre sí. " .
               "Considera factores protectores y vulnerabilidades específicas del paciente según su historia clínica. " .
               "Proporciona recomendaciones específicas para fortalecer la resistencia al estrés y prevenir complicaciones físicas y psicológicas, " .
               "incluyendo estrategias de afrontamiento adaptativas, reorganización de prioridades y acceso a sistemas de apoyo apropiados.";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 