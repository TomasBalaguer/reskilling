<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

class Tdah2QuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    protected $type = 'TDAH2';

    protected array $areasImpacto = [
        'trabajo_estudios' => [
            'id' => 'te',
            'title' => 'Trabajo y Estudios',
            'items' => [
                '1' => 'Me cuesta mantener la concentración en reuniones largas o tareas monótonas.',
                '2' => 'Pierdo interés rápidamente en proyectos que comienzo, incluso si al principio me entusiasmaban.',
                '3' => 'Tengo dificultad para priorizar tareas y responsabilidades diarias.',
                '4' => 'Siento que necesito fechas límite o presión externa para poder terminar cosas.',
                '5' => 'Me distraigo con facilidad, incluso con pequeños ruidos o pensamientos.',
                '6' => 'Siento que no aprovecho mi potencial porque me cuesta organizarme.',
                '7' => 'Me frustro fácilmente cuando no logro concentrarme o terminar mis tareas.',
                '8' => 'He perdido trabajos o dejado estudios por dificultades con la organización o el cumplimiento de plazos.',
                '9' => 'Tiendo a procrastinar y luego me siento abrumado/a con el trabajo acumulado.'
            ]
        ],
        'relaciones_vida_social' => [
            'id' => 'rvs',
            'title' => 'Relaciones y Vida Social',
            'items' => [
                '1' => 'Cambio de intereses y amistades con facilidad, me aburro rápidamente.',
                '2' => 'Tengo dificultades para mantener conversaciones largas sin perder el hilo.',
                '3' => 'A veces interrumpo a los demás sin darme cuenta o hablo en exceso.',
                '4' => 'Siento que mis emociones pueden ser intensas y cambio de humor repentinamente.',
                '5' => 'Me cuesta recordar detalles importantes de conversaciones recientes.',
                '6' => 'Interpreto con facilidad un silencio, una cara seria o un retraso en la respuesta como un rechazo.',
                '7' => 'Mis relaciones de pareja o amistades han sufrido por mi impulsividad o desorganización.',
                '8' => 'Me cuesta establecer límites y decir "no" cuando es necesario.'
            ]
        ],
        'tiempo_libre_rutinas' => [
            'id' => 'tlr',
            'title' => 'Tiempo Libre y Rutinas',
            'items' => [
                '1' => 'Me cuesta relajarme o disfrutar del tiempo libre sin sentirme inquieto/a.',
                '2' => 'Tengo muchas ideas o hobbies, pero dejo las cosas a medias y no las termino.',
                '3' => 'Me resulta difícil ver una película o leer un libro sin perder la atención.',
                '4' => 'Necesito estar en constante movimiento o estimulación para no aburrirme.',
                '5' => 'Me involucro en muchas actividades y luego me siento agotado/a.',
                '6' => 'Siento que mis niveles de energía varían mucho a lo largo del día.',
                '7' => 'He tenido problemas con la justicia o accidentes por tomar decisiones impulsivas.',
                '8' => 'Me cuesta seguir una rutina diaria o mantener una estructura.'
            ]
        ],
        'autoimagen_seguridad' => [
            'id' => 'asp',
            'title' => 'Autoimagen y Seguridad Personal',
            'items' => [
                '1' => 'Me siento inseguro/a cuando recibo comentarios negativos o críticas.',
                '2' => 'Me preocupo por no cumplir con expectativas y siento miedo al fracaso.',
                '3' => 'Tiendo a reaccionar de forma intensa ante ciertas emociones.',
                '4' => 'Siento que necesito la validación de los demás para sentirme bien.',
                '5' => 'A veces siento que no cumplo con todo lo que me propongo y me frustro.',
                '6' => 'Siento que mi creatividad es alta, pero me cuesta enfocarla en algo productivo.',
                '7' => 'Me cuesta tomar decisiones importantes sin dudar mucho tiempo.'
            ]
        ],
        'organizacion_tiempo' => [
            'id' => 'omt',
            'title' => 'Organización y Manejo del Tiempo',
            'items' => [
                '1' => 'Pierdo objetos con frecuencia, como llaves, celular o documentos importantes.',
                '2' => 'Olvido reuniones, citas o fechas importantes con regularidad.',
                '3' => 'Me cuesta planificar a futuro o establecer metas a largo plazo.',
                '4' => 'Me sobrecargo de compromisos y luego no logro cumplirlos.',
                '5' => 'Siento que mi vida carece de orden o estructura.',
                '6' => 'Tiendo a postergar o evitar tareas que requieren esfuerzo mental sostenido.',
                '7' => 'Me cuesta organizar mi espacio (casa, oficina, mochila, etc.).',
                '8' => 'Me cuesta manejar el tiempo y calcular cuánto tardaré en hacer algo.'
            ]
        ],
        'impulsividad_control' => [
            'id' => 'ici',
            'title' => 'Impulsividad y Control de Impulsos',
            'items' => [
                '1' => 'A veces actúo sin pensar en las consecuencias.',
                '2' => 'Me cuesta controlar mis gastos y evito compras impulsivas.',
                '3' => 'Cambio de tema abruptamente en conversaciones sin darme cuenta.',
                '4' => 'Me resulta difícil regular mis emociones en situaciones tensas.',
                '5' => 'Me siento inquieto/a cuando debo esperar en una fila o en el tráfico.',
                '6' => 'Suelo empezar muchas cosas a la vez y dejo la mayoría sin terminar.',
                '7' => 'Me cuesta aceptar críticas sin sentirme abrumado/a.'
            ]
        ]
    ];

    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        $scores = [
            'caracteristicas_personales' => $this->processCaracteristicasPersonales($responses['caracteristicas_personales']),
            'areas_impacto' => $this->processAreasImpacto($responses['areas_impacto'])
        ];
        
        return [
            'scores' => $scores,
            'interpretations' => $this->interpretScores($scores),
            'clinical_interpretations' => $this->generateClinicalInterpretations($scores),
            'summary' => $this->generateSummary($scores),
            'scoring_type' => 'TDAH2',
            'questionnaire_name' => 'Cuestionario de Impacto del TDAH en la Vida Cotidiana',
            'responses' => $responses
        ];
    }

    protected function processCaracteristicasPersonales(array $responses): array
    {
        $caracteristicas = $responses['caracteristica']['answer'];
        $total = count($caracteristicas);
        $tdahCount = 0;
        $asiCount = 0;

        foreach ($caracteristicas as $caracteristica) {
            if ($caracteristica['es_por_tdah']) {
                $tdahCount++;
            }
            if ($caracteristica['soy_asi']) {
                $asiCount++;
            }
        }

        return [
            'total_caracteristicas' => $total,
            'porcentaje_tdah' => $total > 0 ? ($tdahCount / $total) * 100 : 0,
            'porcentaje_asi' => $total > 0 ? ($asiCount / $total) * 100 : 0,
            'caracteristicas' => $caracteristicas
        ];
    }

    protected function processAreasImpacto(array $responses): array
    {
        $result = [];
        foreach ($this->areasImpacto as $areaKey => $area) {
            if (isset($responses[$areaKey])) {
                $selectedItems = $responses[$areaKey]['answer'];
                $totalItems = count($area['items']);
                $selectedCount = count($selectedItems);
                
                $result[$areaKey] = [
                    'area' => $area['title'],
                    'total_items' => $totalItems,
                    'selected_count' => $selectedCount,
                    'percentage' => ($selectedCount / $totalItems) * 100,
                    'selected_items' => array_map(function($itemId) use ($area) {
                        return [
                            'id' => $itemId,
                            'text' => $area['items'][$itemId]
                        ];
                    }, $selectedItems)
                ];
            }
        }
        return $result;
    }

    protected function interpretScores(array $scores): array
    {
        $interpretations = [
            'caracteristicas_personales' => $this->interpretCaracteristicasPersonales($scores['caracteristicas_personales']),
            'areas_impacto' => $this->interpretAreasImpacto($scores['areas_impacto'])
        ];

        return [
            'individual' => $interpretations,
            'overall' => $this->generateOverallInterpretation($scores)
        ];
    }

    protected function interpretCaracteristicasPersonales(array $scores): array
    {
        $interpretation = [];
        
        // Interpretación del número total de características
        if ($scores['total_caracteristicas'] >= 15) {
            $interpretation['total'] = 'Alto nivel de autoconciencia y detección de áreas de mejora';
        } elseif ($scores['total_caracteristicas'] >= 10) {
            $interpretation['total'] = 'Nivel moderado de autoconciencia';
        } else {
            $interpretation['total'] = 'Bajo nivel de autoconciencia o dificultad para identificar áreas de mejora';
        }

        // Interpretación del porcentaje de atribución al TDAH
        if ($scores['porcentaje_tdah'] >= 70) {
            $interpretation['tdah'] = 'Alta atribución de características al TDAH';
        } elseif ($scores['porcentaje_tdah'] >= 40) {
            $interpretation['tdah'] = 'Atribución moderada al TDAH';
        } else {
            $interpretation['tdah'] = 'Baja atribución al TDAH';
        }

        // Interpretación del porcentaje de "Soy así"
        if ($scores['porcentaje_asi'] >= 70) {
            $interpretation['asi'] = 'Alta aceptación de características como parte de la personalidad';
        } elseif ($scores['porcentaje_asi'] >= 40) {
            $interpretation['asi'] = 'Aceptación moderada de características';
        } else {
            $interpretation['asi'] = 'Baja aceptación de características como parte de la personalidad';
        }

        return $interpretation;
    }

    protected function interpretAreasImpacto(array $scores): array
    {
        $interpretations = [];
        foreach ($scores as $areaKey => $area) {
            if ($area['percentage'] >= 70) {
                $interpretations[$areaKey] = 'Alto impacto en esta área';
            } elseif ($area['percentage'] >= 40) {
                $interpretations[$areaKey] = 'Impacto moderado en esta área';
            } else {
                $interpretations[$areaKey] = 'Bajo impacto en esta área';
            }
        }
        return $interpretations;
    }

    protected function generateOverallInterpretation(array $scores): string
    {
        $areasAltoImpacto = 0;
        $areasModeradoImpacto = 0;
        
        foreach ($scores['areas_impacto'] as $area) {
            if ($area['percentage'] >= 70) {
                $areasAltoImpacto++;
            } elseif ($area['percentage'] >= 40) {
                $areasModeradoImpacto++;
            }
        }

        $interpretation = "El cuestionario muestra ";
        
        if ($areasAltoImpacto >= 3) {
            $interpretation .= "un impacto significativo del TDAH en múltiples áreas de la vida cotidiana. ";
        } elseif ($areasAltoImpacto >= 1 || $areasModeradoImpacto >= 2) {
            $interpretation .= "un impacto moderado del TDAH en algunas áreas específicas. ";
        } else {
            $interpretation .= "un impacto limitado del TDAH en la vida cotidiana. ";
        }

        if ($scores['caracteristicas_personales']['porcentaje_tdah'] >= 70) {
            $interpretation .= "Hay una alta conciencia de la influencia del TDAH en las características personales. ";
        } elseif ($scores['caracteristicas_personales']['porcentaje_tdah'] >= 40) {
            $interpretation .= "Existe una conciencia moderada de la influencia del TDAH. ";
        } else {
            $interpretation .= "Hay una baja atribución de características al TDAH. ";
        }

        return $interpretation;
    }

    protected function generateClinicalInterpretations(array $scores): array
    {
        return [
            'caracteristicas_personales' => $this->generateClinicalInterpretationCaracteristicas($scores['caracteristicas_personales']),
            'areas_impacto' => $this->generateClinicalInterpretationAreas($scores['areas_impacto'])
        ];
    }

    protected function generateClinicalInterpretationCaracteristicas(array $scores): string
    {
        $interpretation = "Análisis de características personales:\n";
        $interpretation .= "- Total de características identificadas: {$scores['total_caracteristicas']}\n";
        $interpretation .= "- Porcentaje atribuido al TDAH: " . round($scores['porcentaje_tdah'], 1) . "%\n";
        $interpretation .= "- Porcentaje atribuido a la personalidad: " . round($scores['porcentaje_asi'], 1) . "%\n\n";

        if ($scores['porcentaje_tdah'] >= 70) {
            $interpretation .= "Se observa una alta atribución de características al TDAH, lo que sugiere una buena comprensión del impacto del trastorno.\n";
        } elseif ($scores['porcentaje_tdah'] >= 40) {
            $interpretation .= "Existe una atribución moderada al TDAH, indicando una comprensión parcial del impacto del trastorno.\n";
        } else {
            $interpretation .= "Se observa una baja atribución al TDAH, lo que podría indicar la necesidad de psicoeducación sobre el trastorno.\n";
        }

        return $interpretation;
    }

    protected function generateClinicalInterpretationAreas(array $scores): string
    {
        $interpretation = "Análisis de áreas de impacto:\n\n";
        
        foreach ($scores as $areaKey => $area) {
            $interpretation .= "{$area['area']}:\n";
            $interpretation .= "- Impacto: " . round($area['percentage'], 1) . "%\n";
            $interpretation .= "- Items seleccionados: {$area['selected_count']} de {$area['total_items']}\n";
            
            if ($area['percentage'] >= 70) {
                $interpretation .= "Área de alto impacto que requiere atención prioritaria.\n";
            } elseif ($area['percentage'] >= 40) {
                $interpretation .= "Área de impacto moderado que requiere seguimiento.\n";
            } else {
                $interpretation .= "Área de bajo impacto.\n";
            }
            $interpretation .= "\n";
        }

        return $interpretation;
    }

    protected function generateSummary(array $scores): string
    {
        $summary = "Resumen del Cuestionario de Impacto del TDAH:\n\n";
        
        // Resumen de características personales
        $summary .= "Características Personales:\n";
        $summary .= "- Total de características identificadas: {$scores['caracteristicas_personales']['total_caracteristicas']}\n";
        $summary .= "- Atribución al TDAH: " . round($scores['caracteristicas_personales']['porcentaje_tdah'], 1) . "%\n";
        $summary .= "- Atribución a la personalidad: " . round($scores['caracteristicas_personales']['porcentaje_asi'], 1) . "%\n\n";

        // Resumen de áreas de impacto
        $summary .= "Áreas de Impacto:\n";
        foreach ($scores['areas_impacto'] as $area) {
            $summary .= "- {$area['area']}: " . round($area['percentage'], 1) . "%\n";
        }

        return $summary;
    }

    public function buildPromptSection(array $questionnaireResults): string
    {
        
        $scores = $questionnaireResults['scores'];
        $prompt = "Análisis del Cuestionario de Impacto del TDAH:\n\n";

        // Sección de características personales
        $prompt .= "Características Personales:\n";
        $prompt .= "- Total de características: {$scores['caracteristicas_personales']['total_caracteristicas']}\n";
        $prompt .= "- Atribución al TDAH: " . round($scores['caracteristicas_personales']['porcentaje_tdah'], 1) . "%\n";
        $prompt .= "- Atribución a la personalidad: " . round($scores['caracteristicas_personales']['porcentaje_asi'], 1) . "%\n\n";

        $prompt .= "Características identificadas:\n";
        foreach ($scores['caracteristicas_personales']['caracteristicas'] as $caracteristica) {
            $prompt .= "  * {$caracteristica['caracteristica']}\n";
        }
        $prompt .= "\n";

        // Sección de áreas de impacto
        $prompt .= "Áreas de Impacto:\n";
        foreach ($scores['areas_impacto'] as $area) {
            $prompt .= "- {$area['area']}: " . round($area['percentage'], 1) . "%\n";
            foreach ($area['selected_items'] as $item) {
                $prompt .= "  * {$item['text']}\n";
            }
            $prompt .= "\n";
        }

        // Add raw responses section
        $prompt .= "Respuestas Detalladas:\n\n";
        try {
            // Características personales
            $prompt .= "Características Personales:\n";
        foreach ($questionnaireResults['responses']['caracteristicas_personales']['caracteristica']['answer'] as $caracteristica) {
            $prompt .= "- {$caracteristica['caracteristica']}\n";
            $prompt .= "  Soy asi desde que tengo recuerdo: " . ($caracteristica['es_por_tdah'] ? 'Sí' : 'No') . "\n";
            $prompt .= "  Es algo nuevo en mi: " . ($caracteristica['soy_asi'] ? 'Sí' : 'No') . "\n";
        }
        $prompt .= "\n";

        // Áreas de impacto
        $prompt .= "Respuestas por Área:\n";
        foreach ($questionnaireResults['responses']['areas_impacto'] as $area => $data) {
            $prompt .= "\n{$data['question']}:\n";
            foreach ($data['answer'] as $itemId) {
                if (isset($this->areasImpacto[$area]['items'][$itemId])) {
                    $prompt .= "- " . $this->areasImpacto[$area]['items'][$itemId] . "\n";
                }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error al construir el prompt: ' . $e->getMessage());
        }
        return $prompt;
    }

    public function getInstructions(): string
    {
        $defaultInstructions = "Analiza el cuestionario de impacto del TDAH considerando:\n\n" .
               "1. Características personales identificadas:\n" .
               "   - Número total de características\n" .
               "   - Proporción atribuida al TDAH vs. personalidad\n" .
               "   - Patrones en las características mencionadas\n\n" .
               "2. Áreas de impacto:\n" .
               "   - Identificar áreas de mayor impacto\n" .
               "   - Analizar patrones en los items seleccionados\n" .
               "   - Evaluar la distribución del impacto\n\n" .
               "3. Recomendaciones:\n" .
               "   - Sugerir estrategias específicas para áreas de alto impacto\n" .
               "   - Proponer intervenciones basadas en los patrones identificados\n" .
               "   - Considerar la atribución de características al TDAH vs. personalidad\n\n" .
               "4. Consideraciones adicionales:\n" .
               "   - Evaluar la necesidad de psicoeducación\n" .
               "   - Identificar áreas de fortaleza y resiliencia\n" .
               "   - Sugerir recursos y herramientas específicas";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
}