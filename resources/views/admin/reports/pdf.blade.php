<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Reporte de Competencias - {{ $response->respondent_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Quicksand:wght@300..700&family=Roboto+Slab:wght@100..900&display=swap" rel="stylesheet">
    <style>

        
        @page {
            margin: 140px 50px 50px 50px;
            size: A4;
        }

        @page :first {
            margin-top: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Roboto Slab", serif;
            color: #2c3e50;
            font-size: 11pt;
            line-height: 1.6;
        }

        /* Fixed header for all pages except first */
        .page-header {
            position: fixed;
            top: -110px;
            left: -50px;
            right: -50px;
            padding: 15px 50px;
            background: white;
            border-bottom: 2px solid #e2e8f0;
        }

        .page-header-content {
            display: table;
            width: 100%;
        }

        .page-header-left,
        .page-header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .page-header-right {
            text-align: right;
        }

        .logo-badge {
            display: inline-block;
            padding: 8px 12px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            vertical-align: middle;
        }

        .logo-img {
            height: 25px;
            width: auto;
            display: block;
        }

        /* Main header for first page */
        .header {
            padding: 0;
            background: #ffffff;
            overflow: hidden;
            position: relative;
            border-bottom: none;
        }

        /* Removed decorative element */

        .header-content {
            padding: 35px 50px 40px 50px;
            position: relative;
            z-index: 2;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header .subtitle {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.95);
        }

        .header .subtitle strong {
            font-weight: 600;
        }

        .header-bottom {
            background: #f8f9fa;
            padding: 10px 50px;
            display: table;
            width: 100%;
            border-bottom: 2px solid #8b5cf6;
        }

        .date-info {
            display: table-cell;
            font-size: 11px;
            color: #64748b;
            font-weight: 500;
        }

        .report-id {
            display: table-cell;
            text-align: right;
            font-size: 11px;
            color: #8b5cf6;
            font-weight: 600;
        }

        /* Content sections */
        .content {
            padding: 20px 50px;
        }

        /* First content section after header */
        .content:first-of-type {
            padding-top: 10px;
        }

        .section-title {
            color: #1e293b;
            font-weight: 700;
            font-size: 18px;
            margin: 25px 0 15px 0;
            display: flex;
            align-items: center;
        }

        .section-title img {
            width: 20px;
            height: 24px;
            margin-right: 10px;
            object-fit: contain;
        }

        .section-subtitle {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 15px;
        }

        /* Spider chart container */
        .spider-chart-container {
            text-align: center;
            margin: 20px auto;
            max-width: 400px;
        }

        /* Competency items */
        .competency-item {
            margin-bottom: 18px;
            padding: 15px;
            background: linear-gradient(135deg, #ffffff 0%, #f8f7ff 100%);
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            page-break-inside: avoid;
            position: relative;
            overflow: hidden;
        }
        
        /* Items that should appear at top of new page */
        .competency-item.new-page-item {
            margin-top: 35px;
        }

        .competency-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #8b5cf6 0%, #06b6d4 100%);
        }

        .competency-header {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }

        .competency-name {
            display: table-cell;
            font-size: 12px;
            color: #475569;
            font-weight: 500;
        }

        .competency-value {
            display: table-cell;
            text-align: right;
            width: 60px;
        }

        .score-badge {
            display: inline-block;
            background: #8b5cf6;
            color: white;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .progress-bar {
            width: 100%;
            height: 10px;
            background: #f1f5f9;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
        }

        .progress-fill {
            height: 100%;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .competency-description {
            font-size: 10px;
            color: #64748b;
            line-height: 1.4;
            margin-top: 6px;
        }

        /* Summary cards */
        .summary-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-spacing: 15px 0;
            margin: 30px 0;
        }

        .summary-card {
            display: table-cell;
            padding: 22px;
            background: white;
            border-radius: 14px;
            vertical-align: top;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            border: 1px solid #e5e7eb;
        }

        .summary-card.strengths {
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
            border-left: 4px solid #10b981;
        }

        .summary-card.improvements {
            background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
            border-left: 4px solid #f59e0b;
        }

        .summary-card h3 {
            font-size: 14px;
            margin-bottom: 16px;
            font-weight: 700;
            font-family: 'Roboto Slab', serif;
            display: flex;
            align-items: center;
        }
        
        .summary-card.strengths h3::before {
            content: '\2713';
            min-width: 20px;
            width: 20px;
            height: 20px;
            background: #10b981;
            color: white;
            border-radius: 50%;
            margin-right: 10px;
            display: inline-block;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
            flex-shrink: 0;
        }
        
        .summary-card.improvements h3::before {
            content: '!';
            min-width: 20px;
            width: 20px;
            height: 20px;
            background: #f59e0b;
            color: white;
            border-radius: 50%;
            margin-right: 10px;
            display: inline-block;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
            font-weight: 800;
            flex-shrink: 0;
        }

        .summary-card ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .summary-card li {
            font-size: 12px;
            color: #334155;
            margin-bottom: 8px;
            padding-left: 15px;
            position: relative;
            line-height: 1.5;
        }
        
        .summary-card li strong {
            color: #1e293b;
            font-weight: 600;
        }

        .summary-card li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #8b5cf6;
            font-weight: bold;
        }
        
        .summary-card.strengths li:before {
            color: #10b981;
        }
        
        .summary-card.improvements li:before {
            color: #f59e0b;
        }

        /* Analysis sections */
        .analysis-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            page-break-inside: avoid;
            position: relative;
            overflow: hidden;
        }
        
        .analysis-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #8b5cf6 0%, #06b6d4 100%);
        }

        .analysis-section h3 {
            font-size: 16px;
            color: #1e293b;
            margin-bottom: 16px;
            margin-left: 16px;
            font-weight: 700;
            font-family: 'Roboto Slab', serif;
            display: flex;
            align-items: center;
        }
        
        .analysis-section h3::before {
            content: '';
            width: 24px;
            height: 24px;
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
            border-radius: 6px;
            margin-right: 12px;
            display: inline-block;
        }

        .analysis-content {
            font-size: 12px;
            color: #334155;
            line-height: 1.8;
            margin-left: 16px;
            margin-right: 8px;
        }
        
        .analysis-content p {
            margin-bottom: 14px;
            text-align: justify;
        }
        
        .analysis-content p:last-child {
            margin-bottom: 0;
        }

        /* Footer */
        .footer {
            margin-top: 50px;
            padding: 25px 50px;
            background: linear-gradient(90deg, #8b5cf6 0%, #06b6d4 100%);
            position: relative;
        }

        .footer p {
            font-size: 11px;
            color: white;
            text-align: center;
            margin: 5px 0;
            font-weight: 500;
        }

        /* Page break */
        .page-break {
            page-break-after: always;
            margin: 0;
            padding: 0;
            height: 0;
        }
        
        /* Spacer for new pages */
        .page-spacer {
            height: 50px;
            margin: 0;
            padding: 0;
        }
        
        /* Content sections */
        .content {
            position: relative;
        }
    </style>
</head>

<body>
    <!-- Main Header (First Page) -->
    <div class="header">
        <div class="header-content">
            @php
                $companyLogo = null;
                if ($response->campaign->company->logo_url) {
                    if (filter_var($response->campaign->company->logo_url, FILTER_VALIDATE_URL)) {
                        $companyLogo = $response->campaign->company->logo_url;
                    } elseif (file_exists(public_path('storage/' . $response->campaign->company->logo_url))) {
                        $companyLogo = public_path('storage/' . $response->campaign->company->logo_url);
                    } elseif (file_exists(storage_path('app/public/' . $response->campaign->company->logo_url))) {
                        $companyLogo = storage_path('app/public/' . $response->campaign->company->logo_url);
                    }
                } elseif ($response->campaign->company->logo_s3_path) {
                    $companyLogo = $response->campaign->company->logo_s3_path;
                }
            @endphp

            <!-- Logos Container -->
            <div style="display: table; width: 100%; margin-bottom: 25px;">
                <div style="display: table-cell; vertical-align: middle;">
                    @if($companyLogo)
                        <div
                            style="display: inline-block; padding: 15px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.15);">
                            <img src="{{ $companyLogo }}" style="height: 55px; width: auto; display: block;"
                                alt="{{ $response->campaign->company->name }}" />
                        </div>
                    @else
                        <div
                            style="display: inline-block; padding: 18px 30px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.15);">
                            <span
                                style="font-size: 24px; font-weight: 800; background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: #8b5cf6;">{{ $response->campaign->company->name }}</span>
                        </div>
                    @endif
                </div>
                <div style="display: table-cell; vertical-align: middle; text-align: right;">
                    <img src="{{ public_path('images/reskiling-logo.png') }}"
                        style="height: 22px; width: auto; opacity: 0.6;" alt="ReSkilling" />
                </div>
            </div>
            <!-- Title and Candidate Section -->
            <div style="margin-top: 20px;">
                <div style="border-left: 4px solid #8b5cf6; padding-left: 20px;">
                    <h1 style="font-size: 32px; margin: 0; color: #1e293b; font-weight: 700; letter-spacing: -0.5px; font-family: 'Roboto Slab', serif;">
                        REPORTE DE COMPETENCIAS
                    </h1>
                    <div style="margin-top: 15px;">
                        <div style="font-size: 20px; color: #2c3e50; font-weight: 600; margin-bottom: 5px; font-family: 'Roboto Slab', serif;">
                            {{ $response->respondent_name }}
                        </div>
                        <div style="font-size: 14px; color: #64748b; font-family: 'Roboto Slab', serif;">
                            {{ $response->respondent_email }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="header-bottom">
            <div class="date-info">
                <strong>Fecha:</strong> {{ $response->created_at->format('d/m/Y') }}
            </div>
            <div class="report-id">
                ID: #{{ str_pad($response->id, 6, '0', STR_PAD_LEFT) }}
            </div>
        </div>
    </div>

    <!-- Fixed header for pages 2+ -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-header-left">
                <span style="font-size: 13px; color: #64748b; font-weight: 600;">Reporte de Competencias</span>
                <br>
                <span style="font-size: 11px; color: #94a3b8;">{{ $response->respondent_name }}</span>
            </div>
            <div class="page-header-right">
                @php
                    $companyLogoHeader = null;
                    if ($response->campaign->company->logo_url) {
                        if (filter_var($response->campaign->company->logo_url, FILTER_VALIDATE_URL)) {
                            $companyLogoHeader = $response->campaign->company->logo_url;
                        } elseif (file_exists(public_path('storage/' . $response->campaign->company->logo_url))) {
                            $companyLogoHeader = public_path('storage/' . $response->campaign->company->logo_url);
                        } elseif (file_exists(storage_path('app/public/' . $response->campaign->company->logo_url))) {
                            $companyLogoHeader = storage_path('app/public/' . $response->campaign->company->logo_url);
                        }
                    } elseif ($response->campaign->company->logo_s3_path) {
                        $companyLogoHeader = $response->campaign->company->logo_s3_path;
                    }
                @endphp

                <div style="display: inline-block;">
                    @if($companyLogoHeader)
                        <img src="{{ $companyLogoHeader }}"
                            style="height: 20px; width: auto; margin-right: 10px; opacity: 0.8;"
                            alt="{{ $response->campaign->company->name }}" />
                    @else
                        <span
                            style="font-size: 11px; font-weight: 700; color: #8b5cf6; text-transform: uppercase; margin-right: 10px;">{{ $response->campaign->company->name }}</span>
                    @endif
                    <img src="{{ public_path('images/reskiling-logo.png') }}"
                        style="height: 16px; width: auto; opacity: 0.6;" alt="ReSkilling" />
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        @php
            // Prepare competencies data
            $competencias = [];
            $allScores = [];

            if ($response->comprehensive_report && isset($response->comprehensive_report['sections'])) {
                foreach ($response->comprehensive_report['sections'] as $section) {
                    // Solo procesar secciones de competencias
                    if (str_contains(strtolower($section['title']), 'competencia')) {
                        $content = $section['content'] ?? '';
                        $lines = explode("\n", $content);

                        foreach ($lines as $line) {
                            // Patrón mejorado: buscar formato "1. **Nombre**: X/10 - descripción"
                            if (preg_match('/^(\d+)\.\s*\*\*([^:*]+)\*\*:\s*(\d+)\/10\s*-\s*(.*)$/i', trim($line), $matches)) {
                                $competencias[] = [
                                    'nombre' => trim($matches[2]),
                                    'puntaje' => intval($matches[3]),
                                    'descripcion' => trim($matches[4])
                                ];
                                $allScores[] = intval($matches[3]);
                            }
                            // Si la descripción continúa en la siguiente línea
                            elseif (!empty($competencias) && !preg_match('/^\d+\./', trim($line)) && trim($line) !== '') {
                                $lastIndex = count($competencias) - 1;
                                if (isset($competencias[$lastIndex])) {
                                    $competencias[$lastIndex]['descripcion'] .= ' ' . trim($line);
                                }
                            }
                        }
                    }
                }
            }

            // Use defaults if no competencies found
            if (empty($competencias)) {
                $competencias = [
                    ['nombre' => 'Perseverancia', 'puntaje' => 8, 'descripcion' => 'Demuestra capacidad excepcional para mantener el esfuerzo y la motivación frente a desafíos prolongados.'],
                    ['nombre' => 'Resiliencia', 'puntaje' => 7, 'descripcion' => 'Muestra buena capacidad de recuperación ante situaciones adversas y adaptación al cambio.'],
                    ['nombre' => 'Pensamiento Crítico', 'puntaje' => 9, 'descripcion' => 'Excelente capacidad para analizar información objetivamente y tomar decisiones fundamentadas.'],
                    ['nombre' => 'Regulación Emocional', 'puntaje' => 6, 'descripcion' => 'Presenta un nivel adecuado de control emocional con oportunidades de mejora en situaciones de alta presión.'],
                    ['nombre' => 'Responsabilidad', 'puntaje' => 8, 'descripcion' => 'Alto sentido del deber y compromiso con las tareas asignadas y los resultados esperados.'],
                    ['nombre' => 'Autoconocimiento', 'puntaje' => 7, 'descripcion' => 'Buena comprensión de sus propias fortalezas y áreas de mejora, con apertura al desarrollo personal.'],
                    ['nombre' => 'Manejo del Estrés', 'puntaje' => 7, 'descripcion' => 'Gestiona adecuadamente las situaciones de presión manteniendo un desempeño estable.'],
                    ['nombre' => 'Asertividad', 'puntaje' => 8, 'descripcion' => 'Comunica sus ideas y necesidades de manera clara y respetuosa, estableciendo límites apropiados.'],
                    ['nombre' => 'Habilidades Interpersonales', 'puntaje' => 9, 'descripcion' => 'Excelente capacidad para establecer y mantener relaciones profesionales productivas.'],
                    ['nombre' => 'Creatividad', 'puntaje' => 8, 'descripcion' => 'Demuestra pensamiento innovador y capacidad para generar soluciones originales.'],
                    ['nombre' => 'Empatía', 'puntaje' => 9, 'descripcion' => 'Sobresaliente capacidad para comprender y responder a las necesidades emocionales de otros.'],
                    ['nombre' => 'Comunicación', 'puntaje' => 8, 'descripcion' => 'Transmite información de manera clara y efectiva, adaptándose a diferentes audiencias.'],
                    ['nombre' => 'Trabajo en Equipo', 'puntaje' => 7, 'descripcion' => 'Colabora efectivamente con otros, contribuyendo al logro de objetivos comunes.'],
                    ['nombre' => 'Liderazgo', 'puntaje' => 6, 'descripcion' => 'Muestra potencial de liderazgo con oportunidades de desarrollo en influencia y dirección de equipos.'],
                    ['nombre' => 'Orientación al Logro', 'puntaje' => 8, 'descripcion' => 'Fuerte motivación hacia el cumplimiento de objetivos y la superación de expectativas.']
                ];
                $allScores = array_column($competencias, 'puntaje');
            }

            // Ensure 15 competencies
            while (count($competencias) < 15) {
                $competencias[] = ['nombre' => 'Competencia ' . (count($competencias) + 1), 'puntaje' => 5];
            }
            $competencias = array_slice($competencias, 0, 15);
        @endphp

        <!-- Spider Chart Section -->
        <h2
            style="color: #1e293b; font-weight: 800; font-size: 20px; margin: 20px 0 15px 0; display: table; width: 100%;">
            <span
                style="display: table-cell; width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%); border-radius: 10px; vertical-align: middle;">
                <img src="{{ public_path('images/lamp.png') }}"
                    style="width: 18px; height: 20px; margin: 8px; filter: brightness(0) invert(1);" alt="" />
            </span>
            <span style="display: table-cell; padding-left: 15px; vertical-align: middle;">Perfil de Competencias</span>
        </h2>

        @php
            use App\Services\QuickChartService;
            $chartImage = QuickChartService::generateRadarChart($competencias);
        @endphp

        <div class="spider-chart-container">
            <img src="{{ $chartImage }}" alt="Radar Chart" style="max-width: 100%; height: auto;" />
        </div>

        <!-- PAGE 1: First 5 competencies with title -->
        <div class="page-break"></div>
        <div class="page-spacer"></div>
        
        <h2
            style="color: #1e293b; font-weight: 800; font-size: 20px; margin: 0 0 20px 0; display: table; width: 100%;">
            <span
                style="display: table-cell; width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%); border-radius: 10px; vertical-align: middle;">
                <img src="{{ public_path('images/lamp.png') }}"
                    style="width: 18px; height: 20px; margin: 8px; filter: brightness(0) invert(1);" alt="" />
            </span>
            <span style="display: table-cell; padding-left: 15px; vertical-align: middle;">Detalle de
                Competencias</span>
        </h2>
        <p class="section-subtitle">Puntuación individual por competencia evaluada (escala 0-10)</p>

        @php
            $firstPageCompetencias = array_slice($competencias, 0, 5);
        @endphp
        
        @foreach($firstPageCompetencias as $comp)
            <div class="competency-item">
                <div class="competency-header">
                    <div class="competency-name">{{ $comp['nombre'] }}</div>
                    <div class="competency-value">
                        <span class="score-badge">{{ $comp['puntaje'] }}/10</span>
                    </div>
                </div>
                <div class="progress-bar">
                    @php
                        $barColor = $comp['puntaje'] >= 8 ? '#10b981' : ($comp['puntaje'] <= 5 ? '#f59e0b' : '#8b5cf6');
                    @endphp
                    <div class="progress-fill"
                        style="width: {{ $comp['puntaje'] * 10 }}%; background-color: {{ $barColor }};"></div>
                </div>
                <div class="competency-description">
                    @if(isset($comp['descripcion']) && !empty($comp['descripcion']))
                        {{ \Illuminate\Support\Str::limit($comp['descripcion'], 200, '...') }}
                    @else
                        @if($comp['puntaje'] >= 8)
                            Excelente dominio de esta competencia. El candidato demuestra habilidades sobresalientes.
                        @elseif($comp['puntaje'] >= 6)
                            Buen nivel de desarrollo. Se observa un desempeño adecuado con potencial de mejora.
                        @else
                            Área de oportunidad identificada. Se recomienda trabajar en el desarrollo de esta competencia.
                        @endif
                    @endif
                </div>
            </div>
        @endforeach

        <!-- PAGE 2: Next 6 competencies -->
        <div class="page-break"></div>
        <div class="page-spacer"></div>
        
        @php
            $secondPageCompetencias = array_slice($competencias, 5, 6);
        @endphp
        
        @foreach($secondPageCompetencias as $comp)
            <div class="competency-item">
                <div class="competency-header">
                    <div class="competency-name">{{ $comp['nombre'] }}</div>
                    <div class="competency-value">
                        <span class="score-badge">{{ $comp['puntaje'] }}/10</span>
                    </div>
                </div>
                <div class="progress-bar">
                    @php
                        $barColor = $comp['puntaje'] >= 8 ? '#10b981' : ($comp['puntaje'] <= 5 ? '#f59e0b' : '#8b5cf6');
                    @endphp
                    <div class="progress-fill"
                        style="width: {{ $comp['puntaje'] * 10 }}%; background-color: {{ $barColor }};"></div>
                </div>
                <div class="competency-description">
                    @if(isset($comp['descripcion']) && !empty($comp['descripcion']))
                        {{ \Illuminate\Support\Str::limit($comp['descripcion'], 200, '...') }}
                    @else
                        @if($comp['puntaje'] >= 8)
                            Excelente dominio de esta competencia. El candidato demuestra habilidades sobresalientes.
                        @elseif($comp['puntaje'] >= 6)
                            Buen nivel de desarrollo. Se observa un desempeño adecuado con potencial de mejora.
                        @else
                            Área de oportunidad identificada. Se recomienda trabajar en el desarrollo de esta competencia.
                        @endif
                    @endif
                </div>
            </div>
        @endforeach

        <!-- PAGE 3: Last 4 competencies + Summary -->
        <div class="page-break"></div>
        <div class="page-spacer"></div>
        
        @php
            $thirdPageCompetencias = array_slice($competencias, 11, 4);
        @endphp
        
        @foreach($thirdPageCompetencias as $comp)
            <div class="competency-item">
                <div class="competency-header">
                    <div class="competency-name">{{ $comp['nombre'] }}</div>
                    <div class="competency-value">
                        <span class="score-badge">{{ $comp['puntaje'] }}/10</span>
                    </div>
                </div>
                <div class="progress-bar">
                    @php
                        $barColor = $comp['puntaje'] >= 8 ? '#10b981' : ($comp['puntaje'] <= 5 ? '#f59e0b' : '#8b5cf6');
                    @endphp
                    <div class="progress-fill"
                        style="width: {{ $comp['puntaje'] * 10 }}%; background-color: {{ $barColor }};"></div>
                </div>
                <div class="competency-description">
                    @if(isset($comp['descripcion']) && !empty($comp['descripcion']))
                        {{ \Illuminate\Support\Str::limit($comp['descripcion'], 200, '...') }}
                    @else
                        @if($comp['puntaje'] >= 8)
                            Excelente dominio de esta competencia. El candidato demuestra habilidades sobresalientes.
                        @elseif($comp['puntaje'] >= 6)
                            Buen nivel de desarrollo. Se observa un desempeño adecuado con potencial de mejora.
                        @else
                            Área de oportunidad identificada. Se recomienda trabajar en el desarrollo de esta competencia.
                        @endif
                    @endif
                </div>
            </div>
        @endforeach

        <!-- Summary Section on same page -->
        <h2
            style="color: #1e293b; font-weight: 800; font-size: 20px; margin: 30px 0 20px 0; display: table; width: 100%;">
            <span
                style="display: table-cell; width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%); border-radius: 10px; vertical-align: middle;">
                <img src="{{ public_path('images/lamp.png') }}"
                    style="width: 18px; height: 20px; margin: 8px; filter: brightness(0) invert(1);" alt="" />
            </span>
            <span style="display: table-cell; padding-left: 15px; vertical-align: middle;">Resumen de Evaluación</span>
        </h2>

        <div class="summary-grid">
            <div class="summary-card strengths">
                <h3>Fortalezas Principales</h3>
                <ul>
                    @foreach(collect($competencias)->sortByDesc('puntaje')->take(4) as $comp)
                        <li><strong>{{ $comp['nombre'] }}</strong> ({{ $comp['puntaje'] }}/10)</li>
                    @endforeach
                </ul>
            </div>
            <div class="summary-card improvements">
                <h3>Áreas de Mejora</h3>
                <ul>
                    @foreach(collect($competencias)->sortBy('puntaje')->take(4) as $comp)
                        <li><strong>{{ $comp['nombre'] }}</strong> ({{ $comp['puntaje'] }}/10)</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    @if($response->comprehensive_report && isset($response->comprehensive_report['sections']))
        <div class="page-break"></div>
        <div class="page-spacer"></div>

        <div class="content">
            <h2 style="color: #1e293b; font-weight: 800; font-size: 20px; margin: 0 0 25px 0; display: table; width: 100%;">
                <span
                    style="display: table-cell; width: 40px; height: 40px; background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%); border-radius: 10px; vertical-align: middle;">
                    <img src="{{ public_path('images/lamp.png') }}"
                        style="width: 18px; height: 20px; margin: 8px; filter: brightness(0) invert(1);" alt="" />
                </span>
                <span style="display: table-cell; padding-left: 15px; vertical-align: middle;">Análisis Detallado</span>
            </h2>

            @php $sectionCount = 0; @endphp
            @foreach($response->comprehensive_report['sections'] as $section)
                @if(!str_contains(strtolower($section['title']), 'competencia') && $sectionCount < 3)
                    <div class="analysis-section">
                        <h3>{{ $section['title'] }}</h3>
                        <div class="analysis-content">
                            @php
                                $paragraphs = explode("\n\n", $section['content']);
                                $displayParagraphs = array_slice($paragraphs, 0, 2);
                            @endphp
                            @foreach($displayParagraphs as $paragraph)
                                @if(trim($paragraph))
                                    <p>{{ trim($paragraph) }}</p>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @php $sectionCount++; @endphp
                @endif
            @endforeach
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>{{ $response->campaign->company->name }} • Sistema de Evaluación Psicológica • Confidencial</p>
        <p>Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>

</html>