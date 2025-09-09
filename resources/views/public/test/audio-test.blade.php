<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Recording Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="fas fa-microphone"></i>
                            Test de Grabación de Audio
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="test-results">
                            <h5>Resultados de las Pruebas:</h5>
                            <div id="support-check" class="mb-3">
                                <span class="badge bg-secondary">Comprobando soporte...</span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Audio Component Test -->
                        <div x-data="audioRecorderTest()" x-init="initAudio()">
                            @include('public.components.questions.audio')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Check browser support on page load
        document.addEventListener('DOMContentLoaded', function() {
            const resultDiv = document.getElementById('support-check');
            const results = [];
            
            // Check MediaDevices support
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                results.push('<span class="badge bg-success">✓ MediaDevices API soportado</span>');
            } else {
                results.push('<span class="badge bg-danger">✗ MediaDevices API no soportado</span>');
            }
            
            // Check MediaRecorder support
            if (window.MediaRecorder) {
                results.push('<span class="badge bg-success">✓ MediaRecorder soportado</span>');
            } else {
                results.push('<span class="badge bg-danger">✗ MediaRecorder no soportado</span>');
            }
            
            // Check HTTPS/localhost
            const isSecure = location.protocol === 'https:' || 
                           location.hostname === 'localhost' || 
                           location.hostname === '127.0.0.1';
            
            if (isSecure) {
                results.push('<span class="badge bg-success">✓ Conexión segura (HTTPS/localhost)</span>');
            } else {
                results.push('<span class="badge bg-danger">✗ Requiere HTTPS para grabación de audio</span>');
            }
            
            // Check Alpine.js
            if (window.Alpine) {
                results.push('<span class="badge bg-success">✓ Alpine.js cargado</span>');
            } else {
                results.push('<span class="badge bg-warning">⚠ Alpine.js no detectado</span>');
            }
            
            resultDiv.innerHTML = results.join(' ');
        });
        
        // Test version of audio recorder with logging
        function audioRecorderTest() {
            console.log('Audio recorder test component initializing...');
            
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
                maxDuration: 60, // 1 minute for testing
                recordingStartTime: null,
                timer: null,
                
                // Mock questionnaire methods for testing
                getCurrentResponse() {
                    console.log('Mock getCurrentResponse called');
                    return null;
                },
                
                setResponse(value) {
                    console.log('Mock setResponse called with:', value);
                },
                
                get currentResponseKey() {
                    return 'test_recording';
                },
                
                // Initialize audio recording
                async initAudio() {
                    console.log('=== AUDIO INIT START ===');
                    console.log('Navigator:', !!navigator);
                    console.log('MediaDevices:', !!navigator.mediaDevices);
                    console.log('getUserMedia:', !!navigator.mediaDevices?.getUserMedia);
                    console.log('MediaRecorder:', !!window.MediaRecorder);
                    console.log('Location:', location.protocol, location.hostname);
                    
                    // Check browser support
                    this.isSupported = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
                    console.log('Is supported:', this.isSupported);
                    
                    if (!this.isSupported) {
                        this.error = 'Tu navegador no soporta grabación de audio';
                        console.error('Browser not supported');
                        return;
                    }
                    
                    // Check HTTPS
                    if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                        this.error = 'La grabación de audio requiere una conexión segura (HTTPS)';
                        console.error('HTTPS required');
                        return;
                    }
                    
                    console.log('=== AUDIO INIT COMPLETE ===');
                },
                
                // Test microphone access
                async testMicrophone() {
                    console.log('=== MICROPHONE TEST START ===');
                    
                    try {
                        this.error = null;
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        console.log('Microphone access granted:', stream);
                        
                        const tracks = stream.getTracks();
                        console.log('Audio tracks:', tracks.length);
                        tracks.forEach((track, index) => {
                            console.log(`Track ${index}:`, track.kind, track.label, track.enabled);
                        });
                        
                        // Stop tracks
                        tracks.forEach(track => track.stop());
                        
                        // Update button
                        const testButton = this.$el.querySelector('.btn-outline-info');
                        if (testButton) {
                            testButton.classList.remove('btn-outline-info');
                            testButton.classList.add('btn-success');
                            testButton.innerHTML = '<i class="fas fa-check"></i> Micrófono OK';
                            
                            setTimeout(() => {
                                testButton.classList.remove('btn-success');
                                testButton.classList.add('btn-outline-info');
                                testButton.innerHTML = '<i class="fas fa-microphone-alt"></i> Probar Micrófono';
                            }, 3000);
                        }
                        
                        console.log('=== MICROPHONE TEST SUCCESS ===');
                        
                    } catch (error) {
                        console.error('=== MICROPHONE TEST FAILED ===');
                        console.error('Error:', error);
                        console.error('Error name:', error.name);
                        console.error('Error message:', error.message);
                        
                        if (error.name === 'NotAllowedError') {
                            this.error = 'Permisos de micrófono denegados. Haz clic en el icono de micrófono en la barra de direcciones.';
                        } else {
                            this.error = 'Error al probar el micrófono: ' + error.message;
                        }
                    }
                },
                
                // Toggle recording on/off
                async toggleRecording() {
                    console.log('=== TOGGLE RECORDING ===');
                    console.log('Currently recording:', this.isRecording);
                    
                    if (this.isRecording) {
                        this.stopRecording();
                    } else {
                        await this.startRecording();
                    }
                },
                
                // Start recording
                async startRecording() {
                    console.log('=== START RECORDING ===');
                    
                    try {
                        this.error = null;
                        
                        // Get microphone stream
                        const constraints = { audio: true };
                        console.log('Requesting microphone with constraints:', constraints);
                        
                        this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                        console.log('Stream obtained:', this.stream);
                        
                        // Check MediaRecorder
                        if (!window.MediaRecorder) {
                            throw new Error('MediaRecorder not supported');
                        }
                        
                        // Get MIME type
                        const mimeType = this.getSupportedMimeType();
                        console.log('Using MIME type:', mimeType);
                        
                        // Create MediaRecorder
                        this.mediaRecorder = new MediaRecorder(this.stream, { mimeType });
                        console.log('MediaRecorder created:', this.mediaRecorder);
                        
                        this.audioChunks = [];
                        
                        // Event listeners
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
                        };
                        
                        // Start recording
                        this.mediaRecorder.start();
                        this.isRecording = true;
                        this.recordingStartTime = Date.now();
                        this.startTimer();
                        
                        console.log('Recording started successfully');
                        
                    } catch (error) {
                        console.error('Recording start failed:', error);
                        this.error = 'Error al iniciar grabación: ' + error.message;
                    }
                },
                
                // Stop recording
                stopRecording() {
                    console.log('=== STOP RECORDING ===');
                    
                    if (this.mediaRecorder && this.isRecording) {
                        this.mediaRecorder.stop();
                        this.isRecording = false;
                        this.stopTimer();
                        
                        // Stop stream
                        if (this.stream) {
                            this.stream.getTracks().forEach(track => {
                                console.log('Stopping track:', track.kind, track.label);
                                track.stop();
                            });
                        }
                        
                        console.log('Recording stopped');
                    }
                },
                
                // Process recording
                processRecording() {
                    console.log('=== PROCESS RECORDING ===');
                    console.log('Audio chunks:', this.audioChunks.length);
                    
                    if (this.audioChunks.length === 0) {
                        console.warn('No audio chunks to process');
                        return;
                    }
                    
                    // Create blob
                    const mimeType = this.getSupportedMimeType();
                    this.audioBlob = new Blob(this.audioChunks, { type: mimeType });
                    console.log('Audio blob created:', this.audioBlob.size, 'bytes, type:', this.audioBlob.type);
                    
                    // Create URL
                    this.audioUrl = URL.createObjectURL(this.audioBlob);
                    this.hasRecording = true;
                    
                    console.log('Audio URL created:', this.audioUrl);
                    console.log('=== RECORDING PROCESSED ===');
                },
                
                // Playback
                togglePlayback() {
                    console.log('=== TOGGLE PLAYBACK ===');
                    console.log('Is playing:', this.isPlaying);
                    console.log('Audio URL:', this.audioUrl);
                    
                    if (this.isPlaying) {
                        this.pausePlayback();
                    } else {
                        this.startPlayback();
                    }
                },
                
                startPlayback() {
                    if (!this.audioUrl) {
                        console.warn('No audio URL for playback');
                        return;
                    }
                    
                    const audio = this.$refs.audioPlayer;
                    audio.src = this.audioUrl;
                    
                    audio.play().then(() => {
                        console.log('Playback started');
                        this.isPlaying = true;
                    }).catch(error => {
                        console.error('Playback error:', error);
                        this.error = 'Error al reproducir: ' + error.message;
                    });
                },
                
                pausePlayback() {
                    const audio = this.$refs.audioPlayer;
                    audio.pause();
                    this.isPlaying = false;
                    console.log('Playback paused');
                },
                
                handlePlaybackEnd() {
                    console.log('Playback ended');
                    this.isPlaying = false;
                },
                
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
                    
                    if (this.audioUrl) {
                        URL.revokeObjectURL(this.audioUrl);
                    }
                    
                    this.audioBlob = null;
                    this.audioUrl = null;
                    this.audioChunks = [];
                    this.hasRecording = false;
                    this.currentTime = 0;
                    this.error = null;
                    
                    console.log('Recording reset');
                },
                
                // Timer
                startTimer() {
                    this.stopTimer();
                    this.timer = setInterval(() => {
                        if (this.isRecording) {
                            this.currentTime = (Date.now() - this.recordingStartTime) / 1000;
                            
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
                
                formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = Math.floor(seconds % 60);
                    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
                },
                
                getSupportedMimeType() {
                    const types = [
                        'audio/webm;codecs=opus',
                        'audio/webm',
                        'audio/mp4',
                        'audio/wav'
                    ];
                    
                    for (const type of types) {
                        if (MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(type)) {
                            console.log('Supported MIME type:', type);
                            return type;
                        }
                    }
                    
                    console.warn('Using fallback MIME type');
                    return 'audio/webm';
                }
            };
        }
    </script>
</body>
</html>