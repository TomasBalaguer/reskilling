@extends('public.layout')

@section('title', 'Gracias por participar - ' . $campaign->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <!-- Success Message -->
        <div class="text-center mb-5 fade-in">
            <div class="mb-4">
                <i class="fas fa-check-circle fa-5x text-success"></i>
            </div>
            
            <h1 class="h2 text-success mb-3">¡Evaluación Completada!</h1>
            <p class="lead text-muted">
                Gracias por completar todos los cuestionarios de la evaluación.
            </p>
        </div>

        <!-- Campaign Info -->
        <div class="card mb-4 fade-in">
            <div class="card-body text-center">
                <h5 class="card-title">{{ $campaign->name }}</h5>
                
                @if($campaign->company)
                    <p class="text-muted mb-3">
                        <i class="fas fa-building"></i> {{ $campaign->company->name }}
                    </p>
                @endif
                
                <div class="row text-center">
                    <div class="col-6">
                        <div class="fw-bold text-primary">{{ $campaign->questionnaires->count() }}</div>
                        <small class="text-muted">Cuestionarios completados</small>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold text-success">{{ $campaign->responses_count }}</div>
                        <small class="text-muted">Total de participantes</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="card mb-4 fade-in">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle"></i> ¿Qué sigue ahora?
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <i class="fas fa-check text-success"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Respuestas guardadas</h6>
                            <p class="text-muted small mb-0">
                                Todas tus respuestas han sido guardadas de forma segura en nuestro sistema.
                            </p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <i class="fas fa-cog text-primary"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Procesamiento</h6>
                            <p class="text-muted small mb-0">
                                Nuestro sistema analizará tus respuestas para generar insights valiosos.
                            </p>
                        </div>
                    </div>
                    
                    <div class="timeline-item">
                        <div class="timeline-marker">
                            <i class="fas fa-chart-line text-info"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="mb-1">Resultados</h6>
                            <p class="text-muted small mb-0">
                                Los resultados serán compartidos con la organización correspondiente.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Privacy Notice -->
        <div class="card border-info mb-4 fade-in">
            <div class="card-body">
                <h6 class="text-info">
                    <i class="fas fa-shield-alt"></i> Privacidad y Confidencialidad
                </h6>
                <p class="small text-muted mb-0">
                    Tus respuestas son tratadas con la máxima confidencialidad. 
                    Toda la información será utilizada únicamente para los fines de la evaluación 
                    y no será compartida con terceros sin tu consentimiento expreso.
                </p>
            </div>
        </div>

        <!-- Contact Information -->
        @if($campaign->company->email || $campaign->company->phone)
            <div class="card mb-4 fade-in">
                <div class="card-body text-center">
                    <h6>¿Tienes preguntas?</h6>
                    <p class="text-muted small mb-3">
                        Si tienes alguna consulta sobre esta evaluación, puedes contactarnos:
                    </p>
                    
                    <div class="d-flex justify-content-center gap-3">
                        @if($campaign->company->email)
                            <a href="mailto:{{ $campaign->company->email }}" 
                               class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-envelope"></i> Enviar Email
                            </a>
                        @endif
                        
                        @if($campaign->company->phone)
                            <a href="tel:{{ $campaign->company->phone }}" 
                               class="btn btn-outline-success btn-sm">
                                <i class="fas fa-phone"></i> Llamar
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Final Actions -->
        <div class="text-center fade-in">
            <p class="text-muted mb-3">
                Puedes cerrar esta ventana de forma segura.
            </p>
            
            <div class="d-flex justify-content-center gap-2">
                <button onclick="window.print()" class="btn btn-outline-secondary">
                    <i class="fas fa-print"></i> Imprimir Confirmación
                </button>
                
                <button onclick="window.close()" class="btn btn-primary">
                    <i class="fas fa-times"></i> Cerrar Ventana
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, var(--success-color), var(--info-color));
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.25rem;
    width: 2rem;
    height: 2rem;
    background: white;
    border: 2px solid var(--border-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

.timeline-content {
    margin-left: 0.5rem;
}

/* Print styles */
@media print {
    .btn, .card-footer {
        display: none !important;
    }
    
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    
    body {
        background: white !important;
    }
}

/* Animation delays for staggered appearance */
.fade-in:nth-child(1) { animation-delay: 0.1s; }
.fade-in:nth-child(2) { animation-delay: 0.2s; }
.fade-in:nth-child(3) { animation-delay: 0.3s; }
.fade-in:nth-child(4) { animation-delay: 0.4s; }
.fade-in:nth-child(5) { animation-delay: 0.5s; }
.fade-in:nth-child(6) { animation-delay: 0.6s; }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to elements
    const elements = document.querySelectorAll('.fade-in');
    elements.forEach((element, index) => {
        setTimeout(() => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'all 0.5s ease';
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
    
    // Confetti effect (optional)
    if (typeof confetti !== 'undefined') {
        setTimeout(() => {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 }
            });
        }, 500);
    }
    
    // Auto-scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Prevent accidental back navigation
window.addEventListener('beforeunload', function (e) {
    // Most browsers will ignore this, but it's worth trying
    e.preventDefault();
    e.returnValue = '';
});

// Handle browser back button
window.addEventListener('popstate', function(e) {
    if (confirm('¿Estás seguro de que quieres salir? Tu evaluación ya fue completada exitosamente.')) {
        // Allow navigation
        return true;
    } else {
        // Push state back to current page
        history.pushState(null, null, window.location.href);
        return false;
    }
});

// Push initial state to handle back button
history.pushState(null, null, window.location.href);
</script>
@endsection