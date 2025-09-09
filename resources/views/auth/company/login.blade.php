<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Empresa - Sistema de Cuestionarios</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            max-width: 420px;
            width: 100%;
            padding: 3rem 2.5rem;
            border: 1px solid #f1f5f9;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 1.5rem;
        }
        .login-header h2 {
            color: #1e293b;
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #64748b;
            font-size: 0.875rem;
        }
        .form-control {
            border-radius: 0.5rem;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            border-color: #8b5cf6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 50%, #22c55e 100%);
            border: none;
            border-radius: 0.5rem;
            padding: 0.875rem;
            font-weight: 600;
            transition: all 0.2s ease;
            color: white;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
            background: linear-gradient(135deg, #7c3aed 0%, #0891b2 50%, #16a34a 100%);
        }
        .admin-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f1f5f9;
        }
        .admin-link a {
            color: #8b5cf6;
            font-weight: 500;
        }
        .admin-link a:hover {
            color: #7c3aed;
        }
        .form-check-input:checked {
            background-color: #8b5cf6;
            border-color: #8b5cf6;
        }
        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-card">
                    <div class="login-header">
                        <img src="{{ asset('images/reskiling-logo.png') }}" alt="RE-SKILLING.AI" class="logo">
                        <h2>Panel Empresa</h2>
                        <p>Acceso para usuarios de empresa</p>
                    </div>
                    
                    <form method="POST" action="{{ route('company.login') }}">
                        @csrf
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Correo Electrónico
                            </label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required 
                                   autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Contraseña
                            </label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Remember Me -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Recordar sesión
                            </label>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success btn-login w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                        </button>
                    </form>
                    
                    <div class="admin-link">
                        <small class="text-muted">
                            ¿Eres administrador del sistema? 
                            <a href="{{ route('admin.login') }}" class="text-decoration-none">
                                Acceder al Panel Admin
                            </a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>