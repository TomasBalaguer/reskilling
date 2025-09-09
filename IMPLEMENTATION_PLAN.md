# Plan de Implementación - Sistema Reskiling B2B
## Evaluación de Habilidades Blandas para Empresas

---

## 📋 Resumen Ejecutivo

### Objetivo
Crear una plataforma B2B que permita a las empresas evaluar habilidades blandas de sus empleados mediante cuestionarios con respuestas en audio, procesadas con IA (Gemini) para análisis prosódico y de contenido.

### Arquitectura
- **Frontend**: React + TypeScript (`/questionaires-app`) - Ya existente
- **Backend**: Laravel + Filament (`/reskiling`) - Nuevo
- **Procesamiento**: Google Gemini API para transcripción y análisis
- **Deployment**: Laravel Cloud

### Timeline
- **MVP Funcional**: 5-7 días
- **Clientes objetivo iniciales**: 2 empresas (50 y 400 empleados)

---

## 🏗️ Arquitectura del Sistema

```
┌─────────────────────┐     ┌──────────────────┐     ┌─────────────────────┐
│ questionaires-app   │────▶│    reskiling     │────▶│  Gemini API        │
│ (React Frontend)    │ API │ (Laravel Backend)│     │  (AI Processing)   │
└─────────────────────┘     └──────────────────┘     └─────────────────────┘
         │                           │
         │                           ├── /admin (Super Admin)
         ▼                           │
   [Empleados]                      └── /company (Admin Empresas)
   Sin registro                         con Filament
```

---

## 📁 Estructura de Proyectos

```
/eslab/
├── backend/              # Sistema médico actual (NO TOCAR)
├── reskiling/           # Nuevo backend B2B
│   ├── app/
│   │   ├── Filament/    # Paneles admin
│   │   ├── Models/      # Modelos de datos
│   │   ├── Services/    # Lógica de negocio
│   │   └── Jobs/        # Procesamiento asíncrono
│   └── database/
│       └── migrations/  # Estructura BD
└── questionaires-app/   # Frontend React existente
    └── src/
        ├── pages/       # Páginas de la app
        └── services/    # Llamadas API
```

---

## 🗄️ Estructura de Base de Datos

### Tablas Principales

```sql
-- Empresas cliente
companies
├── id
├── name
├── subdomain
├── logo_url
├── max_campaigns
├── max_responses_per_campaign
├── settings (JSON)
└── timestamps

-- Usuarios administradores de empresa
company_users
├── id
├── company_id
├── name
├── email
├── password
├── role (admin/viewer)
└── timestamps

-- Campañas de evaluación
campaigns
├── id
├── company_id
├── name
├── code (único, para URL pública)
├── description
├── max_responses
├── responses_count (counter cache)
├── active_from
├── active_until
├── public_link_enabled
├── settings (JSON)
└── timestamps

-- Relación campaña-cuestionarios
campaign_questionnaires
├── campaign_id
├── questionnaire_id
├── order
└── is_required

-- Respuestas (sin registro de usuarios)
campaign_responses
├── id
├── campaign_id
├── respondent_name
├── respondent_email
├── age
├── gender
├── occupation
├── responses (JSON - incluye audio paths)
├── interpretation (JSON - resultado IA)
├── processing_status (pending/processing/completed/failed)
├── started_at
├── completed_at
└── timestamps

-- Cuestionarios (copiados del sistema actual)
questionnaires
├── id
├── name
├── description
├── scoring_type
├── category_id
└── timestamps

-- Preguntas de cuestionarios
questionnaire_questions
├── id
├── questionnaire_id
├── question_text
├── question_type
├── options (JSON)
├── order
└── timestamps

-- Prompts para IA
questionnaire_prompts
├── id
├── questionnaire_id
├── prompt (TEXT)
├── is_active
└── timestamps
```

---

## 🚀 Fases de Implementación

### **FASE 1: Setup Inicial y Core** ✅ Checkpoint 1
**Duración**: Día 1
**Entregables**:

- [ ] Proyecto reskiling creado y configurado
- [ ] Filament instalado
- [ ] Base de datos configurada
- [ ] Servicios core copiados desde backend:
  - [ ] `AIInterpretationService.php`
  - [ ] `QuestionnaireProcessing/*`
  - [ ] `ProcessAudioTranscriptionsJob.php`
- [ ] Migraciones creadas y ejecutadas
- [ ] Seeders de cuestionarios importados
- [ ] Configuración de Gemini API (.env)
- [ ] Storage S3 configurado

**Comandos necesarios**:
```bash
cd /Users/howdy/Proyectos/eslab/reskiling
composer require filament/filament:"^3.0"
php artisan filament:install --panels
php artisan make:filament-panel company
php artisan migrate
php artisan db:seed
```

**Verificación**: 
- [ ] Puedo acceder a `/admin/login`
- [ ] Base de datos tiene todas las tablas
- [ ] Puedo ejecutar un test de Gemini API

---

### **FASE 2: Modelos y API** ✅ Checkpoint 2
**Duración**: Día 2
**Entregables**:

