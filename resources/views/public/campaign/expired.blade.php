@extends('public.layout')

@section('title', 'Campaña expirada - ' . $campaign->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="text-center">
            <div class="mb-4">
                <i class="fas fa-calendar-times fa-4x text-danger"></i>
            </div>
            
            <h1 class="h2 mb-3">Campaña Expirada</h1>
            <p class="lead text-muted mb-4">
                Esta evaluación ya no está disponible.
            </p>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $campaign->name }}</h5>
                    @if($campaign->description)
                        <p class="card-text text-muted">{{ $campaign->description }}</p>
                    @endif
                    
                    <div class="alert alert-danger">
                        <i class="fas fa-clock"></i>
                        Esta campaña estuvo activa hasta el 
                        <strong>{{ $campaign->active_until->format('d/m/Y H:i') }}</strong>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="text-muted">
                    Si necesitas participar en esta evaluación, 
                    contacta con la organización responsable para consultar sobre extensiones.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection