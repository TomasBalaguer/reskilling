@extends('admin.layout')

@section('title', 'Empresa: ' . $company->name)
@section('page-title', 'Empresa: ' . $company->name)

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('admin.campaigns.create', ['company_id' => $company->id]) }}" 
           class="btn btn-outline-primary rounded-3" style="border-width: 1px; font-weight: normal;">
            <i class="fas fa-plus"></i> Nueva Campaña
        </a>
        <a href="{{ route('admin.companies.edit', $company->id) }}" 
           class="btn btn-outline-primary rounded-3" style="border-width: 1px; font-weight: normal;">
            <i class="fas fa-edit"></i> Editar
        </a>
        
        <!-- Toggle Status -->
        <form method="POST" action="{{ route('admin.companies.toggle-status', $company->id) }}" class="d-inline">
            @csrf
            @method('PATCH')
            <button type="submit" 
                    class="btn btn-{{ $company->is_active ? 'warning' : 'success' }}"
                    onclick="return confirm('¿Estás seguro de {{ $company->is_active ? 'desactivar' : 'activar' }} esta empresa?')">
                <i class="fas fa-{{ $company->is_active ? 'pause' : 'play' }}"></i> 
                {{ $company->is_active ? 'Desactivar' : 'Activar' }}
            </button>
        </form>
        
        <a href="{{ route('admin.companies') }}" 
           class="btn btn-outline-secondary rounded-3" style="border-width: 1px; font-weight: normal;">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@endsection

@section('content')
    <!-- Información de la empresa -->
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
                            <p><strong>Nombre:</strong> {{ $company->name }}</p>
                            <p><strong>Email de contacto:</strong> {{ $company->contact_email ?? 'N/A' }}</p>
                            <p><strong>Teléfono:</strong> {{ $company->phone ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Estado:</strong> 
                                @if($company->is_active)
                                    <span class="badge bg-success">Activa</span>
                                @else
                                    <span class="badge bg-secondary">Inactiva</span>
                                @endif
                            </p>
                            <p><strong>Creada:</strong> {{ $company->created_at->format('d/m/Y H:i') }}</p>
                            <p><strong>Última actualización:</strong> {{ $company->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="text-primary"><i class="fas fa-cog"></i> Límites y Configuración</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Máximo de Campañas:</strong> 
                                {{ $company->max_campaigns }}
                                <small class="text-muted">(usando {{ $company->campaigns->count() }})</small>
                            </p>
                            <p><strong>Máximo Respuestas por Campaña:</strong> {{ $company->max_responses_per_campaign }}</p>
                        </div>
                        <div class="col-md-6">
                            @php
                                $campaignsUsed = $company->campaigns->count();
                                $campaignsPercent = $company->max_campaigns > 0 ? ($campaignsUsed / $company->max_campaigns) * 100 : 0;
                            @endphp
                            <div class="mb-2">
                                <strong>Uso de Campañas:</strong>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar {{ $campaignsPercent > 80 ? 'bg-danger' : ($campaignsPercent > 60 ? 'bg-warning' : 'bg-success') }}" 
                                         role="progressbar" 
                                         style="width: {{ $campaignsPercent }}%">
                                        {{ $campaignsUsed }}/{{ $company->max_campaigns }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($company->description)
                        <hr>
                        <p><strong>Descripción:</strong></p>
                        <p class="text-muted">{{ $company->description }}</p>
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
                        <h3 class="text-primary">{{ $company->campaigns->count() }}</h3>
                        <p class="text-muted mb-3">Campañas totales</p>
                        
                        <h3 class="text-info">{{ $totalResponses }}</h3>
                        <p class="text-muted mb-3">Respuestas totales</p>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-success">
                                    <strong>{{ $completedResponses }}</strong><br>
                                    Completadas
                                </small>
                            </div>
                            <div class="col-6">
                                <small class="text-warning">
                                    <strong>{{ $pendingResponses }}</strong><br>
                                    Pendientes
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Usuarios de la empresa -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> Usuarios de Empresa ({{ $company->users->count() }})
                    </h5>
                    <button class="btn btn-outline-primary rounded-3" style="border-width: 1px; font-weight: normal;" onclick="showCreateUserModal()">
                        <i class="fas fa-plus"></i> Crear Usuario
                    </button>
                </div>
                <div class="card-body">
                    @if($company->users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Estado</th>
                                        <th>Creado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($company->users as $user)
                                        <tr>
                                            <td><strong>{{ $user->name }}</strong></td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : 'primary' }}">
                                                    {{ ucfirst($user->role) }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                                                    {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-warning rounded-3" style="border-width: 1px; font-weight: normal;" 
                                                            onclick="showResetPasswordModal({{ $user->id }}, '{{ $user->name }}')"
                                                            title="Cambiar contraseña">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                    <form method="POST" 
                                                          action="{{ route('admin.companies.toggle-user-status', [$company->id, $user->id]) }}" 
                                                          class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" 
                                                                class="btn btn-outline-{{ $user->is_active ? 'secondary' : 'success' }} rounded-3" style="border-width: 1px; font-weight: normal;"
                                                                onclick="return confirm('¿{{ $user->is_active ? 'Desactivar' : 'Activar' }} este usuario?')"
                                                                title="{{ $user->is_active ? 'Desactivar' : 'Activar' }} usuario">
                                                            <i class="fas fa-{{ $user->is_active ? 'user-slash' : 'user-check' }}"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-user-plus fa-3x mb-3"></i>
                            <p>No hay usuarios creados para esta empresa</p>
                            <button class="btn btn-outline-primary rounded-3" style="border-width: 1px; font-weight: normal;" onclick="showCreateUserModal()">
                                <i class="fas fa-plus"></i> Crear primer usuario
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Campañas de la empresa -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-bullhorn"></i> Campañas ({{ $company->campaigns->count() }})
            </h5>
        </div>
        <div class="card-body">
            @if($company->campaigns->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
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
                            @foreach($company->campaigns as $campaign)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark">#{{ $campaign->id }}</span>
                                    </td>
                                    <td>
                                        <strong>{{ $campaign->name }}</strong>
                                        @if($campaign->description)
                                            <br><small class="text-muted">{{ Str::limit($campaign->description, 30) }}</small>
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
                                        <span class="badge bg-light text-dark">{{ $campaign->responses->count() }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $campaign->created_at->format('d/m/Y') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.campaigns.detail', $campaign->id) }}" 
                                               class="btn btn-outline-primary rounded-3" style="border-width: 1px; font-weight: normal;" 
                                               title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.campaigns.edit', $campaign->id) }}" 
                                               class="btn btn-outline-secondary rounded-3" style="border-width: 1px; font-weight: normal;" 
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Estado buttons -->
                                            @if($campaign->status !== 'active')
                                                <form method="POST" action="{{ route('admin.campaigns.toggle-status', $campaign->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="active">
                                                    <button type="submit" 
                                                            class="btn btn-outline-success rounded-3" style="border-width: 1px; font-weight: normal;" 
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
                                                            class="btn btn-outline-warning rounded-3" style="border-width: 1px; font-weight: normal;" 
                                                            title="Pausar"
                                                            onclick="return confirm('¿Pausar esta campaña?')">
                                                        <i class="fas fa-pause"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            
                                            <a href="{{ route('admin.campaigns.export', $campaign->id) }}" 
                                               class="btn btn-outline-info rounded-3" style="border-width: 1px; font-weight: normal;" 
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
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No hay campañas para esta empresa</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal para crear usuario -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Usuario de Empresa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createUserForm" method="POST" action="{{ route('admin.companies.create-user', $company->id) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="user_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="user_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="user_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="user_password" name="password" required>
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                        <div class="mb-3">
                            <label for="user_role" class="form-label">Rol</label>
                            <select class="form-control" id="user_role" name="role">
                                <option value="user">Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-3" style="border-width: 1px; font-weight: normal;" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-primary rounded-3" style="border-width: 1px; font-weight: normal;" onclick="submitCreateUser()">Crear Usuario</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para cambiar contraseña -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="resetPasswordForm" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Cambiando contraseña para: <strong id="reset_user_name"></strong>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="password" required>
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="password_confirmation" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-3" style="border-width: 1px; font-weight: normal;" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-warning rounded-3" style="border-width: 1px; font-weight: normal;" onclick="submitResetPassword()">Cambiar Contraseña</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showCreateUserModal() {
            const modal = new bootstrap.Modal(document.getElementById('createUserModal'));
            modal.show();
        }

        function showResetPasswordModal(userId, userName) {
            document.getElementById('reset_user_name').textContent = userName;
            document.getElementById('resetPasswordForm').action = 
                '{{ route("admin.companies.reset-user-password", [$company->id, "__USER_ID__"]) }}'.replace('__USER_ID__', userId);
            
            const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
            modal.show();
        }

        function submitCreateUser() {
            document.getElementById('createUserForm').submit();
        }

        function submitResetPassword() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                return;
            }
            
            if (password.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                return;
            }
            
            document.getElementById('resetPasswordForm').submit();
        }
    </script>
@endsection