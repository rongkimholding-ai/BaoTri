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
    protected ImageManager $imageManager;
    protected int $maxWidth = 1280;
    protected int $maxHeight = 1280;
    protected int $jpegQuality = 70;
    protected string $imagePathPrefix = 'images/';

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Save a processed image and create a record.
     */
    protected function processAndStoreImage(
        int $requestId,
        mixed $input,
        string $uploadedBy,
        string $storagePath,
        ?callable $afterSave = null,
        ?string $logFileName = null
    ): void {
        try {
            $image = $this->imageManager->read($input);
            $image->scaleDown(width: $this->maxWidth, height: $this->maxHeight);

            $fileName = Str::uuid() . '.jpg';
            $dateFolder = now()->format('Y_m_d');
            $fullPath = $this->imagePathPrefix . $dateFolder . '/' . $fileName;

            $encodedImage = $image->encode(new JpegEncoder(quality: $this->jpegQuality));
            Storage::disk('public')->put($fullPath, (string) $encodedImage);

            MaintenanceRequestImage::create([
                'maintenance_request_id' => $requestId,
                'path' => $fullPath,
                'uploaded_by' => $uploadedBy,
            ]);

            if ($afterSave) {
                $afterSave($storagePath);
            }
        } catch (\Throwable $e) {
            Log::error('Upload maintenance image failed', [
                'request_id' => $requestId,
                'file_name' => $logFileName ?? (is_object($input) && method_exists($input, 'getClientOriginalName') ? $input->getClientOriginalName() : (string)$storagePath),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function upload(
        int $requestId,
        array $files,
        string $uploadedBy
    ): void {
        foreach ($files as $file) {
            // Prevent exception stopping the rest
            try {
                $this->processAndStoreImage($requestId, $file, $uploadedBy, '', null);
            } catch (\Throwable $e) {
                // Already logged in processAndStoreImage
                // Optionally: continue; // Already logical
            }
        }
    }

    public function uploadFromTemp(
        int $requestId,
        array $tempFiles,
        string $uploadedBy
    ): void {
        foreach ($tempFiles as $tempFile) {
            $filePath = Storage::path('temp-maintenance/' . $tempFile);

            if (!file_exists($filePath)) {
                continue;
            }

            try {
                $this->processAndStoreImage(
                    $requestId,
                    $filePath,
                    $uploadedBy,
                    $filePath,
                    function ($p) {
                        @unlink($p);
                    },
                    $tempFile
                );
            } catch (\Throwable $e) {
                // Stop further uploading if desired, or remove throw to proceed next
                // For consistency with original: re-throw to halt further
                throw $e;
            }
        }
    }
}