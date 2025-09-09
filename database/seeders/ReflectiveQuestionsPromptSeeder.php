<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\QuestionnairePrompt;

class ReflectiveQuestionsPromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the REFLECTIVE_QUESTIONS questionnaire ID
        $questionnaire = \App\Models\Questionnaire::where('scoring_type', 'REFLECTIVE_QUESTIONS')->first();
        
        if (!$questionnaire) {
            $this->command->error('REFLECTIVE_QUESTIONS questionnaire not found');
            return;
        }
        
        // Check if prompt already exists for this questionnaire
        $existingPrompt = QuestionnairePrompt::where('questionnaire_id', $questionnaire->id)->first();
        
        if ($existingPrompt) {
            // Update existing prompt
            $existingPrompt->update([
                'prompt' => $this->getReflectiveQuestionsPrompt(),
                'is_active' => true
            ]);
            $this->command->info('Updated existing REFLECTIVE_QUESTIONS prompt');
        } else {
            // Create new prompt
            QuestionnairePrompt::create([
                'questionnaire_id' => $questionnaire->id,
                'prompt' => $this->getReflectiveQuestionsPrompt(),
                'is_active' => true
            ]);
            $this->command->info('Created new REFLECTIVE_QUESTIONS prompt');
        }
    }

    /**
     * Get the complete REFLECTIVE_QUESTIONS system prompt
     */
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