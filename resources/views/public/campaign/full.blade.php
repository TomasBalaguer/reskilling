@extends('public.layout')

@section('title', 'Campaña completa - ' . $campaign->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="text-center">
            <div class="mb-4">
                <i class="fas fa-users fa-4x text-info"></i>
            </div>
            
            <h1 class="h2 mb-3">Campaña Completa</h1>
            <p class="lead text-muted mb-4">
                Esta evaluación ya alcanzó el número máximo de participantes.
            </p>
            
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ $campaign->name }}</h5>
                    @if($campaign->description)
                        <p class="card-text text-muted">{{ $campaign->description }}</p>
                    @endif
                    
                    <div class="alert alert-info">
                        <i class="fas fa-chart-pie"></i>
                        Se completaron <strong>{{ $campaign->responses_count }}</strong> de 
                        <strong>{{ $campaign->max_responses }}</strong> respuestas permitidas.
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <p class="text-muted">
                    Gracias por tu interés en participar. 
                    La organización puede decidir crear una nueva campaña en el futuro.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection