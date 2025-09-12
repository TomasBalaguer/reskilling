@props(['competencias' => [], 'width' => 400, 'height' => 400, 'title' => 'Evaluación de Competencias'])

@php
    $totalCompetencias = count($competencias);
    if ($totalCompetencias === 0) {
        $competencias = collect(range(1, 15))->map(fn($i) => [
            'nombre' => "Competencia $i",
            'puntaje' => rand(5, 10)
        ])->toArray();
        $totalCompetencias = 15;
    }

    $centerX = $width / 2;
    $centerY = $height / 2;
    $maxRadius = min($width, $height) * 0.3; // 30% of the smaller dimension
    $maxScore = 10;

    // Calculate points and labels
    $angleStep = 360 / $totalCompetencias;
    $dataPoints = [];
    $labelData = [];

    foreach ($competencias as $index => $competencia) {
        $angle = ($index * $angleStep) - 90; // Start from top
        $angleRad = deg2rad($angle);
        
        // Data point
        $radius = ($competencia['puntaje'] / $maxScore) * $maxRadius;
        $x = $centerX + cos($angleRad) * $radius;
        $y = $centerY + sin($angleRad) * $radius;
        $dataPoints[] = "$x,$y";
        
        // Label positioning
        $labelRadius = $maxRadius + ($width * 0.08); // Dynamic spacing
        $labelX = $centerX + cos($angleRad) * $labelRadius;
        $labelY = $centerY + sin($angleRad) * $labelRadius;
        
        // Text anchor based on position
        $textAnchor = 'middle';
        if ($labelX > $centerX + 3) $textAnchor = 'start';
        elseif ($labelX < $centerX - 3) $textAnchor = 'end';
        
        $labelData[] = [
            'x' => $labelX,
            'y' => $labelY,
            'dataX' => $x,
            'dataY' => $y,
            'nombre' => $competencia['nombre'],
            'puntaje' => $competencia['puntaje'],
            'anchor' => $textAnchor,
            'radius' => $radius
        ];
    }
@endphp

