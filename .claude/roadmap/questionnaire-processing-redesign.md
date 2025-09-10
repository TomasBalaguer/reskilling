# 📋 Roadmap: Sistema de Procesamiento Flexible de Cuestionarios

## 🎯 Objetivo Principal
Transformar el sistema actual (solo audio) en una arquitectura flexible que soporte múltiples tipos de cuestionarios con diferentes flujos de procesamiento.

## 📊 Estado Actual
- ✅ **QuestionnaireType enum** con 8 tipos definidos
- ✅ **Patrón Strategy** parcialmente implementado
- ⚠️ **Procesamiento de audio** hardcodeado en jobs
- ⚠️ **Dos sistemas paralelos** de procesamiento

---

## 📅 FASE 1: ARQUITECTURA DE BASE DE DATOS
**Duración estimada:** 3-4 días

### Checkpoints:

#### 1.1 Crear tabla `processing_strategies`
- [ ] Diseñar esquema de la tabla
- [ ] Crear migración
- [ ] Definir campos:
  - `id`
  - `name` 
  - `processor_class`
  - `configuration` (JSON)
  - `is_active`
  - `created_at/updated_at`
- [ ] Ejecutar migración
- [ ] Verificar tabla creada correctamente

#### 1.2 Modificar tabla `questionnaires`
- [ ] Crear migración para agregar campos:
  - `processor_type` (enum)
  - `processor_config` (JSON)
  - `prompt_template_id` (foreign key nullable)
- [ ] Actualizar modelo Questionnaire
- [ ] Agregar relaciones necesarias
- [ ] Migrar datos existentes

#### 1.3 Crear tabla `questionnaire_prompts`
- [ ] Diseñar estructura:
  ```sql
  - id
  - questionnaire_type (enum)
  - questionnaire_id (nullable)
  - prompt_name
  - prompt_template (TEXT)
  - context_variables (JSON)
  - version
  - is_active
  - created_at/updated_at
  ```
- [ ] Crear migración
- [ ] Crear modelo QuestionnairePrompt
- [ ] Crear seeders con prompts actuales
- [ ] Verificar datos migrados

#### 1.4 Actualizar tabla `campaign_responses`
- [ ] Agregar campo `processing_metadata` (JSON)
- [ ] Agregar campo `processor_used` (string)
- [ ] Agregar campo `processing_version` (string)
- [ ] Actualizar modelo CampaignResponse

---

## 📅 FASE 2: INFRAESTRUCTURA CORE
**Duración estimada:** 4-5 días

### Checkpoints:

#### 2.1 Crear Interfaces Base
- [ ] Crear `app/Services/QuestionnaireProcessing/Contracts/`
- [ ] Implementar `QuestionnaireProcessorInterface.php`:
  ```php
  - process(CampaignResponse $response): ProcessingResult
  - validate(array $data): bool
  - getRequiredJobs(): array
  - buildPrompt(array $context): string
  - getProcessingQueue(): string
  - supportsType(QuestionnaireType $type): bool
  ```
- [ ] Crear `ProcessingResult` class
- [ ] Crear `ProcessingContext` value object
- [ ] Tests unitarios para interfaces

#### 2.2 Implementar Factory Pattern
- [ ] Crear `QuestionnaireProcessorFactory.php`
- [ ] Implementar método `getProcessor(QuestionnaireType $type)`
- [ ] Implementar registro de procesadores
- [ ] Agregar logging y manejo de errores
- [ ] Tests para factory

#### 2.3 Crear Base Abstract Processor
- [ ] Crear `BaseQuestionnaireProcessor.php`
- [ ] Implementar métodos comunes:
  - Logging
  - Validación base
  - Manejo de errores
  - Métricas de procesamiento
- [ ] Crear traits reutilizables
- [ ] Documentar métodos abstractos

#### 2.4 Processing Manager
- [ ] Crear `QuestionnaireProcessingManager.php`
- [ ] Implementar orquestación de procesamiento
- [ ] Integrar con sistema de colas
- [ ] Agregar eventos de procesamiento
- [ ] Tests de integración

---

## 📅 FASE 3: MIGRACIÓN DE AUDIO PROCESSING
**Duración estimada:** 5-6 días

### Checkpoints:

