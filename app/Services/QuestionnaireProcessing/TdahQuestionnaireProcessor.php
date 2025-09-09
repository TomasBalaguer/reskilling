<?php

namespace App\Services\QuestionnaireProcessing;

use App\Models\QuestionnaireSecondaryAssignment;
use App\Services\QuestionnaireProcessing\Traits\HasQuestionnairePrompt;
use Illuminate\Support\Facades\Log;
class TdahQuestionnaireProcessor extends BaseQuestionnaireProcessor
{
    use HasQuestionnairePrompt;

    protected $type = 'TDAH';

    protected array $infanciaScores = [
        'NADA' => 0,
        'POCO' => 1,
        'MODERADO' => 2,
        'MUCHO' => 3
    ];

    protected array $actualidadScores = [
        'NUNCA' => 0,
        'RARA_VEZ' => 1,
        'ALGUNAS_VECES' => 2,
        'CON_FRECUENCIA' => 3,
        'MUY_FRECUENTEMENTE' => 4
    ];

    protected array $dimensiones = [
        'inatencion' => [
            'infancia' => [2, 5, 8, 13],
            'actualidad' => [1, 2, 3, 4, 6, 7, 8, 9, 11]
        ],
        'hiperactividad_impulsividad' => [
            'infancia' => [1, 4, 10, 16],
            'actualidad' => [5, 10, 12, 13, 15, 16, 17, 18]
        ],
        'regulacion_emocional' => [
            'infancia' => [3, 6, 7, 12, 14, 15, 18, 19, 20],
            'actualidad' => [14]
        ]
    ];

    public function calculateScores(array $responses, array $patientData = [], array $result = []): array
    {
        $scores = [
            'infancia' => $this->calculateSectionScores($responses['infancia'], $this->infanciaScores),
            'actualidad' => $this->calculateSectionScores($responses['actualidad'], $this->actualidadScores)
        ];


        return [
            'scores' => $scores,
            'interpretations' => $this->interpretScores($scores),
            'clinical_interpretations' => $this->generateClinicalInterpretations($scores),
            'summary' => $this->generateSummary($scores),
            'scoring_type' => 'TDAH',
            'questionnaire_name' => 'Inventario de Síntomas de TDAH (TDAH)',
            'responses' => $responses
        ];
    }

    protected function generateClinicalInterpretations(array $scores): array
    {
        $clinicalInterpretations = [];  
        foreach (['infancia', 'actualidad'] as $period) {
            $clinicalInterpretations[$period] = [];
            foreach ($scores[$period] as $dimension => $score) {
                $clinicalInterpretations[$period][$dimension] = $this->getClinicalInterpretation($score);
            }
        }
        return $clinicalInterpretations;
    }

    protected function getClinicalInterpretation(int $score): string
    {
        if ($score <= 7) {
            return 'No indica TDAH significativo';
        } elseif ($score <= 15) {
            return 'Puede indicar síntomas leves a moderados';
        } else {
            return 'Sugiere síntomas marcados de TDAH';
        }
    }

    protected function calculateSectionScores(array $responses, array $scoreMapping): array
    {
        $dimensionScores = [
            'inatencion' => 0,
            'hiperactividad_impulsividad' => 0,
            'regulacion_emocional' => 0
        ];

        $section = array_key_exists('q1', $responses) ? 'infancia' : 'actualidad';

        foreach ($responses as $questionId => $response) {
            $questionNumber = (int)substr($questionId, 1);
            
            foreach ($this->dimensiones as $dimension => $questions) {
                if (in_array($questionNumber, $questions[$section])) {
                    $dimensionScores[$dimension] += $scoreMapping[$response['answer']];
                }
            }
        }

        return $dimensionScores;
    }

    protected function generateSummary(array $scores): string
    {
        $summary = '';
        foreach (['infancia', 'actualidad'] as $period) {
            $summary .= ucfirst($period) . ":\n";
        }
        return $summary;
    }

