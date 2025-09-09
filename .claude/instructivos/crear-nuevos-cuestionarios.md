# 📋 Guía para Crear Nuevos Cuestionarios

Esta guía explica paso a paso cómo crear nuevos tipos de cuestionarios en el sistema de evaluación B2B.

## 🏗️ Arquitectura del Sistema

El sistema usa **Strategy Pattern** para manejar diferentes tipos de cuestionarios de manera escalable:

- **Enums**: Definen los tipos disponibles
- **Strategies**: Implementan la lógica específica de cada tipo
- **Factory**: Gestiona y crea las strategies
- **Resources**: Presentan los datos de manera limpia

## 🚀 Pasos para Crear un Nuevo Tipo de Cuestionario

### 1. Agregar el Nuevo Tipo al Enum

**Archivo:** `app/Enums/QuestionnaireType.php`

```php
enum QuestionnaireType: string
{
    // Tipos existentes...
    case NUEVO_TIPO = 'NUEVO_TIPO';

    public function getDisplayName(): string
    {
        return match($this) {
            // Casos existentes...
            self::NUEVO_TIPO => 'Mi Nuevo Cuestionario',
        };
    }

    public function getResponseFormat(): string
    {
        return match($this) {
            // Casos existentes...
            self::NUEVO_TIPO => 'formato_personalizado',
        };
    }

    public function requiresAIProcessing(): bool
    {
        return match($this) {
            // Casos existentes...
            self::NUEVO_TIPO => true, // o false según necesites
        };
    }

    public function getStrategyClass(): string
    {
        return match($this) {
            // Casos existentes...
            self::NUEVO_TIPO => 'App\Services\Questionnaire\Types\NuevoTipoStrategy',
        };
    }
}
```

### 2. Crear el Strategy del Nuevo Tipo

**Archivo:** `app/Services/Questionnaire/Types/NuevoTipoStrategy.php`

```php
<?php

namespace App\Services\Questionnaire\Types;

use App\Enums\QuestionType;
use App\Services\Questionnaire\AbstractQuestionnaireStrategy;
use App\Models\Questionnaire;

class NuevoTipoStrategy extends AbstractQuestionnaireStrategy
{
    public function buildStructure(Questionnaire $questionnaire): array
    {
        if ($questionnaire->structure) {
            return $questionnaire->structure;
        }

        return [
            'metadata' => [
                'evaluation_type' => 'tu_tipo_evaluacion',
                'response_format' => 'tu_formato_respuesta',
                'scoring_method' => 'tu_metodo_puntuacion',
                // Agrega metadatos específicos de tu tipo
            ],
            'sections' => [
                [
                    'id' => 'seccion_principal',
                    'title' => 'Título de tu sección',
                    'description' => 'Descripción de tu sección',
                    'instructions' => [
                        'Instrucción 1',
                        'Instrucción 2',
                    ],
                    'questions' => $this->transformQuestionsToStructure($questionnaire->questions ?? []),
                    'response_type' => 'tu_tipo_respuesta'
                ]
            ]
        ];
    }

    public function calculateScores(array $processedResponses, array $respondentData = []): array
    {
        // Implementa tu lógica de puntuación específica
        return [
            'scoring_type' => 'NUEVO_TIPO',
            'questionnaire_name' => 'Mi Nuevo Cuestionario',
            // Agrega los campos específicos de tu análisis
            'custom_analysis' => $this->performCustomAnalysis($processedResponses),
            'summary' => $this->generateSummary($processedResponses, $respondentData),
        ];
    }

    public function requiresAIProcessing(): bool
    {
        return true; // o false según tu implementación
    }

    public function getSupportedQuestionTypes(): array
    {
        return [
            QuestionType::CUSTOM_TYPE->value, // Define los tipos que soportas
        ];
    }

    protected function getEstimatedDuration(): int
    {
        return 20; // Duración estimada en minutos
    }

    // Métodos privados para tu lógica específica
    private function transformQuestionsToStructure(array $questions): array
    {
        // Transforma las preguntas al formato esperado por el frontend
        $transformedQuestions = [];
        
        foreach ($questions as $id => $questionData) {
            $transformedQuestions[] = [
                'id' => $id,
                'text' => is_array($questionData) ? $questionData['text'] : $questionData,
                'type' => 'tu_tipo_pregunta',
                'required' => true,
                // Agrega propiedades específicas
            ];
        }

        return $transformedQuestions;
    }

    private function performCustomAnalysis(array $processedResponses): array
    {
        // Implementa tu análisis personalizado
        return [];
    }

    private function generateSummary(array $processedResponses, array $respondentData): string
    {
        // Genera un resumen de los resultados
        return "Resumen personalizado de tu cuestionario";
    }
}
```

