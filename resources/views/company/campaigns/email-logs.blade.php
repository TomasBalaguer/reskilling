@extends('company.layout')

@section('title', 'Logs de Email - ' . $campaign->name)
@section('page-title', 'Logs de Email - ' . $campaign->name)

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('company.campaigns.detail', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
           class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Campaña
        </a>
    </div>
@endsection

@section('content')
    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Total</h5>
                            <h2 class="mb-0">{{ $stats['total'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-envelope fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Enviados</h5>
                            <h2 class="mb-0">{{ $stats['sent'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">En Cola</h5>
                            <h2 class="mb-0">{{ $stats['queued'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="card-title">Fallidos</h5>
                            <h2 class="mb-0">{{ $stats['failed'] }}</h2>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fallos Recientes -->
    @if(count($recentFailures) > 0)
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle"></i> Fallos Recientes
                </h5>
            </div>
            <div class="card-body">
                @foreach($recentFailures as $failure)
                    <div class="alert alert-danger" role="alert">
                        <strong>{{ $failure['email'] }}</strong> - {{ $failure['failed_at']->format('d/m/Y H:i') }}
                        <br>
                        <small>{{ $failure['error_message'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Tabla de Logs -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> Historial de Emails
            </h5>
            <span class="badge bg-primary">{{ $emailLogs->total() }} registros</span>
        </div>
        <div class="card-body">
            @if($emailLogs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>En Cola</th>
                                <th>Enviado</th>
                                <th>Error</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($emailLogs as $log)
                                <tr>
                                    <td>{{ $log->recipient_email }}</td>
                                    <td>{{ $log->recipient_name ?? 'N/A' }}</td>
                                    <td>
                                        @switch($log->type)
                                            @case('invitation')
                                                <span class="badge bg-primary">Invitación</span>
                                                @break
                                            @case('bulk')
                                                <span class="badge bg-info">CSV</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $log->type }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @switch($log->status)
                                            @case('sent')
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Enviado
                                                </span>
                                                @break
                                            @case('queued')
                                                <span class="badge bg-warning">
                                                    <i class="fas fa-clock"></i> En Cola
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times"></i> Fallido
                                                </span>
                                                @break
                                            @case('bounced')
                                                <span class="badge bg-dark">
                                                    <i class="fas fa-ban"></i> Rebotado
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $log->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @if($log->queued_at)
                                            <small>{{ $log->queued_at->format('d/m/Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->sent_at)
                                            <small>{{ $log->sent_at->format('d/m/Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->failed_at)
                                            <small>{{ $log->failed_at->format('d/m/Y H:i') }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->error_message || $log->metadata)
                                            <button type="button" 
                                                    class="btn btn-outline-info btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailModal{{ $log->id }}">
                                                <i class="fas fa-info-circle"></i> Detalles
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="d-flex justify-content-center">
                    {{ $emailLogs->appends(['company_id' => request('company_id')])->links() }}
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No hay logs de email para esta campaña</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modales de Detalle -->
    @foreach($emailLogs as $log)
        @if($log->error_message || $log->metadata)
            <div class="modal fade" id="detailModal{{ $log->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detalles del Email - {{ $log->recipient_email }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            @if($log->error_message)
                                <div class="alert alert-danger">
                                    <h6>Error:</h6>
                                    <code>{{ $log->error_message }}</code>
                                </div>
                            @endif
                            
                            @if($log->metadata)
                                <h6>Metadatos:</h6>
                                <pre class="bg-light p-3 rounded"><code>{{ json_encode($log->metadata, JSON_PRETTY_PRINT) }}</code></pre>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection