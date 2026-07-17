<?php

namespace App\Jobs;

use App\Services\SystemImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UploadSystemImagesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $requestId,
        public array $tempFiles,
        public string $uploadedBy
    ) {
    }

    public function handle(
        SystemImageService $imageService
    ): void {
        $imageService->uploadFromTemp(
            $this->requestId,
            $this->tempFiles,
            $this->uploadedBy
        );
    }
}