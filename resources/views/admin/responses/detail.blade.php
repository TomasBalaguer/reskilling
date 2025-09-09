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
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-clipboard-list"></i> Respuestas del Cuestionario
                </h5>
            </div>
            <div class="card-body">
                @php
                    // Obtener preguntas del cuestionario usando buildStructure()
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
                    $responses = $response->processed_responses ?? $response->raw_responses ?? [];
                    $hasResponses = !empty($responses);
                @endphp
                
                @if(count($questions) > 0 && $hasResponses)
                    
                    @foreach($questions as $questionId => $questionData)
                        @if(isset($responses[$questionId]))
                            <div class="border-bottom pb-4 mb-4">
                                <!-- Pregunta -->
                                <div class="mb-3">
                                    <h6 class="text-primary mb-2">
                                        <i class="fas fa-question-circle"></i> Pregunta {{ strtoupper($questionId) }}
                                    </h6>
                                    <div class="bg-light p-3 rounded">
                                        <em>{{ is_array($questionData) ? ($questionData['text'] ?? $questionData) : $questionData }}</em>
                                    </div>
                                </div>
                                
                                <!-- Respuesta -->
                                <div class="mb-2">
                                    <h6 class="text-success mb-2">
                                        <i class="fas fa-microphone"></i> Respuesta (Audio transcrito)
                                    </h6>
                                    @php
                                        $responseText = '';
                                        $audioDuration = null;
                                        
                                        // Intentar obtener transcripción del campo transcriptions
                                        if ($response->transcriptions && isset($response->transcriptions[$questionId])) {
                                            $responseText = $response->transcriptions[$questionId];
                                        }
                                        
                                        // Si no hay transcripción, obtener de responses procesadas/raw
                                        if (empty($responseText)) {
                                            $responseData = $responses[$questionId];
                                            if (is_array($responseData)) {
                                                $responseText = $responseData['transcription'] ?? 
                                                               $responseData['raw_response'] ?? 
                                                               (isset($responseData['processed_response']['text']) ? 
                                                                   $responseData['processed_response']['text'] : 
                                                                   'No se encontró transcripción');
                                            } else {
                                                $responseText = $responseData;
                                            }
                                        }
                                        
                                        // Obtener duración del audio
                                        if (isset($responses[$questionId]['audio_duration'])) {
                                            $audioDuration = $responses[$questionId]['audio_duration'];
                                        } elseif ($response->raw_responses && isset($response->raw_responses[$questionId]['audio_duration'])) {
                                            $audioDuration = $response->raw_responses[$questionId]['audio_duration'];
                                        }
                                    @endphp
                                    
                                    <div class="alert alert-success" role="alert">
                                        <strong>{{ $response->respondent_name }}:</strong> "{{ $responseText }}"
                                    </div>
                                </div>
                                
                                <!-- Duración de audio si está disponible -->
                                @if($audioDuration)
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> Duración del audio: {{ $audioDuration }} segundos
                                    </small>
                                @endif
                            </div>
                        @endif
                    @endforeach
                @elseif(count($questions) > 0)
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
            </div>
        </div>
    @endif


    <!-- Reporte Comprehensivo -->
    @if($response->comprehensive_report)
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-file-medical-alt"></i> Reporte Psicológico Profesional
                </h5>
                <small>Análisis completo y detallado basado en las respuestas y transcripciones de audio</small>
            </div>
            <div class="card-body">
                @if(isset($response->comprehensive_report['sections']) && count($response->comprehensive_report['sections']) > 0)
                    @foreach($response->comprehensive_report['sections'] as $section)
                        <div class="mb-4">
                            <h5 class="text-primary border-bottom pb-2">{{ $section['title'] }}</h5>
                            <div class="content-section">
                                {!! nl2br(e($section['content'])) !!}
                            </div>
                        </div>
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
</style>
@endsection