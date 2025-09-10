<?php

/**
 * Script de prueba para verificar la API de Gemini con audio
 * Ejecutar con: php test_gemini_audio.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuración
$apiKey = $_ENV['GOOGLE_API_KEY'] ?? '';
$model = 'models/gemini-1.5-flash'; // Asegurar que tiene el prefijo models/

if (empty($apiKey)) {
    die("ERROR: No se encontró GOOGLE_API_KEY en el archivo .env\n");
}

echo "========================================\n";
echo "PRUEBA DE GEMINI API CON AUDIO\n";
echo "========================================\n\n";

// Crear un archivo de audio de prueba simple (WAV con un tono)
function createTestWavFile($filename) {
    $sampleRate = 44100;
    $duration = 2; // 2 segundos
    $frequency = 440; // A4
    $amplitude = 0.3;
    
    $numSamples = $sampleRate * $duration;
    $samples = [];
    
    // Generar onda sinusoidal
    for ($i = 0; $i < $numSamples; $i++) {
        $t = $i / $sampleRate;
        $samples[] = (int)($amplitude * 32767 * sin(2 * M_PI * $frequency * $t));
    }
    
    // Crear archivo WAV
    $fp = fopen($filename, 'wb');
    
    // RIFF header
    fwrite($fp, 'RIFF');
    fwrite($fp, pack('V', 36 + $numSamples * 2)); // File size - 8
    fwrite($fp, 'WAVE');
    
    // fmt chunk
    fwrite($fp, 'fmt ');
    fwrite($fp, pack('V', 16)); // Chunk size
    fwrite($fp, pack('v', 1)); // Audio format (PCM)
    fwrite($fp, pack('v', 1)); // Channels (mono)
    fwrite($fp, pack('V', $sampleRate)); // Sample rate
    fwrite($fp, pack('V', $sampleRate * 2)); // Byte rate
    fwrite($fp, pack('v', 2)); // Block align
    fwrite($fp, pack('v', 16)); // Bits per sample
    
    // data chunk
    fwrite($fp, 'data');
    fwrite($fp, pack('V', $numSamples * 2)); // Data size
    
    // Write samples
    foreach ($samples as $sample) {
        fwrite($fp, pack('v', $sample));
    }
    
    fclose($fp);
    
    echo "✅ Archivo WAV de prueba creado: $filename\n";
    echo "   - Duración: {$duration}s\n";
    echo "   - Frecuencia: {$frequency}Hz\n";
    echo "   - Sample rate: {$sampleRate}Hz\n\n";
}

// Crear archivo de prueba
$testFile = __DIR__ . '/test_audio.wav';
createTestWavFile($testFile);

// Leer y codificar el archivo
$audioContent = file_get_contents($testFile);
$audioData = base64_encode($audioContent);
$fileSizeMB = round(strlen($audioContent) / 1024 / 1024, 4);

echo "📊 Información del archivo:\n";
echo "   - Tamaño original: " . strlen($audioContent) . " bytes\n";
echo "   - Tamaño en MB: {$fileSizeMB} MB\n";
echo "   - Tamaño base64: " . strlen($audioData) . " bytes\n\n";

// Probar diferentes configuraciones
$tests = [
    [
        'name' => 'Test 1: camelCase (inlineData, mimeType)',
        'endpoint' => 'v1beta',
        'data' => [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inlineData' => [
                                'mimeType' => 'audio/wav',
                                'data' => $audioData
                            ]
                        ],
                        [
                            'text' => 'Describe este audio de prueba. Es un tono simple generado programáticamente.'
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 1024
            ]
        ]
    ],
    [
        'name' => 'Test 2: snake_case (inline_data, mime_type)',
        'endpoint' => 'v1beta',
        'data' => [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => 'audio/wav',
                                'data' => $audioData
                            ]
                        ],
                        [
                            'text' => 'Describe este audio de prueba. Es un tono simple generado programáticamente.'
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 1024
            ]
        ]
    ]
];

// Ejecutar pruebas
foreach ($tests as $test) {
    echo "========================================\n";
    echo "🧪 {$test['name']}\n";
    echo "========================================\n";
    
    $url = "https://generativelanguage.googleapis.com/{$test['endpoint']}/{$model}:generateContent?key={$apiKey}";
    
    echo "📍 URL: " . str_replace($apiKey, 'API_KEY_HIDDEN', $url) . "\n";
    echo "📦 Estructura JSON:\n";
    
    // Mostrar estructura sin el base64 completo
    $dataForDisplay = $test['data'];
    $dataForDisplay['contents'][0]['parts'][0][array_key_first($test['data']['contents'][0]['parts'][0])]['data'] = 'BASE64_DATA_TRUNCATED';
    echo json_encode($dataForDisplay, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "🚀 Enviando solicitud...\n";
    
    try {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "📡 Código de respuesta HTTP: {$httpCode}\n";
        
        if ($httpCode === 200) {
            echo "✅ ÉXITO! La solicitud fue procesada correctamente.\n";
            $responseData = json_decode($response, true);
            if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                echo "📝 Respuesta de Gemini:\n";
                echo $responseData['candidates'][0]['content']['parts'][0]['text'] . "\n";
            }
        } else {
            echo "❌ ERROR! La solicitud falló.\n";
            echo "📋 Respuesta completa:\n";
            $responseData = json_decode($response, true);
            echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            
            if (isset($responseData['error']['message'])) {
                echo "\n⚠️ Mensaje de error: " . $responseData['error']['message'] . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "💥 Excepción: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Limpiar archivo de prueba
unlink($testFile);
echo "🧹 Archivo de prueba eliminado.\n";

echo "\n========================================\n";
echo "PRUEBA COMPLETADA\n";
echo "========================================\n";