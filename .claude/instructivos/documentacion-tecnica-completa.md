# 🏗️ Documentación Técnica Completa - Sistema Re-Skilling.ai

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
12. [Sistema de Autenticación](#sistema-de-autenticación)
13. [Vistas y Frontend](#vistas-y-frontend)
14. [Sistema de Reportes](#sistema-de-reportes)

---

## 🏛️ Arquitectura General

### Stack Tecnológico
- **Backend**: Laravel 11.x
- **Frontend**: Blade + Alpine.js + Bootstrap 5
- **Base de Datos**: MySQL 8.0
- **Colas**: Database Driver (migrable a Redis)
- **Storage**: S3 AWS para archivos de audio
- **IA**: Google Gemini API para análisis y transcripciones
- **PDF**: Dompdf para generación de reportes

### Patrón Strategy
- **QuestionnaireStrategyInterface**: Contrato base
- **AbstractQuestionnaireStrategy**: Implementación base
- **Strategies específicos**: Para cada tipo de cuestionario
- **QuestionnaireStrategyFactory**: Gestor de strategies

### Flujo de Procesamiento
```
Audio Upload → S3 Storage → Transcripción (Gemini) → 
Análisis Prosódico → Interpretación IA → 
Reporte Comprehensivo → PDF Generation
```

### Arquitectura Multi-tenant
- **Admin**: Super administrador del sistema
- **Company**: Administradores de empresa
- **Respondent**: Usuarios finales que responden cuestionarios

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

### **CompanyUser**
**Ubicación:** `app/Models/CompanyUser.php`
```php
// Campos principales
- id, company_id, name, email, password
- role (admin/user), is_active
- last_login_at, created_at, updated_at

// Relaciones
- company(): BelongsTo

// Métodos
- Autenticación personalizada para empresas
```

### **CampaignEmailLog**
**Ubicación:** `app/Models/CampaignEmailLog.php`
```php
// Campos principales
- id, campaign_id, invitation_id
- email, subject, status
- sent_at, opened_at, clicked_at
- error_message, metadata (JSON)

// Relaciones
- campaign(): BelongsTo
- invitation(): BelongsTo
```

### **QuestionnairePrompt**
**Ubicación:** `app/Models/QuestionnairePrompt.php`
```php
// Campos principales
- id, questionnaire_id, prompt_type
- prompt_text, metadata (JSON)
- is_active, order

// Relaciones
- questionnaire(): BelongsTo
```

---

## 🎮 Controladores

### **AdminController**
**Ubicación:** `app/Http/Controllers/Admin/AdminController.php`

#### Métodos principales:
```php
// Dashboard y estadísticas
dashboard(): View // Vista principal con métricas

// Gestión de empresas
companies(): View // Listado de empresas
companyDetail($companyId): View
createCompany(): View
storeCompany(Request): RedirectResponse
editCompany($companyId): View
updateCompany(Request, $companyId): RedirectResponse
deleteCompany($companyId): RedirectResponse
toggleCompanyStatus($companyId): RedirectResponse

// Gestión de campañas
campaigns(): View // Todas las campañas del sistema
campaignDetail($campaignId): View
createCampaign(Request): View
storeCampaign(Request): RedirectResponse
editCampaign($campaignId): View
updateCampaign(Request, $campaignId): RedirectResponse
toggleCampaignStatus(Request, $campaignId): RedirectResponse

// Gestión de respuestas
responses(): View // Todas las respuestas
responseDetail($responseId): View
generateResponseReport($responseId): PDF
reprocessResponse($responseId): RedirectResponse
deleteResponse($responseId): RedirectResponse

// Utilidades
exportCampaignData($campaignId): CSV
createCompanyUser(Request, $companyId): RedirectResponse
resetCompanyUserPassword(Request, $companyId, $userId): RedirectResponse
```

### **CompanyController**
**Ubicación:** `app/Http/Controllers/Company/CompanyController.php`

#### Métodos principales:
```php
// Dashboard empresa
dashboard(Request): View // Dashboard específico de empresa

// Gestión de campañas propias
campaigns(Request): View
campaignDetail(Request, $campaignId): View
createCampaign(Request): View
storeCampaign(Request): RedirectResponse
editCampaign(Request, $campaignId): View
updateCampaign(Request, $campaignId): RedirectResponse
toggleCampaignStatus(Request, $campaignId): RedirectResponse
toggleCampaignPublicAccess(Request, $campaignId): RedirectResponse

// Gestión de respuestas
responses(Request): View
responseDetail(Request, $responseId): View
generateResponseReport(Request, $responseId): PDF
reprocessResponse(Request, $responseId): RedirectResponse

// Gestión de invitaciones
resendInvitations(Request, $campaignId): RedirectResponse
addSingleInvitation(Request, $campaignId): RedirectResponse
addCSVInvitations(Request, $campaignId): RedirectResponse

// Perfil de empresa
editProfile(Request): View
updateProfile(Request): RedirectResponse
removeLogo(Request): RedirectResponse

// Email logs
campaignEmailLogs(Request, $campaignId): View
```

### **CampaignController (API)**
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

### **CampaignResponseController (API)**
**Ubicación:** `app/Http/Controllers/API/CampaignResponseController.php`

#### Endpoints:
```php
// Crear respuesta
store(Request): JsonResponse

// Subir archivos de audio
uploadAudio(Request, $responseId): JsonResponse

// Obtener estado de procesamiento
getStatus($responseId): JsonResponse
```

### **Auth Controllers**
**Ubicación:** `app/Http/Controllers/Auth/`

#### AdminAuthController:
```php
showLoginForm(): View
login(Request): RedirectResponse
logout(): RedirectResponse
```

#### CompanyAuthController:
```php
showLoginForm(): View
login(Request): RedirectResponse
logout(): RedirectResponse
dashboard(): RedirectResponse // Redirige según empresa
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

### **ComprehensiveReportService**
**Ubicación:** `app/Services/ComprehensiveReportService.php`

#### Métodos principales:
```php
// Generar reporte completo con las 15 competencias
generateComprehensiveReport(CampaignResponse): array

// Estructurar reporte con formato JSON
structureFinalReport(string $aiReport, CampaignResponse): array

// Incluye análisis de:
- 15 competencias soft skills con puntuaciones 1-10
- Puntos fuertes y áreas de desarrollo
- Plan de desarrollo personalizado
- Proyecto integrador recomendado
- Análisis prosódico integrado
```

### **EmailLoggerService**
**Ubicación:** `app/Services/EmailLoggerService.php`

#### Métodos principales:
```php
// Logging de emails
logEmailQueued(Campaign, CampaignInvitation): CampaignEmailLog
logEmailSent(CampaignEmailLog): void
logEmailFailed(CampaignEmailLog, string, Exception): void

// Estadísticas
getCampaignEmailStats(Campaign): array
getRecentFailures(Campaign, int): Collection
```

### **FileStorageService**
**Ubicación:** `app/Services/FileStorageService.php`

#### Métodos principales:
```php
// Gestión de archivos S3
storeAudioFile(UploadedFile, string): array
getFileUrl(string): string
deleteFile(string): bool
getSignedUrl(string, int): string
```

### **QuestionnaireProcessorFactory**
**Ubicación:** `app/Services/QuestionnaireProcessing/QuestionnaireProcessorFactory.php`

Maneja la creación de processors para diferentes tipos de cuestionarios.

---

## 🔄 Jobs y Colas

### **ProcessQuestionnaireAudioJob**
**Ubicación:** `app/Jobs/ProcessQuestionnaireAudioJob.php`
```php
// Constructor
__construct(int $responseId)

// Cola: 'audio-processing'
// Procesa archivos de audio usando Gemini API
// Genera transcripciones y análisis prosódico
// Incluye análisis de emociones y métricas de voz
```

### **ProcessAudioTranscriptionsJob**
**Ubicación:** `app/Jobs/ProcessAudioTranscriptionsJob.php`
```php
// Constructor
__construct(int $responseId)

// Cola: 'audio-processing'
// Versión alternativa para procesamiento de audio
// Maneja múltiples archivos de audio por respuesta
```

### **ProcessTextAnalysisJob**
**Ubicación:** `app/Jobs/ProcessTextAnalysisJob.php`
```php
// Constructor  
__construct(int $responseId)

// Cola: 'ai-processing'
// Analiza respuestas de texto con IA
// Para cuestionarios sin audio
```

### **GenerateAIInterpretationJob**
**Ubicación:** `app/Jobs/GenerateAIInterpretationJob.php`
```php
// Constructor
__construct(int $responseId)

// Cola: 'ai-processing'  
// Genera interpretaciones de IA basadas en transcripciones
// Análisis de 7 habilidades blandas principales
// Integra análisis prosódico con contenido
```

### **GenerateQuestionnaireScoresJob**
**Ubicación:** `app/Jobs/GenerateQuestionnaireScoresJob.php`
```php
// Constructor
__construct(int $responseId)

// Cola: 'scoring'
// Calcula puntuaciones finales usando strategies
// Genera métricas por competencia
```

### **GenerateComprehensiveReportJob**
**Ubicación:** `app/Jobs/GenerateComprehensiveReportJob.php`
```php
// Constructor
__construct(int $responseId)

// Cola: 'reports' 
// Genera reportes integrales con 15 competencias
// Incluye puntuaciones estructuradas (1-10)
// Genera plan de desarrollo personalizado
// Se ejecuta después de completar análisis IA
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

## 🔐 Sistema de Autenticación

### **Multi-tenant Authentication**
```php
// Tres niveles de acceso:
1. Admin (Super Admin)
   - Ruta: /admin/login
   - Guard: 'admin'
   - Modelo: User
   - Acceso total al sistema

2. Company (Admin Empresa)
   - Ruta: /company/login  
   - Guard: 'company'
   - Modelo: CompanyUser
   - Acceso limitado a su empresa

3. Respondent (Usuario Final)
   - Sin autenticación requerida
   - Acceso por código de campaña o invitación
```

### **Middleware**
```php
// app/Http/Middleware/
- AdminAuthenticate: Protege rutas admin
- CompanyAuthenticate: Protege rutas empresa
- EnsureCompanyAccess: Valida acceso a recursos de empresa
```

---

## 🎨 Vistas y Frontend

### **Estructura de Vistas**
```
resources/views/
├── admin/           # Vistas del administrador
│   ├── dashboard.blade.php
│   ├── companies/   # Gestión de empresas
│   ├── campaigns/   # Gestión de campañas
│   ├── responses/   # Gestión de respuestas
│   └── reports/     # Reportes PDF
├── company/         # Vistas de empresa
│   ├── dashboard.blade.php
│   ├── campaigns/   
│   ├── responses/
│   └── profile/
├── auth/            # Vistas de autenticación
│   ├── admin-login.blade.php
│   └── company-login.blade.php
├── partials/        # Componentes reutilizables
│   └── response-detail.blade.php
├── reports/         # Plantillas PDF
│   └── professional-pdf.blade.php
└── layouts/         # Layouts principales
    ├── admin.blade.php
    └── company.blade.php
```

### **Assets y Estilos**
```css
// Bootstrap 5 personalizado
// Gradientes: #667eea → #764ba2
// Colores principales:
- Primary: #667eea
- Success: #10b981
- Warning: #f59e0b
- Danger: #ef4444
```

---

## 📊 Sistema de Reportes

### **Tipos de Reportes**

#### **1. Reporte de Análisis IA**
```json
{
  "habilidades_blandas": {
    "comunicacion": { "puntuacion": 8.5, "analisis": "..." },
    "trabajo_en_equipo": { "puntuacion": 7.2, "analisis": "..." },
    "liderazgo": { "puntuacion": 6.8, "analisis": "..." },
    // ... 7 habilidades totales
  },
  "analisis_prosodico": {
    "tono_emocional": "positivo",
    "nivel_confianza": 85,
    "ritmo_habla": "moderado"
  }
}
```

#### **2. Reporte Comprehensivo (15 Competencias)**
```json
{
  "competencias": {
    "perseverancia": { "puntuacion": 8, "nivel": "Excelente" },
    "resiliencia": { "puntuacion": 7, "nivel": "Bueno" },
    "pensamiento_critico": { "puntuacion": 9, "nivel": "Excelente" },
    // ... 15 competencias totales
  },
  "puntuacion_promedio": 7.5,
  "puntos_fuertes": [...],
  "areas_desarrollo": [...],
  "plan_desarrollo": [...],
  "proyecto_recomendado": {...}
}
```

#### **3. PDF Profesional**
- **Página 1**: Dashboard ejecutivo con score general
- **Página 2**: Análisis detallado 15 competencias
- **Página 3**: Fortalezas y áreas de mejora
- **Página 4**: Plan de desarrollo personalizado

### **Generación de PDFs**
```php
// Usando Dompdf
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('reports.professional-pdf', compact('response'));
return $pdf->download('reporte.pdf');
```

---

## 🚀 Deployment y Configuración

### **Requisitos del Sistema**
- PHP 8.2+
- MySQL 8.0+
- Composer 2.x
- Node.js 18+
- AWS S3 (para audio)

### **Variables de Entorno Críticas**
```env
# Google AI
GOOGLE_AI_API_KEY=
GOOGLE_AI_MODEL=gemini-1.5-flash

# AWS S3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

# Colas
QUEUE_CONNECTION=database

# App
APP_ENV=production
APP_DEBUG=false
```

### **Comandos de Deployment**
```bash
# Instalación inicial
composer install --no-dev
npm install && npm run build
php artisan migrate
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Workers de colas (supervisor)
php artisan queue:work --queue=audio-processing,ai-processing,reports,scoring,default
```

### **Cron Jobs**
```cron
# Limpiar respuestas antiguas
0 2 * * * php artisan responses:cleanup --days=90

# Procesar colas fallidas
*/5 * * * * php artisan queue:retry all
```

---

## 📈 Métricas y Monitoreo

### **KPIs del Sistema**
- Tiempo promedio de procesamiento de audio: ~45s
- Tiempo de generación de reporte IA: ~30s
- Tasa de éxito de transcripciones: >95%
- Confidence score promedio: >0.85

### **Logs Críticos**
```php
Log::channel('audio')->info('Processing audio', ['response_id' => $id]);
Log::channel('ai')->info('AI analysis completed', ['response_id' => $id]);
Log::channel('reports')->info('Report generated', ['response_id' => $id]);
```

### **Tablas de Monitoreo**
- `campaign_responses`: Estado de procesamiento
- `failed_jobs`: Jobs fallidos
- `campaign_email_logs`: Tracking de emails

---

Este documento cubre toda la arquitectura técnica actual del sistema Re-Skilling.ai. Para actualizaciones, modificar este archivo cuando se implementen nuevas funcionalidades.