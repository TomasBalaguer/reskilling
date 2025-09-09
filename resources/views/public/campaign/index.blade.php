@extends('public.layout')

@section('title', $campaign->name . ' - Evaluación Psicológica')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <!-- Campaign Header -->
        <div class="card mb-4 fade-in">
            <div class="card-body text-center">
                <h1 class="h3 mb-3">{{ $campaign->name }}</h1>
                
                @if($campaign->description)
                    <p class="text-muted mb-4">{{ $campaign->description }}</p>
                @endif

                <div class="row text-center">
                    <div class="col-4">
                        <i class="fas fa-clipboard-list text-primary fa-2x mb-2"></i>
                        <div class="fw-bold">{{ $campaign->questionnaires->count() }}</div>
                        <small class="text-muted">Cuestionarios</small>
                    </div>
                    <div class="col-4">
                        <i class="fas fa-clock text-info fa-2x mb-2"></i>
                        <div class="fw-bold">
                            @php
                                $totalMinutes = $campaign->questionnaires->sum('estimated_duration_minutes');
                                $hours = floor($totalMinutes / 60);
                                $minutes = $totalMinutes % 60;
                            @endphp
                            @if($hours > 0)
                                {{ $hours }}h {{ $minutes }}m
                            @else
                                {{ $minutes }}m
                            @endif
                        </div>
                        <small class="text-muted">Duración estimada</small>
                    </div>
                    <div class="col-4">
                        <i class="fas fa-users text-success fa-2x mb-2"></i>
                        <div class="fw-bold">{{ $campaign->responses_count }}</div>
                        <small class="text-muted">Respuestas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Indicator -->
        @php
            $sessionKey = 'campaign_' . $campaign->id;
            $sessionData = Session::get($sessionKey, []);
            $completedQuestionnaires = $sessionData['completed_questionnaires'] ?? [];
            $totalQuestionnaires = $campaign->questionnaires->count();
            $completedCount = count($completedQuestionnaires);
            $progressPercent = $totalQuestionnaires > 0 ? ($completedCount / $totalQuestionnaires) * 100 : 0;
        @endphp

        @if($completedCount > 0)
            <div class="card mb-4 fade-in">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold">Progreso</span>
                        <span class="text-muted">{{ $completedCount }} de {{ $totalQuestionnaires }} completados</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Respondent Info Check -->
        @php
            $respondentInfo = $sessionData['respondent_info'] ?? null;
        @endphp

        @if(!$respondentInfo && !isset($invitation))
            <div class="card mb-4 fade-in">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user"></i> Información Personal
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Para comenzar con los cuestionarios, necesitamos algunos datos básicos.
                    </p>
                    
                    <form action="{{ route('public.campaign.save-respondent', $campaign->code) }}" method="POST">
                        @csrf
                        <input type="hidden" name="campaign_id" value="{{ $campaign->id }}">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nombre completo <span class="text-danger">*</span></label>
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
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-right"></i> Continuar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <!-- Welcome Message -->
            <div class="card mb-4 fade-in">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-user-circle fa-3x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="mb-1">
                                Bienvenido/a, {{ $respondentInfo['name'] ?? $invitation->name }}
                            </h5>
                            <p class="text-muted mb-0">
                                {{ $respondentInfo['email'] ?? $invitation->email }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Questionnaires List -->
            <div class="card fade-in">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list-check"></i> Cuestionarios por Completar
                    </h5>
                </div>
                <div class="card-body">
                    @if($campaign->questionnaires->count() > 0)
                        <div class="row">
                            @foreach($campaign->questionnaires as $index => $questionnaire)
                                @php
                                    $isCompleted = in_array($questionnaire->id, $completedQuestionnaires);
                                    $isNext = !$isCompleted && $index == $completedCount;
                                    $isLocked = !$isCompleted && $index > $completedCount;
                                @endphp
                                
                                <div class="col-md-6 mb-4">
                                    <div class="card h-100 {{ $isCompleted ? 'border-success' : ($isNext ? 'border-primary' : 'border-light') }}">
                                        <div class="card-body d-flex flex-column">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="me-3">
                                                    @switch($questionnaire->questionnaire_type?->value)
                                                        @case('REFLECTIVE_QUESTIONS')
                                                            <i class="fas fa-microphone fa-2x text-primary"></i>
                                                            @break
                                                        @case('MULTIPLE_CHOICE')
                                                        @case('SINGLE_CHOICE')
                                                            <i class="fas fa-check-square fa-2x text-success"></i>
                                                            @break
                                                        @case('TEXT_RESPONSE')
                                                            <i class="fas fa-pen fa-2x text-info"></i>
                                                            @break
                                                        @case('SCALE_RATING')
                                                        @case('BIG_FIVE')
                                                            <i class="fas fa-chart-bar fa-2x text-warning"></i>
                                                            @break
                                                        @default
                                                            <i class="fas fa-clipboard-question fa-2x text-secondary"></i>
                                                    @endswitch
                                                </div>
                                                
                                                <div class="flex-grow-1">
                                                    <h6 class="card-title">{{ $questionnaire->name }}</h6>
                                                    
                                                    @if($questionnaire->description)
                                                        <p class="card-text text-muted small">
                                                            {{ Str::limit($questionnaire->description, 100) }}
                                                        </p>
                                                    @endif
                                                    
                                                    <div class="small text-muted mb-2">
                                                        @if($questionnaire->estimated_duration_minutes)
                                                            <i class="fas fa-clock"></i> {{ $questionnaire->estimated_duration_minutes }} min
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                @if($isCompleted)
                                                    <div class="text-success">
                                                        <i class="fas fa-check-circle fa-2x"></i>
                                                    </div>
                                                @elseif($isLocked)
                                                    <div class="text-muted">
                                                        <i class="fas fa-lock fa-2x"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <div class="mt-auto">
                                                @if($isCompleted)
                                                    <button class="btn btn-success btn-sm w-100" disabled>
                                                        <i class="fas fa-check"></i> Completado
                                                    </button>
                                                @elseif($isNext)
                                                    <a href="{{ route('public.campaign.questionnaire', [$campaign->code, $questionnaire->id]) }}" 
                                                       class="btn btn-primary w-100">
                                                        <i class="fas fa-play"></i> Comenzar
                                                    </a>
                                                @elseif($isLocked)
                                                    <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                                        <i class="fas fa-lock"></i> Bloqueado
                                                    </button>
                                                @else
                                                    <a href="{{ route('public.campaign.questionnaire', [$campaign->code, $questionnaire->id]) }}" 
                                                       class="btn btn-outline-primary w-100">
                                                        <i class="fas fa-arrow-right"></i> Iniciar
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($completedCount === $totalQuestionnaires)
                            <div class="text-center mt-4">
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> 
                                    ¡Felicidades! Has completado todos los cuestionarios.
                                </div>
                                <a href="{{ route('public.campaign.thank-you', $campaign->code) }}" class="btn btn-success btn-lg">
                                    <i class="fas fa-flag-checkered"></i> Finalizar Evaluación
                                </a>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-question fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay cuestionarios disponibles</h5>
                            <p class="text-muted">Esta campaña no tiene cuestionarios configurados.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate cards on load
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, index * 100);
    });
});
</script>
@endsection