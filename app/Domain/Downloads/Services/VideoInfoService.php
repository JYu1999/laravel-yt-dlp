<?php

declare(strict_types=1);

namespace App\Domain\Downloads\Services;

use Illuminate\Support\Facades\Cache;

final class VideoInfoService
{
    public function __construct(private readonly YtDlpService $ytDlpService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getVideoInfo(string $url): array
    {
        $cacheKey = 'video_info:' . md5($url);

        return Cache::remember(
            $cacheKey,
            900,
            fn (): array => $this->ytDlpService->getVideoInfo($url)
        );
    }
}
