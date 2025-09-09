@extends('admin.layout')

@section('title', 'Editar Campaña: ' . $campaign->name)
@section('page-title', 'Editar Campaña: ' . $campaign->name)

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('admin.campaigns.detail', $campaign->id) }}" class="btn btn-outline-secondary">
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
                        <i class="fas fa-edit"></i> Editar Información de la Campaña
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.campaigns.update', $campaign->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Empresa (solo informativa) -->
                                <div class="mb-3">
                                    <label class="form-label">Empresa</label>
                                    <div class="alert alert-light mb-0">
                                        <i class="fas fa-building"></i> {{ $campaign->company->name }}
                                        <small class="text-muted d-block">No se puede cambiar la empresa de una campaña existente</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Código (solo informativo) -->
                                <div class="mb-3">
                                    <label class="form-label">Código de Campaña</label>
                                    <div class="alert alert-light mb-0">
                                        <code>{{ $campaign->code }}</code>
                                        <small class="text-muted d-block">El código no se puede modificar</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Nombre -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre de la Campaña *</label>
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
                                <!-- Estado -->
                                <div class="mb-3">
                                    <label for="status" class="form-label">Estado *</label>
                                    <select class="form-select @error('status') is-invalid @enderror" 
                                            id="status" 
                                            name="status" 
                                            required>
                                        <option value="draft" {{ old('status', $campaign->status) == 'draft' ? 'selected' : '' }}>
                                            Borrador
                                        </option>
                                        <option value="active" {{ old('status', $campaign->status) == 'active' ? 'selected' : '' }}>
                                            Activa
                                        </option>
                                        <option value="paused" {{ old('status', $campaign->status) == 'paused' ? 'selected' : '' }}>
                                            Pausada
                                        </option>
                                        <option value="completed" {{ old('status', $campaign->status) == 'completed' ? 'selected' : '' }}>
                                            Completada
                                        </option>
                                    </select>
                                    @error('status')
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
                                      rows="3">{{ old('description', $campaign->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Máximo respuestas -->
                                <div class="mb-3">
                                    <label for="max_responses" class="form-label">Máximo de Respuestas *</label>
                                    <input type="number" 
                                           class="form-control @error('max_responses') is-invalid @enderror" 
                                           id="max_responses" 
                                           name="max_responses" 
                                           value="{{ old('max_responses', $campaign->max_responses) }}" 
                                           min="1" 
                                           max="{{ $campaign->company->max_responses_per_campaign }}" 
                                           required>
                                    @error('max_responses')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">
                                        Límite de la empresa: {{ $campaign->company->max_responses_per_campaign }}
                                        | Respuestas actuales: {{ $campaign->responses->count() }}
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Estadísticas -->
                                <div class="mb-3">
                                    <label class="form-label">Estadísticas Actuales</label>
                                    <div class="alert alert-info mb-0">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <strong>{{ $campaign->responses->count() }}</strong>
                                                <small class="d-block text-muted">Respuestas</small>
                                            </div>
                                            <div class="col-4">
                                                <strong>{{ $campaign->responses->whereIn('processing_status', ['completed', 'analyzed'])->count() }}</strong>
                                                <small class="d-block text-muted">Completadas</small>
                                            </div>
                                            <div class="col-4">
                                                <strong>{{ $campaign->responses->where('processing_status', 'pending')->count() }}</strong>
                                                <small class="d-block text-muted">Pendientes</small>
                                            </div>
                                        </div>
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
                                           value="{{ old('active_from', $campaign->active_from ? $campaign->active_from->format('Y-m-d') : '') }}">
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
                                           value="{{ old('active_until', $campaign->active_until ? $campaign->active_until->format('Y-m-d') : '') }}">
                                    @error('active_until')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Opcional: fecha hasta cuándo está disponible</small>
                                </div>
                            </div>
                        </div>
                        
                        @if($campaign->responses->count() > 0)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Atención:</strong> Esta campaña ya tiene {{ $campaign->responses->count() }} respuestas. 
                                Algunos cambios podrían afectar la consistencia de los datos.
                            </div>
                        @endif
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.campaigns.detail', $campaign->id) }}" class="btn btn-secondary">
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