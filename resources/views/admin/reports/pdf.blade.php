<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte - {{ $response->respondent_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .header .info {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section h2 {
            color: #007bff;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 5px;
            font-size: 18px;
        }
        .section h3 {
            color: #495057;
            font-size: 16px;
            margin-bottom: 10px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 15px 5px 0;
            width: 30%;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .question-block {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 15px 0;
        }
        .question-text {
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
        .answer-text {
            background-color: #d1edff;
            padding: 10px;
            border-radius: 5px;
            font-style: italic;
        }
        .report-content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .status-badge {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte Psicológico Profesional</h1>
        <div class="info">
            <strong>Respondente:</strong> {{ $response->respondent_name }}<br>
            <strong>Empresa:</strong> {{ $response->campaign->company->name }}<br>
            <strong>Campaña:</strong> {{ $response->campaign->name }}<br>
            <strong>Fecha de generación:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="section">
        <h2>Información del Participante</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nombre:</div>
                <div class="info-value">{{ $response->respondent_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">{{ $response->respondent_email }}</div>
            </div>
            @if($response->respondent_age)
            <div class="info-row">
                <div class="info-label">Edad:</div>
                <div class="info-value">{{ $response->respondent_age }} años</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Fecha de respuesta:</div>
                <div class="info-value">{{ $response->created_at->format('d/m/Y H:i') }}</div>
            </div>
            @if($response->duration_minutes)
            <div class="info-row">
                <div class="info-label">Duración del cuestionario:</div>
                <div class="info-value">{{ $response->duration_minutes }} minutos</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value">
                    <span class="status-badge">{{ ucfirst($response->processing_status) }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($response->questionnaire && ($response->raw_responses || $response->processed_responses))
        <div class="section">
            <h2>Respuestas del Cuestionario</h2>
            @php
                $questions = $response->questionnaire->questions ?? [];
                $responses = $response->processed_responses ?? $response->raw_responses ?? [];
            @endphp
            
            @foreach($questions as $questionId => $questionData)
                @if(isset($responses[$questionId]))
                    <div class="question-block">
                        <div class="question-text">
                            Pregunta {{ strtoupper($questionId) }}: {{ is_array($questionData) ? ($questionData['text'] ?? $questionData) : $questionData }}
                        </div>
                        @php
                            $responseText = '';
                            if ($response->transcriptions && isset($response->transcriptions[$questionId])) {
                                $responseText = $response->transcriptions[$questionId];
                            } else {
                                $responseData = $responses[$questionId];
                                if (is_array($responseData)) {
                                    $responseText = $responseData['transcription'] ?? 
                                                   $responseData['raw_response'] ?? 
                                                   (isset($responseData['processed_response']['text']) ? 
                                                       $responseData['processed_response']['text'] : 
                                                       'No se encontró transcripción');
                                } else {
                                    $responseText = $responseData;
                                }
                            }
                        @endphp
                        <div class="answer-text">
                            <strong>{{ $response->respondent_name }}:</strong> "{{ $responseText }}"
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    @if($response->comprehensive_report)
        <div class="section">
            <h2>Análisis Psicológico Profesional</h2>
            <div class="report-content">
                @if(isset($response->comprehensive_report['sections']) && count($response->comprehensive_report['sections']) > 0)
                    @foreach($response->comprehensive_report['sections'] as $section)
                        <h3>{{ $section['title'] }}</h3>
                        <div>{!! nl2br(e($section['content'])) !!}</div>
                        <br>
                    @endforeach
                @else
                    {!! nl2br(e($response->comprehensive_report['content'] ?? 'Reporte no disponible')) !!}
                @endif
            </div>
        </div>
    @endif

    <div class="footer">
        <p>Este reporte fue generado automáticamente el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</p>
        <p>Sistema de Análisis Psicológico - {{ $response->campaign->company->name }}</p>
    </div>
</body>
</html>