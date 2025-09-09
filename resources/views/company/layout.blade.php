<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel Empresa - Sistema de Cuestionarios')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #22c55e;
            --primary-dark: #16a34a;
            --primary-gradient: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 50%, #22c55e 100%);
            --secondary-color: #06b6d4;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --dark-color: #0f172a;
            --light-gray: #f8fafc;
            --medium-gray: #64748b;
            --border-color: #e2e8f0;
            --sidebar-bg: #ffffff;
            --sidebar-border: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--light-gray);
            color: var(--dark-color);
            line-height: 1.6;
        }

        /* Sidebar Styles */
        .sidebar {
            background: var(--sidebar-bg);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            z-index: 1000;
            border-right: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            border-bottom: 1px solid var(--sidebar-border);
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .logo-container img {
            max-height: 32px;
            width: auto;
            max-width: 180px;
            object-fit: contain;
        }

        .company-info {
            background: var(--light-gray);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .company-info .user-name {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }

        .company-info .company-name {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .sidebar-nav {
            padding: 1.5rem 0 2rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0 1.5rem 0.75rem 1.5rem;
        }

        .nav-item {
            margin: 0.125rem 1rem;
        }

        .nav-link {
            color: var(--text-secondary) !important;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.15s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 500;
            font-size: 0.875rem;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            position: relative;
        }

        .nav-link:hover {
            background: var(--light-gray);
            color: var(--text-primary) !important;
            transform: translateX(2px);
        }

        .nav-link.active {
            background: var(--primary-gradient);
            color: white !important;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.25);
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: -1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 20px;
            background: var(--primary-color);
            border-radius: 0 2px 2px 0;
        }

        .nav-link i {
            width: 18px;
            margin-right: 0.75rem;
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .nav-link.logout {
            color: var(--danger-color) !important;
            margin-top: 1rem;
        }

        .nav-link.logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color) !important;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: var(--light-gray);
        }

        .content-header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
        }

        .content-header h1 {
            font-size: 1.625rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }

        .content-body {
            padding: 2rem 2.5rem;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            background: white;
        }

        .card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
            border-radius: 0.75rem 0.75rem 0 0 !important;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Metric Cards */
        .metric-card {
            background: linear-gradient(135deg, white 0%, #fafafa 100%);
            border-left: 4px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--primary-color)10, transparent);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .metric-card.success {
            border-left-color: var(--success-color);
        }

        .metric-card.warning {
            border-left-color: var(--warning-color);
        }

        .metric-card.danger {
            border-left-color: var(--danger-color);
        }

        .metric-card.info {
            border-left-color: var(--info-color);
        }

        /* Buttons */
        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
            padding: 0.625rem 1.25rem;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
            color: white;
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

        /* Tables */
        .table {
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .table thead th {
            background: var(--light-gray);
            border: none;
            font-weight: 600;
            color: var(--dark-color);
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(34, 197, 94, 0.02);
        }

        /* Badges */
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
        }

        .bg-success {
            background: var(--success-color) !important;
        }

        .bg-warning {
            background: var(--warning-color) !important;
        }

        .bg-danger {
            background: var(--danger-color) !important;
        }

        .bg-info {
            background: var(--info-color) !important;
        }

        .bg-primary {
            background: var(--primary-color) !important;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: 0.5rem;
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

        /* Form Controls */
        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .content-header, .content-body {
                padding: 1.5rem 1rem;
            }
        }

        /* Mobile menu button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <!-- Logo -->
            <div class="logo-container">
                <img src="{{ asset('images/reskiling-logo.png') }}" 
                     alt="RE-SKILLING.AI">
            </div>
            
            @auth('company')
                <div class="company-info">
                    <div class="user-name">{{ Auth::guard('company')->user()->name }}</div>
                    <div class="company-name">{{ Auth::guard('company')->user()->company->name }}</div>
                </div>
            @endauth
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('company.dashboard') ? 'active' : '' }}" 
                       href="{{ route('company.dashboard') }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </div>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Evaluaciones</div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('company.campaigns*') ? 'active' : '' }}" 
                       href="{{ route('company.campaigns') }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}">
                        <i class="fas fa-bullhorn"></i> Campañas
                    </a>
                </div>
                
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('company.responses*') ? 'active' : '' }}" 
                       href="{{ route('company.responses') }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}">
                        <i class="fas fa-clipboard-list"></i> Respuestas
                    </a>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Configuración</div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('company.profile*') ? 'active' : '' }}" 
                       href="{{ route('company.profile.edit') }}{{ request()->has('company_id') ? '?company_id=' . request('company_id') : '' }}">
                        <i class="fas fa-building"></i> Perfil de Empresa
                    </a>
                </div>
                
                <div class="nav-item">
                    <form method="POST" action="{{ route('company.logout') }}">
                        @csrf
                        <button type="submit" class="nav-link logout">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main content -->
    <div class="main-content">
        <div class="content-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1>@yield('page-title')</h1>
                <div>
                    @yield('page-actions')
                </div>
            </div>
        </div>

        <div class="content-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !menuBtn.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    </script>
    
    @yield('scripts')
</body>
</html>