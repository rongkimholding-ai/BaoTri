<?php

namespace App\Jobs;

use App\Services\MaintenanceImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UploadMaintenanceImagesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $requestId,
        public array $tempFiles,
        public string $uploadedBy
    ) {
    }

    public function handle(
        MaintenanceImageService $imageService
    ): void {
        $imageService->uploadFromTemp(
            $this->requestId,
            $this->tempFiles,
            $this->uploadedBy
        );
    }
}