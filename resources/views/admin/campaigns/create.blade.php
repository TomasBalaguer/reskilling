@extends('admin.layout')

@section('title', 'Crear Campaña - Administración')
@section('page-title', 'Crear Nueva Campaña')

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('admin.campaigns') }}" class="btn btn-outline-secondary rounded-3" style="border-width: 1px; font-weight: normal;">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bullhorn"></i> Información de la Campaña
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.campaigns.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Empresa -->
                                <div class="mb-3">
                                    <label for="company_id" class="form-label">Empresa *</label>
                                    <select class="form-select @error('company_id') is-invalid @enderror" 
                                            id="company_id" 
                                            name="company_id" 
                                            required>
                                        <option value="">Seleccionar empresa...</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}" 
                                                    {{ old('company_id', $selectedCompanyId) == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}
                                                ({{ $company->campaigns->count() }}/{{ $company->max_campaigns }} campañas)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('company_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Nombre -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre de la Campaña *</label>
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
                        </div>
                        
                        <!-- Descripción -->
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

                        <!-- Cuestionarios a incluir -->
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
                                                <br><small class="badge bg-info">Tipo: {{ $questionnaire->questionnaire_type ?? 'No especificado' }}</small>
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
                        
                        <div class="row">
                            <div class="col-md-4">
                                <!-- Estado -->
                                <div class="mb-3">
                                    <label for="status" class="form-label">Estado Inicial *</label>
                                    <select class="form-select @error('status') is-invalid @enderror" 
                                            id="status" 
                                            name="status" 
                                            required>
                                        <option value="draft" {{ old('status', 'draft') == 'draft' ? 'selected' : '' }}>
                                            Borrador
                                        </option>
                                        <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>
                                            Activa
                                        </option>
                                        <option value="paused" {{ old('status') == 'paused' ? 'selected' : '' }}>
                                            Pausada
                                        </option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Máximo respuestas -->
                                <div class="mb-3">
                                    <label for="max_responses" class="form-label">Máximo de Respuestas *</label>
                                    <input type="number" 
                                           class="form-control @error('max_responses') is-invalid @enderror" 
                                           id="max_responses" 
                                           name="max_responses" 
                                           value="{{ old('max_responses', 100) }}" 
                                           min="1" 
                                           max="10000" 
                                           required>
                                    @error('max_responses')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Número máximo de usuarios que pueden responder</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Código</label>
                                    <div class="alert alert-info mb-0">
                                        <small><i class="fas fa-info-circle"></i> Se generará automáticamente</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <h6 class="text-primary"><i class="fas fa-calendar-alt"></i> Período de Actividad</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Fecha inicio -->
                                <div class="mb-3">
                                    <label for="active_from" class="form-label">Activa Desde</label>
                                    <input type="date" 
                                           class="form-control @error('active_from') is-invalid @enderror" 
                                           id="active_from" 
                                           name="active_from" 
                                           value="{{ old('active_from') }}">
                                    @error('active_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Opcional: fecha desde cuándo está disponible</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Fecha fin -->
                                <div class="mb-3">
                                    <label for="active_until" class="form-label">Activa Hasta</label>
                                    <input type="date" 
                                           class="form-control @error('active_until') is-invalid @enderror" 
                                           id="active_until" 
                                           name="active_until" 
                                           value="{{ old('active_until') }}">
                                    @error('active_until')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Opcional: fecha hasta cuándo está disponible</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Nota:</strong> Una vez creada la campaña, deberá configurar los cuestionarios asociados desde el sistema de gestión de cuestionarios.
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.campaigns') }}" class="btn btn-outline-secondary rounded-3" style="border-width: 1px; font-weight: normal;">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-outline-primary rounded-3" style="border-width: 1px; font-weight: normal;">
                                <i class="fas fa-save"></i> Crear Campaña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.getElementById('company_id').addEventListener('change', function() {
    const companyId = this.value;
    const maxResponsesInput = document.getElementById('max_responses');
    
    if (companyId) {
        // Aquí podrías hacer una llamada AJAX para obtener el límite de la empresa
        // Por ahora, mostraremos el valor por defecto
        const selectedOption = this.options[this.selectedIndex];
        const companyInfo = selectedOption.textContent;
    }
});
</script>
@endsection