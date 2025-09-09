<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileStorageService
{
    /**
     * Upload audio file to S3
     */
    public function uploadAudio(UploadedFile $file, string $folder = 'responses'): string
    {
        // Check if S3 is properly configured
        if (!$this->isS3Configured()) {
            throw new \Exception('S3 configuration is missing. Please check AWS environment variables.');
        }

        $extension = $file->getClientOriginalExtension();
        
        // If no extension, try to guess from mime type
        if (empty($extension)) {
            $mimeType = $file->getMimeType();
            if (str_contains($mimeType, 'webm')) {
                $extension = 'webm';
            } elseif (str_contains($mimeType, 'mp3')) {
                $extension = 'mp3';
            } elseif (str_contains($mimeType, 'wav')) {
                $extension = 'wav';
            } elseif (str_contains($mimeType, 'ogg')) {
                $extension = 'ogg';
            } else {
                $extension = 'webm'; // default for web recordings
            }
        }
        
        $filename = Str::uuid() . '_' . time() . '.' . $extension;
        $path = $folder . '/' . $filename;
        
        Storage::disk('audio-storage')->put($path, file_get_contents($file));
        
        return $path;
    }

    /**
     * Upload image file to S3
     */
    public function uploadImage(UploadedFile $file, string $folder = 'general'): string
    {
        $filename = Str::uuid() . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $folder . '/' . $filename;
        
        Storage::disk('images')->put($path, file_get_contents($file));
        
        return $path;
    }

    /**
     * Upload company logo
     */
    public function uploadCompanyLogo(UploadedFile $file, int $companyId): string
    {
        return $this->uploadImage($file, "companies/{$companyId}/logo");
    }

    /**
     * Get audio file URL (signed URL for private files)
     */
    public function getAudioUrl(string $path, int $expirationMinutes = 60): string
    {
        // El disco audio-storage ya tiene root => 'audio', no necesitamos prefijo adicional
        return Storage::disk('audio-storage')->temporaryUrl($path, now()->addMinutes($expirationMinutes));
    }

    /**
     * Get image URL (public URL)
     */
    public function getImageUrl(string $path): string
    {
        return Storage::disk('images')->url($path);
    }

    /**
     * Get company logo URL
     */
    public function getCompanyLogoUrl(string $path): string
    {
        return $this->getImageUrl($path);
    }

    /**
     * Delete audio file
     */
    public function deleteAudio(string $path): bool
    {
        return Storage::disk('audio-storage')->delete($path);
    }

    /**
     * Delete image file
     */
    public function deleteImage(string $path): bool
    {
        return Storage::disk('images')->delete($path);
    }

    /**
     * Download audio file for processing
     */
    public function downloadAudioForProcessing(string $path): string
    {
        // El disco audio-storage ya tiene root => 'audio', no necesitamos prefijo adicional
        \Log::info('📥 Descargando audio desde S3', [
            'path' => $path,
            'disk' => 'audio-storage (root: audio/)'
        ]);
        
        // Verificar que el archivo existe
        if (!Storage::disk('audio-storage')->exists($path)) {
            \Log::error('❌ Archivo de audio no existe en S3', [
                'path' => $path,
                'disk' => 'audio-storage',
                'full_s3_path' => 'audio/' . $path
            ]);
            throw new \Exception("Audio file not found in S3: {$path}");
        }
        
        $tempFile = sys_get_temp_dir() . '/' . Str::uuid() . '.tmp';
        file_put_contents($tempFile, Storage::disk('audio-storage')->get($path));
        
        \Log::info('✅ Audio descargado exitosamente', [
            'path' => $path,
            'temp_file' => $tempFile,
            'file_size' => filesize($tempFile)
        ]);
        
        return $tempFile;
    }

    /**
     * Check if audio file exists
     */
    public function audioExists(string $path): bool
    {
        return Storage::disk('audio-storage')->exists($path);
    }

    /**
     * Check if image file exists
     */
    public function imageExists(string $path): bool
    {
        return Storage::disk('images')->exists($path);
    }

    /**
     * Get file size
     */
    public function getAudioSize(string $path): int
    {
        return Storage::disk('audio-storage')->size($path);
    }

    /**
     * Get image size
     */
    public function getImageSize(string $path): int
    {
        return Storage::disk('images')->size($path);
    }

    /**
     * Check if S3 is properly configured
     */
    private function isS3Configured(): bool
    {
        $bucket = config('filesystems.disks.audio-storage.bucket');
        $key = config('filesystems.disks.audio-storage.key');
        $secret = config('filesystems.disks.audio-storage.secret');
        $region = config('filesystems.disks.audio-storage.region');

        return !empty($bucket) && !empty($key) && !empty($secret) && !empty($region);
    }
}