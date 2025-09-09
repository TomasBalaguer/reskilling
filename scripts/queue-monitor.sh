#!/bin/bash

# Script para monitorear queues en Laravel Cloud

echo "🚀 Monitoreando Queues en Laravel Cloud"
echo "======================================"

echo ""
echo "📊 Estado actual de las colas:"
php artisan queue:status

echo ""
echo "❌ Jobs fallidos:"
php artisan queue:failed

echo ""
echo "🔄 Jobs procesándose:"
php artisan queue:work --once --verbose

echo ""
echo "📈 Estadísticas de la base de datos:"
php artisan tinker --execute="
use Illuminate\\Support\\Facades\\DB;
echo 'Jobs pendientes: ' . DB::table('jobs')->count() . PHP_EOL;
echo 'Jobs fallidos: ' . DB::table('failed_jobs')->count() . PHP_EOL;
echo 'Respuestas pendientes: ' . App\\Models\\CampaignResponse::where('processing_status', 'pending')->count() . PHP_EOL;
echo 'Respuestas procesando: ' . App\\Models\\CampaignResponse::where('processing_status', 'processing')->count() . PHP_EOL;
echo 'Respuestas completadas: ' . App\\Models\\CampaignResponse::where('processing_status', 'completed')->count() . PHP_EOL;
"