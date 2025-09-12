<!-- VISTA PARCIAL COMPARTIDA PARA DETALLE DE RESPUESTAS -->
<!-- DOS ACORDEONES PRINCIPALES -->
@php
    // Obtener preguntas del cuestionario
    $questionnaire = $response->questionnaire;
    $questions = [];
    
    if ($questionnaire) {
        // Primero intentar obtener del campo questions directamente (nueva estructura)
        if ($questionnaire->questions && is_array($questionnaire->questions)) {
            $questions = $questionnaire->questions;
        } 
        // Si no, intentar con buildStructure (estructura antigua)
        else {
            $structure = $questionnaire->buildStructure();
            if (isset($structure['sections'])) {
                foreach ($structure['sections'] as $section) {
                    if (isset($section['questions'])) {
                        $questions = array_merge($questions, $section['questions']);
                    }
                }
            }
        }
    }
    
    // Obtener respuestas desde el campo responses (puede ser JSON string o array)
    $allResponses = is_string($response->responses) ? json_decode($response->responses, true) : $response->responses;
    $allResponses = $allResponses ?? [];
    
    $transcriptions = is_string($response->transcriptions) ? json_decode($response->transcriptions, true) : $response->transcriptions;
    $transcriptions = $transcriptions ?? [];
    
    $prosodicAnalysis = is_string($response->prosodic_analysis) ? json_decode($response->prosodic_analysis, true) : $response->prosodic_analysis;
    $prosodicAnalysis = $prosodicAnalysis ?? [];
@endphp