<div class="spider-chart-container" style="display: flex; justify-content: center; align-items: center;">
    <svg width="{{ $width }}" height="{{ $height }}" viewBox="0 0 {{ $width }} {{ $height }}" 
         xmlns="http://www.w3.org/2000/svg" style="max-width: 100%; height: auto;">
        
        <!-- Definitions -->
        <defs>
            <!-- Main gradient for filled area -->
            <radialGradient id="chartGradient-{{ uniqid() }}" cx="50%" cy="50%" r="60%">
                <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:0.7" />
                <stop offset="70%" style="stop-color:#6366f1;stop-opacity:0.4" />
                <stop offset="100%" style="stop-color:#6366f1;stop-opacity:0.2" />
            </radialGradient>
            
            <!-- Stroke gradient -->
            <linearGradient id="strokeGradient-{{ uniqid() }}" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#6366f1" />
                <stop offset="50%" style="stop-color:#7c3aed" />
                <stop offset="100%" style="stop-color:#8b5cf6" />
            </linearGradient>
            
            <!-- Drop shadow filter -->
            <filter id="dropShadow-{{ uniqid() }}" x="-20%" y="-20%" width="140%" height="140%">
                <feDropShadow dx="2" dy="2" stdDeviation="2" flood-color="#000000" flood-opacity="0.1"/>
            </filter>
        </defs>

        <!-- Background circles (grid) -->
        @for ($level = 1; $level <= 5; $level++)
            @php
                $radius = ($level / 5) * $maxRadius;
                $opacity = $level === 5 ? 0.8 : 0.3;
                $strokeWidth = $level === 5 ? 1.5 : 0.8;
            @endphp
            <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="{{ $radius }}" 
                    fill="none" 
                    stroke="{{ $level === 5 ? '#d1d5db' : '#e5e7eb' }}" 
                    stroke-width="{{ $strokeWidth }}" 
                    opacity="{{ $opacity }}"/>
        @endfor

        <!-- Radial guide lines -->
        @foreach ($labelData as $index => $label)
            @php
                $angle = ($index * $angleStep) - 90;
                $angleRad = deg2rad($angle);
                $endX = $centerX + cos($angleRad) * $maxRadius;
                $endY = $centerY + sin($angleRad) * $maxRadius;
            @endphp
            <line x1="{{ $centerX }}" y1="{{ $centerY }}" 
                  x2="{{ $endX }}" y2="{{ $endY }}" 
                  stroke="#e5e7eb" stroke-width="0.8" opacity="0.4"/>
        @endforeach

        <!-- Scale labels -->
        @for ($level = 1; $level <= 5; $level++)
            @php
                $scaleValue = $level * 2; // 2, 4, 6, 8, 10
                $radius = ($level / 5) * $maxRadius;
                $fontSize = max(8, $width * 0.02);
            @endphp
            <text x="{{ $centerX + 6 }}" y="{{ $centerY - $radius + 4 }}" 
                  font-family="system-ui, -apple-system, sans-serif" 
                  font-size="{{ $fontSize }}" 
                  font-weight="500"
                  fill="#6b7280" 
                  text-anchor="start">{{ $scaleValue }}</text>
        @endfor

        <!-- Main data polygon -->
        <polygon points="{{ implode(' ', $dataPoints) }}" 
                 fill="url(#chartGradient-{{ uniqid() }})" 
                 stroke="url(#strokeGradient-{{ uniqid() }})" 
                 stroke-width="2.5"
                 filter="url(#dropShadow-{{ uniqid() }})"
                 opacity="0.9"/>

        <!-- Data points -->
        @foreach ($labelData as $point)
            <!-- Outer glow circle -->
            <circle cx="{{ $point['dataX'] }}" cy="{{ $point['dataY'] }}" r="6" 
                    fill="#6366f1" opacity="0.3"/>
            <!-- Main point -->
            <circle cx="{{ $point['dataX'] }}" cy="{{ $point['dataY'] }}" r="3.5" 
                    fill="#6366f1" stroke="white" stroke-width="2"/>
        @endforeach

        <!-- Score values near points -->
        @foreach ($labelData as $point)
            @php
                $angle = atan2($point['dataY'] - $centerY, $point['dataX'] - $centerX);
                $scoreX = $point['dataX'] + cos($angle) * 12;
                $scoreY = $point['dataY'] + sin($angle) * 12;
                $fontSize = max(9, $width * 0.022);
            @endphp
            <!-- Score background -->
            <circle cx="{{ $scoreX }}" cy="{{ $scoreY }}" r="8" 
                    fill="white" stroke="#6366f1" stroke-width="1" opacity="0.9"/>
            <!-- Score text -->
            <text x="{{ $scoreX }}" y="{{ $scoreY }}" 
                  font-family="system-ui, -apple-system, sans-serif" 
                  font-size="{{ $fontSize }}" 
                  font-weight="bold" 
                  fill="#4338ca" 
                  text-anchor="middle" 
                  dominant-baseline="central">{{ $point['puntaje'] }}</text>
        @endforeach

        <!-- Competency labels -->
        @foreach ($labelData as $label)
            @php
                $fontSize = max(9, $width * 0.024);
                // Adjust Y position for better text alignment
                $adjustedY = $label['y'];
                if (abs($label['x'] - $centerX) < 5) {
                    $adjustedY += ($label['y'] > $centerY) ? 4 : -4;
                }
            @endphp
            <!-- Label background for better readability -->
            <rect x="{{ $label['x'] - (strlen($label['nombre']) * ($fontSize * 0.3)) }}" 
                  y="{{ $adjustedY - ($fontSize * 0.6) }}" 
                  width="{{ strlen($label['nombre']) * ($fontSize * 0.6) }}" 
                  height="{{ $fontSize * 1.2 }}" 
                  fill="white" 
                  opacity="0.8" 
                  rx="2"/>
            <!-- Label text -->
            <text x="{{ $label['x'] }}" y="{{ $adjustedY }}" 
                  font-family="system-ui, -apple-system, sans-serif" 
                  font-size="{{ $fontSize }}" 
                  font-weight="600" 
                  fill="#1f2937" 
                  text-anchor="{{ $label['anchor'] }}" 
                  dominant-baseline="middle">{{ $label['nombre'] }}</text>
        @endforeach

        <!-- Center point -->
        <circle cx="{{ $centerX }}" cy="{{ $centerY }}" r="2" fill="#6b7280" opacity="0.7"/>

        <!-- Title -->
        @if($title)
            @php $titleFontSize = max(12, $width * 0.032); @endphp
            <text x="{{ $centerX }}" y="{{ $height * 0.06 }}" 
                  font-family="system-ui, -apple-system, sans-serif" 
                  font-size="{{ $titleFontSize }}" 
                  font-weight="700" 
                  fill="#1f2937" 
                  text-anchor="middle">{{ $title }}</text>
        @endif
    </svg>
</div>

<style>
.spider-chart-container {
    page-break-inside: avoid;
}
@media print {
    .spider-chart-container svg {
        max-width: none !important;
        width: {{ $width }}px !important;
        height: {{ $height }}px !important;
    }
}
</style>