#### 3.1 Crear AudioQuestionnaireProcessor
- [ ] Implementar clase en `app/Services/QuestionnaireProcessing/Processors/`
- [ ] Migrar lógica de `ProcessQuestionnaireAudioJob`
- [ ] Implementar métodos de la interfaz:
  - [ ] `process()` - Procesar respuestas de audio
  - [ ] `validate()` - Validar archivos de audio
  - [ ] `getRequiredJobs()` - Retornar jobs necesarios
  - [ ] `buildPrompt()` - Construir prompts para Gemini
- [ ] Mantener compatibilidad con código existente

#### 3.2 Refactorizar Jobs Existentes
- [ ] Actualizar `ProcessQuestionnaireAudioJob`:
  - [ ] Usar nuevo processor
  - [ ] Mantener firma de método actual
  - [ ] Agregar fallback para compatibilidad
- [ ] Actualizar `GenerateAIInterpretationJob`
- [ ] Actualizar `GenerateComprehensiveReportJob`
- [ ] Verificar que jobs existentes siguen funcionando

#### 3.3 Testing de Compatibilidad
- [ ] Crear suite de tests para audio processing
- [ ] Verificar procesamiento de respuestas existentes
- [ ] Test de regresión completo
- [ ] Verificar reportes generados correctamente
- [ ] Performance testing

#### 3.4 Migración de Datos
- [ ] Script para actualizar `processor_used` en respuestas existentes
- [ ] Migrar prompts hardcodeados a base de datos
- [ ] Verificar integridad de datos
- [ ] Backup antes de migración

---

## 📅 FASE 4: NUEVOS PROCESADORES
**Duración estimada:** 6-7 días

### Checkpoints:

#### 4.1 MultipleChoiceProcessor
- [ ] Crear clase processor
- [ ] Implementar lógica de scoring:
  - [ ] Cálculo de puntuaciones
  - [ ] Análisis estadístico
  - [ ] Generación de métricas
- [ ] Crear job específico si necesario
- [ ] Implementar buildPrompt() para análisis
- [ ] Tests unitarios
- [ ] Crear cuestionario de ejemplo

#### 4.2 TextResponseProcessor
- [ ] Crear clase processor
- [ ] Implementar análisis de texto:
  - [ ] Integración con Gemini para análisis
  - [ ] Extracción de keywords
  - [ ] Análisis de sentimiento
- [ ] Crear TextAnalysisJob
- [ ] Implementar prompts específicos
- [ ] Tests unitarios
- [ ] Cuestionario de ejemplo

#### 4.3 ScaleRatingProcessor
- [ ] Crear clase processor
- [ ] Implementar cálculos:
  - [ ] Promedios y desviaciones
  - [ ] Percentiles
  - [ ] Comparación con benchmarks
- [ ] Lógica de interpretación
- [ ] Visualización de datos
- [ ] Tests unitarios
- [ ] Cuestionario de ejemplo

#### 4.4 MixedTypeProcessor
- [ ] Crear clase processor
- [ ] Implementar orquestación de múltiples tipos
- [ ] Combinar resultados de diferentes processors
- [ ] Manejo de secciones del cuestionario
- [ ] Tests de integración
- [ ] Cuestionario mixto de ejemplo

---

## 📅 FASE 5: SISTEMA DE PROMPTS DINÁMICOS
**Duración estimada:** 4-5 días

### Checkpoints:

#### 5.1 PromptBuilder Service
- [ ] Crear `app/Services/PromptBuilder.php`
- [ ] Implementar template engine:
  - [ ] Variables de contexto
  - [ ] Condicionales
  - [ ] Loops para datos dinámicos
- [ ] Cache de prompts compilados
- [ ] Versionado de prompts
- [ ] Tests unitarios

#### 5.2 Prompt Template Management
- [ ] CRUD para prompts en base de datos
- [ ] Sistema de variables predefinidas
- [ ] Preview de prompts con datos de ejemplo
- [ ] Historial de cambios
- [ ] Rollback de versiones

#### 5.3 Integración con Processors
- [ ] Actualizar todos los processors para usar PromptBuilder
- [ ] Migrar prompts hardcodeados
- [ ] Sistema de fallback
- [ ] Cache y optimización
- [ ] Tests de integración

#### 5.4 Admin Interface
- [ ] Vista para gestión de prompts
- [ ] Editor con syntax highlighting
- [ ] Preview en tiempo real
- [ ] Testing interface
- [ ] Documentación de variables disponibles

