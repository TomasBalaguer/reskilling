@extends('company.layout')

@section('title', 'Respuestas - ' . $company->name)
@section('page-title', 'Respuestas de ' . $company->name)

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list"></i> Mis Respuestas
            </h5>
            <span class="badge bg-primary">{{ $responses->total() }} total</span>
        </div>
        <div class="card-body">
            @if($responses->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Respondente</th>
                                <th>Email</th>
                                <th>Campaña</th>
                                <th>Estado</th>
                                <th>Reporte IA</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($responses as $response)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">#{{ $response->id }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $response->respondent_name }}</strong>
                                    </td>
                                    <td>{{ $response->respondent_email }}</td>
                                    <td>
                                        <a href="{{ route('company.campaigns.detail', $response->campaign_id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                                           class="text-decoration-none">
                                            {{ $response->campaign->name }}
                                        </a>
                                        <br><small class="text-muted">{{ $response->campaign->code }}</small>
                                    </td>
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
                                        @if($response->comprehensive_report)
                                            <span class="badge bg-success status-badge">
                                                <i class="fas fa-file-medical"></i> Listo
                                            </span>
                                        @else
                                            <span class="badge bg-secondary status-badge">
                                                <i class="fas fa-hourglass-half"></i> Pendiente
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $response->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('company.responses.detail', $response->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                                               class="btn btn-outline-primary" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($response->comprehensive_report || $response->processing_status === 'analyzed')
                                                <a href="{{ route('company.responses.report', $response->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
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
                
                <!-- Paginación -->
                <div class="d-flex justify-content-center">
                    {{ $responses->appends(['company_id' => request('company_id')])->links() }}
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No hay respuestas registradas para su empresa</p>
                </div>
            @endif
        </div>
    </div>
@endsection