@extends('public.layout')

@section('title', 'Invitación expirada - ' . $invitation->campaign->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="text-center">
            <div class="mb-4">
                <i class="fas fa-clock fa-4x text-warning"></i>
            </div>
            
            <h1 class="h2 mb-3">Invitación Expirada</h1>
            <p class="lead text-muted mb-4">
                Tu invitación para participar en esta evaluación ha expirado.
            </p>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $invitation->campaign->name }}</h5>
                    @if($invitation->campaign->description)
                        <p class="card-text text-muted">{{ $invitation->campaign->description }}</p>
                    @endif
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-calendar-times"></i>
                        Esta invitación expiró el {{ $invitation->expires_at->format('d/m/Y H:i') }}.
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="text-muted">
                    Contacta con {{ $invitation->campaign->company->name ?? 'la organización' }} 
                    para solicitar una nueva invitación.
                </p>
                
                @if($invitation->campaign->company && $invitation->campaign->company->email)
                    <a href="mailto:{{ $invitation->campaign->company->email }}" class="btn btn-outline-primary me-2">
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