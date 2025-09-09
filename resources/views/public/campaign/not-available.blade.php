@extends('public.layout')

@section('title', 'Acceso restringido - ' . $campaign->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="text-center">
            <div class="mb-4">
                <i class="fas fa-lock fa-4x text-warning"></i>
            </div>
            
            <h1 class="h2 mb-3">Acceso Restringido</h1>
            <p class="lead text-muted mb-4">
                Esta campaña requiere una invitación específica para participar.
            </p>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $campaign->name }}</h5>
                    @if($campaign->description)
                        <p class="card-text text-muted">{{ $campaign->description }}</p>
                    @endif
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-envelope"></i>
                        Esta campaña utiliza invitaciones por email. 
                        Solo las personas que recibieron una invitación específica pueden participar.
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="text-muted">
                    Si crees que deberías tener acceso a esta evaluación, 
                    contacta con la organización que administra esta campaña.
                </p>
                
                @if($campaign->company && $campaign->company->email)
                    <a href="mailto:{{ $campaign->company->email }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-envelope"></i> Contactar Organización
                    </a>
                @endif
                
                <a href="/" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Ir al inicio
                </a>
            </div>
        </div>
    </div>
</div>
@endsection