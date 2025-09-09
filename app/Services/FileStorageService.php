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
        $tempFile = sys_get_temp_dir() . '/' . Str::uuid() . '.tmp';
        file_put_contents($tempFile, Storage::disk('audio-storage')->get($path));
        
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
}