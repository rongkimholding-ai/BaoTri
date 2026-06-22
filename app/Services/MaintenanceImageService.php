<?php
namespace App\Services;

use App\Models\MaintenanceRequestImage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
class MaintenanceImageService
{
    public function upload(
        int $requestId,
        array $files,
        string $uploadedBy
    ): void {
        $manager = new ImageManager(new Driver());
    
        foreach ($files as $file) {
            try {
                $image = $manager->read($file);
                $image->scaleDown(
                    width: 1280,
                    height: 1280
                );
                $fileName =  Str::uuid() . '.jpg';
                $dateFolder = now()->format('Y_m_d');
                $path = 'images/' . $dateFolder . '/' . $fileName;
    
                $encodedImage = $image->encode(
                    new JpegEncoder(
                        quality: 70
                    )
                );
                
                Storage::disk('public')->put(
                    $path,
                    (string) $encodedImage
                );
    
                MaintenanceRequestImage::create([
                    'maintenance_request_id' => $requestId,
                    'path' => $path,
                    'uploaded_by' => $uploadedBy,
                ]);
    
            } catch (\Throwable $e) {
                Log::error('Upload maintenance image failed', [
                    'request_id' => $requestId,
                    'file_name' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function uploadFromTemp(
        int $requestId,
        array $tempFiles,
        string $uploadedBy
    ): void
    {
        $manager = new ImageManager(
            new Driver()
        );
    
        foreach ($tempFiles as $tempFile) {
    
            $filePath = Storage::path(
                'temp-maintenance/' . $tempFile
            );
    
            if (! file_exists($filePath)) {
                continue;
            }
    
            try {
    
                $image = $manager->read(
                    $filePath
                );
    
                $image->scaleDown(
                    width: 1280,
                    height: 1280
                );
    
                $fileName =
                    Str::uuid()
                    . '.jpg';
    
                $dateFolder =
                    now()->format('Y_m_d');
    
                $path =
                    'images/' .
                    $dateFolder .
                    '/' .
                    $fileName;
    
                $encodedImage =
                    $image->encode(
                        new JpegEncoder(
                            quality: 70
                        )
                    );
    
                Storage::disk('public')->put(
                    $path,
                    (string) $encodedImage
                );
    
                MaintenanceRequestImage::create([
                    'maintenance_request_id' => $requestId,
                    'path' => $path,
                    'uploaded_by' => $uploadedBy,
                ]);
    
                unlink($filePath);
    
            } catch (\Throwable $e) {
    
                Log::error(
                    'Upload maintenance image failed',
                    [
                        'request_id' => $requestId,
                        'file_name' => $tempFile,
                        'error' => $e->getMessage(),
                    ]
                );
                throw $e;
            }
        }
    }
}