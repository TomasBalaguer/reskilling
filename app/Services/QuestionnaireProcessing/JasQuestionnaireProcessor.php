<?php

namespace App\Services\QuestionnaireProcessing;

use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;

/**
 * Processor for JAS (Jenkins Activity Survey) questionnaire
 * Used to assess Type A Behavior Pattern
 */
class JasQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    /**
     * The questionnaire type identifier
     *
     * @var string
     */
    protected $type = 'JAS';
    
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
        
        // Inicializar arrays para almacenar resultados
        $scores = [];
        $interpretations = [];
        
        // Verificamos que tengamos las respuestas en el formato correcto
        if (!isset($responses['conducta_habitual'])) {
            $result['summary'] = 'Formato de respuestas incorrecto para el cuestionario JAS';
            return $result;
        }
        
        // Calcular puntuaciones directas para cada escala según tablas de ponderación
        
        // ESCALA TIPO A (Global)
        $typeAItems = [3, 5, 6, 7, 9, 10, 11, 16, 17, 18, 19, 21, 25, 28, 30, 32, 35, 37, 40, 43, 46];
        $typeAScore = 0;
        
        // ESCALA S (Prisa e impaciencia)
        $sItems = [1, 2, 6, 7, 8, 9, 10, 12, 13, 14, 17, 18, 22, 23, 25, 26, 30, 32, 35, 39, 44];
        $sScore = 0;
        
        // ESCALA J (Implicación en el trabajo)
        $jItems = [3, 4, 10, 11, 17, 21, 24, 25, 30, 31, 32, 33, 34, 36, 37, 38, 41, 44, 47, 48, 49, 50, 51, 52];
        $jScore = 0;
        
        // ESCALA H (Conducta competitiva/dura)
        $hItems = [2, 3, 7, 15, 16, 17, 18, 19, 20, 22, 24, 26, 27, 29, 31, 42, 43, 45, 46, 51];
        $hScore = 0;
        
        // Tablas de pesos para cada escala según la respuesta elegida (A, B, C, D, E o en blanco)
        
        // Tabla 3: Pesos para escala Tipo A
        $typeAWeights = [
            3 => ['blank' => 7, 'A' => 11, 'B' => 10, 'C' => 1, 'D' => 12],
            5 => ['blank' => 8, 'A' => 17, 'B' => 2],
            6 => ['blank' => 9, 'A' => 23, 'B' => 14, 'C' => 2, 'D' => 2],
            7 => ['blank' => -10, 'A' => -26, 'B' => -10, 'C' => -3],
            9 => ['blank' => 37, 'A' => 54, 'B' => 20, 'C' => 3],
            10 => ['blank' => 9, 'A' => 18, 'B' => 8, 'C' => 1],
            11 => ['blank' => 9, 'A' => 2, 'B' => 2, 'C' => 15],
            16 => ['blank' => 31, 'A' => 58, 'B' => 43, 'C' => 9, 'D' => 3],
            17 => ['blank' => -4, 'A' => -11, 'B' => -8, 'C' => -1, 'D' => -1],
            18 => ['blank' => 40, 'A' => 81, 'B' => 67, 'C' => 9, 'D' => 4],
            19 => ['blank' => 12, 'A' => 2, 'B' => 7, 'C' => 26],
            21 => ['blank' => 8, 'A' => 14, 'B' => 2, 'C' => 3, 'D' => 14],
            25 => ['blank' => 16, 'A' => 31, 'B' => 20, 'C' => 7, 'D' => 2],
            28 => ['blank' => 6, 'A' => 11, 'B' => 4, 'C' => 1, 'D' => 1],
            30 => ['blank' => 12, 'A' => 2, 'B' => 8, 'C' => 21],
            32 => ['blank' => -9, 'A' => -1, 'B' => -5, 'C' => -15],
            35 => ['blank' => 6, 'A' => 13, 'B' => 2, 'C' => 17],
            37 => ['blank' => 6, 'A' => 2, 'B' => 10, 'C' => 16],
            40 => ['blank' => 15, 'A' => 2, 'B' => 12, 'C' => 25],
            43 => ['blank' => 8, 'A' => 14, 'B' => 2, 'C' => 2, 'D' => 2],
            46 => ['blank' => 10, 'A' => 20, 'B' => 10, 'C' => 4, 'D' => 1]
        ];
        
        // Tabla 4: Pesos para escala S (Prisa e impaciencia)
        $sWeights = [
            1 => ['blank' => 16, 'A' => 3, 'B' => 16, 'C' => 40],
            2 => ['blank' => -8, 'A' => -8, 'B' => -3, 'C' => -19],
            6 => ['blank' => 21, 'A' => 56, 'B' => 25, 'C' => 4, 'D' => 7],
            7 => ['blank' => 17, 'A' => 39, 'B' => 14, 'C' => 3],
            8 => ['blank' => 6, 'A' => 10, 'B' => 5, 'C' => 1],
            9 => ['blank' => 24, 'A' => 43, 'B' => 8, 'C' => 3],
            10 => ['blank' => 20, 'A' => 40, 'B' => 15, 'C' => 2],
            12 => ['blank' => -5, 'A' => -11, 'B' => -5, 'C' => -1],
            13 => ['blank' => 10, 'A' => 1, 'B' => 11, 'C' => 17],
            14 => ['blank' => 13, 'A' => 2, 'B' => 11, 'C' => 28, 'D' => 20],
            17 => ['blank' => -11, 'A' => -28, 'B' => -20, 'C' => -6, 'D' => -3],
            18 => ['blank' => 14, 'A' => 28, 'B' => 21, 'C' => 2, 'D' => 5],
            22 => ['blank' => 8, 'A' => 19, 'B' => 14, 'C' => 4, 'D' => 1],
            23 => ['blank' => 11, 'A' => 21, 'B' => 12, 'C' => 5, 'D' => 2],
            25 => ['blank' => 12, 'A' => 32, 'B' => 15, 'C' => 2, 'D' => 7],
            26 => ['blank' => 12, 'A' => 32, 'B' => 20, 'C' => 4, 'D' => 2],
            30 => ['blank' => 9, 'A' => 2, 'B' => 5, 'C' => 16],
            32 => ['blank' => 13, 'A' => 2, 'B' => 9, 'C' => 20],
            35 => ['blank' => 6, 'A' => 14, 'B' => 2, 'C' => 14],
            39 => ['blank' => 5, 'A' => 2, 'B' => 13],
            44 => ['blank' => -9, 'A' => -18, 'B' => -9, 'C' => -4, 'D' => -2]
        ];
        
        // Tabla 5: Pesos para escala J (Implicación en el trabajo)
        $jWeights = [
            3 => ['blank' => 17, 'A' => 24, 'B' => 26, 'C' => 2, 'D' => 9],
            4 => ['blank' => 7, 'A' => 10, 'B' => 11, 'C' => 7, 'D' => 3, 'E' => 1],
            10 => ['blank' => 17, 'A' => 17, 'B' => 20, 'C' => 3],
            11 => ['blank' => 5, 'A' => 9, 'B' => 9, 'C' => 1],
            17 => ['blank' => -9, 'A' => -14, 'B' => -13, 'C' => -7, 'D' => -1],
            21 => ['blank' => 11, 'A' => 5, 'B' => 2, 'C' => 13, 'D' => 14],
            24 => ['blank' => 15, 'A' => 17, 'B' => 17, 'C' => 11, 'D' => 1],
            25 => ['blank' => 19, 'A' => 5, 'B' => 24, 'C' => 24, 'D' => 29],
            30 => ['blank' => 5, 'A' => 1, 'B' => 4, 'C' => 8],
            31 => ['blank' => 7, 'A' => 15, 'B' => 1, 'C' => 6],
            32 => ['blank' => 14, 'A' => 1, 'B' => 9, 'C' => 20],
            33 => ['blank' => -4, 'A' => -1, 'B' => -5, 'C' => -8],
            34 => ['blank' => 16, 'A' => 2, 'B' => 27],
            36 => ['blank' => 11, 'A' => 2, 'B' => 6, 'C' => 19],
            37 => ['blank' => 12, 'A' => 2, 'B' => 19, 'C' => 26],
            38 => ['blank' => 10, 'A' => 2, 'B' => 2, 'C' => 18, 'D' => 19],
            41 => ['blank' => 9, 'A' => 1, 'B' => 7, 'C' => 12],
            44 => ['blank' => -32, 'A' => -42, 'B' => -35, 'C' => -25, 'D' => -4],
            47 => ['blank' => 9, 'A' => 18, 'B' => 4, 'C' => 2],
            48 => ['blank' => 12, 'A' => 14, 'B' => 3, 'C' => 1],
            49 => ['blank' => 15, 'A' => 18, 'B' => 3, 'C' => 2],
            50 => ['blank' => 17, 'A' => 2, 'B' => 11, 'C' => 20, 'D' => 26, 'E' => 28],
            51 => ['blank' => 21, 'A' => 2, 'B' => 9, 'C' => 11, 'D' => 15, 'E' => 31],
            52 => ['blank' => 20, 'A' => 3, 'B' => 31, 'C' => 20]
        ];
        
        // Tabla 6: Pesos para escala H (Conducta competitiva/dura)
        $hWeights = [
            2 => ['blank' => 1, 'A' => 0, 'B' => 1, 'C' => 3],
            3 => ['blank' => 6, 'A' => 8, 'B' => 9, 'C' => 1, 'D' => 6],
            7 => ['blank' => 10, 'A' => 10, 'B' => 5, 'C' => 16],
            15 => ['blank' => 3, 'A' => 7, 'B' => 3, 'C' => 2, 'D' => 8],
            16 => ['blank' => 9, 'A' => 28, 'B' => 9, 'C' => 2, 'D' => 2],
            17 => ['blank' => 3, 'A' => 10, 'B' => 5, 'C' => 1, 'D' => 4],
            18 => ['blank' => -1, 'A' => -2, 'B' => -1, 'C' => 0, 'D' => 0],
            19 => ['blank' => -2, 'A' => -1, 'B' => -1, 'C' => -5],
            20 => ['blank' => 8, 'A' => 15, 'B' => 6, 'C' => 1, 'D' => 4],
            22 => ['blank' => -7, 'A' => -18, 'B' => -11, 'C' => -4, 'D' => -4],
            24 => ['blank' => 5, 'A' => 10, 'B' => 4, 'C' => 1, 'D' => 2],
            26 => ['blank' => 7, 'A' => 16, 'B' => 9, 'C' => 2, 'D' => 9],
            27 => ['blank' => 6, 'A' => 9, 'B' => 3, 'C' => 18],
            29 => ['blank' => 3, 'A' => 1, 'B' => 5, 'C' => 1],
            31 => ['blank' => 12, 'A' => 12, 'B' => 4, 'C' => 16],
            42 => ['blank' => 12, 'A' => 25, 'B' => 7, 'C' => 3, 'D' => 1],
            43 => ['blank' => 11, 'A' => 19, 'B' => 4, 'C' => 3, 'D' => 1],
            45 => ['blank' => 10, 'A' => 18, 'B' => 8, 'C' => 3, 'D' => 1],
            46 => ['blank' => 3, 'A' => 8, 'B' => 3, 'C' => 1, 'D' => 1],
            51 => ['blank' => 10, 'A' => 38, 'B' => 17, 'C' => 17, 'D' => 10, 'E' => 10]
        ];
        
        // Calcular puntuación para cada escala
        foreach ($responses['conducta_habitual'] as $qNum => $qData) {
            // Extraer el número de la pregunta del formato "qXX"
            $questionNum = (int)substr($qNum, 1);
            $answer = $qData['answer'];
            
            // Calcular Escala Tipo A
            if (in_array($questionNum, $typeAItems) && isset($typeAWeights[$questionNum][$answer])) {
                $typeAScore += $typeAWeights[$questionNum][$answer];
            }
            
            // Calcular Escala S (Prisa e impaciencia)
            if (in_array($questionNum, $sItems) && isset($sWeights[$questionNum][$answer])) {
                $sScore += $sWeights[$questionNum][$answer];
            }
            
            // Calcular Escala J (Implicación en el trabajo)
            if (in_array($questionNum, $jItems) && isset($jWeights[$questionNum][$answer])) {
                $jScore += $jWeights[$questionNum][$answer];
            }
            
            // Calcular Escala H (Conducta competitiva/dura)
            if (in_array($questionNum, $hItems) && isset($hWeights[$questionNum][$answer])) {
                $hScore += $hWeights[$questionNum][$answer];
            }
        }
        
        // Almacenar las puntuaciones directas
        $scores = [
            'tipo_a' => [
                'raw_score' => $typeAScore,
                'name' => 'Patrón de Conducta Tipo A (Global)',
                'description' => 'Mide el patrón global de conducta Tipo A caracterizado por urgencia temporal, alta competitividad y hostilidad'
            ],
            'prisa_impaciencia' => [
                'raw_score' => $sScore,
                'name' => 'Prisa e Impaciencia (Factor S)',
                'description' => 'Mide la urgencia temporal y la impaciencia en la conducta cotidiana'
            ],
            'implicacion_trabajo' => [
                'raw_score' => $jScore,
                'name' => 'Implicación en el Trabajo (Factor J)',
                'description' => 'Mide el compromiso y la dedicación al trabajo'
            ],
            'competitividad' => [
                'raw_score' => $hScore,
                'name' => 'Conducta Competitiva/Dura (Factor H)',
                'description' => 'Mide la tendencia a la competición y la dureza en las relaciones interpersonales'
            ]
        ];
        
        // Interpretación de las puntuaciones
        // Nota: Estas son interpretaciones aproximadas. En una implementación real,
        // se deberían utilizar baremos estandarizados por edad, sexo, ocupación, etc.
        
        // Para el Patrón de Conducta Tipo A (Global)
        if ($typeAScore > 250) {
            $interpretations['tipo_a'] = "Perfil de Tipo A muy marcado";
        } elseif ($typeAScore > 150) {
            $interpretations['tipo_a'] = "Perfil de Tipo A moderado";
        } elseif ($typeAScore > 0) {
            $interpretations['tipo_a'] = "Perfil con algunos rasgos de Tipo A";
        } else {
            $interpretations['tipo_a'] = "Perfil predominantemente de Tipo B";
        }
        
        // Para el Factor S (Prisa e Impaciencia)
        if ($sScore > 150) {
            $interpretations['prisa_impaciencia'] = "Elevado nivel de prisa e impaciencia";
        } elseif ($sScore > 100) {
            $interpretations['prisa_impaciencia'] = "Nivel moderado-alto de prisa e impaciencia";
        } elseif ($sScore > 50) {
            $interpretations['prisa_impaciencia'] = "Nivel moderado de prisa e impaciencia";
        } else {
            $interpretations['prisa_impaciencia'] = "Bajo nivel de prisa e impaciencia";
        }
        
        // Para el Factor J (Implicación en el Trabajo)
        if ($jScore > 200) {
            $interpretations['implicacion_trabajo'] = "Elevada implicación en el trabajo";
        } elseif ($jScore > 100) {
            $interpretations['implicacion_trabajo'] = "Implicación moderada-alta en el trabajo";
        } elseif ($jScore > 0) {
            $interpretations['implicacion_trabajo'] = "Implicación moderada en el trabajo";
        } else {
            $interpretations['implicacion_trabajo'] = "Baja implicación en el trabajo";
        }
        
        // Para el Factor H (Conducta Competitiva/Dura)
        if ($hScore > 150) {
            $interpretations['competitividad'] = "Elevada competitividad y dureza en relaciones";
        } elseif ($hScore > 75) {
            $interpretations['competitividad'] = "Competitividad y dureza moderada-alta";
        } elseif ($hScore > 25) {
            $interpretations['competitividad'] = "Competitividad y dureza moderada";
        } else {
            $interpretations['competitividad'] = "Baja competitividad y dureza";
        }
        
        // Determinar el tipo de riesgo coronario basado en la puntuación global
        $riskLevel = '';
        if ($typeAScore > 250 && ($sScore > 150 || $hScore > 150)) {
            $riskLevel = "Alto riesgo de trastornos coronarios asociados al estrés";
        } elseif ($typeAScore > 150) {
            $riskLevel = "Riesgo moderado de trastornos coronarios asociados al estrés";
        } else {
            $riskLevel = "Bajo riesgo de trastornos coronarios asociados al estrés";
        }
        
        // Generar resumen clínico basado en los patrones
        $summary = $this->generateJasSummary($typeAScore, $sScore, $hScore, $jScore);
        
        // Asignar resultados
        $result['scores'] = $scores;
        $result['interpretations'] = $interpretations;
        $result['risk_level'] = $riskLevel;
        $result['summary'] = $summary;
        $result['scoring_type'] = 'JAS';
        $result['questionnaire_name'] = 'Jenkins Activity Survey (JAS)';
        
        return $result;
    }
    
    /**
     * Generates a clinical summary based on the JAS scores
     *
     * @param int $typeAScore Global Type A score
     * @param int $sScore Speed and Impatience score
     * @param int $hScore Hard-driving/competitive score
     * @param int $jScore Job involvement score
     * @return string Clinical summary
     */
    private function generateJasSummary($typeAScore, $sScore, $hScore, $jScore): string
    {
        if ($typeAScore > 250) {
            if ($sScore > 150 && $hScore > 150) {
                return "Presenta un patrón de conducta Tipo A muy marcado, caracterizado por elevados niveles de prisa/impaciencia y comportamiento competitivo/hostil. Este patrón está asociado con un alto riesgo de trastornos coronarios relacionados con el estrés. Se recomienda implementar estrategias para manejar la urgencia temporal y modificar patrones de comportamiento hostil.";
            } elseif ($sScore > 150) {
                return "Muestra un patrón de conducta Tipo A muy marcado, con un componente predominante de prisa e impaciencia. Este patrón está asociado con riesgo coronario elevado. Se beneficiaría de técnicas para reducir la urgencia temporal en actividades cotidianas.";
            } elseif ($hScore > 150) {
                return "Presenta un patrón de conducta Tipo A muy marcado, con un componente predominante de competitividad y hostilidad. Este patrón está asociado con riesgo coronario elevado. Sería recomendable desarrollar habilidades interpersonales que reduzcan la hostilidad y la excesiva competitividad.";
            } else {
                return "Muestra un patrón de conducta Tipo A muy marcado, con elevada implicación en el trabajo. Este patrón puede aumentar el riesgo de problemas coronarios. Se recomienda equilibrar la dedicación laboral con otras áreas de la vida.";
            }
        } elseif ($typeAScore > 150) {
            if ($sScore > 100 && $jScore > 100) {
                return "Presenta un patrón de conducta Tipo A moderado, caracterizado por impaciencia significativa y alta implicación laboral. Este patrón puede estar asociado con un riesgo coronario moderado. Podría beneficiarse de técnicas de manejo del tiempo y establecimiento de límites en el ámbito laboral.";
            } elseif ($hScore > 75 && $jScore > 100) {
                return "Muestra un patrón de conducta Tipo A moderado, con componentes de competitividad y alta implicación laboral. Este patrón puede asociarse con un riesgo coronario moderado. Sería conveniente desarrollar un estilo de afrontamiento menos competitivo y más cooperativo.";
            } else {
                return "Presenta características moderadas del patrón de conducta Tipo A. Existe cierto riesgo de problemas coronarios asociados al estrés, aunque no es elevado. Se recomienda mantener el equilibrio entre las distintas áreas vitales y atender a los momentos de tensión excesiva.";
            }
        } else {
            if ($sScore > 100 || $hScore > 75) {
                return "Aunque no presenta un patrón global de conducta Tipo A marcado, muestra algunos rasgos específicos que podrían beneficiarse de atención. El riesgo coronario asociado a estos patrones es bajo. Se recomienda mantener los hábitos de equilibrio que ya practica.";
            } else {
                return "Presenta predominantemente un patrón de conducta Tipo B, caracterizado por un enfoque más relajado y menos competitivo. El riesgo coronario asociado a este patrón es bajo. Este estilo de comportamiento suele favorecer un mejor manejo del estrés.";
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
        
        // Añadir puntuaciones de escalas del JAS
        if (isset($questionnaireResults['scores']) && !empty($questionnaireResults['scores'])) {
            $promptSection .= "### Escalas de Conducta Tipo A (JAS):\n";
            
            // Escala global primero
            if (isset($questionnaireResults['scores']['tipo_a'])) {
                $score = $questionnaireResults['scores']['tipo_a'];
                $promptSection .= "- **" . ($score['name'] ?? 'Patrón de Conducta Tipo A (Global)') . "**: ";
                $promptSection .= "Puntuación " . $score['raw_score'];
                
                if (isset($questionnaireResults['interpretations']['tipo_a'])) {
                    $promptSection .= " - " . $questionnaireResults['interpretations']['tipo_a'];
                }
                
                $promptSection .= "\n\n";
            }
            
            // Factores específicos
            $promptSection .= "#### Factores específicos:\n";
            $factors = [
                'prisa_impaciencia' => 'Prisa e Impaciencia (Factor S)',
                'implicacion_trabajo' => 'Implicación en el Trabajo (Factor J)',
                'competitividad' => 'Conducta Competitiva/Dura (Factor H)'
            ];
            
            foreach ($factors as $factorKey => $factorName) {
                if (isset($questionnaireResults['scores'][$factorKey])) {
                    $score = $questionnaireResults['scores'][$factorKey];
                    $promptSection .= "- **" . ($score['name'] ?? $factorName) . "**: ";
                    $promptSection .= "Puntuación " . $score['raw_score'];
                    
                    if (isset($questionnaireResults['interpretations'][$factorKey])) {
                        $promptSection .= " - " . $questionnaireResults['interpretations'][$factorKey];
                    }
                    
                    $promptSection .= "\n";
                }
            }
            $promptSection .= "\n";
        }
        
        // Añadir nivel de riesgo coronario
        if (isset($questionnaireResults['risk_level']) && !empty($questionnaireResults['risk_level'])) {
            $promptSection .= "### Evaluación de riesgo coronario:\n";
            $promptSection .= $questionnaireResults['risk_level'] . "\n\n";
        }
        
        // Añadir resumen global
        if (isset($questionnaireResults['summary']) && !empty($questionnaireResults['summary'])) {
            $promptSection .= "### Resumen global del JAS:\n";
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
        $defaultInstructions = "Basándote en los resultados del Inventario de Actividad de Jenkins (JAS), proporciona:\n\n" .
               "1. Una interpretación clínica detallada del patrón de conducta Tipo A/B identificado.\n" .
               "2. Análisis de los factores específicos (prisa/impaciencia, implicación en el trabajo, competitividad).\n" .
               "3. Evaluación del nivel de riesgo coronario asociado a este patrón de conducta.\n" .
               "4. Recomendaciones específicas para:\n" .
               "   - Manejar la urgencia temporal y la impaciencia.\n" .
               "   - Equilibrar la dedicación al trabajo.\n" .
               "   - Moderar la competitividad y hostilidad (si están presentes).\n" .
               "5. Consideraciones sobre las implicaciones para la salud física y psicológica.\n" .
            "6. Estrategias de intervención para reducir el riesgo coronario y mejorar la calidad de vida.\n";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }
} 