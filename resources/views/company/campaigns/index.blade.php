@extends('company.layout')

@section('title', 'Campañas - ' . $company->name)
@section('page-title', 'Campañas de ' . $company->name)

@section('page-actions')
    <div class="btn-group" role="group">
        @if($company->campaigns()->count() < $company->max_campaigns)
            <a href="{{ route('company.campaigns.create') }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
               class="btn btn-success">
                <i class="fas fa-plus"></i> Nueva Campaña
            </a>
        @else
            <div class="alert alert-warning mb-0 p-2 small" style="line-height: 1.2;">
                <i class="fas fa-exclamation-triangle"></i> Límite alcanzado: {{ $company->max_campaigns }} campañas
            </div>
        @endif
    </div>
@endsection

@section('content')
    <div class="card card-compact">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-medium">
                <i class="fas fa-bullhorn"></i> Mis Campañas
            </h5>
            <span class="badge bg-primary">{{ $campaigns->total() }} total</span>
        </div>
        <div class="card-body">
            @if($campaigns->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-refined">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Respuestas</th>
                                <th>Creada</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaigns as $campaign)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">#{{ $campaign->id }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-medium">{{ $campaign->name }}</span>
                                        @if($campaign->description)
                                            <br><small class="text-muted fw-normal">{{ Str::limit($campaign->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $campaign->code }}</span>
                                    </td>
                                    <td>
                                        @switch($campaign->status)
                                            @case('active')
                                                <span class="badge bg-success status-badge">
                                                    <i class="fas fa-play"></i> Activa
                                                </span>
                                                @break
                                            @case('paused')
                                                <span class="badge bg-warning status-badge">
                                                    <i class="fas fa-pause"></i> Pausada
                                                </span>
                                                @break
                                            @case('completed')
                                                <span class="badge bg-info status-badge">
                                                    <i class="fas fa-check"></i> Completada
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary status-badge">
                                                    <i class="fas fa-draft2digital"></i> Borrador
                                                </span>
                                        @endswitch
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $campaign->responses_count }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $campaign->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('company.campaigns.detail', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                                               class="btn btn-outline-primary" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('company.campaigns.export', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                                               class="btn btn-outline-success" 
                                               title="Exportar datos">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="d-flex justify-content-center">
                    {{ $campaigns->appends(['company_id' => request('company_id')])->links() }}
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No hay campañas registradas para su empresa</p>
                </div>
            @endif
        </div>
    </div>
@endsection