---

## 📅 FASE 6: TESTING Y DOCUMENTACIÓN
**Duración estimada:** 3-4 días

### Checkpoints:

#### 6.1 Suite de Testing Completa
- [ ] Tests unitarios para cada processor
- [ ] Tests de integración del sistema completo
- [ ] Tests de regresión
- [ ] Performance benchmarks
- [ ] Tests de carga

#### 6.2 Documentación Técnica
- [ ] Documentar arquitectura del sistema
- [ ] Guía para agregar nuevos processors
- [ ] API documentation
- [ ] Diagramas de flujo
- [ ] Ejemplos de código

#### 6.3 Documentación de Usuario
- [ ] Manual de configuración de cuestionarios
- [ ] Guía de creación de prompts
- [ ] Troubleshooting guide
- [ ] FAQs

---

## 📅 FASE 7: DEPLOYMENT Y MONITOREO
**Duración estimada:** 2-3 días

### Checkpoints:

#### 7.1 Preparación para Producción
- [ ] Revisión de código completa
- [ ] Optimización de queries
- [ ] Configuración de colas por tipo
- [ ] Variables de entorno
- [ ] Scripts de deployment

#### 7.2 Monitoreo y Métricas
- [ ] Dashboard de procesamiento por tipo
- [ ] Alertas de fallos
- [ ] Métricas de performance
- [ ] Logs estructurados
- [ ] Integración con monitoring tools

#### 7.3 Rollout Strategy
- [ ] Plan de rollout gradual
- [ ] Feature flags
- [ ] Rollback plan
- [ ] Comunicación a usuarios
- [ ] Post-deployment verification

---

## 🎯 Criterios de Éxito

### Funcionales
- ✅ Sistema procesa correctamente todos los tipos de cuestionarios
- ✅ Compatibilidad total con cuestionarios de audio existentes
- ✅ Nuevos tipos de cuestionarios funcionando
- ✅ Sistema de prompts dinámicos operativo

### No Funcionales
- ✅ Performance igual o mejor que sistema actual
- ✅ Código mantenible y extensible
- ✅ Documentación completa
- ✅ Cobertura de tests > 80%

### Métricas de Éxito
- Tiempo de procesamiento audio: < 45 segundos
- Tiempo de procesamiento multiple choice: < 5 segundos
- Tiempo de agregado de nuevo tipo: < 2 horas
- Cero downtime durante migración

---

## 🚨 Riesgos y Mitigaciones

### Riesgo 1: Incompatibilidad con datos existentes
**Mitigación:** 
- Extensive testing con datos reales
- Migración gradual con feature flags
- Backup completo antes de deployment

### Riesgo 2: Performance degradation
**Mitigación:**
- Benchmarking continuo
- Optimización proactiva
- Colas separadas por tipo

### Riesgo 3: Complejidad excesiva
**Mitigación:**
- Diseño simple y claro
- Documentación extensiva
- Code reviews regulares

---

## 📝 Notas de Implementación

### Prioridades
1. **Alta:** Mantener compatibilidad con sistema actual
2. **Alta:** Performance para audio processing
3. **Media:** Nuevos tipos de cuestionarios
4. **Baja:** Features avanzados de prompts

### Dependencias
- Laravel 11.x
- PHP 8.2+
- MySQL 8.0+
- Redis para cache
- Queue workers configurados

### Contactos
- **Tech Lead:** [Nombre]
- **Product Owner:** [Nombre]
- **QA Lead:** [Nombre]

---

## 📊 Tracking de Progreso

| Fase | Estado | Inicio | Fin | Completado |
|------|--------|--------|-----|------------|
| Fase 1: Base de Datos | ⏳ Pendiente | - | - | 0% |
| Fase 2: Infraestructura | ⏳ Pendiente | - | - | 0% |
| Fase 3: Migración Audio | ⏳ Pendiente | - | - | 0% |
| Fase 4: Nuevos Processors | ⏳ Pendiente | - | - | 0% |
| Fase 5: Prompts Dinámicos | ⏳ Pendiente | - | - | 0% |
| Fase 6: Testing | ⏳ Pendiente | - | - | 0% |
| Fase 7: Deployment | ⏳ Pendiente | - | - | 0% |

---

**Última actualización:** {{ now() }}
**Versión del documento:** 1.0.0