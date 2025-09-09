@extends('public.layout')

@section('title', 'Campaña Ya Completada - ' . $campaign->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="text-center">
            <!-- Success Icon -->
            <div class="mb-4">
                <i class="fas fa-check-circle fa-5x" style="color: var(--success-color);"></i>
            </div>
            
            <h1 class="h2 mb-3">¡Campaña Ya Completada!</h1>
            <p class="lead text-muted mb-4">
                Ya has completado todos los cuestionarios de esta campaña con el email 
                <strong>{{ $email }}</strong>
            </p>
            
            <!-- Campaign Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">{{ $campaign->name }}</h5>
                    @if($campaign->description)
                        <p class="card-text text-muted">{{ $campaign->description }}</p>
                    @endif
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center p-3 rounded" 
                                 style="background: rgba(16, 185, 129, 0.1);">
                                <i class="fas fa-clipboard-check fa-2x me-3" style="color: var(--success-color);"></i>
                                <div class="text-start">
                                    <h6 class="mb-1">Cuestionarios Completados</h6>
                                    <p class="mb-0 text-muted">{{ count($completed_questionnaires) }} de {{ count($completed_questionnaires) }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-center p-3 rounded" 
                                 style="background: rgba(99, 102, 241, 0.1);">
                                <i class="fas fa-calendar-check fa-2x me-3" style="color: var(--primary-color);"></i>
                                <div class="text-start">
                                    <h6 class="mb-1">Completado el</h6>
                                    <p class="mb-0 text-muted">
                                        {{ collect($completed_questionnaires)->max('completed_at')?->format('d/m/Y H:i') ?? 'Fecha no disponible' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Completed Questionnaires List -->
            @if(!empty($completed_questionnaires))
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-list-check"></i> Cuestionarios Completados
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach($completed_questionnaires as $completed)
                                <div class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-check-circle text-success me-3"></i>
                                        <div>
                                            <h6 class="mb-1">{{ $completed['questionnaire']->name }}</h6>
                                            @if($completed['questionnaire']->description)
                                                <small class="text-muted">{{ $completed['questionnaire']->description }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        {{ $completed['completed_at']->format('d/m/Y H:i') }}
                                    </small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Message and Actions -->
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> ¿Qué puedes hacer ahora?</h6>
                <ul class="text-start mb-0">
                    <li>Tus respuestas han sido guardadas exitosamente</li>
                    <li>Los resultados están siendo procesados</li>
                    <li>Si necesitas modificar alguna respuesta, contacta al administrador de la campaña</li>
                    @if($campaign->company->email)
                        <li>Contacto: <a href="mailto:{{ $campaign->company->email }}">{{ $campaign->company->email }}</a></li>
                    @endif
                </ul>
            </div>
            
            <!-- Actions -->
            <div class="mt-4">
                <a href="/" class="btn btn-primary">
                    <i class="fas fa-home"></i> Ir al Inicio
                </a>
                
                @if($campaign->company->email)
                    <a href="mailto:{{ $campaign->company->email }}" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-envelope"></i> Contactar Soporte
                    </a>
                @endif
            </div>
            
            <!-- Thank you message -->
            <div class="mt-5 p-4 rounded" style="background: linear-gradient(135deg, var(--light-gray), #f3f4f6);">
                <h5 style="color: var(--primary-color);">
                    <i class="fas fa-heart"></i> ¡Gracias por tu participación!
                </h5>
                <p class="text-muted mb-0">
                    Tu colaboración es muy valiosa para mejorar nuestros procesos y servicios.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Auto-redirect after some time (optional)
    // setTimeout(() => {
    //     window.location.href = '/';
    // }, 30000); // 30 seconds
</script>
@endsection