- [ ] Modelos Eloquent creados:
  - [ ] Company
  - [ ] CompanyUser
  - [ ] Campaign
  - [ ] CampaignResponse
  - [ ] Relaciones definidas
- [ ] API Controllers:
  - [ ] `CampaignValidationController`
  - [ ] `CampaignResponseController`
  - [ ] `AudioUploadController`
- [ ] Rutas API configuradas:
  ```php
  POST /api/campaign/validate/{code}
  GET  /api/campaign/{code}/info
  GET  /api/campaign/{code}/questionnaires
  POST /api/campaign/{code}/response
  POST /api/campaign/{code}/upload-audio
  GET  /api/campaign/response/{id}/status
  GET  /api/campaign/response/{id}/result
  ```
- [ ] Middleware CORS configurado
- [ ] Jobs de procesamiento configurados

**Verificación**:
- [ ] Postman collection creada y probada
- [ ] Puedo validar un código de campaña
- [ ] Puedo crear una respuesta vía API

---

### **FASE 3: Admin Panel con Filament** ✅ Checkpoint 3
**Duración**: Día 2-3
**Entregables**:

**Super Admin Panel (/admin)**:
- [ ] CompanyResource
  - [ ] Crear/editar empresas
  - [ ] Asignar límites
  - [ ] Ver estadísticas
- [ ] CampaignResource
  - [ ] Ver todas las campañas
  - [ ] Estadísticas globales
- [ ] QuestionnaireResource
  - [ ] Gestionar cuestionarios
  - [ ] Editar prompts
- [ ] Dashboard widgets:
  - [ ] Total respuestas
  - [ ] Campañas activas
  - [ ] Procesamiento en cola

**Company Panel (/company)**:
- [ ] Multi-tenancy configurado
- [ ] CampaignResource (filtrado por empresa)
  - [ ] Crear campañas
  - [ ] Seleccionar cuestionarios
  - [ ] Generar código/link
- [ ] ResponseResource
  - [ ] Ver respuestas
  - [ ] Ver interpretaciones
  - [ ] Filtros y búsqueda
- [ ] Widgets de estadísticas:
  - [ ] Tasa de completitud
  - [ ] Promedio de habilidades
  - [ ] Distribución por competencia
- [ ] Exportación:
  - [ ] Excel individual
  - [ ] PDF de reporte
  - [ ] CSV masivo

**Verificación**:
- [ ] Puedo crear una empresa como super admin
- [ ] Puedo logearme como admin de empresa
- [ ] Puedo crear una campaña y ver su código
- [ ] Puedo ver respuestas con interpretaciones

---

### **FASE 4: Integración Frontend** ✅ Checkpoint 4
**Duración**: Día 4
**Entregables**:

- [ ] Configurar API URL en questionaires-app
- [ ] Adaptar rutas para campaigns:
  - [ ] `/campaign/:code` - Landing
  - [ ] `/campaign/:code/questionnaire/:id` - Responder
  - [ ] `/campaign/:code/complete` - Finalización
- [ ] Componentes actualizados:
  - [ ] `CampaignValidator.tsx`
  - [ ] `RespondentForm.tsx`
  - [ ] `AudioRecorder.tsx`
- [ ] Manejo de estados:
  - [ ] Campaign context
  - [ ] Response tracking
  - [ ] Progress indicator
- [ ] Integración con nueva API

**Verificación**:
- [ ] Puedo acceder con código de campaña
- [ ] Puedo grabar audio y enviarlo
- [ ] Veo confirmación al completar
- [ ] Los datos llegan al backend

---

### **FASE 5: Procesamiento y Análisis** ✅ Checkpoint 5
**Duración**: Día 4-5
**Entregables**:

- [ ] Queue workers configurados
- [ ] Procesamiento de audio funcionando:
  - [ ] Transcripción con Gemini
  - [ ] Análisis prosódico
  - [ ] Generación de interpretación
- [ ] Notificaciones:
  - [ ] Email cuando se completa procesamiento
  - [ ] Webhook opcional para empresas
- [ ] Reintentos en caso de fallo
- [ ] Logs estructurados

**Verificación**:
- [ ] Audio se transcribe correctamente
- [ ] Interpretación incluye 15 competencias
- [ ] Análisis prosódico presente
- [ ] Sin timeouts en procesamiento

---

### **FASE 6: Testing y Optimización** ✅ Checkpoint 6
**Duración**: Día 5-6
**Entregables**:

- [ ] Tests unitarios core
- [ ] Tests de integración API
- [ ] Pruebas con usuarios reales:
  - [ ] Empresa 1 (50 empleados)
  - [ ] Empresa 2 (400 empleados)
- [ ] Optimizaciones:
  - [ ] Cache de respuestas
  - [ ] Paginación eficiente
  - [ ] Compresión de audio
- [ ] Documentación:
  - [ ] Manual de admin
  - [ ] Guía de empresa
  - [ ] API docs

**Verificación**:
- [ ] 10+ respuestas procesadas sin errores
- [ ] Exportación masiva funciona
- [ ] Tiempos de respuesta < 2s
- [ ] Sin errores 500

