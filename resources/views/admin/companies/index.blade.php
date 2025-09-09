@extends('admin.layout')

@section('title', 'Empresas - Administración')
@section('page-title', 'Todas las Empresas')

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nueva Empresa
        </a>
    </div>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-building"></i> Lista de Empresas
            </h5>
            <span class="badge bg-primary">{{ $companies->total() }} total</span>
        </div>
        <div class="card-body">
            @if($companies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Contacto</th>
                                <th>Estado</th>
                                <th>Campañas</th>
                                <th>Respuestas</th>
                                <th>Creada</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companies as $company)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">#{{ $company->id }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $company->name }}</strong>
                                        @if($company->description)
                                            <br><small class="text-muted">{{ Str::limit($company->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($company->contact_email)
                                            <i class="fas fa-envelope"></i> {{ $company->contact_email }}<br>
                                        @endif
                                        @if($company->contact_phone)
                                            <i class="fas fa-phone"></i> {{ $company->contact_phone }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($company->is_active)
                                            <span class="badge bg-success status-badge">
                                                <i class="fas fa-check"></i> Activa
                                            </span>
                                        @else
                                            <span class="badge bg-secondary status-badge">
                                                <i class="fas fa-pause"></i> Inactiva
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $company->campaigns_count ?? 0 }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $company->responses_count ?? 0 }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $company->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.companies.detail', $company->id) }}" 
                                               class="btn btn-outline-primary" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.companies.edit', $company->id) }}" 
                                               class="btn btn-outline-secondary" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Toggle Status -->
                                            <form method="POST" action="{{ route('admin.companies.toggle-status', $company->id) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="btn btn-outline-{{ $company->is_active ? 'warning' : 'success' }}" 
                                                        title="{{ $company->is_active ? 'Desactivar' : 'Activar' }}"
                                                        onclick="return confirm('¿Estás seguro de {{ $company->is_active ? 'desactivar' : 'activar' }} esta empresa?')">
                                                    <i class="fas fa-{{ $company->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            
                                            <!-- Delete -->
                                            <form method="POST" action="{{ route('admin.companies.delete', $company->id) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-outline-danger" 
                                                        title="Eliminar"
                                                        onclick="return confirm('¿Estás seguro de eliminar esta empresa? Esta acción no se puede deshacer.')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación -->
                <div class="d-flex justify-content-center">
                    {{ $companies->links() }}
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No hay empresas registradas</p>
                </div>
            @endif
        </div>
    </div>
@endsection