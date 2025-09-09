@extends('company.layout')

@section('title', 'Campaña: ' . $campaign->name)
@section('page-title', 'Campaña: ' . $campaign->name)

@section('page-actions')
    <div class="d-flex gap-2">
        @if($campaign->responses()->count() === 0)
            <a href="{{ route('company.campaigns.edit', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
               class="btn btn-custom-purple btn-sm px-3">
                <i class="fas fa-edit me-2"></i>Editar
            </a>
        @endif
        <a href="{{ route('company.campaigns.export', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
           class="btn btn-custom-purple btn-sm px-3">
            <i class="fas fa-download me-2"></i>Exportar CSV
        </a>
        <a href="{{ route('company.campaigns') }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
           class="btn btn-custom-purple btn-sm px-3">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
@endsection

@section('content')
<style>
.btn-custom-purple {
    color: #11B981;
    border-color: #11B981;
    background-color: transparent;
}

.btn-custom-purple:hover,
.btn-custom-purple:focus,
.btn-custom-purple:active,
.btn-custom-purple.active {
    color: white;
    background-color: #11B981;
    border-color: #11B981;
    box-shadow: 0 0 0 0.2rem rgba(95, 8, 206, 0.25);
}

.btn-custom-purple:focus {
    outline: 0;
}

.stats-number {
    color: #11B981 !important;
    font-weight: 600;
}

