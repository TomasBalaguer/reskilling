<div x-data="audioRecorder()" x-init="initAudio()">
    <!-- Audio Recording Interface -->
    <div class="audio-recorder" :class="{ 'recording': isRecording }">
        <div class="text-center">
            <!-- Microphone Icon -->
            <div class="mb-3">
                <i class="fas fa-microphone fa-4x" 
                   :class="isRecording ? 'text-danger pulse-recording' : 'text-primary'"></i>
            </div>
            
            <!-- Instructions -->
            <div class="mb-3" x-show="!hasRecording && !isRecording">
                <h6 class="text-muted">Instrucciones para grabar</h6>
                <p class="small text-muted mb-0">
                    <i class="fas fa-info-circle"></i>
                    Haz clic en el botón de grabar y responde la pregunta con claridad.
                    <br>Puedes grabar varias veces hasta que estés satisfecho con tu respuesta.
                </p>
            </div>
            
            <!-- Timer -->
            <div class="timer" 
                 :class="{ 'recording': isRecording }" 
                 x-show="isRecording || hasRecording">
                <i class="fas fa-clock"></i>
                <span x-text="formatTime(currentTime)"></span>
                <span x-show="maxDuration > 0" x-text="' / ' + formatTime(maxDuration)"></span>
            </div>
            
            <!-- Recording Status -->
            <div class="mb-3" x-show="isRecording">
                <div class="text-danger">
                    <i class="fas fa-circle text-danger me-2" style="animation: blink 1s infinite;"></i>
                    <strong>Grabando...</strong>
                </div>
                <p class="small text-muted mt-2">
                    Habla con claridad y evita ruidos de fondo
                </p>
            </div>
            
            <!-- Playback Info -->
            <div class="mb-3" x-show="hasRecording && !isRecording">
                <div class="text-success">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <strong>Audio grabado exitosamente</strong>
                </div>
                <p class="small text-muted mt-2">
                    Puedes reproducir tu grabación o grabar nuevamente si lo deseas
                </p>
            </div>
        </div>
        
        <!-- Audio Controls -->
        <div class="audio-controls">
            <!-- Record Button -->
            <button type="button" 
                    class="btn btn-lg"
                    :class="isRecording ? 'btn-danger' : 'btn-primary'"
                    @click="toggleRecording()"
                    :disabled="isPlaying">
                <i class="fas" 
                   :class="isRecording ? 'fa-stop' : 'fa-microphone'"></i>
                <span x-text="isRecording ? 'Detener' : 'Grabar'"></span>
            </button>
            
            <!-- Play Button -->
            <button type="button" 
                    class="btn btn-outline-success btn-lg"
                    @click="togglePlayback()"
                    x-show="hasRecording && !isRecording"
                    :disabled="isRecording">
                <i class="fas" 
                   :class="isPlaying ? 'fa-pause' : 'fa-play'"></i>
                <span x-text="isPlaying ? 'Pausar' : 'Reproducir'"></span>
            </button>
            
            <!-- Reset Button -->
            <button type="button" 
                    class="btn btn-outline-warning btn-lg"
                    @click="resetRecording()"
                    x-show="hasRecording && !isRecording"
                    :disabled="isRecording || isPlaying">
                <i class="fas fa-redo"></i>
                <span>Grabar de nuevo</span>
            </button>
        </div>
        
        <!-- Error Messages -->
        <div class="alert alert-danger mt-3" x-show="error">
            <i class="fas fa-exclamation-triangle"></i>
            <span x-text="error"></span>
        </div>
        
        <!-- Browser Compatibility Warning -->
        <div class="alert alert-warning mt-3" x-show="!isSupported">
            <i class="fas fa-exclamation-triangle"></i>
            Tu navegador no soporta grabación de audio. Por favor, utiliza Chrome, Firefox o Safari.
        </div>
        
        <!-- Microphone Test Button -->
        <div class="mt-3" x-show="isSupported && !hasRecording && !isRecording">
            <button type="button" 
                    class="btn btn-outline-info btn-sm"
                    @click="testMicrophone()"
                    :disabled="isRecording">
                <i class="fas fa-microphone-alt"></i>
                Probar Micrófono
            </button>
            <small class="text-muted d-block mt-1">
                Haz clic para verificar que tu micrófono funciona correctamente
            </small>
        </div>
    </div>
    
    <!-- Hidden audio element for playback -->
    <audio x-ref="audioPlayer" 
           @ended="handlePlaybackEnd()" 
           @timeupdate="updatePlaybackTime()"
           style="display: none;"></audio>
