@php
    // Example competencies data - replace with your actual data
    $competencias = $competencias ?? [
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

    $totalCompetencias = count($competencias);
    $centerX = 200;
    $centerY = 200;
    $maxRadius = 150;
    $maxScore = 10;

    // Calculate angles for each competency (starting from top and going clockwise)
    $angleStep = 360 / $totalCompetencias;
    $points = [];
    $labelPoints = [];

    foreach ($competencias as $index => $competencia) {
        $angle = ($index * $angleStep) - 90; // Start from top (-90 degrees)
        $angleRad = deg2rad($angle);
        
        // Calculate point position based on score
        $radius = ($competencia['puntaje'] / $maxScore) * $maxRadius;
        $x = $centerX + cos($angleRad) * $radius;
        $y = $centerY + sin($angleRad) * $radius;
        $points[] = "$x,$y";
        
        // Calculate label position (outside the chart)
        $labelRadius = $maxRadius + 40;
        $labelX = $centerX + cos($angleRad) * $labelRadius;
        $labelY = $centerY + sin($angleRad) * $labelRadius;
        
        // Calculate text anchor based on position
        $textAnchor = 'middle';
        if ($labelX > $centerX + 5) {
            $textAnchor = 'start';
        } elseif ($labelX < $centerX - 5) {
            $textAnchor = 'end';
        }
        
        $labelPoints[] = [
            'x' => $labelX,
            'y' => $labelY,
            'nombre' => $competencia['nombre'],
            'puntaje' => $competencia['puntaje'],
            'anchor' => $textAnchor,
            'angle' => $angle
        ];
    }

    $polygonPoints = implode(' ', $points);
@endphp

<svg width="400" height="400" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
    <!-- Definitions for gradients and patterns -->
    <defs>
        <!-- Gradient fill for the radar chart -->
        <radialGradient id="radarGradient" cx="50%" cy="50%" r="50%">
            <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:0.8" />
            <stop offset="100%" style="stop-color:#6366f1;stop-opacity:0.3" />
        </radialGradient>
        
        <!-- Gradient for the stroke -->
        <linearGradient id="strokeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#6366f1" />
            <stop offset="100%" style="stop-color:#8b5cf6" />
        </linearGradient>
    </defs>

    <!-- Background circles (scale lines) -->
    @for ($i = 1; $i <= 4; $i++)
        @php
            $radius = ($i / 4) * $maxRadius;
        @endphp
        <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="{{ $radius }}" 
                fill="none" stroke="#e5e7eb" stroke-width="1" opacity="0.5"/>
    @endfor

    <!-- Outer border circle -->
    <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="{{ $maxRadius }}" 
            fill="none" stroke="#d1d5db" stroke-width="2"/>

    <!-- Radial lines from center to each competency -->
    @foreach ($labelPoints as $index => $label)
        @php
            $angle = ($index * $angleStep) - 90;
            $angleRad = deg2rad($angle);
            $endX = $centerX + cos($angleRad) * $maxRadius;
            $endY = $centerY + sin($angleRad) * $maxRadius;
        @endphp
        <line x1="{{ $centerX }}" y1="{{ $centerY }}" x2="{{ $endX }}" y2="{{ $endY }}" 
              stroke="#e5e7eb" stroke-width="1" opacity="0.5"/>
    @endforeach

    <!-- Scale labels (0, 2.5, 5, 7.5, 10) -->
    @for ($i = 1; $i <= 4; $i++)
        @php
            $scaleValue = ($i / 4) * $maxScore;
            $radius = ($i / 4) * $maxRadius;
        @endphp
        <text x="{{ $centerX + 5 }}" y="{{ $centerY - $radius + 5 }}" 
              font-family="Arial, sans-serif" font-size="10" fill="#6b7280" text-anchor="start">
            {{ $scaleValue }}
        </text>
    @endfor

    <!-- Data polygon with gradient fill -->
    <polygon points="{{ $polygonPoints }}" 
             fill="url(#radarGradient)" 
             stroke="url(#strokeGradient)" 
             stroke-width="2"/>

    <!-- Data points -->
    @foreach ($competencias as $index => $competencia)
        @php
            $angle = ($index * $angleStep) - 90;
            $angleRad = deg2rad($angle);
            $radius = ($competencia['puntaje'] / $maxScore) * $maxRadius;
            $pointX = $centerX + cos($angleRad) * $radius;
            $pointY = $centerY + sin($angleRad) * $radius;
        @endphp
        <circle cx="{{ $pointX }}" cy="{{ $pointY }}" r="4" 
                fill="#6366f1" stroke="white" stroke-width="2"/>
        
        <!-- Score value near each point -->
        @php
            $scoreX = $pointX + cos($angleRad) * 15;
            $scoreY = $pointY + sin($angleRad) * 15;
        @endphp
        <text x="{{ $scoreX }}" y="{{ $scoreY }}" 
              font-family="Arial, sans-serif" font-size="10" font-weight="bold" 
              fill="#374151" text-anchor="middle" dominant-baseline="middle">
            {{ $competencia['puntaje'] }}
        </text>
    @endforeach

    <!-- Competency labels around the perimeter -->
    @foreach ($labelPoints as $label)
        @php
            // Adjust y position based on text anchor for better alignment
            $adjustedY = $label['y'];
            if (abs($label['x'] - $centerX) < 10) { // Near vertical center
                $adjustedY += ($label['y'] > $centerY) ? 5 : -5;
            }
        @endphp
        <text x="{{ $label['x'] }}" y="{{ $adjustedY }}" 
              font-family="Arial, sans-serif" font-size="11" font-weight="500" 
              fill="#374151" text-anchor="{{ $label['anchor'] }}" dominant-baseline="middle">
            {{ $label['nombre'] }}
        </text>
    @endforeach

    <!-- Center point -->
    <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="3" fill="#6b7280"/>

    <!-- Title (optional) -->
    <text x="{{ $centerX }}" y="20" 
          font-family="Arial, sans-serif" font-size="16" font-weight="bold" 
          fill="#1f2937" text-anchor="middle">
        Evaluación de Competencias
    </text>
</svg>