<style>
    .main-accordion .accordion-item {
        border: none;
        border-radius: 20px;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        overflow: hidden;
        background: white;
    }
    
    .main-accordion .accordion-button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 500;
        font-size: 1.1rem;
        border: none;
        padding: 1.25rem 1.5rem;
        border-radius: 0;
    }
    
    .main-accordion .accordion-button:not(.collapsed) {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: none;
    }
    
    .main-accordion .accordion-button::after {
        filter: brightness(0) invert(1);
    }
    
    .main-accordion .accordion-button:focus {
        box-shadow: none;
        border-color: transparent;
    }
    
    .questions-accordion .accordion-item {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 0.75rem;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .questions-accordion .accordion-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .questions-accordion .accordion-button {
        background: white;
        color: #374151;
        font-weight: 400;
        border: none;
        padding: 1rem 1.25rem;
        transition: background 0.2s;
    }
    
    .questions-accordion .accordion-button:not(.collapsed) {
        background: #f3f4f6;
        color: #111827;
    }
    
    .questions-accordion .accordion-button:hover {
        background: #f9fafb;
    }
    
    .questions-accordion .accordion-button:focus {
        box-shadow: none;
        border-color: transparent;
    }
    
    .questions-accordion .accordion-body {
        background: #fafbfc;
        border-top: 1px solid #e5e7eb;
    }
    
    .section-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6b7280;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }
    
    .metric-card {
        background: white;
        border-radius: 10px;
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        transition: all 0.2s;
    }
    
    .metric-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .emotion-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 0.75rem;
        text-align: center;
        transition: all 0.2s;
    }
    
    .emotion-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .response-text {
        background: white;
        border-radius: 10px;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    .badge-metric {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .observation-box {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border-radius: 12px;
        padding: 1rem;
        border: 1px solid #fbbf24;
    }
    
    .btn-outline-primary {
        transition: all 0.3s ease;
    }
    
    .btn-outline-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.2);
    }
    
    .collapse {
        transition: height 0.35s ease;
    }
    
    .bg-light {
        background: linear-gradient(135deg, #f8f9fa 0%, #f3f4f6 100%) !important;
    }
</style>

<div class="accordion main-accordion mb-4" id="mainAccordion">
    <!-- ACORDEÓN 1: PREGUNTAS Y RESPUESTAS -->
    @if($response->questionnaire && count($allResponses) > 0)
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingQuestions">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#collapseQuestions" aria-expanded="true" aria-controls="collapseQuestions">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Análisis de Respuestas del Cuestionario
                </button>
            </h2>
            <div id="collapseQuestions" class="accordion-collapse collapse show" 
                 aria-labelledby="headingQuestions" data-bs-parent="#mainAccordion">
                <div class="accordion-body">
                    <div class="accordion questions-accordion" id="questionsAccordion">
                        @php 
                            $questionNumber = 1; 
                            // Si no hay questions del cuestionario, usar las keys de allResponses
                            $questionKeys = count($questions) > 0 ? array_keys($questions) : array_keys($allResponses);
                        @endphp
                        @foreach($allResponses as $questionId => $responseData)
                        @php
                            // Obtener los datos de la pregunta
                            $questionText = '';
                            $questionTitle = '';
                            $questionSkills = '';
                            
                            // Intentar con el ID directo primero
                            $questionData = null;
                            if (isset($questions[$questionId])) {
                                $questionData = $questions[$questionId];
                            } 
                            // Si no, intentar quitando el prefijo "reflective_questions_"
                            else {
                                $cleanId = str_replace('reflective_questions_', '', $questionId);
                                if (isset($questions[$cleanId])) {
                                    $questionData = $questions[$cleanId];
                                }
                            }
                            
                            if ($questionData) {
                                if (is_array($questionData)) {
                                    // Nueva estructura con question, title, skills
                                    $questionText = $questionData['question'] ?? 
                                                   $questionData['text'] ?? 
                                                   $questionData;
                                    $questionTitle = $questionData['title'] ?? '';
                                    $questionSkills = $questionData['skills'] ?? '';
                                } else {
                                    $questionText = $questionData;
                                }
                            }
                            
                            // Si no encontramos la pregunta, usar un texto más descriptivo
                            if (empty($questionText) && empty($questionTitle)) {
                                $questionNumber = str_replace(['reflective_questions_', 'q'], '', $questionId);
                                $questionTitle = "Pregunta Reflexiva " . $questionNumber;
                                $questionText = "Respuesta a pregunta " . $questionNumber;
                            }
                            
                            $geminiAnalysis = $responseData['gemini_analysis'] ?? null;
                            $transcription = $responseData['transcription_text'] ?? 
                                            $transcriptions[$questionId] ?? 
                                            ($geminiAnalysis['transcripcion'] ?? '');
                        @endphp
                        @if($responseData)
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-{{ $questionId }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse-{{ $questionId }}" aria-expanded="false" 
                                            aria-controls="collapse-{{ $questionId }}">
                                        <span class="badge bg-gradient rounded-circle me-2" style="background: linear-gradient(135deg, #667eea, #764ba2); min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">{{ $questionNumber }}</span>
                                        <div style="font-size: 0.95rem;">
                                            @if($questionTitle)
                                                <strong>{{ $questionTitle }}</strong>
                                                @if($questionSkills)
                                                    <span class="text-muted ms-2" style="font-size: 0.85rem; font-style: italic;">({{ $questionSkills }})</span>
                                                @endif
                                            @else
                                                {{ $questionText }}
                                            @endif
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse-{{ $questionId }}" class="accordion-collapse collapse" 
                                     aria-labelledby="heading-{{ $questionId }}" data-bs-parent="#questionsAccordion">
                                    <div class="accordion-body">
                                    <!-- PREGUNTA COMPLETA (COLAPSABLE) -->
                                    @if($questionText)
                                        <div class="mb-3">
                                            <div class="d-flex align-items-center mb-2">
                                                <button class="btn btn-sm btn-outline-primary" type="button" 
                                                        data-bs-toggle="collapse" 
                                                        data-bs-target="#question-detail-{{ $questionId }}" 
                                                        aria-expanded="false"
                                                        onclick="this.innerHTML = this.getAttribute('aria-expanded') === 'true' ? '<i class=\'fas fa-question-circle me-1\'></i> Ver pregunta completa' : '<i class=\'fas fa-eye-slash me-1\'></i> Ocultar pregunta'">
                                                    <i class="fas fa-question-circle me-1"></i> Ver pregunta completa
                                                </button>
                                                @if($questionSkills)
                                                    <span class="ms-3 text-muted small" style="font-style: italic;">
                                                        <i class="fas fa-star me-1"></i> {{ $questionSkills }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="collapse" id="question-detail-{{ $questionId }}">
                                                <div class="p-3 bg-light rounded">
                                                    @if($questionTitle)
                                                        <h6 class="text-primary mb-2">{{ $questionTitle }}</h6>
                                                    @endif
                                                    <p class="mb-0" style="white-space: pre-line; font-size: 0.95rem;">{{ $questionText }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    <!-- RESPUESTA TRANSCRITA -->
                                    @if($transcription)
                                        <div class="mb-3">
                                            <div class="section-label">Respuesta Transcrita</div>
                                            <div class="response-text">
                                                {{ $transcription }}
                                            </div>
                                        
                                        @if($audioUrl = null)
                                            @php
                                                $audioFiles = is_string($response->audio_files) ? json_decode($response->audio_files, true) : $response->audio_files;
                                                $audioFiles = $audioFiles ?? [];
                                                if (isset($audioFiles[$questionId]) && isset($audioFiles[$questionId]['s3_path'])) {
                                                    try {
                                                        $audioUrl = \Storage::disk('audio-storage')->temporaryUrl(
                                                            $audioFiles[$questionId]['s3_path'],
                                                            now()->addMinutes(30)
                                                        );
                                                    } catch (\Exception $e) {}
                                                }
                                            @endphp
                                            @if($audioUrl)
                                                <div class="mt-2">
                                                    <button class="btn btn-sm btn-outline-success">
                                                        <i class="fas fa-volume-up"></i> Audio disponible
                                                    </button>
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                                
                                    @if($geminiAnalysis)
                                        <!-- ANÁLISIS EMOCIONAL -->
                                        @if(isset($geminiAnalysis['analisis_emocional']))
                                            <div class="mb-3">
                                                <div class="section-label">Análisis Emocional</div>
                                                @php $emociones = $geminiAnalysis['analisis_emocional']; @endphp
                                                
                                                <div class="row g-2">
                                                    <div class="col">
                                                        <div class="emotion-card">
                                                            <span style="font-size: 1.5rem;">😊</span>
                                                            <div class="mt-1">
                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Felicidad</small>
                                                                <strong class="text-success">{{ round(($emociones['felicidad'] ?? 0) * 100) }}%</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="emotion-card">
                                                            <span style="font-size: 1.5rem;">😢</span>
                                                            <div class="mt-1">
                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Tristeza</small>
                                                                <strong class="text-info">{{ round(($emociones['tristeza'] ?? 0) * 100) }}%</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="emotion-card">
                                                            <span style="font-size: 1.5rem;">😰</span>
                                                            <div class="mt-1">
                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Ansiedad</small>
                                                                <strong class="text-warning">{{ round(($emociones['ansiedad'] ?? 0) * 100) }}%</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="emotion-card">
                                                            <span style="font-size: 1.5rem;">😠</span>
                                                            <div class="mt-1">
                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Enojo</small>
                                                                <strong class="text-danger">{{ round(($emociones['enojo'] ?? 0) * 100) }}%</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="emotion-card">
                                                            <span style="font-size: 1.5rem;">😨</span>
                                                            <div class="mt-1">
                                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Miedo</small>
                                                                <strong class="text-secondary">{{ round(($emociones['miedo'] ?? 0) * 100) }}%</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-12 mt-2 text-center">
                                                        <span class="badge-metric" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
                                                            <i class="fas fa-star me-1"></i>
                                                            Emoción Dominante: {{ ucfirst($emociones['emocion_dominante'] ?? 'N/A') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    
                                        <!-- MÉTRICAS DEL HABLA -->
                                        @if(isset($geminiAnalysis['metricas_prosodicas']))
                                            <div class="mb-3">
                                                <div class="section-label">Métricas del Habla</div>
                                                @php $metricas = $geminiAnalysis['metricas_prosodicas']; @endphp
                                                
                                                <div class="row g-2">
                                                    <div class="col-md-4">
                                                        <div class="metric-card">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">Velocidad</small>
                                                                <strong>{{ ucfirst($metricas['velocidad_habla'] ?? 'Normal') }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="metric-card">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">Titubeos</small>
                                                                <span class="badge {{ ($metricas['titubeos'] ?? 0) > 2 ? 'bg-warning' : 'bg-success' }}">
                                                                    {{ $metricas['titubeos'] ?? 0 }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="metric-card">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">Energía</small>
                                                                <strong class="text-info">{{ round(($metricas['energia_vocal'] ?? 0) * 100) }}%</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="metric-card">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">Claridad</small>
                                                                <strong class="text-success">{{ round(($metricas['claridad_diccion'] ?? 0) * 100) }}%</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="metric-card">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">Duración</small>
                                                                <strong>{{ $geminiAnalysis['duracion_segundos'] ?? 'N/A' }}s</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="metric-card">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">Pausas</small>
                                                                <span class="badge {{ ($metricas['pausas_significativas'] ?? 0) > 2 ? 'bg-warning' : 'bg-success' }}">
                                                                    {{ $metricas['pausas_significativas'] ?? 0 }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    
                                        <!-- INDICADORES PSICOLÓGICOS -->
                                        @if(isset($geminiAnalysis['indicadores_psicologicos']))
                                            <div class="mb-3">
                                                <div class="section-label">Indicadores Psicológicos</div>
                                                @php $indicadores = $geminiAnalysis['indicadores_psicologicos']; @endphp
                                                
                                                <div class="row g-2">
                                                    @foreach($indicadores as $indicador => $valor)
                                                        <div class="col-md-6">
                                                            <div class="metric-card">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $indicador)) }}</small>
                                                                    <span class="badge {{ $valor >= 0.7 ? 'bg-success' : ($valor >= 0.4 ? 'bg-warning' : 'bg-danger') }}">
                                                                        {{ round($valor * 100) }}%
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    
                                        <!-- OBSERVACIONES -->
                                        @if(isset($geminiAnalysis['observaciones']) && $geminiAnalysis['observaciones'])
                                            <div class="observation-box mt-3">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fas fa-lightbulb text-warning me-2"></i>
                                                    <span class="section-label mb-0">Observaciones del Análisis</span>
                                                </div>
                                                <p class="mb-0 text-dark" style="font-size: 0.9rem; line-height: 1.5;">{{ $geminiAnalysis['observaciones'] }}</p>
                                            </div>
                                        @endif
                                    @endif
                                    </div>
                                </div>
                            </div>
                            @php $questionNumber++; @endphp
                        @endif
                    @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- ACORDEÓN 2: REPORTE COMPREHENSIVO -->
    @if($response->comprehensive_report)
        @php
            $report = is_string($response->comprehensive_report) ? 
                     json_decode($response->comprehensive_report, true) : 
                     $response->comprehensive_report;
        @endphp
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingReport">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                        data-bs-target="#collapseReport" aria-expanded="false" aria-controls="collapseReport">
                    <i class="fas fa-file-medical-alt me-2"></i>
                    Reporte Psicológico Integral - Análisis de 15 Competencias
                </button>
            </h2>
            <div id="collapseReport" class="accordion-collapse collapse" 
                 aria-labelledby="headingReport" data-bs-parent="#mainAccordion">
                <div class="accordion-body">
                    @if(isset($report['sections']) && count($report['sections']) > 0)
                        @foreach($report['sections'] as $section)
                            <div class="report-section mb-4">
                                <h5 class="text-primary border-bottom pb-2">
                                    {{ $section['title'] }}
                                </h5>
                                
                                @if(str_contains(strtolower($section['title']), 'competencias'))
                                    <!-- Parsear y mostrar las 15 competencias -->
                                    @php
                                        $competencias = [];
                                        $lines = explode("\n", $section['content']);
                                        foreach($lines as $line) {
                                            if (preg_match('/^(\d+)\.\s*(?:\*\*)?(.+?)(?:\*\*)?:\s*(\d+)\/10\s*[-–]\s*(.+)$/i', trim($line), $matches)) {
                                                $competencias[] = [
                                                    'numero' => $matches[1],
                                                    'nombre' => trim($matches[2], '* '),
                                                    'puntaje' => $matches[3],
                                                    'descripcion' => $matches[4]
                                                ];
                                            }
                                        }
                                    @endphp
                                    
                                    @if(count($competencias) > 0)
                                        <div class="row">
                                            @foreach($competencias as $comp)
                                                <div class="col-12 mb-3">
                                                    <div class="competency-item p-3 border rounded">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="mb-0">
                                                                <span class="badge me-2" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">{{ $comp['numero'] }}</span>
                                                                {{ $comp['nombre'] }}
                                                            </h6>
                                                            <span class="competency-score-badge fs-6" data-score="{{ $comp['puntaje'] }}">
                                                                {{ $comp['puntaje'] }}/10
                                                            </span>
                                                        </div>
                                                        <div class="progress mb-2" style="height: 8px; background-color: #f1f5f9;">
                                                            <div class="competency-progress-bar" 
                                                                 data-score="{{ $comp['puntaje'] }}"
                                                                 style="width: {{ $comp['puntaje'] * 10 }}%; height: 100%; transition: width 0.6s ease;"></div>
                                                        </div>
                                                        <small class="text-muted">{{ $comp['descripcion'] }}</small>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="content-section">
                                            {!! nl2br(e($section['content'])) !!}
                                        </div>
                                    @endif
                                @else
                                    <div class="content-section">
                                        {!! nl2br(e($section['content'])) !!}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @elseif(isset($report['content']))
                        <div class="content-report">
                            {!! nl2br(e($report['content'])) !!}
                        </div>
                    @endif
                    
                    @if(isset($report['generated_at']))
                        <hr>
                        <small class="text-muted">
                            <i class="fas fa-clock"></i>
                            Reporte generado el {{ \Carbon\Carbon::parse($report['generated_at'])->format('d/m/Y H:i') }}
                        </small>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<!-- ESTILOS CSS -->
<style>
.question-card {
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
.question-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.emotion-box {
    padding: 10px;
    border-radius: 8px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}
.emotion-box:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}
.competency-item {
    background: #f8f9fa;
    transition: all 0.3s ease;
}
.competency-item:hover {
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.content-section {
    line-height: 1.8;
    font-size: 14px;
    color: #495057;
}
.report-section h5 {
    color: #6366f1;
    font-weight: 600;
}
.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0c63e4;
}
.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(0,0,0,.125);
}

/* Competency Scores - Purple Theme */
.competency-score-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 0.375rem;
    font-weight: 600;
    display: inline-block;
}

.competency-score-badge[data-score="10"],
.competency-score-badge[data-score="9"] {
    background: linear-gradient(135deg, #8b5cf6, #d946ef);
    color: white;
}

.competency-score-badge[data-score="8"],
.competency-score-badge[data-score="7"] {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
}

.competency-score-badge[data-score="6"],
.competency-score-badge[data-score="5"] {
    background: linear-gradient(135deg, #a5b4fc, #c4b5fd);
    color: #4f46e5;
}

.competency-score-badge[data-score="4"],
.competency-score-badge[data-score="3"] {
    background: #e0e7ff;
    color: #4f46e5;
}

.competency-score-badge[data-score="2"],
.competency-score-badge[data-score="1"],
.competency-score-badge[data-score="0"] {
    background: #f3f4f6;
    color: #6b7280;
}

/* Progress Bars - Matching Purple Gradient */
.competency-progress-bar[data-score="10"],
.competency-progress-bar[data-score="9"] {
    background: linear-gradient(90deg, #8b5cf6, #d946ef);
}

.competency-progress-bar[data-score="8"],
.competency-progress-bar[data-score="7"] {
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
}

.competency-progress-bar[data-score="6"],
.competency-progress-bar[data-score="5"] {
    background: linear-gradient(90deg, #818cf8, #a5b4fc);
}

.competency-progress-bar[data-score="4"],
.competency-progress-bar[data-score="3"] {
    background: #c4b5fd;
}

.competency-progress-bar[data-score="2"],
.competency-progress-bar[data-score="1"],
.competency-progress-bar[data-score="0"] {
    background: #d1d5db;
}
</style>