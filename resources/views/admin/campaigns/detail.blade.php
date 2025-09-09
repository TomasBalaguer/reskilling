@extends('admin.layout')

@section('title', 'Campaña: ' . $campaign->name)
@section('page-title', 'Campaña: ' . $campaign->name)

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('admin.campaigns.edit', $campaign->id) }}" 
           class="btn btn-primary">
            <i class="fas fa-edit"></i> Editar
        </a>
        
        <!-- Estado toggle -->
        @if($campaign->status === 'draft' || $campaign->status === 'paused')
            <form method="POST" action="{{ route('admin.campaigns.toggle-status', $campaign->id) }}" class="d-inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="active">
                <button type="submit" 
                        class="btn btn-success"
                        onclick="return confirm('¿Activar esta campaña?')">
                    <i class="fas fa-play"></i> Activar
                </button>
            </form>
        @endif
        
        @if($campaign->status === 'active')
            <form method="POST" action="{{ route('admin.campaigns.toggle-status', $campaign->id) }}" class="d-inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="paused">
                <button type="submit" 
                        class="btn btn-warning"
                        onclick="return confirm('¿Pausar esta campaña?')">
                    <i class="fas fa-pause"></i> Pausar
                </button>
            </form>
        @endif
        
        @if($campaign->status !== 'completed')
            <form method="POST" action="{{ route('admin.campaigns.toggle-status', $campaign->id) }}" class="d-inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="completed">
                <button type="submit" 
                        class="btn btn-info"
                        onclick="return confirm('¿Marcar esta campaña como completada?')">
                    <i class="fas fa-check"></i> Completar
                </button>
            </form>
        @endif
        
        <a href="{{ route('admin.campaigns.export', $campaign->id) }}" 
           class="btn btn-outline-success">
            <i class="fas fa-download"></i> Exportar CSV
        </a>
        <a href="{{ route('admin.campaigns') }}" 
           class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@endsection

@section('content')
    <!-- Información de la campaña -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> {{ $campaign->name }}</p>
                            <p><strong>Código:</strong> 
                                <span class="badge bg-primary">{{ $campaign->code }}</span>
                            </p>
                            <p><strong>Empresa:</strong> {{ $campaign->company->name }}</p>
                            <p><strong>Estado:</strong> 
                                <span class="badge bg-{{ $campaign->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($campaign->status) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Creada:</strong> {{ $campaign->created_at->format('d/m/Y H:i') }}</p>
                            <p><strong>Activa desde:</strong> 
                                {{ $campaign->active_from ? $campaign->active_from->format('d/m/Y') : 'N/A' }}
                            </p>
                            <p><strong>Activa hasta:</strong> 
                                {{ $campaign->active_until ? $campaign->active_until->format('d/m/Y') : 'N/A' }}
                            </p>
                            <p><strong>Máx. respuestas:</strong> {{ $campaign->max_responses ?? 'Sin límite' }}</p>
                        </div>
                    </div>
                    
                    @if($campaign->description)
                        <hr>
                        <p><strong>Descripción:</strong></p>
                        <p class="text-muted">{{ $campaign->description }}</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie"></i> Estadísticas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <h3 class="text-primary">{{ $campaign->responses->count() }}</h3>
                        <p class="text-muted mb-3">Respuestas totales</p>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-success">
                                    <strong>{{ $campaign->responses->whereIn('processing_status', ['completed', 'analyzed'])->count() }}</strong><br>
                                    Completadas
                                </small>
                            </div>
                            <div class="col-4">
                                <small class="text-info">
                                    <strong>{{ $campaign->responses->where('processing_status', 'processing')->count() }}</strong><br>
                                    Procesando
                                </small>
                            </div>
                            <div class="col-4">
                                <small class="text-warning">
                                    <strong>{{ $campaign->responses->where('processing_status', 'pending')->count() }}</strong><br>
                                    Pendientes
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cuestionarios de la campaña -->
    @if($campaign->questionnaires->count() > 0)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list-check"></i> Cuestionarios
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($campaign->questionnaires as $questionnaire)
                        <div class="col-md-6 mb-3">
                            <div class="card border-left-primary">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $questionnaire->name }}</h6>
                                    <p class="card-text text-muted small">{{ $questionnaire->description }}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-info">{{ $questionnaire->questionnaire_type?->value }}</span>
                                        <small class="text-muted">
                                            ~{{ $questionnaire->estimated_duration_minutes }} min
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Lista de respuestas -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list"></i> Respuestas ({{ $campaign->responses->count() }})
            </h5>
        </div>
        <div class="card-body">
            @if($campaign->responses->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th>IA Completada</th>
                                <th>Fecha</th>
                                <th>Duración</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaign->responses as $response)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">#{{ $response->id }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $response->respondent_name }}</strong>
                                    </td>
                                    <td>{{ $response->respondent_email }}</td>
                                    <td>
                                        @switch($response->processing_status)
                                            @case('completed')
                                            @case('analyzed')
                                                <span class="badge bg-success status-badge">
                                                    <i class="fas fa-check"></i> Completada
                                                </span>
                                                @break
                                            @case('processing')
                                                <span class="badge bg-info status-badge">
                                                    <i class="fas fa-spinner"></i> Procesando
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger status-badge">
                                                    <i class="fas fa-exclamation"></i> Error
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary status-badge">
                                                    <i class="fas fa-clock"></i> Pendiente
                                                </span>
                                        @endswitch
                                    </td>
                                    <td class="text-center">
                                        @if($response->ai_analysis)
                                            <span class="badge bg-success status-badge">
                                                <i class="fas fa-robot"></i> Sí
                                            </span>
                                        @else
                                            <span class="badge bg-secondary status-badge">
                                                <i class="fas fa-hourglass-half"></i> No
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $response->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        @if($response->duration_minutes)
                                            <small>{{ $response->duration_minutes }} min</small>
                                        @else
                                            <small class="text-muted">N/A</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.responses.detail', $response->id) }}" 
                                               class="btn btn-outline-primary" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($response->comprehensive_report || $response->processing_status === 'analyzed')
                                                <a href="{{ route('admin.responses.report', $response->id) }}" 
                                                   class="btn btn-outline-success" 
                                                   title="Descargar reporte">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No hay respuestas para esta campaña aún</p>
                </div>
            @endif
        </div>
    </div>
@endsection