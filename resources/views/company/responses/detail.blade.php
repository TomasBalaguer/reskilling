@extends('company.layout')

@section('title', 'Respuesta #' . $response->id . ' - ' . $response->respondent_name)
@section('page-title', 'Análisis de Respuesta #' . $response->id)

@section('page-actions')
    <div class="btn-group" role="group">
        @if($response->comprehensive_report || $response->processing_status === 'analyzed')
            <a href="{{ route('company.responses.report', $response->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
               class="btn btn-success">
                <i class="fas fa-download"></i> Descargar Reporte PDF
            </a>
        @endif
        
        <form method="POST" action="{{ route('company.responses.reprocess', $response->id) }}" class="d-inline">
            @csrf
            @if(request()->has('company_id'))
                <input type="hidden" name="company_id" value="{{ request('company_id') }}">
            @endif
            <button type="submit" class="btn btn-warning" 
                    onclick="return confirm('¿Estás seguro de que quieres re-procesar esta respuesta?')">
                <i class="fas fa-redo"></i> Re-procesar
            </button>
        </form>
        
        <a href="{{ route('company.campaigns.detail', $response->campaign_id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
           class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Campaña
        </a>
    </div>
@endsection

@section('content')
    <!-- Información del respondente -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user"></i> Información del Respondente
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> {{ $response->respondent_name }}</p>
                            <p><strong>Email:</strong> {{ $response->respondent_email }}</p>
                            <p><strong>Edad:</strong> {{ $response->respondent_age ?? 'No especificada' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Campaña:</strong> {{ $response->campaign->name }}</p>
                            <p><strong>Cuestionario:</strong> {{ $response->questionnaire->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> Estado del Procesamiento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        @switch($response->processing_status)
                            @case('completed')
                            @case('analyzed')
                                <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                                <h6 class="text-success">Completado</h6>
                                @break
                            @case('processing')
                                <i class="fas fa-spinner fa-spin fa-3x text-info mb-2"></i>
                                <h6 class="text-info">Procesando</h6>
                                @break
                            @case('failed')
                                <i class="fas fa-exclamation-circle fa-3x text-danger mb-2"></i>
                                <h6 class="text-danger">Error</h6>
                                @break
                            @default
                                <i class="fas fa-clock fa-3x text-warning mb-2"></i>
                                <h6 class="text-warning">Pendiente</h6>
                        @endswitch
                        
                        <hr>
                        <small class="text-muted">
                            <strong>Enviado:</strong><br>
                            {{ $response->created_at->format('d/m/Y H:i') }}
                        </small>
                        
                        @if($response->duration_minutes)
                            <br><br>
                            <small class="text-muted">
                                <strong>Duración:</strong><br>
                                {{ $response->duration_minutes }} minutos
                            </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DOS ACORDEONES PRINCIPALES -->
    @php
        // Obtener preguntas del cuestionario
        $questionnaire = $response->questionnaire;
        $questions = [];
        if ($questionnaire) {
            $structure = $questionnaire->buildStructure();
            if (isset($structure['sections'])) {
                foreach ($structure['sections'] as $section) {
                    if (isset($section['questions'])) {
                        $questions = array_merge($questions, $section['questions']);
                    }
                }
            }
        }
        
        // Obtener respuestas desde el campo responses (JSON)
        $allResponses = json_decode($response->responses, true) ?? [];
        $transcriptions = json_decode($response->transcriptions, true) ?? [];
        $prosodicAnalysis = json_decode($response->prosodic_analysis, true) ?? [];
    @endphp
    
    <div class="accordion mb-4" id="mainAccordion">
        <!-- ACORDEÓN 1: PREGUNTAS Y RESPUESTAS -->
        @if($response->questionnaire && count($allResponses) > 0)
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingQuestions">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseQuestions" aria-expanded="true" aria-controls="collapseQuestions">
                        <i class="fas fa-clipboard-list me-2"></i>
                        <strong>Análisis de Respuestas del Cuestionario</strong>
                    </button>
                </h2>
                <div id="collapseQuestions" class="accordion-collapse collapse show" 
                     aria-labelledby="headingQuestions" data-bs-parent="#mainAccordion">
                    <div class="accordion-body">
                        @php $questionNumber = 1; @endphp
                        @foreach($questions as $questionId => $questionData)
                            @if(isset($allResponses[$questionId]))
                                @php
                                    $responseData = $allResponses[$questionId];
                                    $geminiAnalysis = $responseData['gemini_analysis'] ?? null;
                                    $transcription = $responseData['transcription_text'] ?? 
                                                    $transcriptions[$questionId] ?? 
                                                    ($geminiAnalysis['transcripcion'] ?? '');
                                @endphp
                                
                                <div class="question-card mb-4 p-4 border rounded bg-white">
                                    <!-- PREGUNTA -->
                                    <div class="d-flex align-items-start mb-3">
                                        <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">{{ $questionNumber }}</span>
                                        <div class="flex-grow-1">
                                            <p class="text-muted mb-2">{{ is_array($questionData) ? ($questionData['text'] ?? 'Pregunta ' . $questionNumber) : $questionData }}</p>
                                        </div>
                                    </div>
                                    
                                    <!-- RESPUESTA TRANSCRITA -->
                                    @if($transcription)
                                        <div class="mb-3">
                                            <h6 class="text-primary mb-2">Respuesta Transcrita:</h6>
                                            <div class="p-3 bg-light rounded">
                                                {{ $transcription }}
                                            </div>
                                            
                                            @if($audioUrl = null)
                                                @php
                                                    $audioFiles = json_decode($response->audio_files, true) ?? [];
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
                                            <div class="mb-4">
                                                <h6 class="text-dark mb-3">Análisis Emocional</h6>
                                                @php $emociones = $geminiAnalysis['analisis_emocional']; @endphp
                                                
                                                <div class="row text-center mb-3">
                                                    <div class="col">
                                                        <div class="emotion-box">
                                                            <span style="font-size: 2rem;">😊</span><br>
                                                            <small class="text-muted">Felicidad</small><br>
                                                            <strong class="text-success">{{ round(($emociones['felicidad'] ?? 0) * 100) }}%</strong>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="emotion-box">
                                                            <span style="font-size: 2rem;">😢</span><br>
                                                            <small class="text-muted">Tristeza</small><br>
                                                            <strong class="text-info">{{ round(($emociones['tristeza'] ?? 0) * 100) }}%</strong>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="emotion-box">
                                                            <span style="font-size: 2rem;">😰</span><br>
                                                            <small class="text-muted">Ansiedad</small><br>
                                                            <strong class="text-warning">{{ round(($emociones['ansiedad'] ?? 0) * 100) }}%</strong>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="emotion-box">
                                                            <span style="font-size: 2rem;">😠</span><br>
                                                            <small class="text-muted">Enojo</small><br>
                                                            <strong class="text-danger">{{ round(($emociones['enojo'] ?? 0) * 100) }}%</strong>
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="emotion-box">
                                                            <span style="font-size: 2rem;">😨</span><br>
                                                            <small class="text-muted">Miedo</small><br>
                                                            <strong class="text-secondary">{{ round(($emociones['miedo'] ?? 0) * 100) }}%</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-center p-2 bg-light rounded">
                                                    <small class="text-muted">Emoción dominante:</small> 
                                                    <strong class="text-primary">{{ ucfirst($emociones['emocion_dominante'] ?? 'N/A') }}</strong>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <!-- MÉTRICAS DEL HABLA -->
                                        @if(isset($geminiAnalysis['metricas_prosodicas']))
                                            <div class="mb-4">
                                                <h6 class="text-dark mb-3">Métricas del Habla</h6>
                                                @php $metricas = $geminiAnalysis['metricas_prosodicas']; @endphp
                                                
                                                <div class="row">
                                                    <div class="col-md-4 mb-2">
                                                        <small class="text-muted">Velocidad</small><br>
                                                        <strong class="text-primary">{{ ucfirst($metricas['velocidad_habla'] ?? 'Normal') }}</strong>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <small class="text-muted">Titubeos</small><br>
                                                        <strong class="{{ ($metricas['titubeos'] ?? 0) > 2 ? 'text-warning' : 'text-success' }}">
                                                            {{ $metricas['titubeos'] ?? 0 }}
                                                        </strong>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <small class="text-muted">Energía Vocal</small><br>
                                                        <strong class="text-primary">{{ round(($metricas['energia_vocal'] ?? 0) * 100) }}%</strong>
                                                    </div>
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-4 mb-2">
                                                        <small class="text-muted">Claridad</small><br>
                                                        <strong class="text-success">{{ round(($metricas['claridad_diccion'] ?? 0) * 100) }}%</strong>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <small class="text-muted">Duración</small><br>
                                                        <strong>{{ $geminiAnalysis['duracion_segundos'] ?? 'N/A' }}s</strong>
                                                    </div>
                                                    <div class="col-md-4 mb-2">
                                                        <small class="text-muted">Pausas</small><br>
                                                        <strong class="{{ ($metricas['pausas_significativas'] ?? 0) > 2 ? 'text-warning' : 'text-success' }}">
                                                            {{ $metricas['pausas_significativas'] ?? 0 }}
                                                        </strong>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <!-- INDICADORES PSICOLÓGICOS -->
                                        @if(isset($geminiAnalysis['indicadores_psicologicos']))
                                            <div class="mb-4">
                                                <h6 class="text-dark mb-3">Indicadores Psicológicos</h6>
                                                @php $indicadores = $geminiAnalysis['indicadores_psicologicos']; @endphp
                                                
                                                <div class="row">
                                                    @foreach($indicadores as $indicador => $valor)
                                                        <div class="col-md-6 mb-2">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $indicador)) }}</small>
                                                                <strong class="{{ $valor >= 0.7 ? 'text-success' : ($valor >= 0.4 ? 'text-warning' : 'text-danger') }}">
                                                                    {{ round($valor * 100) }}%
                                                                </strong>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <!-- OBSERVACIONES -->
                                        @if(isset($geminiAnalysis['observaciones']) && $geminiAnalysis['observaciones'])
                                            <div class="p-3 bg-light rounded border-start border-primary border-3">
                                                <h6 class="text-dark mb-2">Observaciones del Análisis</h6>
                                                <p class="mb-0 text-muted">{{ $geminiAnalysis['observaciones'] }}</p>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                                @php $questionNumber++; @endphp
                            @endif
                        @endforeach
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
                        <strong>Reporte Psicológico Integral - Análisis de 15 Competencias</strong>
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
                                                                    <span class="badge bg-primary me-2">{{ $comp['numero'] }}</span>
                                                                    {{ $comp['nombre'] }}
                                                                </h6>
                                                                <span class="badge bg-{{ $comp['puntaje'] >= 8 ? 'success' : ($comp['puntaje'] >= 5 ? 'warning' : 'danger') }} fs-6">
                                                                    {{ $comp['puntaje'] }}/10
                                                                </span>
                                                            </div>
                                                            <div class="progress mb-2" style="height: 8px;">
                                                                <div class="progress-bar bg-{{ $comp['puntaje'] >= 8 ? 'success' : ($comp['puntaje'] >= 5 ? 'warning' : 'danger') }}" 
                                                                     style="width: {{ $comp['puntaje'] * 10 }}%"></div>
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

    <!-- Sección de depuración oculta (comentada) -->
    @if(false)
        <!-- Debug Info -->
        <div class="alert alert-secondary mt-4">
            <h6>Debug - Estructura de datos:</h6>
            <pre>{{ json_encode($allResponses, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    <!-- Análisis Prosódico -->
    @if($response->prosodic_analysis)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-wave-square"></i> Análisis Prosódico
                </h5>
            </div>
            <div class="card-body">
                <pre class="bg-light p-3 rounded">{{ json_encode($response->prosodic_analysis, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif

    <!-- Debug: Información técnica -->
    <div class="card border-secondary">
        <div class="card-header bg-light">
            <h6 class="mb-0 text-muted">
                <i class="fas fa-cog"></i> Información Técnica
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        <strong>ID Respuesta:</strong> {{ $response->id }}<br>
                        <strong>Estado IA:</strong> {{ $response->ai_analysis_status ?? 'N/A' }}<br>
                        <strong>Completado IA:</strong> {{ $response->ai_analysis_completed_at ?? 'N/A' }}<br>
                    </small>
                </div>
                <div class="col-md-6">
                    <small class="text-muted">
                        <strong>Tipo de acceso:</strong> {{ $response->access_type ?? 'N/A' }}<br>
                        <strong>Token de acceso:</strong> {{ $response->access_token ?? 'N/A' }}<br>
                        <strong>Reporte generado:</strong> {{ $response->report_generated_at ?? 'N/A' }}<br>
                    </small>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
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
    color: #0d6efd;
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
</style>
@endsection