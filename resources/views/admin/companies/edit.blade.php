@extends('admin.layout')

@section('title', 'Editar Empresa: ' . $company->name)
@section('page-title', 'Editar Empresa: ' . $company->name)

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('admin.companies.detail', $company->id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Editar Información de la Empresa
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.companies.update', $company->id) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Nombre -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre de la Empresa *</label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $company->name) }}" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email de Contacto</label>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email', $company->email) }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Teléfono -->
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Teléfono</label>
                                    <input type="text" 
                                           class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" 
                                           name="phone" 
                                           value="{{ old('phone', $company->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Estado -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               {{ old('is_active', $company->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Empresa Activa
                                        </label>
                                    </div>
                                    <small class="text-muted">Las empresas inactivas no pueden crear campañas</small>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <h6 class="text-primary"><i class="fas fa-cog"></i> Límites y Configuración</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Máximo de campañas -->
                                <div class="mb-3">
                                    <label for="max_campaigns" class="form-label">Máximo de Campañas *</label>
                                    <input type="number" 
                                           class="form-control @error('max_campaigns') is-invalid @enderror" 
                                           id="max_campaigns" 
                                           name="max_campaigns" 
                                           value="{{ old('max_campaigns', $company->max_campaigns) }}" 
                                           min="1" 
                                           max="1000" 
                                           required>
                                    @error('max_campaigns')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">
                                        <small class="text-muted">Actualmente tiene {{ $company->campaigns->count() }} campañas</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Máximo de respuestas por campaña -->
                                <div class="mb-3">
                                    <label for="max_responses_per_campaign" class="form-label">Máximo Respuestas por Campaña *</label>
                                    <input type="number" 
                                           class="form-control @error('max_responses_per_campaign') is-invalid @enderror" 
                                           id="max_responses_per_campaign" 
                                           name="max_responses_per_campaign" 
                                           value="{{ old('max_responses_per_campaign', $company->max_responses_per_campaign) }}" 
                                           min="10" 
                                           max="10000" 
                                           required>
                                    @error('max_responses_per_campaign')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Límite de usuarios por campaña</small>
                                </div>
                            </div>
                        </div>
                        
                        @if($company->campaigns->count() > 0)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Nota:</strong> Reducir los límites no afectará las campañas existentes, pero puede impedir crear nuevas.
                            </div>
                        @endif
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.companies.detail', $company->id) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Empresa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection