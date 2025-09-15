@extends('public.layout')

@section('title', $questionnaire->name . ' - ' . $campaign->name)

@section('styles')
<style>
    .intro-content {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #495057;
    }
    .intro-content h1, .intro-content h2, .intro-content h3, 
    .intro-content h4, .intro-content h5, .intro-content h6 {
        color: #2c3e50;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }
    .intro-content ul, .intro-content ol {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }
    .intro-content li {
        margin-bottom: 0.5rem;
    }
    .intro-content p {
        margin-bottom: 1rem;
    }
    .intro-content strong {
        color: #2c3e50;
        font-weight: 600;
    }
</style>
@endsection

@section('content')
<div x-data="questionnaireApp()" x-init="init()">
    <!-- Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('public.campaign.access', $campaign->code) }}" 
                           class="btn btn-outline-secondary me-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h4 class="mb-1">{{ $questionnaire->name }}</h4>
                            <p class="text-muted mb-0">{{ $campaign->name }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="small text-muted mb-1">Progreso</div>
                    <div class="progress mb-2">
                        <div class="progress-bar" 
                             :style="'width: ' + progressPercent + '%'"
                             x-text="progressPercent + '%'">
                        </div>
                    </div>
                    <div class="small text-muted">
                        <span x-show="showIntro">Introducción</span>
                        <span x-show="!showIntro">Sección <span x-text="currentSectionIndex + 1"></span> de <span x-text="sections.length"></span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Card (Introduction or Questions) -->
    <div class="question-card">
        <!-- Introduction Slide -->
        @if($questionnaire->intro)
        <div x-show="showIntro">
            <div class="mb-4">
                <h5 class="question-title">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    Introducción
                </h5>
            </div>
            <div class="intro-content">
                {!! $questionnaire->intro !!}
            </div>
        </div>
        @endif

        <!-- Questions Content -->
        <div x-show="!showIntro">
        <!-- Section Title (hidden) -->
        <!-- <div class="mb-4" x-show="currentSection && currentSection.title">
            <h5 class="question-title" x-text="currentSection?.title"></h5>
            <p class="text-muted" x-show="currentSection?.description" x-text="currentSection?.description"></p>
        </div> -->

        <!-- Instructions -->
        <div class="alert alert-info mb-4" x-show="currentSection?.instructions && currentSection.instructions.length > 0">
            <h6><i class="fas fa-info-circle"></i> Instrucciones:</h6>
            <template x-for="instruction in currentSection?.instructions || []">
                <p class="mb-1" x-text="instruction"></p>
            </template>
        </div>

        <!-- Current Question -->
        <div x-show="currentQuestion" class="question-wrapper">
            <div class="mb-4">
                <!-- Question Number Badge -->
                <div class="mb-4">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                        <i class="fas fa-question-circle me-2"></i>
                        Pregunta <span x-text="currentQuestionIndex + 1"></span> de <span x-text="currentSection?.questions?.length || 0"></span>
                    </span>
                </div>
                
                <!-- Question Title -->
                <template x-if="currentQuestion?.title">
                    <h2 class="question-main-title" x-text="currentQuestion?.title"></h2>
                </template>
                
                <!-- Question Text with line breaks -->
                <div class="question-text" x-html="currentQuestion?.question ? currentQuestion.question.replace(/\\n/g, '<br>') : (currentQuestion?.text?.replace(/\\n/g, '<br>') || '')"></div>
                
                <!-- Question Type Specific Components -->
                <div x-show="currentSection?.response_type === 'audio_response'">
                    @include('public.components.questions.audio')
                </div>
                
                <div x-show="currentSection?.response_type === 'text_response' || currentSection?.response_type === 'text_input'">
                    @include('public.components.questions.text')
                </div>
                
                <div x-show="currentSection?.response_type === 'single_choice' || currentSection?.response_type === 'radio'">
                    @include('public.components.questions.single-choice')
                </div>
                
                <div x-show="currentSection?.response_type === 'multiple_choice' || currentSection?.response_type === 'checkbox'">
                    @include('public.components.questions.multiple-choice')
                </div>
                
                <div x-show="currentSection?.response_type === 'rating' || currentSection?.response_type === 'scale'">
                    @include('public.components.questions.scale')
                </div>
            </div>
        </div>
        </div> <!-- End Questions Content -->
    </div> <!-- End question-card -->

    <!-- Navigation -->
    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <!-- Previous Button -->
                <button type="button" 
                        class="btn btn-outline-secondary"
                        @click="previousQuestion()"
                        :disabled="showIntro || (currentQuestionIndex === 0 && currentSectionIndex === 0)"
                        x-show="!showIntro">
                    <i class="fas fa-chevron-left me-2"></i>
                    <span class="d-none d-sm-inline">Anterior</span>
                </button>
                
                <!-- Empty space when showing intro -->
                <div x-show="showIntro"></div>
                
                <!-- Progress Indicator -->
                <div class="progress-indicator" x-show="!showIntro">
                    <i class="fas fa-tasks me-2"></i>
                    <span x-text="currentQuestionIndex + 1"></span> / <span x-text="currentSection?.questions?.length || 0"></span>
                </div>
                
                <!-- Navigation Buttons -->
                <div>
                    <!-- Start Button (for intro) -->
                    <button type="button" 
                            class="btn btn-primary"
                            @click="startQuestionnaire()"
                            x-show="showIntro">
                        <i class="fas fa-play me-2"></i>
                        Iniciar
                    </button>
                    
                    <!-- Next Button (for questions) -->
                    <button type="button" 
                            class="btn btn-primary"
                            @click="nextQuestion()"
                            x-show="!showIntro && (!isLastQuestion || !isLastSection)"
                            :disabled="!hasCurrentResponse">
                        <span class="d-none d-sm-inline">Siguiente</span>
                        <i class="fas fa-chevron-right ms-2"></i>
                    </button>
                    
                    <!-- Submit Button (for last question) -->
                    <button type="button" 
                            class="btn btn-success"
                            @click="submitQuestionnaire()"
                            x-show="!showIntro && isLastQuestion && isLastSection"
                            :disabled="!hasCurrentResponse || submitting">
                        <span x-show="!submitting">
                            <i class="fas fa-check-circle me-2"></i>
                            <span class="d-none d-sm-inline">Finalizar</span>
                        </span>
                        <span x-show="submitting">
                            <i class="fas fa-spinner fa-spin me-2"></i>
                            <span class="d-none d-sm-inline">Enviando...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div class="text-center py-5" x-show="!currentSection && !showIntro">
        <i class="fas fa-spinner fa-spin fa-2x text-primary mb-3"></i>
        <p>Cargando cuestionario...</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
function questionnaireApp() {
    return {
        // Data
        questionnaire: @json($questionnaire),
        structure: @json($structure),
        campaignCode: '{{ $campaign->code }}',
        hasIntro: {{ $questionnaire->intro ? 'true' : 'false' }},
        
        // State
        sections: [],
        currentSectionIndex: 0,
        currentQuestionIndex: 0,
        responses: {},
        submitting: false,
        startTime: Date.now(),
        showIntro: {{ $questionnaire->intro ? 'true' : 'false' }},
        
        // Computed properties
        get currentSection() {
            return this.sections[this.currentSectionIndex] || null;
        },
        
        get currentQuestion() {
            if (!this.currentSection?.questions) return null;
            return this.currentSection.questions[this.currentQuestionIndex] || null;
        },
        
        get progressPercent() {
            if (!this.sections.length) return 0;
            const totalQuestions = this.sections.reduce((sum, section) => sum + (section.questions?.length || 0), 0);
            const answeredQuestions = Object.keys(this.responses).length;
            return Math.round((answeredQuestions / totalQuestions) * 100);
        },
        
        get isLastQuestion() {
            if (!this.currentSection?.questions) return true;
            return this.currentQuestionIndex === this.currentSection.questions.length - 1;
        },
        
        get isLastSection() {
            return this.currentSectionIndex === this.sections.length - 1;
        },
        
        get hasCurrentResponse() {
            if (!this.currentQuestion) {
                console.log('hasCurrentResponse: No current question');
                return false;
            }
            
            const key = `${this.currentSection.id}_${this.currentQuestion.id}`;
            const response = this.responses[key];
            
            console.log('hasCurrentResponse check:');
            console.log('- Key:', key);
            console.log('- Response:', response);
            console.log('- Section response type:', this.currentSection?.response_type);
            
            // For audio responses, check if we have a valid audio response object
            if (this.currentSection?.response_type === 'audio_response') {
                const hasValidAudio = response && response.type === 'audio' && response.duration > 0;
                console.log('- Audio response valid:', hasValidAudio);
                console.log('- Response object:', response);
                console.log('- Duration:', response?.duration);
                
                // Also check if we have the audio file
                const responseKey = this.currentResponseKey;
                const hasAudioFile = window.audioRecorder?.files?.[responseKey];
                console.log('- Has audio file:', !!hasAudioFile);
                
                return hasValidAudio && hasAudioFile;
            }
            
            // For other response types
            const hasValidResponse = response !== undefined && response !== null && response !== '';
            console.log('- Other response valid:', hasValidResponse);
            return hasValidResponse;
        },
        
        get currentResponseKey() {
            if (!this.currentQuestion || !this.currentSection) return null;
            return `${this.currentSection.id}_${this.currentQuestion.id}`;
        },
        
        // Methods
        init() {
            this.sections = this.structure.sections || [];
            console.log('Questionnaire initialized:', this.sections);
            
            // Make questionnaire app available globally for components
            window.questionnaireApp = this;
            
            // Initialize global audio recorder
            if (!window.audioRecorder) {
                window.audioRecorder = { files: {} };
                console.log('Global audio recorder initialized');
            }
            
            // Wait for Alpine to be fully initialized and then notify components
            this.$nextTick(() => {
                console.log('Questionnaire app ready, dispatching event');
                document.dispatchEvent(new CustomEvent('questionnaire-ready', {
                    detail: { app: this }
                }));
            });
        },
        
        startQuestionnaire() {
            this.showIntro = false;
            this.startTime = Date.now(); // Reset timer when actually starting
            console.log('Questionnaire started after intro');
        },
        
        setResponse(value) {
            if (!this.currentResponseKey) return;
            this.responses[this.currentResponseKey] = value;
            console.log('Response set:', this.currentResponseKey, value);
            
            // For audio responses, also ensure the blob is saved properly
            if (value && value.type === 'audio' && window.audioRecorder?.files) {
                if (!window.audioRecorder.files[this.currentResponseKey]) {
                    console.warn('Audio response saved but no audio file found');
                }
            }
        },
        
        getCurrentResponse() {
            if (!this.currentResponseKey) return null;
            return this.responses[this.currentResponseKey];
        },
        
        nextQuestion() {
            console.log('=== NEXT QUESTION CLICKED ===');
            console.log('Has current response:', this.hasCurrentResponse);
            console.log('Current response key:', this.currentResponseKey);
            console.log('Current response:', this.responses[this.currentResponseKey]);
            console.log('Current section response type:', this.currentSection?.response_type);
            
            if (!this.hasCurrentResponse) {
                console.warn('No current response, cannot proceed');
                return;
            }
            
            console.log('Current question index:', this.currentQuestionIndex);
            console.log('Current section questions length:', this.currentSection?.questions?.length);
            console.log('Current section index:', this.currentSectionIndex);
            console.log('Total sections:', this.sections.length);
            
            const oldResponseKey = this.currentResponseKey;
            
            if (this.currentQuestionIndex < (this.currentSection?.questions?.length || 0) - 1) {
                this.currentQuestionIndex++;
                console.log('Moving to next question in same section:', this.currentQuestionIndex);
            } else if (this.currentSectionIndex < this.sections.length - 1) {
                this.currentSectionIndex++;
                this.currentQuestionIndex = 0;
                console.log('Moving to next section:', this.currentSectionIndex);
            } else {
                console.log('At last question of last section');
            }
            
            // Notify audio components about question change
            this.notifyQuestionChange(oldResponseKey, this.currentResponseKey);
        },
        
        previousQuestion() {
            const oldResponseKey = this.currentResponseKey;
            
            // If we're at the first question of the first section and there's an intro, go back to intro
            if (this.currentQuestionIndex === 0 && this.currentSectionIndex === 0 && this.hasIntro) {
                this.showIntro = true;
                return;
            }
            
            if (this.currentQuestionIndex > 0) {
                this.currentQuestionIndex--;
            } else if (this.currentSectionIndex > 0) {
                this.currentSectionIndex--;
                const prevSection = this.sections[this.currentSectionIndex];
                this.currentQuestionIndex = (prevSection?.questions?.length || 1) - 1;
            }
            
            // Notify audio components about question change
            this.notifyQuestionChange(oldResponseKey, this.currentResponseKey);
        },
        
        // Notify audio components when question changes
        notifyQuestionChange(oldKey, newKey) {
            console.log('=== QUESTION CHANGED ===');
            console.log('From:', oldKey, 'To:', newKey);
            
            // Dispatch custom event for audio components
            document.dispatchEvent(new CustomEvent('question-changed', {
                detail: {
                    oldResponseKey: oldKey,
                    newResponseKey: newKey,
                    currentSection: this.currentSection,
                    currentQuestion: this.currentQuestion
                }
            }));
        },
        
        async submitQuestionnaire() {
            if (this.submitting) return;
            
            this.submitting = true;
            
            try {
                const formData = new FormData();
                formData.append('responses', JSON.stringify(this.responses));
                formData.append('duration_minutes', Math.round((Date.now() - this.startTime) / 60000));
                formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
                
                // Add audio files if any
                this.addAudioFilesToFormData(formData);
                
                const response = await fetch(`/c/${this.campaignCode}/q/${this.questionnaire.id}/submit`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Redirect to campaign main page
                    window.location.href = `/c/${this.campaignCode}`;
                } else {
                    alert('Error al enviar las respuestas: ' + (result.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Submit error:', error);
                alert('Error al enviar las respuestas. Por favor, intenta de nuevo.');
            } finally {
                this.submitting = false;
            }
        },
        
        addAudioFilesToFormData(formData) {
            // Get audio files from the global audio recorder
            if (window.audioRecorder && window.audioRecorder.files) {
                console.log('Adding audio files to form data:', window.audioRecorder.files);
                Object.entries(window.audioRecorder.files).forEach(([key, file]) => {
                    if (file instanceof Blob) {
                        formData.append(`audio_files[${key}]`, file);
                        console.log('Audio file added:', key, file.size, 'bytes');
                    }
                });
            } else {
                console.log('No audio files found for submission');
            }
        }
    }
}

// Initialize start time
document.addEventListener('DOMContentLoaded', function() {
    window.questionnaireStartTime = Date.now();
});
</script>
@endsection