    protected function interpretScores(array $scores): array
    {
        $interpretations = [];
        foreach (['infancia', 'actualidad'] as $period) {
            $interpretations[$period] = [];
            foreach ($scores[$period] as $dimension => $score) {
                $interpretations[$period][$dimension] = $this->getDimensionInterpretation($score);
            }
        }

        return [
            'individual' => $interpretations,
            'evolution' => $this->analyzeEvolution($scores)
        ];
    }

    protected function getDimensionInterpretation(int $score): string
    {
        if ($score <= 7) {
            return 'No indica TDAH significativo';
        } elseif ($score <= 15) {
            return 'Puede indicar síntomas leves a moderados';
        } else {
            return 'Sugiere síntomas marcados de TDAH';
        }
    }

    protected function analyzeEvolution(array $scores): string
    {
        $infanciaTotal = array_sum($scores['infancia']);
        $actualidadTotal = array_sum($scores['actualidad']);
        
        if ($actualidadTotal < $infanciaTotal * 0.5) {
            return 'Se observa una mejora significativa de los síntomas con respecto a la infancia';
        } elseif ($actualidadTotal < $infanciaTotal * 0.8) {
            return 'Se observa una mejora moderada de los síntomas con respecto a la infancia';
        } elseif ($actualidadTotal <= $infanciaTotal * 1.2) {
            return 'Los síntomas se mantienen similares a los de la infancia';
        } else {
            return 'Se observa un incremento en la intensidad de los síntomas respecto a la infancia';
        }
    }

