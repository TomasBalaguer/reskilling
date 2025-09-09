@extends('admin.layout')

@section('title', 'Respuesta #' . $response->id . ' - ' . $response->respondent_name)
@section('page-title', 'Análisis de Respuesta #' . $response->id)

@section('page-actions')
    <div class="btn-group" role="group">
        @if($response->comprehensive_report || $response->processing_status === 'analyzed')
            <a href="{{ route('admin.responses.report', $response->id) }}" 
               class="btn btn-success">
                <i class="fas fa-download"></i> Descargar Reporte PDF
            </a>
        @endif
        
        <form method="POST" action="{{ route('admin.responses.reprocess', $response->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-warning" 
                    onclick="return confirm('¿Estás seguro de que quieres re-procesar esta respuesta?')">
                <i class="fas fa-redo"></i> Re-procesar
            </button>
        </form>
        
        <a href="{{ route('admin.campaigns.detail', $response->campaign_id) }}" 
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
                            <p><strong>Empresa:</strong> {{ $response->campaign->company->name }}</p>
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

    <!-- Respuestas del cuestionario -->
    @if($response->questionnaire)
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list"></i> Análisis Detallado de Respuestas
                </h5>
            </div>
            <div class="card-body">
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
                    
                    // Obtener respuestas y análisis
                    $allResponses = $response->responses ?? [];
                    $transcriptions = $response->transcriptions ?? [];
                    $prosodicAnalysis = $response->prosodic_analysis ?? [];
                @endphp
                
                @if(count($questions) > 0)
                    @php $questionNumber = 1; @endphp
                    @foreach($questions as $questionId => $questionData)
                        <div class="question-block mb-5 pb-4 border-bottom">
                            <!-- PREGUNTA -->
                            <div class="mb-3">
                                <h5 class="text-primary">
                                    <span class="badge bg-primary me-2">{{ $questionNumber }}</span>
                                    Pregunta
                                </h5>
                                <div class="bg-light p-3 rounded">
                                    <strong>{{ is_array($questionData) ? ($questionData['text'] ?? $questionData) : $questionData }}</strong>
                                </div>
                            </div>
                            
                            <!-- RESPUESTA TRANSCRITA -->
                            @if(isset($transcriptions[$questionId]) || isset($allResponses[$questionId]['transcription_text']))
                                @php
                                    $transcription = $transcriptions[$questionId] ?? $allResponses[$questionId]['transcription_text'] ?? '';
                                @endphp
                                
                                @if($transcription)
                                    <div class="mb-3">
                                        <h6 class="text-success">
                                            <i class="fas fa-microphone"></i> Respuesta Transcrita
                                        </h6>
                                        <div class="alert alert-light border" role="alert">
                                            <em>"{{ $transcription }}"</em>
                                        </div>
                                    </div>
                                @endif
                            @endif
                            
                            <!-- ANÁLISIS PROSÓDICO -->
                            @if(isset($prosodicAnalysis[$questionId]) || isset($allResponses[$questionId]['gemini_analysis']))
                                @php
                                    $analysis = $prosodicAnalysis[$questionId] ?? $allResponses[$questionId]['gemini_analysis'] ?? null;
                                @endphp
                                
                                @if($analysis)
                                    <div class="mb-3">
                                        <h6 class="text-info">
                                            <i class="fas fa-chart-line"></i> Análisis de la Respuesta
                                        </h6>
                                        
                                        <div class="row">
                                            <!-- Análisis Emocional -->
                                            @if(isset($analysis['analisis_emocional']))
                                                <div class="col-md-6 mb-3">
                                                    <div class="card h-100">
                                                        <div class="card-header bg-light">
                                                            <strong>Estado Emocional</strong>
                                                        </div>
                                                        <div class="card-body">
                                                            @php $emociones = $analysis['analisis_emocional']; @endphp
                                                            
                                                            <div class="mb-2">
                                                                <strong>Emoción dominante:</strong> 
                                                                <span class="badge bg-success">{{ ucfirst($emociones['emocion_dominante'] ?? 'N/A') }}</span>
                                                            </div>
                                                            
                                                            @foreach(['felicidad' => 'success', 'tristeza' => 'info', 'ansiedad' => 'warning', 'enojo' => 'danger', 'miedo' => 'secondary'] as $emocion => $color)
                                                                @if(isset($emociones[$emocion]))
                                                                    <div class="mb-2">
                                                                        <small>{{ ucfirst($emocion) }}:</small>
                                                                        <div class="progress" style="height: 20px;">
                                                                            <div class="progress-bar bg-{{ $color }}" 
                                                                                 style="width: {{ $emociones[$emocion] * 100 }}%">
                                                                                {{ round($emociones[$emocion] * 100) }}%
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            
                                            <!-- Indicadores Psicológicos -->
                                            @if(isset($analysis['indicadores_psicologicos']))
                                                <div class="col-md-6 mb-3">
                                                    <div class="card h-100">
                                                        <div class="card-header bg-light">
                                                            <strong>Indicadores Psicológicos</strong>
                                                        </div>
                                                        <div class="card-body">
                                                            @php $indicadores = $analysis['indicadores_psicologicos']; @endphp
                                                            
                                                            @foreach($indicadores as $indicador => $valor)
                                                                <div class="mb-2">
                                                                    <small>{{ ucfirst(str_replace('_', ' ', $indicador)) }}:</small>
                                                                    <div class="progress" style="height: 20px;">
                                                                        <div class="progress-bar bg-primary" 
                                                                             style="width: {{ $valor * 100 }}%">
                                                                            {{ round($valor * 100) }}%
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Métricas del Habla -->
                                        @if(isset($analysis['metricas_prosodicas']))
                                            <div class="card mb-3">
                                                <div class="card-header bg-light">
                                                    <strong>Características del Habla</strong>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        @php $metricas = $analysis['metricas_prosodicas']; @endphp
                                                        
                                                        <div class="col-md-3">
                                                            <strong>Velocidad:</strong> 
                                                            <span class="badge bg-info">{{ ucfirst($metricas['velocidad_habla'] ?? 'N/A') }}</span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <strong>Variación tonal:</strong> 
                                                            <span class="badge bg-info">{{ ucfirst($metricas['variacion_tonal'] ?? 'N/A') }}</span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <strong>Titubeos:</strong> 
                                                            <span class="badge bg-{{ ($metricas['titubeos'] ?? 0) > 2 ? 'warning' : 'success' }}">
                                                                {{ $metricas['titubeos'] ?? 0 }}
                                                            </span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <strong>Pausas largas:</strong> 
                                                            <span class="badge bg-{{ ($metricas['pausas_significativas'] ?? 0) > 2 ? 'warning' : 'success' }}">
                                                                {{ $metricas['pausas_significativas'] ?? 0 }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    
                                                    @if(isset($metricas['claridad_diccion']))
                                                        <div class="mt-2">
                                                            <strong>Claridad de dicción:</strong>
                                                            <div class="progress" style="height: 20px;">
                                                                <div class="progress-bar bg-success" 
                                                                     style="width: {{ $metricas['claridad_diccion'] * 100 }}%">
                                                                    {{ round($metricas['claridad_diccion'] * 100) }}%
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <!-- Observaciones -->
                                        @if(isset($analysis['observaciones']) && $analysis['observaciones'])
                                            <div class="alert alert-info">
                                                <strong>Observaciones:</strong> {{ $analysis['observaciones'] }}
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @endif
                            
                            <!-- Audio Player -->
                            @php
                                $audioUrl = null;
                                if ($response->audio_files && isset($response->audio_files[$questionId])) {
                                    $audioFile = $response->audio_files[$questionId];
                                    if (is_array($audioFile) && isset($audioFile['s3_path'])) {
                                        try {
                                            $audioUrl = \Storage::disk('audio-storage')->temporaryUrl(
                                                $audioFile['s3_path'],
                                                now()->addMinutes(30)
                                            );
                                        } catch (\Exception $e) {
                                            // Fallback
                                        }
                                    }
                                }
                            @endphp
                            
                            @if($audioUrl)
                                <div class="mt-3">
                                    <audio controls class="w-100" style="max-width: 500px;">
                                        <source src="{{ $audioUrl }}" type="audio/mp4">
                                        <source src="{{ $audioUrl }}" type="audio/mpeg">
                                    </audio>
                                </div>
                            @endif
                        </div>
                        @php $questionNumber++; @endphp
                    @endforeach
                @else
                    @if(count($questions) > 0)
                        <!-- Mostrar preguntas sin respuestas cuando están pendientes -->
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Respuesta en procesamiento.</strong> 
                            Las respuestas de audio están siendo transcritas y analizadas.
                        </div>
                        
                        @foreach($questions as $questionId => $questionData)
                            <div class="border-bottom pb-3 mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="fas fa-question-circle"></i> Pregunta {{ strtoupper($questionId) }}
                                </h6>
                                <div class="bg-light p-3 rounded">
                                    <em>{{ is_array($questionData) ? ($questionData['text'] ?? $questionData) : $questionData }}</em>
                                </div>
                                <div class="mt-2 text-muted">
                                    <i class="fas fa-hourglass-half"></i> Esperando transcripción de audio...
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-warning">
                            No se encontraron las preguntas del cuestionario.
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif


    <!-- Reporte Comprehensivo con 15 Competencias -->
    @if($response->comprehensive_report)
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">
                    <i class="fas fa-file-medical-alt"></i> Reporte Psicológico Profesional - Análisis de Competencias
                </h4>
                <small>Evaluación integral de habilidades blandas basada en las respuestas y análisis prosódico</small>
            </div>
            <div class="card-body">
                @if(isset($response->comprehensive_report['sections']) && count($response->comprehensive_report['sections']) > 0)
                    @foreach($response->comprehensive_report['sections'] as $section)
                        @if(str_contains(strtolower($section['title']), 'competencias'))
                            <!-- Sección especial para competencias -->
                            <div class="mb-5">
                                <h4 class="text-primary border-bottom pb-2 mb-4">
                                    <i class="fas fa-star"></i> {{ $section['title'] }}
                                </h4>
                                
                                @php
                                    // Parsear las competencias del contenido
                                    $competencias = [];
                                    $lines = explode("\n", $section['content']);
                                    $currentCompetencia = null;
                                    
                                    foreach($lines as $line) {
                                        // Detectar líneas que empiezan con número y tienen un puntaje
                                        if (preg_match('/^(\d+)\.\s*\*\*(.+?)\*\*:\s*(\d+)\/10\s*[-–]\s*(.+)$/i', $line, $matches)) {
                                            $competencias[] = [
                                                'numero' => $matches[1],
                                                'nombre' => $matches[2],
                                                'puntaje' => $matches[3],
                                                'descripcion' => $matches[4]
                                            ];
                                        } elseif (preg_match('/^(\d+)\.\s*(.+?):\s*(\d+)\/10\s*[-–]\s*(.+)$/i', $line, $matches)) {
                                            $competencias[] = [
                                                'numero' => $matches[1],
                                                'nombre' => $matches[2],
                                                'puntaje' => $matches[3],
                                                'descripcion' => $matches[4]
                                            ];
                                        }
                                    }
                                @endphp
                                
                                @if(count($competencias) > 0)
                                    <div class="row">
                                        @foreach($competencias as $comp)
                                            <div class="col-md-6 mb-4">
                                                <div class="card h-100 shadow-sm">
                                                    <div class="card-header bg-light">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <h6 class="mb-0">
                                                                <span class="badge bg-primary me-2">{{ $comp['numero'] }}</span>
                                                                {{ $comp['nombre'] }}
                                                            </h6>
                                                            <span class="badge bg-{{ $comp['puntaje'] >= 8 ? 'success' : ($comp['puntaje'] >= 5 ? 'warning' : 'danger') }} fs-6">
                                                                {{ $comp['puntaje'] }}/10
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="progress mb-2" style="height: 10px;">
                                                            <div class="progress-bar bg-{{ $comp['puntaje'] >= 8 ? 'success' : ($comp['puntaje'] >= 5 ? 'warning' : 'danger') }}" 
                                                                 style="width: {{ $comp['puntaje'] * 10 }}%"></div>
                                                        </div>
                                                        <small class="text-muted">{{ $comp['descripcion'] }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <!-- Si no se puede parsear, mostrar el contenido tal cual -->
                                    <div class="content-section">
                                        {!! nl2br(e($section['content'])) !!}
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- Otras secciones del reporte -->
                            <div class="mb-4">
                                <h5 class="text-primary border-bottom pb-2">
                                    @if(str_contains(strtolower($section['title']), 'personalidad'))
                                        <i class="fas fa-user-circle"></i>
                                    @elseif(str_contains(strtolower($section['title']), 'fuerte'))
                                        <i class="fas fa-thumbs-up"></i>
                                    @elseif(str_contains(strtolower($section['title']), 'desarrollar'))
                                        <i class="fas fa-chart-line"></i>
                                    @elseif(str_contains(strtolower($section['title']), 'propuesta') || str_contains(strtolower($section['title']), 'skilling'))
                                        <i class="fas fa-graduation-cap"></i>
                                    @else
                                        <i class="fas fa-info-circle"></i>
                                    @endif
                                    {{ $section['title'] }}
                                </h5>
                                <div class="content-section">
                                    {!! nl2br(e($section['content'])) !!}
                                </div>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="content-report">
                        {!! nl2br(e($response->comprehensive_report['content'] ?? 'Reporte no disponible')) !!}
                    </div>
                @endif
                
                @if(isset($response->comprehensive_report['generated_at']))
                    <hr>
                    <small class="text-muted">
                        <i class="fas fa-clock"></i>
                        Reporte generado el {{ \Carbon\Carbon::parse($response->comprehensive_report['generated_at'])->format('d/m/Y H:i') }}
                    </small>
                @endif
            </div>
        </div>
    @elseif($response->processing_status === 'analyzed' || $response->ai_analysis_status === 'completed')
        <div class="alert alert-info">
            <i class="fas fa-spinner fa-spin"></i> El reporte comprehensivo con las 15 competencias está siendo generado...
        </div>
    @endif

    <!-- Datos Técnicos Adicionales -->
    @if($response->transcriptions && !$response->questionnaire->questions)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-microphone"></i> Transcripciones de Audio (Raw)
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Nota:</strong> Las transcripciones se muestran integradas con cada pregunta arriba.
                    Esta sección solo se muestra si hay problemas con la estructura de preguntas.
                </div>
                @if(is_array($response->transcriptions))
                    @foreach($response->transcriptions as $questionId => $transcription)
                        <div class="border-bottom pb-3 mb-3">
                            <h6 class="text-muted">Pregunta {{ $questionId }}</h6>
                            <p class="bg-light p-3 rounded">{{ $transcription }}</p>
                        </div>
                    @endforeach
                @else
                    <pre class="bg-light p-3 rounded">{{ $response->transcriptions }}</pre>
                @endif
            </div>
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
.content-section {
    line-height: 1.6;
    font-size: 14px;
}
.content-report {
    line-height: 1.6;
    font-size: 14px;
}
.card.border-secondary {
    border: 1px solid #dee2e6 !important;
}
/* Estilos para el reproductor de audio */
audio {
    height: 40px;
    border-radius: 5px;
}
audio::-webkit-media-controls-panel {
    background-color: #f8f9fa;
}
.gap-3 {
    gap: 1rem !important;
}
</style>
@endsection