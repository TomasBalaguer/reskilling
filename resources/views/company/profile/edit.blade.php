@extends('company.layout')

@section('title', 'Perfil de Empresa - ' . $company->name)
@section('page-title', 'Perfil de Empresa')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-compact">
            <div class="card-header">
                <h5 class="mb-0 fw-medium">
                    <i class="fas fa-building"></i> Información de la Empresa
                </h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h6 class="fw-medium"><i class="fas fa-exclamation-triangle"></i> Errores en el formulario:</h6>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('company.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="company_id" value="{{ request('company_id') }}">

                    <!-- Logo Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="fw-medium mb-3" style="color: var(--primary-color);">
                                <i class="fas fa-image"></i> Logo de la Empresa
                            </h6>
                            
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="logo-preview-container" style="border: 2px dashed var(--border-color); border-radius: 0.75rem; padding: 1rem; text-align: center; background: var(--light-gray);">
                                        @if($company->logo_url)
                                            <img src="{{ Storage::url($company->logo_url) }}" 
                                                 alt="Logo de {{ $company->name }}" 
                                                 class="img-fluid rounded"
                                                 style="max-height: 120px; max-width: 100%;">
                                        @else
                                            <div style="padding: 2rem; color: var(--medium-gray);">
                                                <i class="fas fa-image fa-3x mb-2"></i>
                                                <p class="mb-0">Sin logo</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <div class="mb-3">
                                        <label for="logo" class="form-label">Subir nuevo logo</label>
                                        <input type="file" 
                                               class="form-control @error('logo') is-invalid @enderror" 
                                               id="logo" 
                                               name="logo" 
                                               accept="image/*"
                                               onchange="previewLogo(this)">
                                        <div class="form-text">
                                            <i class="fas fa-info-circle"></i> 
                                            Formatos permitidos: JPEG, PNG, JPG, GIF, SVG. Tamaño máximo: 2MB
                                        </div>
                                        @error('logo')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    @if($company->logo_url)
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm" 
                                                onclick="removeLogo()">
                                            <i class="fas fa-trash"></i> Eliminar logo actual
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr style="border-color: var(--border-color); margin: 2rem 0;">

                    <!-- Company Information -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="fw-medium mb-3" style="color: var(--primary-color);">
                                <i class="fas fa-info-circle"></i> Información General
                            </h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nombre de la empresa <span class="text-danger">*</span></label>
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

                        <div class="col-md-6 mb-3">
                            <label for="subdomain" class="form-label">Subdominio</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       value="{{ $company->subdomain }}" 
                                       readonly 
                                       disabled>
                                <span class="input-group-text">.psico-eval.com</span>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-lock"></i> El subdominio no se puede modificar
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email de contacto</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $company->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="tel" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $company->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Company Limits (Read-only) -->
                    <hr style="border-color: var(--border-color); margin: 2rem 0;">
                    
                    <div class="row">
                        <div class="col-12">
                            <h6 class="fw-bold mb-3" style="color: var(--medium-gray);">
                                <i class="fas fa-chart-bar"></i> Límites del Plan
                            </h6>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Máximo de campañas</label>
                            <input type="text" 
                                   class="form-control" 
                                   value="{{ $company->max_campaigns }}" 
                                   readonly 
                                   disabled>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Máximo de respuestas por campaña</label>
                            <input type="text" 
                                   class="form-control" 
                                   value="{{ $company->max_responses_per_campaign }}" 
                                   readonly 
                                   disabled>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
                        <a href="{{ route('company.dashboard') }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Dashboard
                        </a>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Form for removing logo -->
<form id="remove-logo-form" action="{{ route('company.profile.remove-logo') }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
    <input type="hidden" name="company_id" value="{{ request('company_id') }}">
</form>
@endsection

@section('scripts')
<script>
    // Preview logo before upload
    function previewLogo(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const container = document.querySelector('.logo-preview-container');
                container.innerHTML = `
                    <img src="${e.target.result}" 
                         alt="Vista previa del logo" 
                         class="img-fluid rounded"
                         style="max-height: 120px; max-width: 100%;">
                `;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Remove current logo
    function removeLogo() {
        if (confirm('¿Está seguro que desea eliminar el logo actual?')) {
            document.getElementById('remove-logo-form').submit();
        }
    }

    // Auto-hide success alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-success');
        alerts.forEach(alert => {
            if (alert.querySelector('.btn-close')) {
                alert.querySelector('.btn-close').click();
            }
        });
    }, 5000);
</script>
@endsection