.stats-label {
    color: #11B981 !important;
    opacity: 0.8;
}
</style>
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
                        <h3 class="stats-number">{{ $campaign->responses->count() }}</h3>
                        <p class="stats-label mb-3">Respuestas totales</p>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="stats-label">
                                    <strong class="stats-number">{{ $campaign->responses->whereIn('processing_status', ['completed', 'analyzed'])->count() }}</strong><br>
                                    Completadas
                                </small>
                            </div>
                            <div class="col-4">
                                <small class="stats-label">
                                    <strong class="stats-number">{{ $campaign->responses->where('processing_status', 'processing')->count() }}</strong><br>
                                    Procesando
                                </small>
                            </div>
                            <div class="col-4">
                                <small class="stats-label">
                                    <strong class="stats-number">{{ $campaign->responses->where('processing_status', 'pending')->count() }}</strong><br>
                                    Pendientes
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de Acceso -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-link"></i> Acceso a la Campaña
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Toggle Public Access -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <label class="form-label mb-0"><strong>Acceso Público:</strong></label>
                                    <form method="POST" action="{{ route('company.campaigns.toggle-public-access', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="publicAccessSwitch" 
                                                   {{ $campaign->allow_public_access ? 'checked' : '' }}
                                                   onchange="this.form.submit()">
                                            <label class="form-check-label" for="publicAccessSwitch">
                                                {{ $campaign->allow_public_access ? 'Habilitado' : 'Deshabilitado' }}
                                            </label>
                                        </div>
                                    </form>
                                </div>
                                <small class="form-text text-muted">
                                    {{ $campaign->allow_public_access ? 'Cualquier persona con el enlace puede acceder' : 'Solo personas invitadas pueden acceder' }}
                                </small>
                            </div>

                            @if($campaign->allow_public_access)
                                <div class="mb-3">
                                    <label class="form-label"><strong>Enlace Público:</strong></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" 
                                               value="{{ config('app.frontend_url') }}/c/{{ $campaign->code }}" 
                                               id="publicLink" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('publicLink')">
                                            <i class="fas fa-copy"></i> Copiar
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Este enlace puede ser usado por cualquier persona</small>
                                </div>
                            @endif
                            
                            @if($campaign->access_type === 'email_list')
                                <div class="mb-3">
                                    <p><strong>Tipo de acceso:</strong> <span class="badge bg-info">Lista de Emails</span></p>
                                    <p><small class="text-muted">
                                        Se enviaron invitaciones automáticamente a {{ $campaign->invitations->count() }} personas.
                                    </small></p>
                                </div>
                            @else
                                <p><strong>Tipo de acceso:</strong> <span class="badge bg-success">Enlace Público</span></p>
                            @endif
                        </div>
                        
                        <div class="col-md-4 text-end">
                            @if($campaign->invitations->count() > 0)
                                <form method="POST" action="{{ route('company.campaigns.resend-invitations', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                                      style="display: inline-block;">
                                    @csrf
                                    <button type="submit" class="btn btn-custom-purple btn-sm mb-2 px-3" onclick="return confirm('¿Estás seguro de que quieres reenviar todas las invitaciones?')">
                                        <i class="fas fa-envelope me-2"></i>Reenviar Invitaciones
                                    </button>
                                </form>
                                <br>
                            @endif
                            
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-custom-purple btn-sm px-3" onclick="showSingleInviteModal()">
                                    <i class="fas fa-user-plus me-2"></i>Invitar Persona
                                </button>
                                <button class="btn btn-custom-purple btn-sm px-3" onclick="showCSVUploadModal()">
                                    <i class="fas fa-upload me-2"></i>Subir CSV
                                </button>
                                <a href="{{ route('company.campaigns.email-logs', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                                   class="btn btn-custom-purple btn-sm px-3">
                                    <i class="fas fa-eye me-2"></i>Ver Logs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Invitaciones -->
    @if($campaign->invitations->count() > 0)
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <button class="btn btn-link text-decoration-none p-0 w-100 text-start collapsed" 
                                    type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#invitationsCollapse" 
                                    aria-expanded="false" 
                                    aria-controls="invitationsCollapse">
                                <i class="fas fa-envelope"></i> Invitaciones Enviadas ({{ $campaign->invitations->count() }})
                                <i class="fas fa-chevron-down float-end mt-1"></i>
                            </button>
                        </h5>
                    </div>
                    <div class="collapse" id="invitationsCollapse">
                        <div class="card-body" style="font-size: 0.875rem;">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Email</th>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        <th>Enviado</th>
                                        <th>Respondió</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invitations as $invitation)
                                        @php
                                            $hasResponse = $campaign->responses->where('respondent_email', $invitation->email)->first();
                                        @endphp
                                        <tr class="{{ $hasResponse ? 'table-success' : ($invitation->status === 'opened' ? '' : 'table-warning') }}">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $invitation->email }}</td>
                                            <td>{{ $invitation->name ?: '-' }}</td>
                                            <td>
                                                @if($hasResponse)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Respondió
                                                    </span>
                                                @elseif($invitation->status === 'opened')
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-envelope"></i> Enviado
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock"></i> Pendiente
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $invitation->updated_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td>
                                                @if($hasResponse)
                                                    {{ $hasResponse->created_at->format('d/m/Y H:i') }}
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="copyInvitationLink('{{ $invitation->token }}')" 
                                                            title="Copiar enlace de invitación">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                    @if(!$hasResponse)
                                                        <form method="POST" 
                                                              action="{{ route('company.campaigns.resend-invitations', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                                                              style="display: inline;">
                                                            @csrf
                                                            <input type="hidden" name="single_email" value="{{ $invitation->email }}">
                                                            <button type="submit" class="btn btn-outline-success" 
                                                                    title="Reenviar invitación">
                                                                <i class="fas fa-paper-plane"></i>
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

                        <!-- Pagination -->
                        @if($invitations->hasPages())
                            <div class="d-flex justify-content-center mt-3">
                                {{ $invitations->appends(request()->query())->links() }}
                            </div>
                        @endif
                        
                        <!-- Statistics -->
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h5 class="stats-number">{{ $campaign->invitations->count() }}</h5>
                                    <small class="stats-label">Total Invitados</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h5 class="stats-number">{{ $campaign->invitations->where('status', 'opened')->count() }}</h5>
                                    <small class="stats-label">Enviados</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    @php
                                        $respondedCount = $campaign->invitations->filter(function($inv) use ($campaign) {
                                            return $campaign->responses->where('respondent_email', $inv->email)->count() > 0;
                                        })->count();
                                    @endphp
                                    <h5 class="stats-number">{{ $respondedCount }}</h5>
                                    <small class="stats-label">Respondieron</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    @php
                                        $responseRate = $campaign->invitations->count() > 0 
                                            ? round(($respondedCount / $campaign->invitations->count()) * 100, 1) 
                                            : 0;
                                    @endphp
                                    <h5 class="stats-number">{{ $responseRate }}%</h5>
                                    <small class="stats-label">Tasa de Respuesta</small>
                                </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
                                <th>Reporte IA</th>
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
                                        @if($response->duration_minutes)
                                            <small>{{ $response->duration_minutes }} min</small>
                                        @else
                                            <small class="text-muted">N/A</small>
                                        @endif
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
            @else
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>No hay respuestas para esta campaña aún</p>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Modal para invitación individual -->
    <div class="modal fade" id="singleInviteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invitar Persona</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="singleInviteForm" method="POST" action="{{ route('company.campaigns.add-invitation', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}">
                        @csrf
                        <div class="mb-3">
                            <label for="invite_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="invite_email" name="email" required 
                                   placeholder="persona@ejemplo.com">
                        </div>
                        <div class="mb-3">
                            <label for="invite_name" class="form-label">Nombre (opcional)</label>
                            <input type="text" class="form-control" id="invite_name" name="name" 
                                   placeholder="Nombre de la persona">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitSingleInvite()">Enviar Invitación</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para subir CSV adicional -->
    <div class="modal fade" id="csvUploadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subir Lista de Emails Adicional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="csvUploadForm" method="POST" action="{{ route('company.campaigns.add-csv', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" enctype="multipart/form-data">
                        @csrf
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Sube un archivo CSV para agregar más invitados a esta campaña.
                            <br><small>Formato esperado: email, nombre (opcional)</small>
                        </div>
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">Archivo CSV</label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" 
                                   accept=".csv" required onchange="previewAdditionalCSV(this)">
                        </div>
                        
                        <!-- Preview del CSV -->
                        <div id="additional_csv_preview" style="display: none;">
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> Preview del archivo CSV
                                <span id="additional_csv_count"></span>
                            </div>
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Email</th>
                                            <th>Nombre</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="additional_csv_preview_body">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="submitCSVUpload()">Agregar Invitados</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            document.execCommand('copy');
            
            // Show feedback
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copiado';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-secondary');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 2000);
        }
        
        function showSingleInviteModal() {
            const modal = new bootstrap.Modal(document.getElementById('singleInviteModal'));
            modal.show();
        }
        
        function showCSVUploadModal() {
            const modal = new bootstrap.Modal(document.getElementById('csvUploadModal'));
            modal.show();
        }
        
        function submitSingleInvite() {
            const form = document.getElementById('singleInviteForm');
            const email = document.getElementById('invite_email').value;
            
            if (!email) {
                alert('Por favor ingresa un email.');
                return;
            }
            
            form.submit();
        }
        
        function submitCSVUpload() {
            const form = document.getElementById('csvUploadForm');
            const file = document.getElementById('csv_file').files[0];
            
            if (!file) {
                alert('Por favor selecciona un archivo CSV.');
                return;
            }
            
            form.submit();
        }
        
        function previewAdditionalCSV(input) {
            const file = input.files[0];
            const previewDiv = document.getElementById('additional_csv_preview');
            const previewBody = document.getElementById('additional_csv_preview_body');
            const csvCount = document.getElementById('additional_csv_count');
            
            if (!file) {
                previewDiv.style.display = 'none';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const csv = e.target.result;
                const lines = csv.split('\n').filter(line => line.trim() !== '');
                
                previewBody.innerHTML = '';
                let validEmails = 0;
                let invalidEmails = 0;
                let isFirstLineHeader = false;
                
                lines.forEach((line, index) => {
                    const columns = line.split(',').map(col => col.trim().replace(/"/g, ''));
                    const email = columns[0];
                    const name = columns[1] || '';
                    
                    if (index === 0 && !isValidEmail(email)) {
                        isFirstLineHeader = true;
                        return;
                    }
                    
                    const isValid = isValidEmail(email);
                    if (isValid) validEmails++;
                    else invalidEmails++;
                    
                    const rowNumber = isFirstLineHeader ? index : index + 1;
                    const statusClass = isValid ? 'text-success' : 'text-danger';
                    const statusIcon = isValid ? 'fas fa-check' : 'fas fa-times';
                    const statusText = isValid ? 'Válido' : 'Email inválido';
                    
                    const row = document.createElement('tr');
                    row.className = isValid ? '' : 'table-warning';
                    row.innerHTML = `
                        <td>${rowNumber}</td>
                        <td>${email}</td>
                        <td>${name}</td>
                        <td class="${statusClass}">
                            <i class="${statusIcon}"></i> ${statusText}
                        </td>
                    `;
                    previewBody.appendChild(row);
                });
                
                csvCount.innerHTML = `
                    - <strong>${validEmails}</strong> emails válidos
                    ${invalidEmails > 0 ? `, <span class="text-warning">${invalidEmails} inválidos</span>` : ''}
                `;
                
                previewDiv.style.display = 'block';
            };
            
            reader.readAsText(file);
        }
        
        function copyInvitationLink(token) {
            const invitationUrl = '{{ config("app.frontend_url") }}/i/' + token;
            
            // Create temporary input to copy text
            const tempInput = document.createElement('input');
            tempInput.value = invitationUrl;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            
            // Show feedback
            const button = event.target.closest('button');
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-primary');
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-primary');
            }, 2000);
        }
    </script>
@endsection