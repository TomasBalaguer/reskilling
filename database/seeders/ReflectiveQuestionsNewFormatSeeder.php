<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Questionnaire;
use App\Models\QuestionnairePrompt;

class ReflectiveQuestionsNewFormatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder demonstrates the new question format with title and skills fields
     */
    public function run(): void
    {
        // Create REFLECTIVE_QUESTIONS questionnaire with new format
        $reflectiveQuestionnaire = Questionnaire::create([
            'name' => 'Preguntas Reflexivas - Nuevo Formato',
            'description' => 'Cuestionario de preguntas reflexivas para evaluación de habilidades blandas mediante respuestas de audio',
            'scoring_type' => 'REFLECTIVE_QUESTIONS',
            'questions' => [
                'q1' => [
                    'question' => "Si pudieras mandarte un mensaje a vos mismo/a hace unos años, ¿qué te dirías sobre quién sos hoy y lo que fuiste aprendiendo de vos?\n\n¿Qué aspectos de tu personalidad han cambiado?\n¿Cuáles se mantienen?",
                    'title' => 'Reflexión Personal',
                    'skills' => 'Autoconocimiento, Introspección, Crecimiento personal',
                    'type' => 'audio'
                ],
                'q2' => [
                    'question' => "Contame una vez en la que algo que te importaba no salió como esperabas.\n\n¿Qué hiciste después?\n¿Qué aprendiste de eso?",
                    'title' => 'Superación de Adversidades',
                    'skills' => 'Resiliencia, Adaptabilidad, Aprendizaje continuo',
                    'type' => 'audio'
                ],
                'q3' => [
                    'question' => "Tuviste que decidir entre seguir con algo que querías o cambiar de camino por algo nuevo.\n\n¿Qué hiciste?\n¿Cómo lo pensaste?",
                    'title' => 'Toma de Decisiones',
                    'skills' => 'Pensamiento crítico, Análisis, Evaluación de opciones',
                    'type' => 'audio'
                ],
                'q4' => [
                    'question' => "Contame alguna situación, en un grupo de estudio, equipo o trabajo en donde algo no funcionaba (alguien no participaba, hubo malentendidos o tensión).\n\n¿Cómo lo manejaste?\n¿Qué dijiste o hiciste?",
                    'title' => 'Gestión de Conflictos',
                    'skills' => 'Liderazgo, Comunicación efectiva, Resolución de problemas',
                    'type' => 'audio'
                ],
                'q5' => [
                    'question' => "Contame una vez que resolviste un problema de una manera poco común.\n\n¿Qué hiciste diferente?\n¿Por qué creés que funcionó?",
                    'title' => 'Innovación y Creatividad',
                    'skills' => 'Creatividad, Pensamiento lateral, Innovación',
                    'type' => 'audio'
                ],
                'q6' => [
                    'question' => "¿En qué cosas te dan ganas de esforzarte hoy?\n\n¿Qué te gustaría lograr a futuro (en la carrera, en tu vida o en lo que hacés)?",
                    'title' => 'Motivación y Objetivos',
                    'skills' => 'Motivación intrínseca, Planificación, Visión de futuro',
                    'type' => 'audio'
                ],
                'q7' => [
                    'question' => "Lee el siguiente relato y contesta las preguntas:\n\n\"Después de meses trabajando en mi idea, finalmente presenté mi Proyecto frente a un grupo de profesores...\"\n\n¿Qué creés que sintió esa persona?\n¿Qué harías en su lugar?\n¿Qué le dirías si fuera parte de tu equipo o un amigo?",
                    'title' => 'Empatía y Comprensión',
                    'skills' => 'Empatía, Inteligencia emocional, Apoyo interpersonal',
                    'type' => 'audio'
                ]
            ],
            'max_duration_minutes' => 5,
            'is_active' => true,
        ]);

        // Create prompt for REFLECTIVE_QUESTIONS
        QuestionnairePrompt::create([
            'questionnaire_id' => $reflectiveQuestionnaire->id,
            'prompt' => $this->getReflectiveQuestionsPrompt(),
            'is_active' => true,
        ]);

        $this->command->info('Cuestionario con nuevo formato creado exitosamente:');
        $this->command->info('- Preguntas Reflexivas con títulos y skills (REFLECTIVE_QUESTIONS)');
    }

    private function getReflectiveQuestionsPrompt(): string
    {
        return "INFORME PSICOLÓGICO INTEGRAL DE HABILIDADES BLANDAS - ANÁLISIS TEXTO + PROSODIA\n\n" .
               
               "Necesito un informe lo más detallado posible de esta persona, utilizando el contenido de la respuesta + prosodia. " .
               "Como si fueses un psicólogo especialista en Habilidades Blandas y Personalidad. El informe tiene que estar escrito en tono " .
               "profesional, descriptivo y teniendo en cuenta que lo lee directamente el candidato. El candidato es una persona que quiere " .
               "conocer en profundidad todas sus habilidades como punto de partida para desarrollar algunas.\n\n" .
               
               "**ESTRUCTURA REQUERIDA DEL INFORME:**\n\n" .
               
               "## RESUMEN DESCRIPTIVO DE PERSONALIDAD\n" .
               "Una descripción integral de los patrones de personalidad observados, integrando tanto las respuestas verbales como los indicadores prosódicos y emocionales.\n\n" .
               
               "## ANÁLISIS DETALLADO DE COMPETENCIAS\n" .
               "Para cada competencia, proporciona un **puntaje del 1 al 10** (siendo 10 muy alto y 1 muy pobre) + **descripción detallada** " .
               "de esa competencia en esta persona. Puedes utilizar frases que usó para ejemplificar.\n\n" .
               
               "### 1. PERSEVERANCIA (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 2. RESILIENCIA (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 3. PENSAMIENTO CRÍTICO Y ADAPTABILIDAD (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 4. REGULACIÓN EMOCIONAL (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 5. RESPONSABILIDAD (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 6. AUTOCONOCIMIENTO (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 7. MANEJO DEL ESTRÉS (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 8. ASERTIVIDAD (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 9. HABILIDAD PARA CONSTRUIR RELACIONES (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 10. CREATIVIDAD (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 11. EMPATÍA (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 12. CAPACIDAD DE INFLUENCIA Y COMUNICACIÓN (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 13. CAPACIDAD Y ESTILO DE LIDERAZGO (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 14. TRABAJO EN EQUIPO (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 15. ORIENTACIÓN AL LOGRO (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "## TABLA DE PUNTAJES FINALES\n" .
               "| Competencia | Puntaje |\n" .
               "|-------------|---------|";
    }
}