---

### **FASE 7: Deployment** ✅ Checkpoint 7
**Duración**: Día 6-7
**Entregables**:

- [ ] Laravel Cloud configurado:
  - [ ] Aplicación creada
  - [ ] Base de datos provisionada
  - [ ] Variables de entorno
  - [ ] Storage S3
- [ ] Deployment inicial
- [ ] SSL configurado
- [ ] Queue workers en producción
- [ ] Monitoring configurado
- [ ] Backups automáticos

**Verificación**:
- [ ] App accesible en producción
- [ ] Empresas pueden logear
- [ ] Procesamiento funciona
- [ ] Sin errores en logs

---

## 🛠️ Stack Tecnológico

### Backend (reskiling)
- **Framework**: Laravel 11
- **Admin Panel**: Filament 3
- **Queue**: Laravel Jobs + Database Driver
- **Storage**: S3 (audio files)
- **Cache**: Redis/Database
- **AI**: Google Gemini 1.5 Flash

### Frontend (questionaires-app)
- **Framework**: React 19 + TypeScript
- **Routing**: React Router v7
- **Forms**: React Hook Form
- **Styles**: Tailwind CSS
- **HTTP**: Axios
- **State**: Zustand

### Deployment
- **Backend**: Laravel Cloud
- **Frontend**: Vercel/Netlify
- **Storage**: AWS S3
- **Database**: MySQL 8

---

## 📊 Métricas de Éxito

### MVP (Semana 1)
- [ ] 2 empresas pueden crear campañas
- [ ] 50+ respuestas procesadas
- [ ] 0 errores críticos
- [ ] Tiempo procesamiento < 30s por audio

### Mes 1
- [ ] 5 empresas activas
- [ ] 500+ respuestas procesadas
- [ ] 95% uptime
- [ ] NPS > 7

### Mes 3
- [ ] 20 empresas
- [ ] 5000+ respuestas
- [ ] Features adicionales:
  - [ ] Comparación entre campañas
  - [ ] Benchmarking sectorial
  - [ ] API pública

---

## 🚨 Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Timeout en procesamiento | Media | Alto | Jobs asíncronos, aumentar límites |
| Costos Gemini API | Media | Medio | Cache agresivo, batch processing |
| Sobrecarga con 400 usuarios | Alta | Alto | Queue, rate limiting, escalado horizontal |
| Datos sensibles expuestos | Baja | Crítico | Encriptación, GDPR compliance |

---

## 📝 Checklist Pre-Launch

### Técnico
- [ ] Todos los tests pasan
- [ ] No hay errores en logs (últimas 24h)
- [ ] Backups configurados y probados
- [ ] SSL certificado válido
- [ ] Queue workers estables
- [ ] Monitoring activo

### Negocio
- [ ] Términos de servicio definidos
- [ ] Política de privacidad
- [ ] Pricing definido
- [ ] Contratos con empresas piloto
- [ ] Soporte configurado

### Documentación
- [ ] README actualizado
- [ ] API documentada
- [ ] Manual de usuario empresa
- [ ] FAQ para empleados
- [ ] Guía de troubleshooting

---

## 🔄 Proceso de Desarrollo Diario

### Rutina Diaria
1. **Mañana**: Review del checkpoint anterior
2. **Desarrollo**: Trabajo en fase actual
3. **Testing**: Pruebas de lo desarrollado
4. **Tarde**: Update del progreso
5. **Commit**: Push a repositorio

### Comandos Frecuentes
```bash
# Backend (reskiling)
php artisan serve
php artisan queue:work
php artisan filament:make-resource
php artisan migrate:fresh --seed
php artisan tinker

# Frontend (questionaires-app)
npm run dev
npm run build
npm run type-check

# Deployment
git push
php artisan deploy
```

---

## 📞 Contactos y Recursos

### APIs y Servicios
- **Gemini API Console**: https://makersuite.google.com/app/apikey
- **Laravel Cloud**: https://cloud.laravel.com
- **Filament Docs**: https://filamentphp.com/docs

### Repositorios
- **Backend médico**: `/eslab/backend` (referencia, no modificar)
- **Reskiling**: `/eslab/reskiling` (nuevo desarrollo)
- **Frontend**: `/eslab/questionaires-app` (adaptar)

---

## 📈 Evolución Post-MVP

### Fase 2 (Mes 2-3)
- [ ] Autenticación SSO empresarial
- [ ] Integración con HR systems (ATS)
- [ ] Dashboard analytics avanzado
- [ ] Comparación entre departamentos
- [ ] Planes de desarrollo personalizados

### Fase 3 (Mes 4-6)
- [ ] Multi-idioma
- [ ] White label para empresas
- [ ] API pública
- [ ] Mobile app
- [ ] Certificaciones digitales

---

## ✅ Estado Actual

**Fecha inicio**: 8 de Septiembre 2025
**Estado**: En planificación
**Próximo paso**: Comenzar FASE 1 - Setup Inicial

---

*Este documento debe actualizarse diariamente con el progreso y cualquier cambio en el plan.*