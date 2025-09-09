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
                                            <br><small class="badge bg-info">{{ $questionnaire->questionnaire_type->getDisplayName() ?? 'Tipo: ' . $questionnaire->questionnaire_type->value }}</small>
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
                                    <br><small class="text-muted">Solo las personas en la lista podrán acceder mediante invitación</small>
                                </label>
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
                                           accept=".csv">
                                    @error('email_list')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
    const emailListFile = document.getElementById('email_list_file');

    function toggleEmailOptions() {
        if (emailListRadio.checked) {
            emailListOptions.style.display = 'block';
            emailListFile.required = true;
        } else {
            emailListOptions.style.display = 'none';
            emailListFile.required = false;
        }
    }

    publicLinkRadio.addEventListener('change', toggleEmailOptions);
    emailListRadio.addEventListener('change', toggleEmailOptions);

    // Initial state
    toggleEmailOptions();
});
</script>
@endsection