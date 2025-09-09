@extends('admin.layout')

@section('title', 'Dashboard - Administración')
@section('page-title', 'Dashboard')

@section('content')
    <!-- Métricas principales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-metric">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title text-muted">Empresas</h5>
                            <h2 class="text-primary">{{ $stats['total_companies'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-building fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-metric">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title text-muted">Campañas</h5>
                            <h2 class="text-success">{{ $stats['total_campaigns'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-bullhorn fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-metric">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title text-muted">Respuestas Totales</h5>
                            <h2 class="text-info">{{ $stats['total_responses'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card card-metric">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title text-muted">Procesadas</h5>
                            <h2 class="text-warning">{{ $stats['completed_responses'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Respuestas recientes -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Respuestas Recientes
                    </h5>
                    <a href="{{ route('admin.responses') }}" class="btn btn-sm btn-outline-primary">
                        Ver todas <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body">
                    @if($recentResponses->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Empresa</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentResponses as $response)
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-dark">#{{ $response->id }}</span>
                                            </td>
                                            <td>{{ $response->respondent_name }}</td>
                                            <td>{{ $response->respondent_email }}</td>
                                            <td>{{ $response->campaign->company->name }}</td>
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
                                            <td>
                                                <small class="text-muted">
                                                    {{ $response->created_at->diffForHumans() }}
                                                </small>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.responses.detail', $response->id) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No hay respuestas recientes</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection