# AWS S3 Configuration Guide

Este documento explica cómo configurar AWS S3 para el almacenamiento de archivos en el sistema de cuestionarios.

## 🚀 Configuración Inicial

### 1. Instalar dependencias AWS S3
```bash
composer require league/flysystem-aws-s3-v3
```

### 2. Configurar variables de entorno

Agrega estas variables a tu archivo `.env`:

```env
# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your_access_key_here
AWS_SECRET_ACCESS_KEY=your_secret_key_here
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false

# Set default filesystem to S3 for production
FILESYSTEM_DISK=s3
```

### 3. Ejecutar migraciones
```bash
php artisan migrate
```

## 📁 Estructura de archivos en S3

El sistema organiza los archivos de la siguiente manera:

```
your-bucket/
├── audio/
│   └── responses/
│       ├── audio_abc123_1234567890.mp3
│       ├── audio_def456_1234567891.wav
│       └── ...
├── images/
│   ├── general/
│   │   └── uploaded_images.jpg
│   └── companies/
│       └── {company_id}/
│           └── logo/
│               └── logo_123_1234567890.png
└── documents/
    └── reports/
        └── generated_reports.pdf
```

## 🔧 Discos de almacenamiento configurados

- **`s3`**: Almacenamiento privado general
- **`s3-public`**: Almacenamiento público general  
- **`audio-storage`**: Específico para archivos de audio (privado)
- **`images`**: Específico para imágenes (público)

## 🎵 Manejo de archivos de audio

### Subida automática
Los archivos de audio se suben automáticamente a S3 cuando los usuarios completan cuestionarios.

### Acceso a archivos
```php
// En el modelo CampaignResponse
$response = CampaignResponse::find(1);

// Obtener URLs firmadas para reproducir audio
$audioUrls = $response->audio_urls; // Array de URLs firmadas

// Descargar archivo para procesamiento
$tempFile = $response->downloadAudioForProcessing('audio_0');
```

### URLs firmadas
Los archivos de audio son privados y requieren URLs firmadas que expiran en 60 minutos por defecto.

## 🖼️ Manejo de imágenes

### Logos de empresas
```php
$company = Company::find(1);
$logoUrl = $company->logo_url; // URL pública del logo en S3
```

### Subir nueva imagen
```php
$fileStorage = new FileStorageService();
$s3Path = $fileStorage->uploadImage($file, 'folder_name');
$publicUrl = $fileStorage->getImageUrl($s3Path);
```

## 🔄 Migrar archivos existentes

Para migrar archivos locales existentes a S3:

```bash
# Migrar todos los archivos
php artisan files:migrate-to-s3

# Migrar solo audios
php artisan files:migrate-to-s3 --type=audio

# Migrar solo imágenes
php artisan files:migrate-to-s3 --type=images

# Forzar migración (incluso si ya tienen path S3)
php artisan files:migrate-to-s3 --force
```

## 🛡️ Seguridad

### Archivos privados
- Audios de respuestas: Acceso mediante URLs firmadas temporales
- Documentos confidenciales: Acceso controlado por la aplicación

### Archivos públicos
- Logos de empresas: Acceso directo público
- Imágenes generales: Acceso directo público

## 🔧 Servicios disponibles

### FileStorageService
Servicio principal para manejo de archivos:

```php
$fileStorage = new FileStorageService();

// Subir audio
$audioPath = $fileStorage->uploadAudio($file, 'responses');

// Subir imagen
$imagePath = $fileStorage->uploadImage($file, 'general');

// Obtener URL firmada de audio (60 min)
$url = $fileStorage->getAudioUrl($audioPath);

// Obtener URL pública de imagen
$url = $fileStorage->getImageUrl($imagePath);

// Descargar para procesamiento IA
$tempFile = $fileStorage->downloadAudioForProcessing($audioPath);
```

## 🚨 Troubleshooting

### Error de permisos AWS
```bash
# Verificar credenciales
aws sts get-caller-identity

# Verificar acceso al bucket
aws s3 ls s3://your-bucket-name
```

### Archivos no se suben
1. Verificar variables de entorno
2. Comprobar permisos del bucket S3
3. Revisar logs: `tail -f storage/logs/laravel.log`

### URLs no funcionan
1. Para archivos privados: Verificar que uses URLs firmadas
2. Para archivos públicos: Verificar configuración de bucket público

## 📊 Monitoreo

### Verificar uso de S3
```bash
# Ver archivos en S3
aws s3 ls s3://your-bucket-name --recursive

# Ver tamaño total
aws s3api list-objects-v2 --bucket your-bucket-name --query 'sum(Contents[].Size)' --output text
```

### Logs importantes
- Errores de subida: `storage/logs/laravel.log`
- Procesamiento de audio: Queue logs
- Migraciones: Command output

---

## 🆘 Soporte

Para problemas o preguntas sobre la configuración S3, revisar:
1. Este documento
2. Logs de Laravel
3. Configuración de AWS IAM
4. Políticas del bucket S3