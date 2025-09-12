<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Evaluación Psicológica')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --dark-color: #1f2937;
            --light-gray: #f9fafb;
            --medium-gray: #6b7280;
            --border-color: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f9fafb 0%, #e5e7eb 100%);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Header */
        .public-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }

        .public-header .brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            text-decoration: none;
        }

        .public-header .brand:hover {
            color: var(--primary-dark);
        }

        /* Main Content */
        .public-main {
            min-height: calc(100vh - 140px);
            padding: 2rem 0;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            background: white;
        }

        .card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1), 0 4px 10px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
            border-radius: 1rem 1rem 0 0 !important;
        }

        .card-body {
            padding: 2rem;
        }

        /* Buttons */
        .btn {
            border-radius: 0.75rem;
            font-weight: 500;
            padding: 0.875rem 1.5rem;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        /* Forms */
        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        /* Progress Bar */
        .progress {
            height: 1rem;
            border-radius: 0.5rem;
            background: var(--light-gray);
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 0.5rem;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border-left-color: var(--success-color);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border-left-color: var(--danger-color);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: #92400e;
            border-left-color: var(--warning-color);
        }

        .alert-info {
            background: rgba(6, 182, 212, 0.1);
            color: #155e75;
            border-left-color: var(--info-color);
        }

        /* Question Cards */
        .question-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
            border-radius: 1.5rem;
            padding: 3rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .question-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .question-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        /* Question Components */
        .question-wrapper {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .question-main-title {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.75rem;
            line-height: 1.3;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .question-skills {
            display: inline-block;
            font-style: italic;
            color: var(--primary-color);
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            padding: 0.4rem 1.2rem;
            background: rgba(99, 102, 241, 0.08);
            border-radius: 2rem;
            border: 1px solid rgba(99, 102, 241, 0.2);
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }
        
        .question-text {
            font-size: 1rem;
            color: var(--dark-color);
            line-height: 1.7;
            white-space: pre-line;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 1rem;
            border: 1px solid rgba(229, 231, 235, 0.5);
            animation: fadeInUp 0.6s ease-out 0.2s both;
            text-align: left;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Audio Components */
        .audio-recorder {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03) 0%, rgba(139, 92, 246, 0.03) 100%);
            border-radius: 1.5rem;
            padding: 2.5rem;
            text-align: center;
            border: 2px solid rgba(99, 102, 241, 0.15);
            transition: all 0.4s ease;
            margin-top: 2rem;
        }

        .audio-recorder.recording {
            border-color: var(--danger-color);
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(239, 68, 68, 0.08) 100%);
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.2);
            animation: recordingPulse 2s ease-in-out infinite;
        }
        
        @keyframes recordingPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .audio-mic-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }
        
        .audio-mic-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 35px rgba(99, 102, 241, 0.4);
        }
        
        .audio-mic-icon.recording {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
            animation: pulse 1.5s infinite;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }
        
        .audio-mic-icon.has-recording {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }
        
        .audio-mic-icon i {
            font-size: 3rem;
            color: white;
        }

        .audio-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .audio-controls .btn {
            min-width: 140px;
            padding: 0.75rem 1.5rem;
            border-radius: 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .audio-controls .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .timer {
            font-size: 1.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0.75rem 0;
            letter-spacing: 1px;
        }

        .timer.recording {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .audio-status-message {
            padding: 0.75rem 1.25rem;
            border-radius: 1rem;
            margin: 0.75rem 0;
            font-weight: 500;
            font-size: 0.9rem;
            animation: fadeInUp 0.5s ease-out;
        }
        
        .audio-status-message.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .audio-status-message.recording {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .audio-status-message .small {
            font-size: 0.8rem;
        }

        /* Footer */
        .public-footer {
            background: white;
            border-top: 1px solid var(--border-color);
            padding: 1.5rem 0;
            margin-top: auto;
            text-align: center;
            color: var(--medium-gray);
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pulse-recording {
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Navigation Controls */
        .question-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid rgba(99, 102, 241, 0.1);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .question-nav .btn {
            border-radius: 2rem;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .question-nav .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.2);
        }
        
        .progress-indicator {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 2rem;
            color: var(--primary-color);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .question-card {
                padding: 2rem 1.5rem;
            }
            
            .question-main-title {
                font-size: 1.25rem;
            }
            
            .question-text {
                font-size: 0.95rem;
                padding: 1.25rem;
                line-height: 1.6;
            }
            
            .question-skills {
                font-size: 0.8rem;
                padding: 0.35rem 1rem;
            }
            
            .audio-mic-icon {
                width: 100px;
                height: 100px;
            }
            
            .audio-mic-icon i {
                font-size: 2.5rem;
            }
            
            .timer {
                font-size: 1.5rem;
            }
            
            .audio-controls .btn {
                min-width: 120px;
                padding: 0.6rem 1.2rem;
                font-size: 0.95rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .btn {
                padding: 0.75rem 1.25rem;
            }
            
            .question-nav {
                padding: 1rem;
            }
            
            .question-nav .btn {
                padding: 0.6rem 1.5rem;
                font-size: 0.95rem;
            }
            
            .question-card {
                padding: 1.5rem;
            }
        }
    </style>

    @yield('styles')
</head>
<body>
    <!-- Header -->
    <header class="public-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Reskiling Logo (Left) -->
                <div class="reskiling-logo">
                    @if(file_exists(public_path('images/reskiling-logo.png')))
                        <img src="{{ asset('images/reskiling-logo.png') }}" 
                             alt="Reskiling" 
                             style="height: 40px; max-width: 200px; object-fit: contain;">
                    @else
                        <a href="/" class="brand">
                            <i class="fas fa-brain"></i> Evaluación Psicológica
                        </a>
                    @endif
                </div>
                
                <!-- Company Logo (Right) -->
                @if(isset($campaign) && $campaign->company)
                    <div class="company-logo">
                        @if($campaign->company->logo_url)
                            <img src="{{ Storage::url($campaign->company->logo_url) }}" 
                                 alt="Logo {{ $campaign->company->name }}" 
                                 style="height: 40px; max-width: 200px; object-fit: contain;">
                        @else
                            <div class="text-muted small">
                                <i class="fas fa-building"></i> {{ $campaign->company->name }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="public-main">
        <div class="container">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('info'))
                <div class="alert alert-info alert-dismissible fade show fade-in" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ session('info') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="public-footer">
        <div class="container">
            <p class="mb-0">
                &copy; {{ date('Y') }} Sistema de Evaluación Psicológica. 
                Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });

        // Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>

    @yield('scripts')
</body>
</html>