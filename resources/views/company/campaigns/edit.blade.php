@extends('company.layout')

@section('title', 'Editar Campaña - ' . $campaign->name)
@section('page-title', 'Editar Campaña: ' . $campaign->name)

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('company.campaigns.detail', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
           class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Detalle
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-edit"></i> Editar Campaña
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
                    Solo se pueden editar campañas sin respuestas. Esta campaña tiene <strong>{{ $campaign->responses()->count() }}</strong> respuestas.
                </div>

                <form action="{{ route('company.campaigns.update', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nombre de la Campaña <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $campaign->name) }}" 
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
                                       value="{{ old('max_responses', $campaign->max_responses) }}" 
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
                                  rows="3">{{ old('description', $campaign->description) }}</textarea>
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
                                       value="{{ old('active_from', $campaign->active_from ? $campaign->active_from->format('Y-m-d\TH:i') : '') }}" 
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
                                       value="{{ old('active_until', $campaign->active_until ? $campaign->active_until->format('Y-m-d\TH:i') : '') }}" 
                                       required>
                                @error('active_until')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de Acceso -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-cog"></i> Configuración de Acceso
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Tipo de Acceso Actual:</h6>
                                    <div class="alert alert-info">
                                        @if($campaign->access_type === 'public_link')
                                            <i class="fas fa-globe"></i> <strong>Enlace Público</strong>
                                            <br><small>Cualquier persona con el enlace puede participar</small>
                                        @else
                                            <i class="fas fa-envelope"></i> <strong>Lista de Emails</strong>
                                            <br><small>Solo personas invitadas pueden participar</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    @if($campaign->access_type === 'email_list')
                                        <h6>Permitir Acceso Público:</h6>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="allow_public_access" 
                                                   id="allow_public_access" 
                                                   value="1"
                                                   {{ old('allow_public_access', $campaign->allow_public_access) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="allow_public_access">
                                                <strong>Permitir también acceso público</strong>
                                                <br><small class="text-muted">El enlace público también funcionará, no solo las invitaciones por email</small>
                                            </label>
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-info-circle"></i>
                                            Las campañas de enlace público siempre permiten acceso público.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('company.campaigns.detail', $campaign->id) }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                           class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar Campaña
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection