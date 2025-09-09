@extends('admin.layout')

@section('title', 'Crear Empresa - Administración')
@section('page-title', 'Crear Nueva Empresa')

@section('page-actions')
    <div class="btn-group" role="group">
        <a href="{{ route('admin.companies') }}" class="btn btn-outline-secondary">
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
                        <i class="fas fa-building"></i> Información de la Empresa
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.companies.store') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Nombre -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nombre de la Empresa *</label>
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
                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email de Contacto</label>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email') }}">
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
                                           value="{{ old('phone') }}">
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
                                               {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Empresa Activa
                                        </label>
                                    </div>
                                    <small class="text-muted">Las empresas inactivas no pueden crear campañas</small>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <h6 class="text-primary"><i class="fas fa-user"></i> Usuario Administrador</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Email del usuario admin -->
                                <div class="mb-3">
                                    <label for="admin_email" class="form-label">Email del Usuario Admin *</label>
                                    <input type="email" 
                                           class="form-control @error('admin_email') is-invalid @enderror" 
                                           id="admin_email" 
                                           name="admin_email" 
                                           value="{{ old('admin_email') }}" 
                                           required>
                                    @error('admin_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Email para acceder al panel de empresa</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Contraseña temporal -->
                                <div class="mb-3">
                                    <label for="admin_password" class="form-label">Contraseña Temporal *</label>
                                    <input type="password" 
                                           class="form-control @error('admin_password') is-invalid @enderror" 
                                           id="admin_password" 
                                           name="admin_password" 
                                           minlength="6" 
                                           required>
                                    @error('admin_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Mínimo 6 caracteres</small>
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
                                           value="{{ old('max_campaigns', 10) }}" 
                                           min="1" 
                                           max="1000" 
                                           required>
                                    @error('max_campaigns')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Número máximo de campañas que puede crear la empresa</small>
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
                                           value="{{ old('max_responses_per_campaign', 500) }}" 
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
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.companies') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Crear Empresa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection