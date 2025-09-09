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

    <!-- Incluir vista parcial compartida -->
    @include('partials.response-detail', ['response' => $response])

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
@endsection