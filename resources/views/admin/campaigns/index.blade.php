@extends('admin.layout')

@section('title', 'Campañas - Administración')
@section('page-title', 'Todas las Campañas')

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('admin.campaigns.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Campaña
        </a>
    </div>
@endsection

@section('content')
    <div class="card card-compact">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-medium">
                <i class="fas fa-bullhorn"></i> Lista de Campañas
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
                                <th>Empresa</th>
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
                                    <td>{{ $campaign->company->name }}</td>
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
                                            <a href="{{ route('admin.campaigns.detail', $campaign->id) }}" 
                                               class="btn btn-outline-primary" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.campaigns.edit', $campaign->id) }}" 
                                               class="btn btn-outline-secondary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Estado toggle -->
                                            @if($campaign->status === 'draft' || $campaign->status === 'paused')
                                                <form method="POST" action="{{ route('admin.campaigns.toggle-status', $campaign->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" 
                                                            class="btn btn-outline-success" 
                                                            title="Activar"
                                                            onclick="return confirm('¿Activar esta campaña?')">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            @if($campaign->status === 'active')
                                                <form method="POST" action="{{ route('admin.campaigns.toggle-status', $campaign->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="paused">
                                                    <button type="submit" 
                                                            class="btn btn-outline-warning" 
                                                            title="Pausar"
                                                            onclick="return confirm('¿Pausar esta campaña?')">
                                                        <i class="fas fa-pause"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <a href="{{ route('admin.campaigns.export', $campaign->id) }}" 
                                               class="btn btn-outline-info" 
                                               title="Exportar datos">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            
                                            <!-- Delete solo si no tiene respuestas -->
                                            @if($campaign->responses_count == 0)
                                                <form method="POST" action="{{ route('admin.campaigns.delete', $campaign->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-outline-danger" 
                                                            title="Eliminar"
                                                            onclick="return confirm('¿Eliminar esta campaña? Esta acción no se puede deshacer.')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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
                    {{ $campaigns->links() }}
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No hay campañas registradas</p>
                </div>
            @endif
        </div>
    </div>
@endsection