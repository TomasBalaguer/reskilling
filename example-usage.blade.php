<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spider Chart Example</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 20px;
            background-color: #f9fafb;
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>Reporte de Competencias</h1>
        <p>Evaluación integral de habilidades y competencias</p>
    </div>

    <div class="chart-container">
        @php
            $competenciasEjemplo = [
                ['nombre' => 'Perseverancia', 'puntaje' => 8],
                ['nombre' => 'Resiliencia', 'puntaje' => 7],
                ['nombre' => 'Adaptabilidad', 'puntaje' => 9],
                ['nombre' => 'Liderazgo', 'puntaje' => 6],
                ['nombre' => 'Comunicación', 'puntaje' => 8],
                ['nombre' => 'Trabajo en equipo', 'puntaje' => 7],
                ['nombre' => 'Creatividad', 'puntaje' => 9],
                ['nombre' => 'Pensamiento crítico', 'puntaje' => 8],
                ['nombre' => 'Resolución de problemas', 'puntaje' => 7],
                ['nombre' => 'Inteligencia emocional', 'puntaje' => 8],
                ['nombre' => 'Gestión del tiempo', 'puntaje' => 6],
                ['nombre' => 'Aprendizaje continuo', 'puntaje' => 9],
                ['nombre' => 'Innovación', 'puntaje' => 7],
                ['nombre' => 'Orientación al logro', 'puntaje' => 8],
                ['nombre' => 'Responsabilidad', 'puntaje' => 9]
            ];
        @endphp

        <!-- Using the component -->
        <x-spider-chart 
            :competencias="$competenciasEjemplo" 
            :width="500" 
            :height="500" 
            title="Evaluación de Competencias Profesionales" />
    </div>

    <!-- Alternative: Direct SVG embedding for maximum PDF compatibility -->
    <div class="chart-container">
        <h2 style="text-align: center; margin-bottom: 20px;">Versión Directa SVG</h2>
        
        @php
            $competencias = $competenciasEjemplo;
            $width = 400;
            $height = 400;
            $centerX = $width / 2;
            $centerY = $height / 2;
            $maxRadius = 140;
            $maxScore = 10;
            $totalCompetencias = count($competencias);
            $angleStep = 360 / $totalCompetencias;
            
            $points = [];
            foreach ($competencias as $index => $competencia) {
                $angle = ($index * $angleStep) - 90;
                $angleRad = deg2rad($angle);
                $radius = ($competencia['puntaje'] / $maxScore) * $maxRadius;
                $x = $centerX + cos($angleRad) * $radius;
                $y = $centerY + sin($angleRad) * $radius;
                $points[] = "$x,$y";
            }
        @endphp

        <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}">
            <defs>
                <radialGradient id="radarFill" cx="50%" cy="50%" r="50%">
                    <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:0.8" />
                    <stop offset="100%" style="stop-color:#6366f1;stop-opacity:0.3" />
                </radialGradient>
            </defs>

            <!-- Grid circles -->
            @for ($i = 1; $i <= 5; $i++)
                <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="{{ ($i/5) * $maxRadius }}" 
                        fill="none" stroke="#e5e7eb" stroke-width="1" opacity="0.6"/>
            @endfor

            <!-- Grid lines -->
            @foreach ($competencias as $index => $competencia)
                @php
                    $angle = ($index * $angleStep) - 90;
                    $angleRad = deg2rad($angle);
                    $endX = $centerX + cos($angleRad) * $maxRadius;
                    $endY = $centerY + sin($angleRad) * $maxRadius;
                @endphp
                <line x1="{{ $centerX }}" y1="{{ $centerY }}" x2="{{ $endX }}" y2="{{ $endY }}" 
                      stroke="#e5e7eb" stroke-width="1" opacity="0.4"/>
            @endforeach

            <!-- Scale numbers -->
            @for ($i = 1; $i <= 5; $i++)
                <text x="{{ $centerX + 5 }}" y="{{ $centerY - (($i/5) * $maxRadius) + 4 }}" 
                      font-size="10" fill="#6b7280">{{ $i * 2 }}</text>
            @endfor

            <!-- Data polygon -->
            <polygon points="{{ implode(' ', $points) }}" 
                     fill="url(#radarFill)" 
                     stroke="#6366f1" 
                     stroke-width="2"/>

            <!-- Data points and labels -->
            @foreach ($competencias as $index => $competencia)
                @php
                    $angle = ($index * $angleStep) - 90;
                    $angleRad = deg2rad($angle);
                    $radius = ($competencia['puntaje'] / $maxScore) * $maxRadius;
                    $x = $centerX + cos($angleRad) * $radius;
                    $y = $centerY + sin($angleRad) * $radius;
                    
                    $labelRadius = $maxRadius + 25;
                    $labelX = $centerX + cos($angleRad) * $labelRadius;
                    $labelY = $centerY + sin($angleRad) * $labelRadius;
                    
                    $textAnchor = 'middle';
                    if ($labelX > $centerX + 3) $textAnchor = 'start';
                    elseif ($labelX < $centerX - 3) $textAnchor = 'end';
                @endphp
                
                <!-- Point -->
                <circle cx="{{ $x }}" cy="{{ $y }}" r="3" fill="#6366f1" stroke="white" stroke-width="2"/>
                
                <!-- Score -->
                <text x="{{ $x + cos($angleRad) * 12 }}" y="{{ $y + sin($angleRad) * 12 }}" 
                      font-size="9" font-weight="bold" fill="#4338ca" text-anchor="middle">
                    {{ $competencia['puntaje'] }}
                </text>
                
                <!-- Label -->
                <text x="{{ $labelX }}" y="{{ $labelY }}" 
                      font-size="10" font-weight="500" fill="#374151" 
                      text-anchor="{{ $textAnchor }}">
                    {{ $competencia['nombre'] }}
                </text>
            @endforeach
        </svg>
    </div>
</body>
</html>