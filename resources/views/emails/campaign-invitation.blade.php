<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Habilidades Blandas</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #2c3e50;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .header {
            background-color: #ffffff;
            padding: 40px 40px 20px 40px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }
        .company-logo {
            max-width: 180px;
            max-height: 80px;
            margin-bottom: 30px;
        }
        .main-title {
            font-size: 32px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 30px 0;
            letter-spacing: -0.5px;
        }
        .content {
            padding: 40px;
            background-color: #ffffff;
        }
        .greeting {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 25px;
        }
        .intro-text {
            font-size: 15px;
            color: #5a6c7d;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .company-name {
            font-weight: 600;
            color: #2c3e50;
        }
        .highlight-text {
            font-size: 15px;
            color: #5a6c7d;
            line-height: 1.7;
            margin: 20px 0;
        }
        .tech-mention {
            font-size: 15px;
            color: #5a6c7d;
            margin: 20px 0;
        }
        .tech-name {
            font-weight: 600;
            color: #667eea;
        }
        .time-info {
            font-weight: 600;
            color: #2c3e50;
        }
        .cta-container {
            text-align: center;
            margin: 35px 0;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #11B981 100%);
            color: white;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .deadline-text {
            font-size: 14px;
            color: #7a8a9a;
            margin: 25px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            text-align: center;
        }
        .deadline-date {
            font-weight: 600;
            color: #2c3e50;
        }
        .signature {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid #e9ecef;
        }
        .signature-text {
            font-size: 14px;
            color: #5a6c7d;
            margin-bottom: 5px;
        }
        .signature-company {
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer-logo {
            max-width: 120px;
            margin-bottom: 10px;
            opacity: 0.8;
        }
        .footer-text {
            font-size: 11px;
            color: #95a5b5;
            margin-top: 10px;
        }
        .alternative-link {
            font-size: 13px;
            color: #7a8a9a;
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            word-break: break-all;
        }
        .alternative-link a {
            color: #667eea;
            text-decoration: none;
        }
        @media (max-width: 600px) {
            .container {
                margin: 0;
                border-radius: 0;
            }
            .header, .content, .footer {
                padding: 30px 20px;
            }
            .main-title {
                font-size: 26px;
            }
            .cta-button {
                padding: 14px 30px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header con logo de la empresa -->
        <div class="header">
            @if($campaign->company->logo_url)
                <img src="{{ config('app.url') . Storage::url($campaign->company->logo_url) }}" 
                     alt="{{ $campaign->company->name }}" 
                     class="company-logo">
            @else
                <div style="font-size: 24px; font-weight: 600; color: #2c3e50; margin-bottom: 30px;">
                    {{ $campaign->company->name }}
                </div>
            @endif
            
            <h1 class="main-title">Potencia tu futuro profesional</h1>
        </div>

        <!-- Contenido principal -->
        <div class="content">
            <div class="greeting">
                Hola, {{ $invitation->name ?: 'Participante' }}
            </div>
            
            <p class="intro-text">
                En <span class="company-name">{{ $campaign->company->name }}</span>, estamos comprometidos con tu 
                desarrollo integral. Por eso, te invitamos a realizar un diagnóstico de 
                habilidades blandas, una herramienta clave para que identifiques tus 
                fortalezas y áreas de oportunidad de cara al mercado laboral.
            </p>

            <p class="highlight-text">
                Conocer y potenciar competencias como la comunicación, el trabajo en 
                equipo y la resolución de problemas es fundamental para tu éxito 
                profesional.
            </p>

            <p class="tech-mention">
                Este test, potenciado por la tecnología de <span class="tech-name">Re-skilling.ai</span>, te tomará 
                aproximadamente <span class="time-info">15 minutos</span>. Los resultados te ofrecerán una guía 
                valiosa para tu crecimiento.
            </p>

            <div class="cta-container">
                <a href="{{ $invitationUrl }}" class="cta-button">
                    Realizar mi Test Ahora
                </a>
            </div>

            @if($campaign->active_until)
            <div class="deadline-text">
                Te recomendamos completarlo antes del <span class="deadline-date">{{ $campaign->active_until->format('d/m/Y H:i') }}</span> para 
                aprovechar al máximo esta oportunidad.
            </div>
            @endif

            <div class="alternative-link">
                Si el botón no funciona, puedes copiar y pegar este enlace en tu navegador:<br>
                <a href="{{ $invitationUrl }}">{{ $invitationUrl }}</a>
            </div>

            <div class="signature">
                <div class="signature-text">Atentamente,</div>
                <div class="signature-company">{{ $campaign->company->name }}</div>
            </div>
        </div>

        <!-- Footer con logo de Re-Skilling -->
        <div class="footer">
            <img src="{{ config('app.url') }}/images/reskiling-logo.png" 
                 alt="Re-Skilling.ai" 
                 class="footer-logo">
            <div class="footer-text">
                Powered by Re-Skilling.ai - Sistema de Análisis de Competencias
            </div>
        </div>
    </div>
</body>
</html>