### 3. Agregar Nuevos Tipos de Pregunta (Si es necesario)

**Archivo:** `app/Enums/QuestionType.php`

```php
enum QuestionType: string
{
    // Tipos existentes...
    case CUSTOM_TYPE = 'custom_type';

    public function getDisplayName(): string
    {
        return match($this) {
            // Casos existentes...
            self::CUSTOM_TYPE => 'Mi Tipo Personalizado',
        };
    }
}
```

### 4. Crear Seeder para el Nuevo Cuestionario

**Archivo:** `database/seeders/NuevoTipoSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Questionnaire;
use App\Enums\QuestionnaireType;

class NuevoTipoSeeder extends Seeder
{
    public function run(): void
    {
        Questionnaire::create([
            'name' => 'Mi Nuevo Cuestionario',
            'description' => 'Descripción del nuevo cuestionario',
            'scoring_type' => 'NUEVO_TIPO',
            'questionnaire_type' => QuestionnaireType::NUEVO_TIPO,
            'questions' => [
                'q1' => [
                    'text' => '¿Primera pregunta de tu cuestionario?',
                    'type' => 'custom_type'
                ],
                'q2' => [
                    'text' => '¿Segunda pregunta?',
                    'type' => 'custom_type'
                ],
            ],
            'max_duration_minutes' => 20,
            'estimated_duration_minutes' => 15,
            'is_active' => true,
            'version' => 1
        ]);
    }
}
```

### 5. Registrar el Seeder

En `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    // Seeders existentes...
    $this->call(NuevoTipoSeeder::class);
}
```

## 🧪 Testing del Nuevo Cuestionario

### 1. Ejecutar Seeder
```bash
php artisan db:seed --class=NuevoTipoSeeder
```

### 2. Probar en API
```bash
# Crear campaña con el nuevo cuestionario
# Acceder via API para verificar estructura
curl -X GET "http://localhost:8001/api/campaigns/{code}"
```

### 3. Verificar Procesamiento
- Enviar respuestas de prueba
- Verificar que los jobs se ejecuten correctamente
- Revisar los logs para errores

## 📝 Ejemplos de Tipos Comunes

### Cuestionario de Satisfacción
```php
case SATISFACTION_SURVEY = 'SATISFACTION_SURVEY';

// Strategy: Escalas + comentarios opcionales
// Procesamiento: Estadístico + análisis de sentimientos
```

### Evaluación 360 Grados
```php
case FEEDBACK_360 = 'FEEDBACK_360';

// Strategy: Multiple choice + escalas por competencia
// Procesamiento: Agregación por evaluador + análisis comparativo
```

### Assessment de Ventas
```php
case SALES_ASSESSMENT = 'SALES_ASSESSMENT';

// Strategy: Escenarios + audio + multiple choice
// Procesamiento: IA + puntuación por habilidad comercial
```

## ⚠️ Consideraciones Importantes

1. **Validaciones**: Asegúrate de validar todas las respuestas según el tipo
2. **Performance**: Considera el impacto de procesamiento si usas IA
3. **Frontend**: Coordina con el equipo de frontend los nuevos tipos de pregunta
4. **Testing**: Siempre prueba con datos reales antes de producción
5. **Documentation**: Actualiza esta guía con tus nuevos tipos

## 🔧 Comandos Útiles

```bash
# Limpiar cache después de cambios
php artisan config:clear
php artisan cache:clear

# Regenerar autoload si es necesario
composer dump-autoload

# Ejecutar jobs en desarrollo
php artisan queue:work

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

## 📞 Soporte

Si encuentras problemas o necesitas ayuda:
1. Revisa los logs en `storage/logs/`
2. Verifica que el Factory pueda instanciar tu Strategy
3. Asegúrate de que los tipos estén bien definidos en los Enums
4. Prueba primero con datos simples antes de casos complejos