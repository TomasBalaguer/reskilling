@extends('admin.layout')

@section('title', 'Dashboard - Administración')
@section('page-title', 'Dashboard')

@section('content')
    <style>
        /* Dashboard specific styles */
        .card-metric {
            min-height: 100px;
            transition: all 0.3s ease;
        }
        
        .card-metric:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .card-metric .card-body {
            padding: 1.25rem;
        }
        
        .status-badge {
            font-weight: 400;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .table-responsive {
            margin: -0.25rem 0;
        }
        
        .table.table-hover tbody tr {
            transition: background-color 0.15s ease;
        }
        
        .table.table-hover tbody tr:hover {
            background-color: rgba(99, 102, 241, 0.02);
        }
    </style>
    <!-- Métricas principales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-metric">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-subtitle mb-1" style="color: var(--text-secondary); font-weight: 400; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.025em;">Empresas</h6>
                            <h3 style="color: var(--primary-color); font-weight: 500; margin: 0; font-size: 1.75rem;">{{ $stats['total_companies'] }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-building text-primary" style="font-size: 1.5rem; opacity: 0.3;"></i>
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
                            <h6 class="card-subtitle mb-1" style="color: var(--text-secondary); font-weight: 400; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.025em;">Campañas</h6>
                            <h3 style="color: var(--success-color); font-weight: 500; margin: 0; font-size: 1.75rem;">{{ $stats['total_campaigns'] }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-bullhorn text-success" style="font-size: 1.5rem; opacity: 0.3;"></i>
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
                            <h6 class="card-subtitle mb-1" style="color: var(--text-secondary); font-weight: 400; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.025em;">Respuestas Totales</h6>
                            <h3 style="color: var(--info-color); font-weight: 500; margin: 0; font-size: 1.75rem;">{{ $stats['total_responses'] }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list text-info" style="font-size: 1.5rem; opacity: 0.3;"></i>
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
                            <h6 class="card-subtitle mb-1" style="color: var(--text-secondary); font-weight: 400; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.025em;">Procesadas</h6>
                            <h3 style="color: var(--warning-color); font-weight: 500; margin: 0; font-size: 1.75rem;">{{ $stats['completed_responses'] }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle text-warning" style="font-size: 1.5rem; opacity: 0.3;"></i>
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
                    <h6 class="mb-0" style="font-weight: 500; font-size: 0.95rem;">
                        <i class="fas fa-history" style="font-size: 0.875rem; margin-right: 0.5rem;"></i> Respuestas Recientes
                    </h6>
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