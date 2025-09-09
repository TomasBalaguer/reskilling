@extends('company.layout')

@section('title', 'Nueva Campaña - ' . $company->name)
@section('page-title', 'Nueva Campaña')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus"></i> Crear Nueva Campaña
                </h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    Campañas disponibles: <strong>{{ $company->max_campaigns - $company->campaigns()->count() }}</strong> de {{ $company->max_campaigns }}
                </div>

                <form action="{{ route('company.campaigns.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre de la Campaña <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_responses" class="form-label">Máximo de Respuestas <span class="text-danger">*</span></label>
                                <input type="number" 
                                       class="form-control @error('max_responses') is-invalid @enderror" 
                                       id="max_responses" 
                                       name="max_responses" 
                                       value="{{ old('max_responses', 100) }}" 
                                       min="1" 
                                       max="{{ $company->max_responses_per_campaign }}"
                                       required>
                                <small class="form-text text-muted">
                                    Límite máximo: {{ $company->max_responses_per_campaign }}
                                </small>
                                @error('max_responses')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="active_from" class="form-label">Activa desde <span class="text-danger">*</span></label>
                                <input type="datetime-local" 
                                       class="form-control @error('active_from') is-invalid @enderror" 
                                       id="active_from" 
                                       name="active_from" 
                                       value="{{ old('active_from', now()->format('Y-m-d\TH:i')) }}" 
                                       required>
                                @error('active_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="active_until" class="form-label">Activa hasta <span class="text-danger">*</span></label>
                                <input type="datetime-local" 
                                       class="form-control @error('active_until') is-invalid @enderror" 
                                       id="active_until" 
                                       name="active_until" 
                                       value="{{ old('active_until', now()->addMonth()->format('Y-m-d\TH:i')) }}" 
                                       required>
                                @error('active_until')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card border-secondary mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-clipboard-list"></i> Cuestionarios a Incluir</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Seleccione los cuestionarios que formarán parte de esta campaña:</p>
                            @if($questionnaires->count() > 0)
                                @foreach($questionnaires as $questionnaire)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input @error('questionnaires') is-invalid @enderror" 
                                               type="checkbox" 
                                               name="questionnaires[]" 
                                               id="questionnaire_{{ $questionnaire->id }}" 
                                               value="{{ $questionnaire->id }}"
                                               {{ in_array($questionnaire->id, old('questionnaires', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="questionnaire_{{ $questionnaire->id }}">
                                            <strong>{{ $questionnaire->name }}</strong>
                                            @if($questionnaire->description)
                                                <br><small class="text-muted">{{ $questionnaire->description }}</small>
                                            @endif
                                            <br><small class="badge bg-info">{{ $questionnaire->getQuestionnaireType()->getDisplayName() ?? 'Tipo: ' . $questionnaire->getQuestionnaireType()->value }}</small>
                                        </label>
                                    </div>
                                @endforeach
                                @error('questionnaires')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            @else
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> No hay cuestionarios activos disponibles.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-key"></i> Tipo de Acceso</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="access_type" 
                                       id="public_link" 
                                       value="public_link" 
                                       {{ old('access_type', 'public_link') === 'public_link' ? 'checked' : '' }}>
                                <label class="form-check-label" for="public_link">
                                    <strong>Enlace Público</strong>
                                    <br><small class="text-muted">Se generará un código único que se puede compartir libremente</small>
                                </label>
                            </div>
                            
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="radio" 
                                       name="access_type" 
                                       id="email_list" 
                                       value="email_list" 
                                       {{ old('access_type') === 'email_list' ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_list">
                                    <strong>Lista de Emails</strong>
                                    <br><small class="text-muted">Se enviará a las personas en la lista mediante invitación</small>
                                </label>
                            </div>
                            
                            <!-- Allow Public Access checkbox for Email List -->
                            <div class="mt-3" id="allow_public_access_option" style="{{ old('access_type') === 'email_list' ? '' : 'display: none;' }}">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="allow_public_access" 
                                           id="allow_public_access" 
                                           value="1"
                                           {{ old('allow_public_access', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="allow_public_access">
                                        <strong>Permitir también acceso público</strong>
                                        <br><small class="text-muted">El enlace público también funcionará, no solo las invitaciones por email</small>
                                    </label>
                                </div>
                            </div>

                            <div class="mt-3" id="email_list_options" style="{{ old('access_type') === 'email_list' ? '' : 'display: none;' }}">
                                <div class="alert alert-info">
                                    <i class="fas fa-upload"></i> Subir archivo CSV con emails
                                    <br><small>Formato esperado: email, nombre (opcional)</small>
                                </div>
                                <div class="mb-3">
                                    <label for="email_list_file" class="form-label">Archivo CSV</label>
                                    <input type="file" 
                                           class="form-control @error('email_list') is-invalid @enderror" 
                                           id="email_list_file" 
                                           name="email_list" 
                                           accept=".csv"
                                           onchange="previewCSV(this)">
                                    @error('email_list')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <!-- CSV Preview -->
                                <div id="csv_preview" style="display: none;">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> Preview del archivo CSV
                                        <span id="csv_count"></span>
                                    </div>
                                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Email</th>
                                                    <th>Nombre</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody id="csv_preview_body">
                                                <!-- Preview rows will be inserted here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('company.campaigns') }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                           class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Crear Campaña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const publicLinkRadio = document.getElementById('public_link');
    const emailListRadio = document.getElementById('email_list');
    const emailListOptions = document.getElementById('email_list_options');
    const allowPublicAccessOption = document.getElementById('allow_public_access_option');
    const emailListFile = document.getElementById('email_list_file');

    function toggleEmailOptions() {
        if (emailListRadio.checked) {
            emailListOptions.style.display = 'block';
            allowPublicAccessOption.style.display = 'block';
            emailListFile.required = true;
        } else {
            emailListOptions.style.display = 'none';
            allowPublicAccessOption.style.display = 'none';
            emailListFile.required = false;
        }
    }

    publicLinkRadio.addEventListener('change', toggleEmailOptions);
    emailListRadio.addEventListener('change', toggleEmailOptions);

    // Initial state
    toggleEmailOptions();
});

function previewCSV(input) {
    const file = input.files[0];
    const previewDiv = document.getElementById('csv_preview');
    const previewBody = document.getElementById('csv_preview_body');
    const csvCount = document.getElementById('csv_count');
    
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
            
            // Check if first line is header
            if (index === 0 && !isValidEmail(email)) {
                isFirstLineHeader = true;
                return; // Skip header row
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
        
        // Update count
        const totalEmails = validEmails + invalidEmails;
        csvCount.innerHTML = `
            - <strong>${validEmails}</strong> emails válidos
            ${invalidEmails > 0 ? `, <span class="text-warning">${invalidEmails} inválidos</span>` : ''}
        `;
        
        previewDiv.style.display = 'block';
    };
    
    reader.readAsText(file);
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}
</script>
@endsection