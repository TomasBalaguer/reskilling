<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Administración - Sistema de Cuestionarios')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom Button Styles -->
    <link href="{{ asset('css/buttons.css') }}" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --admin-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%);
            --secondary-color: #8b5cf6;
            --success-color: #6366f1;
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
            background: white;
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-right: 1px solid #f1f5f9;
        }

        .sidebar-header {
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            text-align: center;
        }

        .sidebar-header .logo {
            max-width: 180px;
            height: auto;
            margin-bottom: 1rem;
        }

        .sidebar-header .user-info {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1rem;
            border-radius: 0.75rem;
            margin-top: 1rem;
            border: 1px solid #e2e8f0;
        }

        .sidebar-header .user-info .name {
            color: var(--text-primary);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .sidebar-header .user-info .role {
            color: var(--text-secondary);
            font-size: 0.75rem;
            font-weight: 500;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0.25rem 0.75rem;
        }

        .nav-section {
            padding: 1rem 0 0.5rem 0;
        }

        .nav-section-title {
            color: var(--text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 1.5rem 0.5rem 1.5rem;
        }

        .nav-link {
            color: var(--text-secondary) !important;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-weight: 400;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            margin: 0.125rem 0;
            font-size: 0.875rem;
        }

        .nav-link:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--text-primary) !important;
            border-left: 3px solid var(--primary-color);
            padding-left: calc(1.5rem - 3px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #8b5cf6 10%, #06b6d4 50%, #22c55e 90%);
            color: white !important;
            box-shadow: 0 1px 4px rgba(139, 92, 246, 0.15);
            font-weight: 500;
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: var(--light-gray);
        }

        .content-header {
            background: white;
            padding: 1.25rem 2rem;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }

        .content-header h1 {
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--dark-color);
            margin: 0;
            letter-spacing: -0.025em;
        }

        .content-body {
            padding: 1.5rem 2rem;
        }

        /* Cards */
        .card {
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
            background: white;
        }

        .card:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            padding: 1rem 1.25rem;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }

        .card-body {
            padding: 1.25rem;
        }

        /* Metric Cards */
        .metric-card {
            background: white;
            border-left: 3px solid var(--primary-color);
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
            border-radius: 0.375rem;
            font-weight: 400;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
            border: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
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
            font-weight: 500;
            color: var(--text-secondary);
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .table tbody td {
            padding: 0.875rem 1rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .table tbody tr:hover {
            background: rgba(220, 38, 38, 0.02);
        }

        /* Badges */
        .badge {
            font-weight: 400;
            font-size: 0.75rem;
            padding: 0.375rem 0.625rem;
            border-radius: 0.25rem;
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
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
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

        /* Modern Button Styles */
        .btn-success {
            background-color: #6366f1 !important;
            border-color: #6366f1 !important;
            font-weight: 400 !important;
        }
        
        .btn-success:hover {
            background-color: #4f46e5 !important;
            border-color: #4f46e5 !important;
        }
        
        /* Table Action Buttons */
        table .btn {
            padding: 0.25rem 0.75rem !important;
            font-size: 0.875rem !important;
            font-weight: 400 !important;
            border-radius: 0.375rem !important;
        }
        
        table .btn i {
            font-size: 0.875rem;
        }
        
        .btn-info {
            background-color: #6366f1;
            border-color: #6366f1;
            font-weight: 400;
        }
        
        .btn-info:hover {
            background-color: #4f46e5;
            border-color: #4f46e5;
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
            <img src="{{ asset('images/reskiling-logo.png') }}" alt="RE-SKILLING.AI" class="logo">
            
            @auth('web')
                <div class="user-info">
                    <div class="name">{{ Auth::user()->name }}</div>
                    <div class="role">Administrador del Sistema</div>
                </div>
            @endauth
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Panel Principal</div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                       href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </div>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Gestión</div>
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.companies*') ? 'active' : '' }}" 
                       href="{{ route('admin.companies') }}">
                        <i class="fas fa-building"></i> Empresas
                    </a>
                </div>
                
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.campaigns*') ? 'active' : '' }}" 
                       href="{{ route('admin.campaigns') }}">
                        <i class="fas fa-bullhorn"></i> Campañas
                    </a>
                </div>
                
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.responses*') ? 'active' : '' }}" 
                       href="{{ route('admin.responses') }}">
                        <i class="fas fa-clipboard-list"></i> Respuestas
                    </a>
                </div>
            </div>
            
            <div class="nav-section" style="margin-top: 2rem;">
                <div class="nav-item">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="nav-link">
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