    public function buildPromptSection(array $questionnaireResults): string
    {

        $scores = $questionnaireResults['scores'];
        $prompt = "Análisis de Cuestionario TDAH:\n\n";
        // Análisis de la infancia
        $prompt .= "Período de Infancia:\n";
        foreach ($scores['infancia'] as $dimension => $score) {
            $prompt .= "- " . ucfirst(str_replace('_', ' ', $dimension)) . ": $score puntos\n";
        }
        // Análisis de la actualidad
        $prompt .= "\nPeríodo Actual (últimos 6 meses):\n";
        foreach ($scores['actualidad'] as $dimension => $score) {
            $prompt .= "- " . ucfirst(str_replace('_', ' ', $dimension)) . ": $score puntos\n";
        }

        try {
            foreach ($questionnaireResults['responses'] as $key => $response) {
                $prompt .= "### Respuestas del paciente:\n";
                $prompt .= "- " .$key . ":\n";
                foreach ($response as $question => $answer) {
                foreach ($answer as $key => $value) {
                    $prompt .= "- " . $key . ": " . $value . "\n";
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
        $defaultInstructions = "Basándote en los resultados del Inventario de Síntomas de TDAH (TDAH), proporciona:\n\n" .
               "1. Una interpretación detallada del nivel de severidad de los síntomas de TDAH.\n" .
               "2. Análisis de las dimensiones cognitivo-afectiva y somática.\n" .
               "3. Identificación de áreas que requieren atención clínica inmediata.\n" .   
               "4. Recomendaciones específicas basadas en el perfil de síntomas.\n" .
               "5. Consideración de factores de riesgo y aspectos que requieren seguimiento.";

        return $this->getInstructionsWithPrompt($defaultInstructions);
    }

    public function processCollectiveResponses(array $responses): array
    {
        $allScores = [];
        $respondentTypes = [];
        $respondentDetails = [];

        foreach ($responses as $response) {
            $scores = $this->calculateScores($response['responses']);
            $allScores[] = $scores;
            
            // Obtener información del respondente
            $respondentType = $response['respondent_type'] ?? 'patient';
            $respondentTypes[] = $respondentType;
            
            $respondentDetails[] = [
                'type' => $respondentType,
                'name' => $response['respondent_name'] ?? null,
                'relationship' => $response['respondent_relationship'] ?? null,
                'scores' => $scores['scores']
            ];
        }

        return [
            'individual_scores' => $allScores,
            'respondent_details' => $respondentDetails,
            'collective_analysis' => $this->generateCollectiveAnalysis($allScores, $respondentTypes, $respondentDetails)
        ];
    }

    protected function generateCollectiveAnalysis(array $allScores, array $respondentTypes, array $respondentDetails): array
    {
        $averageScores = [
            'infancia' => [
                'inatencion' => 0,
                'hiperactividad_impulsividad' => 0,
                'regulacion_emocional' => 0
            ],
            'actualidad' => [
                'inatencion' => 0,
                'hiperactividad_impulsividad' => 0,
                'regulacion_emocional' => 0
            ]
        ];

        $count = count($allScores);

        // Calcular promedios
        foreach ($allScores as $scores) {
            foreach (['infancia', 'actualidad'] as $period) {
                foreach ($scores['scores'][$period] as $dimension => $score) {
                    $averageScores[$period][$dimension] += $score / $count;
                }
            }
        }

        return [
            'average_scores' => $averageScores,
            'interpretation' => $this->interpretCollectiveScores($averageScores),
            'respondent_comparison' => $this->compareRespondents($respondentDetails),
            'prompt' => $this->buildCollectivePrompt($averageScores, $respondentTypes, $respondentDetails)
        ];
    }

    protected function interpretCollectiveScores(array $averageScores): array
    {
        $interpretation = [];
        
        foreach (['infancia', 'actualidad'] as $period) {
            $interpretation[$period] = [];
            foreach ($averageScores[$period] as $dimension => $score) {
                $interpretation[$period][$dimension] = $this->getDimensionInterpretation((int)$score);
            }
        }

        return $interpretation;
    }

    protected function compareRespondents(array $respondentDetails): array
    {
        $comparison = [];
        
        foreach (['infancia', 'actualidad'] as $period) {
            $comparison[$period] = [];
            foreach (['inatencion', 'hiperactividad_impulsividad', 'regulacion_emocional'] as $dimension) {
                $comparison[$period][$dimension] = [];
                
                foreach ($respondentDetails as $respondent) {
                    $comparison[$period][$dimension][] = [
                        'respondent_type' => $respondent['type'],
                        'respondent_name' => $respondent['name'],
                        'relationship' => $respondent['relationship'],
                        'score' => $respondent['scores'][$period][$dimension]
                    ];
                }
                
                // Ordenar por puntuación para facilitar comparación
                usort($comparison[$period][$dimension], function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
            }
        }
        
        return $comparison;
    }

    protected function buildCollectivePrompt(array $averageScores, array $respondentTypes, array $respondentDetails): string
    {
        $prompt = "Análisis Colectivo de Cuestionario TDAH:\n\n";
        $prompt .= "Respondentes: " . implode(", ", array_unique($respondentTypes)) . "\n\n";

        // Detalles individuales por respondente
        $prompt .= "Puntuaciones por Respondente:\n";
        foreach ($respondentDetails as $respondent) {
            $prompt .= "\n" . ucfirst($respondent['type']);
            if ($respondent['name']) {
                $prompt .= " - {$respondent['name']}";
            }
            if ($respondent['relationship']) {
                $prompt .= " ({$respondent['relationship']})";
            }
            $prompt .= ":\n";
            
            foreach (['infancia', 'actualidad'] as $period) {
                $prompt .= "  " . ucfirst($period) . ":\n";
                foreach ($respondent['scores'][$period] as $dimension => $score) {
                    $prompt .= "    - " . ucfirst(str_replace('_', ' ', $dimension)) . ": $score puntos\n";
                }
            }
        }

        // Promedios generales
        $prompt .= "\nPromedios Generales:\n";
        foreach (['infancia', 'actualidad'] as $period) {
            $prompt .= ucfirst($period) . ":\n";
            foreach ($averageScores[$period] as $dimension => $score) {
                $prompt .= "- " . ucfirst(str_replace('_', ' ', $dimension)) . ": " . 
                          number_format($score, 2) . " puntos (promedio)\n";
            }
            $prompt .= "\n";
        }

        return $prompt;
    }
} 