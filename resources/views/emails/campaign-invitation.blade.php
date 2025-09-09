<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación a Participar</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .logo-section {
            margin-bottom: 30px;
            text-align: center;
        }
        .company-logo {
            max-width: 120px;
            max-height: 60px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .powered-by {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
            margin-top: 15px;
        }
        .neurografy-logo {
            max-width: 80px;
            max-height: 30px;
            vertical-align: middle;
            margin-left: 5px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .content {
            padding: 40px 30px;
        }
        .campaign-info {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 4px solid #667eea;
        }
        .campaign-name {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .campaign-description {
            color: #666;
            font-size: 14px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
            margin: 25px 0;
            transition: transform 0.2s;
        }
        .cta-button:hover {
            transform: translateY(-2px);
        }
        .questionnaires-list {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .questionnaire-item {
            margin: 10px 0;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .questionnaire-item:last-child {
            border-bottom: none;
        }
        .questionnaire-name {
            font-weight: bold;
            color: #333;
        }
        .questionnaire-type {
            font-size: 12px;
            background-color: #667eea;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
            margin-top: 5px;
        }
        .footer {
            background-color: #2c3e50;
            color: #ecf0f1;
            text-align: center;
            padding: 30px;
            font-size: 14px;
        }
        .footer a {
            color: #3498db;
            text-decoration: none;
        }
        .important-note {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
        }
        @media (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .header, .content, .footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header with logos -->
        <div class="header">
            <div class="logo-section">
                @if($campaign->company->logo_url)
                    <img src="{{ config('app.url') . Storage::url($campaign->company->logo_url) }}" 
                         alt="{{ $campaign->company->name }}" 
                         class="company-logo">
                @endif
                
                <div class="powered-by">
                    Powered by 
                    <img src="{{ config('app.url') }}/images/neurografy-logo-white.png" 
                         alt="Neurografy" 
                         class="neurografy-logo">
                </div>
            </div>
            
            <h1>¡Estás Invitado!</h1>
            <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">
                {{ $campaign->company->name }} te invita a participar en una evaluación importante
            </p>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Hola{{ $invitation->name ? ' ' . $invitation->name : '' }},</p>
            
            <p>Has sido invitado/a a participar en la siguiente campaña de evaluación:</p>

            <div class="campaign-info">
                <div class="campaign-name">{{ $campaign->name }}</div>
                @if($campaign->description)
                    <div class="campaign-description">{{ $campaign->description }}</div>
                @endif
            </div>

            @if($campaign->questionnaires->count() > 0)
                <h3>Cuestionarios a completar:</h3>
                <div class="questionnaires-list">
                    @foreach($campaign->questionnaires as $questionnaire)
                        <div class="questionnaire-item">
                            <div class="questionnaire-name">{{ $questionnaire->name }}</div>
                            @if($questionnaire->description)
                                <div style="font-size: 14px; color: #666; margin: 5px 0;">
                                    {{ $questionnaire->description }}
                                </div>
                            @endif
                            <span class="questionnaire-type">
                                {{ $questionnaire->getQuestionnaireType()->getDisplayName() }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="important-note">
                <strong>Importante:</strong> Esta invitación es personal e intransferible. 
                La campaña estará disponible desde el {{ $campaign->active_from->format('d/m/Y H:i') }} 
                hasta el {{ $campaign->active_until->format('d/m/Y H:i') }}.
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ $invitationUrl }}" class="cta-button">
                    Comenzar Evaluación
                </a>
            </div>

            <p style="font-size: 14px; color: #666;">
                Si el botón no funciona, puedes copiar y pegar este enlace en tu navegador:<br>
                <a href="{{ $invitationUrl }}" style="color: #667eea; word-break: break-all;">{{ $invitationUrl }}</a>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Este email fue enviado por {{ $campaign->company->name }}</p>
            <p style="font-size: 12px; opacity: 0.8;">
                Sistema de evaluación powered by <a href="https://neurografy.com">Neurografy</a>
            </p>
        </div>
    </div>
</body>
</html>