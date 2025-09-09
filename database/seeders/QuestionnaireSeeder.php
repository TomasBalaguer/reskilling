<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Questionnaire;
use App\Models\QuestionnairePrompt;

class QuestionnaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create REFLECTIVE_QUESTIONS questionnaire
        $reflectiveQuestionnaire = Questionnaire::create([
            'name' => 'Preguntas Reflexivas',
            'description' => 'Cuestionario de preguntas reflexivas para evaluación de habilidades blandas mediante respuestas de audio',
            'scoring_type' => 'REFLECTIVE_QUESTIONS',
            'questions' => [
                'q1' => 'Si pudieras mandarte un mensaje a vos mismo/a hace unos años, ¿qué te dirías sobre quién sos hoy y lo que fuiste aprendiendo de vos?',
                'q2' => 'Contame una vez en la que algo que te importaba no salió como esperabas. ¿Qué hiciste después? ¿Qué aprendiste de eso?',
                'q3' => 'Tuviste que decidir entre seguir con algo que querías o cambiar de camino por algo nuevo. ¿Qué hiciste? ¿Cómo lo pensaste?',
                'q4' => 'Contame alguna situacion, en un grupo de estudio, equipo o trabajo en donde algo no funcionaba (alguien no participaba, hubo malentendidos o tensión). ¿Cómo lo manejaste? ¿Qué dijiste o hiciste?',
                'q5' => 'Contame una vez que resolviste un problema de una manera poco común. ¿Qué hiciste diferente y por qué creés que funcionó?',
                'q6' => '¿En qué cosas te dan ganas de esforzarte hoy? ¿Qué te gustaría lograr a futuro (en la carrera, en tu vida o en lo que hacés)?',
                'q7' => 'Lee el siguiente relato y contesta las preguntas: "Después de meses trabajando en mi idea, finalmente presenté mi Proyecto frente a un grupo de profesores..." ¿Qué creés que sintió esa persona? ¿Qué harías en su lugar? ¿Qué le dirías si fuera parte de tu equipo o un amigo?'
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

        $this->command->info('Cuestionario creado exitosamente:');
        $this->command->info('- Preguntas Reflexivas (REFLECTIVE_QUESTIONS)');
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
               
               "### 14. CURIOSIDAD Y CAPACIDAD DE APRENDIZAJE (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "### 15. TOLERANCIA A LA FRUSTRACIÓN (Puntaje: X/10)\n" .
               "Descripción basada en contenido + análisis prosódico\n\n" .
               
               "## PUNTOS FUERTES\n" .
               "Las competencias con más puntaje y por qué se destacan.\n\n" .
               
               "## ÁREAS A DESARROLLAR\n" .
               "Mínimo 3 o 4 competencias donde se observaron los puntajes más bajos y por qué.\n\n" .
               
               "## PROPUESTA DE RE-SKILLING PERSONALIZADA\n" .
               "Proponer recomendaciones específicas y prácticas para desarrollar los puntos débiles identificados.\n\n" .
               
               "**METODOLOGÍA DE ANÁLISIS:**\n" .
               "- Integra SIEMPRE contenido textual + indicadores prosódicos (pausas, titubeos, energía vocal, emociones, etc.)\n" .
               "- Utiliza citas directas de las respuestas cuando sea relevante\n" .
               "- Correlaciona lo que dice vs. cómo lo dice para identificar autenticidad y coherencia\n" .
               "- Considera patrones emocionales y de estrés en la voz para evaluar regulación emocional y manejo del estrés\n" .
               "- Evalúa claridad de dicción y fluidez para competencias comunicacionales\n" .
               "- Tono profesional pero accesible para el candidato";
    }
}