</div>

<style>
@keyframes blink {
    0%, 50% { opacity: 1; }
    51%, 100% { opacity: 0.3; }
}

.audio-recorder {
    transition: all 0.3s ease;
}

.audio-recorder.recording {
    box-shadow: 0 0 20px rgba(239, 68, 68, 0.3);
}

.pulse-recording {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
</style>

<script>
function audioRecorder() {
    return {
        // Audio recording state
        mediaRecorder: null,
        audioChunks: [],
        audioBlob: null,
        audioUrl: null,
        stream: null,
        
        // UI state
        isRecording: false,
        isPlaying: false,
        hasRecording: false,
        isSupported: false,
        error: null,
        
        // Timing
        currentTime: 0,
        maxDuration: 180, // 3 minutes in seconds (3 * 60 = 180)
        recordingStartTime: null,
        playbackStartTime: null,
        timer: null,
        
        // Setup flags
        questionChangeListenerSetup: false,
        
        // Initialize audio recording
        async initAudio() {
            console.log('=== AUDIO COMPONENT INIT ===');
            console.log('Navigator:', !!navigator);
            console.log('MediaDevices:', !!navigator.mediaDevices);
            console.log('getUserMedia:', !!navigator.mediaDevices?.getUserMedia);
            console.log('MediaRecorder:', !!window.MediaRecorder);
            console.log('Location:', location.protocol, location.hostname);
            
            // Check browser support
            this.isSupported = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
            console.log('Is supported:', this.isSupported);
            
            if (!this.isSupported) {
                this.error = 'Tu navegador no soporta grabación de audio. Por favor, usa Chrome, Firefox o Safari.';
                console.error('MediaDevices not supported');
                return;
            }
            
            // Check if we're on HTTPS or localhost (required for microphone access)
            if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                this.error = 'La grabación de audio requiere una conexión segura (HTTPS).';
                console.error('Audio recording requires HTTPS');
                return;
            }
            
            // Wait for questionnaire to be ready
            this.waitForQuestionnaire();
            
            console.log('=== AUDIO COMPONENT INIT COMPLETE ===');
        },
        
        // Wait for questionnaire app to be ready
        waitForQuestionnaire() {
            if (window.questionnaireApp) {
                console.log('Questionnaire already available');
                this.setupWithQuestionnaire();
                this.setupQuestionChangeListener();
                return;
            }
            
            console.log('Waiting for questionnaire to be ready...');
            document.addEventListener('questionnaire-ready', (event) => {
                console.log('Questionnaire ready event received');
                this.setupWithQuestionnaire();
                this.setupQuestionChangeListener();
            }, { once: true });
            
            // Fallback timeout
            setTimeout(() => {
                if (!window.questionnaireApp) {
                    console.warn('Questionnaire app not available after timeout, using fallback');
                }
                this.setupWithQuestionnaire();
                this.setupQuestionChangeListener();
            }, 2000);
        },
        
        // Setup listener for question changes
        setupQuestionChangeListener() {
            if (this.questionChangeListenerSetup) {
                console.log('Question change listener already setup');
                return;
            }
            
            console.log('Setting up question change listener');
            this.questionChangeListenerSetup = true;
            
            document.addEventListener('question-changed', (event) => {
                console.log('Question change event received:', event.detail);
                console.log('Current section response type:', event.detail.currentSection?.response_type);
                
                // Only refresh if this is an audio question
                if (event.detail.currentSection?.response_type === 'audio_response') {
                    console.log('Refreshing audio component for new audio question');
                    // Use a small delay to ensure Alpine.js has updated
                    setTimeout(() => {
                        this.refreshForNewQuestion();
                    }, 100);
                } else {
                    console.log('New question is not audio type, skipping refresh');
                }
            });
        },
        
        // Setup component with questionnaire context
        setupWithQuestionnaire() {
            console.log('Setting up audio component with questionnaire context');
            console.log('Initial maxDuration:', this.maxDuration, 'seconds');
            
            // Get max duration from question if available
            try {
                const questionnaireApp = window.questionnaireApp;
                console.log('Current question:', questionnaireApp?.currentQuestion);
                console.log('Current section:', questionnaireApp?.currentSection);
                
                if (questionnaireApp?.currentQuestion?.max_duration) {
                    // Assume max_duration is already in seconds
                    this.maxDuration = questionnaireApp.currentQuestion.max_duration;
                    console.log('Max duration set from question:', this.maxDuration, 'seconds');
                } else if (questionnaireApp?.currentSection?.max_duration) {
                    // Also check section level
                    this.maxDuration = questionnaireApp.currentSection.max_duration;
                    console.log('Max duration set from section:', this.maxDuration, 'seconds');
                } else {
                    console.log('No max duration found in question or section, using default:', this.maxDuration, 'seconds');
                }
                
                console.log('Final maxDuration:', this.maxDuration, 'seconds');
                console.log('Should display as:', this.formatTime(this.maxDuration));
                
            } catch (e) {
                console.warn('Could not get max duration from question:', e);
            }
            
            // Check for existing recording
            this.checkExistingRecording();
        },
        
        // Test microphone access
        async testMicrophone() {
            try {
                this.error = null;
                console.log('Testing microphone access...');
                
                // Try to get microphone access
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                
                // If successful, show success message and stop the stream
                const tracks = stream.getTracks();
                tracks.forEach(track => track.stop());
                
                // Show temporary success message
                const originalError = this.error;
                this.error = null;
                
                // Create temporary success indicator
                const testButton = this.$el.querySelector('.btn-outline-info');
                if (testButton) {
                    testButton.classList.remove('btn-outline-info');
                    testButton.classList.add('btn-success');
                    testButton.innerHTML = '<i class="fas fa-check"></i> Micrófono OK';
                    
                    setTimeout(() => {
                        testButton.classList.remove('btn-success');
                        testButton.classList.add('btn-outline-info');
                        testButton.innerHTML = '<i class="fas fa-microphone-alt"></i> Probar Micrófono';
                    }, 2000);
                }
                
                console.log('Microphone test successful');
                
            } catch (error) {
                console.error('Microphone test failed:', error);
                
                // Show specific error message
                if (error.name === 'NotAllowedError') {
                    this.error = 'Permisos de micrófono denegados. Haz clic en el icono de micrófono en la barra de direcciones para permitir el acceso.';
                } else if (error.name === 'NotFoundError') {
                    this.error = 'No se encontró ningún micrófono conectado.';
                } else {
                    this.error = 'Error al probar el micrófono: ' + (error.message || error.name);
                }
            }
        },
        
        // Check if there's already a recording for this question
        checkExistingRecording() {
            try {
                const questionnaireApp = window.questionnaireApp;
                if (questionnaireApp && questionnaireApp.getCurrentResponse) {
                    const currentResponse = questionnaireApp.getCurrentResponse();
                    const responseKey = questionnaireApp.currentResponseKey;
                    
                    console.log('=== CHECKING EXISTING RECORDING ===');
                    console.log('Response key:', responseKey);
                    console.log('Current response:', currentResponse);
                    console.log('Available audio files:', Object.keys(window.audioRecorder?.files || {}));
                    
                    if (currentResponse && currentResponse.type === 'audio') {
                        this.hasRecording = true;
                        this.currentTime = currentResponse.duration || 0;
                        
                        // Try to recover the actual audio file if it exists
                        if (responseKey && window.audioRecorder?.files?.[responseKey]) {
                            this.audioBlob = window.audioRecorder.files[responseKey];
                            this.audioUrl = URL.createObjectURL(this.audioBlob);
                            console.log('Audio file recovered for playback');
                        } else {
                            console.log('Audio metadata found but file not available for playback');
                        }
                        
                        console.log('Existing audio recording restored:', {
                            duration: this.currentTime,
                            hasFile: !!this.audioBlob,
                            canPlay: !!this.audioUrl
                        });
                    } else {
                        console.log('No existing audio recording found');
                    }
                }
            } catch (error) {
                console.warn('Could not check existing recording:', error);
            }
        },
        
        // Toggle recording on/off
        async toggleRecording() {
            if (this.isRecording) {
                this.stopRecording();
            } else {
                await this.startRecording();
            }
        },
        
        // Start recording
        async startRecording() {
            try {
                this.error = null;
                console.log('Starting audio recording...');
                
                // Request microphone access with detailed constraints
                const constraints = {
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                        sampleRate: 44100
                    }
                };
                
                console.log('Requesting microphone access...');
                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                console.log('Microphone access granted');
                
                // Check if MediaRecorder is supported
                if (!window.MediaRecorder) {
                    throw new Error('MediaRecorder no está soportado en este navegador');
                }
                
                // Get supported MIME type
                const mimeType = this.getSupportedMimeType();
                console.log('Using MIME type:', mimeType);
                
                // Setup MediaRecorder
                this.mediaRecorder = new MediaRecorder(this.stream, {
                    mimeType: mimeType
                });
                
                this.audioChunks = [];
                
                this.mediaRecorder.ondataavailable = (event) => {
                    console.log('Data available:', event.data.size, 'bytes');
                    if (event.data.size > 0) {
                        this.audioChunks.push(event.data);
                    }
                };
                
                this.mediaRecorder.onstop = () => {
                    console.log('Recording stopped, processing...');
                    this.processRecording();
                };
                
                this.mediaRecorder.onerror = (event) => {
                    console.error('MediaRecorder error:', event.error);
                    this.error = 'Error durante la grabación: ' + event.error.name;
                    this.stopRecording();
                };
                
                this.mediaRecorder.onstart = () => {
                    console.log('Recording started successfully');
                };
                
                // Start recording
                this.mediaRecorder.start(100); // Collect data every 100ms
                this.isRecording = true;
                this.recordingStartTime = Date.now();
                this.currentTime = 0;
                
                // Start timer
                this.startTimer();
                
            } catch (error) {
                console.error('Recording error:', error);
                
                // Provide specific error messages
                if (error.name === 'NotAllowedError') {
                    this.error = 'Acceso al micrófono denegado. Por favor, permite el acceso al micrófono y recarga la página.';
                } else if (error.name === 'NotFoundError') {
                    this.error = 'No se encontró ningún micrófono. Verifica que tengas un micrófono conectado.';
                } else if (error.name === 'NotReadableError') {
                    this.error = 'El micrófono está siendo usado por otra aplicación.';
                } else if (error.name === 'OverconstrainedError') {
                    this.error = 'El micrófono no cumple con los requisitos necesarios.';
                } else {
                    this.error = 'Error al acceder al micrófono: ' + (error.message || error.name || 'Error desconocido');
                }
            }
        },
        
        // Stop recording
        stopRecording() {
            if (this.mediaRecorder && this.isRecording) {
                this.mediaRecorder.stop();
                this.isRecording = false;
                this.stopTimer();
                
                // Stop all tracks
                this.stream?.getTracks().forEach(track => track.stop());
            }
        },
        
        // Process the completed recording
        processRecording() {
            if (this.audioChunks.length === 0) return;
            
            // Create blob
            this.audioBlob = new Blob(this.audioChunks, { 
                type: this.getSupportedMimeType() 
            });
            
            // Create URL for playback
            this.audioUrl = URL.createObjectURL(this.audioBlob);
            this.hasRecording = true;
            
            // Save response to questionnaire
            this.saveAudioResponse();
        },
        
        // Save audio response to questionnaire data
        saveAudioResponse() {
            console.log('=== SAVING AUDIO RESPONSE ===');
            
            const responseData = {
                type: 'audio',
                duration: this.currentTime,
                mimeType: this.getSupportedMimeType(),
                size: this.audioBlob?.size || 0,
                timestamp: Date.now()
            };
            
            console.log('Response data to save:', responseData);
            
            // Ensure window.audioRecorder exists and has files property
            if (!window.audioRecorder) {
                window.audioRecorder = { files: {} };
                console.log('Created window.audioRecorder');
            }
            
            if (!window.audioRecorder.files) {
                window.audioRecorder.files = {};
                console.log('Created window.audioRecorder.files');
            }
            
            // Try to save using global questionnaire app
            if (window.questionnaireApp && this.audioBlob) {
                try {
                    const responseKey = window.questionnaireApp.currentResponseKey;
                    console.log('Response key:', responseKey);
                    console.log('Current audioRecorder state:', window.audioRecorder);
                    
                    if (responseKey) {
                        // Save audio blob - ensure we have the files object
                        window.audioRecorder.files[responseKey] = this.audioBlob;
                        console.log('Audio blob saved to global storage');
                        
                        // Save response data to questionnaire
                        if (typeof window.questionnaireApp.setResponse === 'function') {
                            window.questionnaireApp.setResponse(responseData);
                            console.log('Audio response saved to questionnaire:', responseKey);
                        } else {
                            console.error('setResponse is not a function');
                        }
                    } else {
                        console.error('No response key available');
                    }
                } catch (error) {
                    console.error('Error saving audio response:', error);
                    console.log('Error details:', error.message, error.stack);
                }
            } else {
                console.error('Missing requirements - questionnaireApp:', !!window.questionnaireApp, 'audioBlob:', !!this.audioBlob);
            }
            
            console.log('=== AUDIO RESPONSE SAVE COMPLETE ===');
        },
        
        // Toggle audio playback
        togglePlayback() {
            if (this.isPlaying) {
                this.pausePlayback();
            } else {
                this.startPlayback();
            }
        },
        
        // Start audio playback
        startPlayback() {
            if (!this.audioUrl) return;
            
            const audio = this.$refs.audioPlayer;
            audio.src = this.audioUrl;
            audio.currentTime = 0;
            
            audio.play().then(() => {
                this.isPlaying = true;
                this.playbackStartTime = Date.now();
                this.startTimer();
            }).catch(error => {
                console.error('Playback error:', error);
                this.error = 'Error al reproducir el audio';
            });
        },
        
        // Pause audio playback
        pausePlayback() {
            const audio = this.$refs.audioPlayer;
            audio.pause();
            this.isPlaying = false;
            this.stopTimer();
        },
        
        // Handle playback end
        handlePlaybackEnd() {
            this.isPlaying = false;
            this.stopTimer();
            this.currentTime = this.getCurrentRecordingDuration();
        },
        
        // Update time during playback
        updatePlaybackTime() {
            if (this.isPlaying) {
                const audio = this.$refs.audioPlayer;
                this.currentTime = audio.currentTime;
            }
        },
        
        // Reset recording
        resetRecording() {
            console.log('=== RESET RECORDING ===');
            
            this.stopRecording();
            this.pausePlayback();
            
            // Clean up current URLs
            if (this.audioUrl) {
                URL.revokeObjectURL(this.audioUrl);
            }
            
            this.audioBlob = null;
            this.audioUrl = null;
            this.audioChunks = [];
            this.hasRecording = false;
            this.currentTime = 0;
            this.error = null;
            
            // Clear response and audio file
            try {
                const questionnaireApp = window.questionnaireApp;
                if (questionnaireApp && questionnaireApp.currentResponseKey) {
                    const responseKey = questionnaireApp.currentResponseKey;
                    
                    // Clear from global audio files
                    if (window.audioRecorder?.files && window.audioRecorder.files[responseKey]) {
                        delete window.audioRecorder.files[responseKey];
                        console.log('Audio file cleared from global storage:', responseKey);
                    }
                    
                    // Clear from questionnaire responses
                    if (typeof questionnaireApp.setResponse === 'function') {
                        questionnaireApp.setResponse(null);
                        console.log('Response cleared from questionnaire');
                    }
                }
            } catch (error) {
                console.error('Error clearing response:', error);
            }
            
            console.log('Recording reset complete');
        },
        
        // Refresh the component state when switching questions
        refreshForNewQuestion() {
            console.log('=== REFRESHING FOR NEW QUESTION ===');
            const currentResponseKey = window.questionnaireApp?.currentResponseKey;
            console.log('New question response key:', currentResponseKey);
            
            // Stop any current activity
            this.stopRecording();
            this.pausePlayback();
            
            // Clean up current URLs but don't delete files from global storage
            if (this.audioUrl) {
                URL.revokeObjectURL(this.audioUrl);
            }
            
            // Reset component state completely
            this.audioBlob = null;
            this.audioUrl = null;
            this.audioChunks = [];
            this.hasRecording = false;
            this.currentTime = 0;
            this.error = null;
            this.isRecording = false;
            this.isPlaying = false;
            
            console.log('Component state reset, checking for existing recording...');
            
            // Re-setup with questionnaire context (gets new maxDuration, etc.)
            this.setupWithQuestionnaire();
            
            // Force Alpine.js reactivity update
            this.$nextTick(() => {
                console.log('Component refreshed for new question, final state:', {
                    hasRecording: this.hasRecording,
                    currentTime: this.currentTime,
                    audioBlob: !!this.audioBlob,
                    audioUrl: !!this.audioUrl
                });
            });
        },
        
        // Timer functions
        startTimer() {
            this.stopTimer(); // Ensure no duplicate timers
            this.timer = setInterval(() => {
                if (this.isRecording) {
                    this.currentTime = (Date.now() - this.recordingStartTime) / 1000;
                    
                    // Auto-stop at max duration
                    if (this.maxDuration > 0 && this.currentTime >= this.maxDuration) {
                        this.stopRecording();
                    }
                }
            }, 100);
        },
        
        stopTimer() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        
        // Get current recording duration
        getCurrentRecordingDuration() {
            if (this.recordingStartTime && this.isRecording) {
                return (Date.now() - this.recordingStartTime) / 1000;
            }
            return this.currentTime;
        },
        
        // Format time display
        formatTime(seconds) {
            console.log('Formatting time for seconds:', seconds);
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            const formatted = `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            console.log('Formatted time:', formatted);
            return formatted;
        },
        
        // Get supported MIME type
        getSupportedMimeType() {
            // Prioritize formats supported by Gemini API
            // Note: Most browsers only support webm/ogg for MediaRecorder
            const types = [
                'audio/mp4;codecs=mp4a.40.2',
                'audio/mp4',
                'audio/mpeg',
                'audio/wav',
                'audio/ogg;codecs=opus',
                'audio/webm;codecs=opus',
                'audio/webm;codecs=vp8,opus',
                'audio/webm'
            ];
            
            // Check MediaRecorder support first
            if (!window.MediaRecorder) {
                console.warn('MediaRecorder not supported');
                return 'audio/webm';
            }
            
            for (const type of types) {
                if (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(type)) {
                    console.log('Supported MIME type found:', type);
                    
                    // Warning if we're using WebM (not supported by Gemini)
                    if (type.includes('webm')) {
                        console.warn('WARNING: Using WebM format which is not supported by Gemini API. Backend conversion will be needed.');
                    }
                    
                    return type;
                }
            }
            
            // Last resort fallback
            console.warn('No supported MIME type found, using WebM fallback (not supported by Gemini)');
            return 'audio/webm';
        }
    }
}

// Global audio recorder initialization - ensure it exists
if (!window.audioRecorder) {
    window.audioRecorder = {
        files: {},
        getAudioFiles() {
            return this.files;
        }
    };
    console.log('Global audio recorder initialized');
}
</script>