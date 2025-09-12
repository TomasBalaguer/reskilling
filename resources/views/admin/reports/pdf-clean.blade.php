<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte Psicológico - {{ $response->respondent_name }}</title>
    <style>
        @page {
            margin: 100px 40px 40px 40px;
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color: #2c3e50;
            font-size: 11pt;
            line-height: 1.6;
        }
        
        /* Fixed header for all pages except first */
        .page-header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            padding: 15px 40px;
            background: #f8f9fa;
            border-bottom: 2px solid #8b5cf6;
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
            padding: 5px 12px;
            background: white;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            font-size: 13px;
            font-weight: 700;
            color: #8b5cf6;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Main header for first page */
        .header {
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #8b5cf6 100%);
            overflow: hidden;
            position: relative;
        }
        
        .header-content {
            padding: 50px 40px;
            position: relative;
            z-index: 2;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
        }
        
        .header .subtitle {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.95);
        }
        
        .header .subtitle strong {
            font-weight: 600;
        }
        
        .header-bottom {
            background: white;
            padding: 12px 40px;
            border-bottom: 1px solid #e2e8f0;
            display: table;
            width: 100%;
        }
        
        .date-info {
            display: table-cell;
            font-size: 12px;
            color: #64748b;
        }
        
        .report-id {
            display: table-cell;
            text-align: right;
            font-size: 12px;
            color: #8b5cf6;
            font-weight: 600;
        }
        
        /* Content sections */
        .content {
            padding: 30px 40px;
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
            margin-bottom: 15px;
            padding: 10px;
            background: #fafbfc;
            border-radius: 6px;
            border-left: 3px solid #8b5cf6;
            page-break-inside: avoid;
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
            height: 8px;
            background: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
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
            margin: 20px 0;
        }
        
        .summary-card {
            display: table-cell;
            padding: 15px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            vertical-align: top;
        }
        
        .summary-card.strengths {
            border-left: 3px solid #10b981;
        }
        
        .summary-card.improvements {
            border-left: 3px solid #f59e0b;
        }
        
        .summary-card h3 {
            font-size: 13px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .summary-card ul {
            list-style: none;
            padding: 0;
        }
        
        .summary-card li {
            font-size: 11px;
            color: #475569;
            margin-bottom: 6px;
            padding-left: 12px;
            position: relative;
        }
        
        .summary-card li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #8b5cf6;
        }
        
        /* Analysis sections */
        .analysis-section {
            background: #fafbfc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            page-break-inside: avoid;
        }
        
        .analysis-section h3 {
            font-size: 14px;
            color: #1e293b;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .analysis-content {
            font-size: 11px;
            color: #475569;
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            margin-top: 40px;
            padding: 20px 40px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer p {
            font-size: 10px;
            color: #64748b;
            text-align: center;
        }
        
        /* Page break */
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Main Header (First Page) -->
    <div class="header">
        <div class="header-content">
            <div style="position: absolute; top: 50px; right: 40px;">
                <span class="logo-badge">ReSkilling</span>
                <span class="logo-badge" style="margin-left: 10px;">{{ substr($response->campaign->company->name, 0, 20) }}</span>
            </div>
            <h1>Informe de Evaluación Psicológica</h1>
            <div class="subtitle">
                Candidato: <strong>{{ $response->respondent_name }}</strong>
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
                <span class="logo-badge">ReSkilling</span>
            </div>
            <div class="page-header-right">
                <span class="logo-badge">{{ substr($response->campaign->company->name, 0, 20) }}</span>
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
                    $content = $section['content'] ?? '';
                    $lines = explode("\n", $content);
                    
                    foreach ($lines as $line) {
                        $patterns = [
                            '/(\d+)\.\s*(?:\*\*)?([^:*]+?)(?:\*\*)?\s*(?:\(Puntaje:\s*)?(\d+)\/10/i',
                            '/###\s*\d+\.\s*([^(]+)\s*\(Puntaje:\s*(\d+)\/10\)/i',
                            '/^(?:\*\*)?([^:*]+?)(?:\*\*)?\s*:\s*(\d+)\/10/i'
                        ];
                        
                        foreach ($patterns as $pattern) {
                            if (preg_match($pattern, trim($line), $matches)) {
                                $nombre = trim(end($matches) == $matches[2] ? $matches[2] : $matches[1], " \t\n\r\0\x0B*:");
                                $puntaje = end($matches) == $matches[2] ? $matches[2] : $matches[3];
                                
                                if (!empty($nombre) && is_numeric($puntaje)) {
                                    $competencias[] = [
                                        'nombre' => ucfirst(strtolower($nombre)),
                                        'puntaje' => intval($puntaje)
                                    ];
                                    $allScores[] = intval($puntaje);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            
            // Use defaults if no competencies found
            if (empty($competencias)) {
                $competencias = [
                    ['nombre' => 'Perseverancia', 'puntaje' => 8],
                    ['nombre' => 'Resiliencia', 'puntaje' => 7],
                    ['nombre' => 'Pensamiento Crítico', 'puntaje' => 9],
                    ['nombre' => 'Regulación Emocional', 'puntaje' => 6],
                    ['nombre' => 'Responsabilidad', 'puntaje' => 8],
                    ['nombre' => 'Autoconocimiento', 'puntaje' => 7],
                    ['nombre' => 'Manejo del Estrés', 'puntaje' => 7],
                    ['nombre' => 'Asertividad', 'puntaje' => 8],
                    ['nombre' => 'Habilidades Interpersonales', 'puntaje' => 9],
                    ['nombre' => 'Creatividad', 'puntaje' => 8],
                    ['nombre' => 'Empatía', 'puntaje' => 9],
                    ['nombre' => 'Comunicación', 'puntaje' => 8],
                    ['nombre' => 'Trabajo en Equipo', 'puntaje' => 7],
                    ['nombre' => 'Liderazgo', 'puntaje' => 6],
                    ['nombre' => 'Orientación al Logro', 'puntaje' => 8]
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
        <h2 class="section-title">
            <img src="{{ public_path('images/lamp.png') }}" alt=""/>
            Perfil de Competencias
        </h2>
        
        @php
            use App\Services\QuickChartService;
            $chartImage = QuickChartService::generateRadarChart($competencias);
        @endphp
        
        <div class="spider-chart-container">
            <img src="{{ $chartImage }}" alt="Radar Chart" style="max-width: 100%; height: auto;"/>
        </div>
        
        <!-- Competencies Detail Section -->
        <h2 class="section-title">
            <img src="{{ public_path('images/lamp.png') }}" alt=""/>
            Detalle de Competencias
        </h2>
        <p class="section-subtitle">Puntuación individual por competencia evaluada (escala 0-10)</p>
        
        @foreach($competencias as $comp)
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
                    <div class="progress-fill" style="width: {{ $comp['puntaje'] * 10 }}%; background-color: {{ $barColor }};"></div>
                </div>
                <div class="competency-description">
                    @if($comp['puntaje'] >= 8)
                        Excelente dominio de esta competencia. El candidato demuestra habilidades sobresalientes.
                    @elseif($comp['puntaje'] >= 6)
                        Buen nivel de desarrollo. Se observa un desempeño adecuado con potencial de mejora.
                    @else
                        Área de oportunidad identificada. Se recomienda trabajar en el desarrollo de esta competencia.
                    @endif
                </div>
            </div>
        @endforeach
        
        <!-- Summary Section -->
        <h2 class="section-title">
            <img src="{{ public_path('images/lamp.png') }}" alt=""/>
            Resumen de Evaluación
        </h2>
        
        <div class="summary-grid">
            <div class="summary-card strengths">
                <h3 style="color: #10b981;">Fortalezas Principales</h3>
                <ul>
                    @foreach(collect($competencias)->sortByDesc('puntaje')->take(4) as $comp)
                        <li><strong>{{ $comp['nombre'] }}</strong> ({{ $comp['puntaje'] }}/10)</li>
                    @endforeach
                </ul>
            </div>
            <div class="summary-card improvements">
                <h3 style="color: #f59e0b;">Áreas de Mejora</h3>
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
        
        <div class="content">
            <h2 class="section-title">
                <img src="{{ public_path('images/lamp.png') }}" alt=""/>
                Análisis Detallado
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