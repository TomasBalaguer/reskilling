# 🏗️ Documentación Técnica Completa - Sistema de Cuestionarios B2B

## 📋 Índice
1. [Arquitectura General](#arquitectura-general)
2. [Modelos](#modelos)
3. [Controladores](#controladores)
4. [Servicios](#servicios)
5. [Jobs y Colas](#jobs-y-colas)
6. [Eventos y Listeners](#eventos-y-listeners)
7. [Resources](#resources)
8. [Enums](#enums)
9. [Endpoints API](#endpoints-api)
10. [Base de Datos](#base-de-datos)
11. [Configuración](#configuración)

---

## 🏛️ Arquitectura General

### Patrón Strategy
- **QuestionnaireStrategyInterface**: Contrato base
- **AbstractQuestionnaireStrategy**: Implementación base
- **Strategies específicos**: Para cada tipo de cuestionario
- **QuestionnaireStrategyFactory**: Gestor de strategies

### Flujo de Procesamiento
```
Respuesta → Evento → Listener → Jobs → Análisis → Reporte
```

---

## 🗃️ Modelos

### **Campaign**
**Ubicación:** `app/Models/Campaign.php`
```php
// Campos principales
- id, name, description, code, status
- active_from, active_until
- max_responses, responses_count
- public_link_code, email_invitations_enabled

// Relaciones
- company(): BelongsTo
- questionnaires(): BelongsToMany  
- responses(): HasMany
- invitations(): HasMany

// Métodos clave
- isActive(): bool
- hasCapacity(): bool
```

### **Company**
**Ubicación:** `app/Models/Company.php`
```php
// Campos principales
- id, name, subdomain, logo_url
- email, phone, max_campaigns
- settings (JSON), is_active

// Relaciones
- campaigns(): HasMany
- users(): HasMany (CompanyUser)
```

### **Questionnaire**
**Ubicación:** `app/Models/Questionnaire.php`
```php
// Campos principales
- id, name, description, scoring_type
- questionnaire_type (Enum), questions (JSON)
- structure (JSON), metadata (JSON)
- max_duration_minutes, estimated_duration_minutes

// Relaciones
- campaigns(): BelongsToMany
- prompts(): HasMany (QuestionnairePrompt)

// Métodos clave Strategy Pattern
- getStrategy(): QuestionnaireStrategyInterface
- buildStructure(): array
- calculateScores(array, array): array
- requiresAIProcessing(): bool
```

### **CampaignResponse**
**Ubicación:** `app/Models/CampaignResponse.php`
```php
// Campos principales
- id, campaign_id, questionnaire_id
- respondent_name, respondent_email, respondent_type
- raw_responses (JSON), processed_responses (JSON)
- questionnaire_scores (JSON), ai_analysis (JSON)
- transcriptions (JSON), comprehensive_report (JSON)

// Estados de procesamiento
- processing_status: 'pending' | 'processing' | 'transcribing' 
  | 'analyzed' | 'completed' | 'failed'

// Timestamps de control
- transcription_completed_at, ai_analysis_completed_at
- scoring_completed_at, report_generated_at
```

### **CampaignInvitation**
**Ubicación:** `app/Models/CampaignInvitation.php`
```php
// Campos principales  
- id, campaign_id, name, email, token
- status, expires_at, opened_at, completed_at

// Relaciones
- campaign(): BelongsTo
- campaignResponse(): HasOne
```

---

## 🎮 Controladores

### **CampaignController**
**Ubicación:** `app/Http/Controllers/API/CampaignController.php`

#### Endpoints:
```php
// Acceso público por código
getByCode(string $code): QuestionnaireAssignmentResource

// Verificar código de seguridad adicional  
verifyCode(Request $request, string $code): JsonResponse

// Acceso por invitación
getByInvitation(string $token): QuestionnaireAssignmentResource
```

---

## 🔧 Servicios

### **AIInterpretationService**
**Ubicación:** `app/Services/AIInterpretationService.php`

#### Métodos principales:
```php
// Interpretación general (audio + transcripciones)
generateInterpretation(CampaignResponse, Questionnaire, array): array

// Análisis específico de texto
generateTextAnalysis(CampaignResponse, Questionnaire, array): array

// Análisis de audio con Gemini
analyzeAudioWithGemini(string $audioPath, string $questionText): array
```

### **QuestionnaireProcessorFactory**
**Ubicación:** `app/Services/QuestionnaireProcessing/QuestionnaireProcessorFactory.php`

Maneja la creación de processors para diferentes tipos de cuestionarios.

---

## 🔄 Jobs y Colas

### **ProcessAudioTranscriptionsJob**
**Ubicación:** `app/Jobs/ProcessAudioTranscriptionsJob.php`
```php
// Constructor
__construct(int $responseId)

// Cola: 'audio-processing'
// Procesa archivos de audio y genera transcripciones
```

### **ProcessTextAnalysisJob**
**Ubicación:** `app/Jobs/ProcessTextAnalysisJob.php`
```php
// Constructor  
__construct(int $responseId)

// Cola: 'ai-processing'
// Analiza respuestas de texto con IA
```

### **GenerateAIInterpretationJob**
**Ubicación:** `app/Jobs/GenerateAIInterpretationJob.php`
```php
// Constructor
__construct(int $responseId)

// Cola: 'ai-processing'  
// Genera interpretaciones de IA basadas en transcripciones
```

### **GenerateQuestionnaireScoresJob**
**Ubicación:** `app/Jobs/GenerateQuestionnaireScoresJob.php`
```php
// Constructor
__construct(int $responseId)

// Cola: 'scoring'
// Calcula puntuaciones finales usando strategies
```

### **GenerateComprehensiveReportJob**
**Ubicación:** `app/Jobs/GenerateComprehensiveReportJob.php`
```php
// Constructor
__construct(int $responseId)

// Cola: 'reporting'
// Genera reportes integrales finales
```

---

## 📡 Eventos y Listeners

### **Eventos**

#### **QuestionnaireResponseSubmitted**
**Ubicación:** `app/Events/QuestionnaireResponseSubmitted.php`
```php
// Constructor
__construct(CampaignResponse $response, array $processedData, bool $requiresAI)

// Se dispara cuando se envía una respuesta
```

#### **AudioTranscriptionCompleted**
**Ubicación:** `app/Events/AudioTranscriptionCompleted.php`
```php
// Constructor  
__construct(CampaignResponse $response, array $transcriptionData, bool $success)

// Se dispara al completar transcripciones
```

#### **AIAnalysisCompleted**
**Ubicación:** `app/Events/AIAnalysisCompleted.php`
```php
// Constructor
__construct(CampaignResponse $response, array $analysisResults, bool $success)

// Se dispara al completar análisis de IA
```

### **Listeners**

#### **ProcessQuestionnaireResponse**
**Ubicación:** `app/Listeners/ProcessQuestionnaireResponse.php`
- Escucha: `QuestionnaireResponseSubmitted`
- Decide qué jobs disparar según el tipo de cuestionario

#### **ProcessTranscriptionResults**
**Ubicación:** `app/Listeners/ProcessTranscriptionResults.php`
- Escucha: `AudioTranscriptionCompleted`
- Dispara análisis de IA si es necesario

#### **ProcessAIAnalysisResults**
**Ubicación:** `app/Listeners/ProcessAIAnalysisResults.php`
- Escucha: `AIAnalysisCompleted`
- Dispara generación de puntuaciones finales

---

## 📦 Resources

### **QuestionnaireAssignmentResource**
**Ubicación:** `app/Http/Resources/QuestionnaireAssignmentResource.php`
```php
// Métodos factory
static forCampaign(Campaign $campaign, string $accessToken): self
static forInvitation(CampaignInvitation $invitation): self

// Estructura compatible con frontend existente
```

### **CampaignResource**
**Ubicación:** `app/Http/Resources/CampaignResource.php`
- Información completa de campaña con empresa

### **QuestionnaireResource**
**Ubicación:** `app/Http/Resources/QuestionnaireResource.php`
- Metadatos enriquecidos del cuestionario

### **CampaignResponseResource**
**Ubicación:** `app/Http/Resources/CampaignResponseResource.php`
- Estado completo del procesamiento de respuestas

### **QuestionnaireAnalysisResource**
**Ubicación:** `app/Http/Resources/QuestionnaireAnalysisResource.php`
- Análisis detallado y resultados

---

## 🏷️ Enums

### **QuestionnaireType**
**Ubicación:** `app/Enums/QuestionnaireType.php`
```php
enum QuestionnaireType: string {
    REFLECTIVE_QUESTIONS = 'REFLECTIVE_QUESTIONS'
    MULTIPLE_CHOICE = 'MULTIPLE_CHOICE'
    SINGLE_CHOICE = 'SINGLE_CHOICE'
    TEXT_RESPONSE = 'TEXT_RESPONSE'
    SCALE_RATING = 'SCALE_RATING'
    PERSONALITY_ASSESSMENT = 'PERSONALITY_ASSESSMENT'
    BIG_FIVE = 'BIG_FIVE'
    MIXED_FORMAT = 'MIXED_FORMAT'
}

// Métodos
getDisplayName(): string
getResponseFormat(): string
isAudioBased(): bool
requiresAIProcessing(): bool
getStrategyClass(): string
```

### **QuestionType**
**Ubicación:** `app/Enums/QuestionType.php`
```php
enum QuestionType: string {
    TEXT_INPUT = 'text_input'
    TEXTAREA = 'textarea'
    MULTIPLE_CHOICE = 'multiple_choice'
    SINGLE_CHOICE = 'single_choice'
    LIKERT_SCALE = 'likert_scale'
    NUMERIC_SCALE = 'numeric_scale'
    AUDIO_RESPONSE = 'audio_response'
    // ... más tipos
}
```

---

## 🌐 Endpoints API

### **Campaigns**
```http
# Obtener cuestionarios por código de campaña
GET /api/campaigns/{code}

# Verificar código de seguridad adicional
POST /api/campaigns/{code}/verify
Content-Type: application/json
{
  "security_code": "optional_security_code"
}

# Obtener cuestionarios por token de invitación  
GET /api/campaigns/invitation/{token}
```

### **Responses** (Por implementar según necesidades)
```http
# Enviar respuestas
POST /api/campaigns/{code}/responses

# Obtener estado de procesamiento
GET /api/responses/{id}/status

# Obtener análisis completo
GET /api/responses/{id}/analysis
```

---

## 🗄️ Base de Datos

### **Migraciones Principales**

#### **campaigns**
```sql
- id, name, description, code
- status (enum: draft, active, paused, completed)
- active_from, active_until (timestamps)
- max_responses (int), responses_count (int)
- public_link_code (string, nullable)
- email_invitations_enabled (boolean)
- company_id (foreign key)
```

#### **questionnaires** 
```sql
- id, name, description, scoring_type
- questionnaire_type (enum)
- questions (JSON), structure (JSON), metadata (JSON)
- configuration (JSON), settings (JSON)
- max_duration_minutes, estimated_duration_minutes
- is_active (boolean), version (int)
```

#### **campaign_responses**
```sql
- id, campaign_id, questionnaire_id
- respondent_name, respondent_email, respondent_type
- raw_responses (JSON), processed_responses (JSON)
- questionnaire_scores (JSON), ai_analysis (JSON)
- transcriptions (JSON), comprehensive_report (JSON)
- processing_status (enum)
- multiple timestamps for processing stages
```

#### **campaign_invitations**
```sql
- id, campaign_id, name, email, token
- status (enum: pending, opened, completed, expired)
- expires_at, opened_at, completed_at
```

#### **Tablas Pivot**
```sql
campaign_questionnaires: campaign_id, questionnaire_id, order, is_required
```

---

## ⚙️ Configuración

### **Queue Configuration**
```php
// config/queue.php - Colas específicas
'connections' => [
    'database' => [
        'audio-processing' => [...],
        'ai-processing' => [...], 
        'scoring' => [...],
        'reporting' => [...]
    ]
]
```

### **AI Service Configuration**
```php
// config/services.php
'google' => [
    'api_key' => env('GOOGLE_AI_API_KEY'),
    'model' => env('GOOGLE_AI_MODEL', 'gemini-1.5-flash'),
    'project_id' => env('GOOGLE_PROJECT_ID'),
    'location' => env('GOOGLE_LOCATION', 'us-central1'),
]
```

### **Event Service Provider**
```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    QuestionnaireResponseSubmitted::class => [ProcessQuestionnaireResponse::class],
    AudioTranscriptionCompleted::class => [ProcessTranscriptionResults::class],
    AIAnalysisCompleted::class => [ProcessAIAnalysisResults::class],
];
```

---

## 🔍 Strategies Implementados

### **ReflectiveQuestionsStrategy**
- **Audio + IA**: Transcripción + análisis prosódico
- **Habilidades blandas**: 7 dimensiones principales
- **Duración**: ~35 minutos

### **MultipleChoiceStrategy** 
- **Selección múltiple**: Puntuación ponderada
- **Estadísticas**: Tasa de acierto, análisis de patrones
- **Duración**: ~20 minutos

### **TextResponseStrategy**
- **Texto libre**: Análisis de IA de contenido
- **Competencias**: Comunicación, pensamiento crítico
- **Duración**: ~25 minutos

### **BigFiveStrategy**
- **Personalidad**: Modelo Big Five completo
- **Estadísticas**: Percentiles, análisis comparativo  
- **Duración**: ~18 minutos

### **MixedFormatStrategy**
- **Combina cualquier tipo**: Máxima flexibilidad
- **Procesamiento híbrido**: Cada tipo con su strategy
- **Duración**: Variable según composición

---

## 🚀 Comandos de Desarrollo

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders  
php artisan db:seed

# Procesar colas
php artisan queue:work

# Limpiar cache
php artisan config:clear
php artisan cache:clear

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ejecutar tests (cuando estén implementados)
php artisan test
```

---

## 🔧 Debugging y Monitoreo

### **Logs Principales**
- `storage/logs/laravel.log`: Log general
- Queue failures: Tabla `failed_jobs`
- Processing status: Campo `processing_status` en responses

### **Métricas de Performance**
- Tiempo de procesamiento por job
- Tasa de éxito de transcripciones
- Confidence scores de análisis IA
- Tiempo total de procesamiento end-to-end

### **Estados de Procesamiento**
```
pending → processing → transcribing → transcribed 
→ analyzing_text → analyzed → calculating_scores 
→ completed → report_generated
```

---

Este documento cubre toda la arquitectura técnica actual. Para actualizaciones, modificar este archivo cuando se implementen nuevas funcionalidades.