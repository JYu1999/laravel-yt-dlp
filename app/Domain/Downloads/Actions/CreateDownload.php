<?php

declare(strict_types=1);

namespace App\Domain\Downloads\Actions;

use App\Domain\Downloads\Enums\DownloadStatus;
use App\Domain\Downloads\Exceptions\DownloadConcurrencyException;
use App\Domain\Downloads\Models\DownloadTask;
use App\Jobs\DownloadJob;

final class CreateDownload
{
    public function handle(string $url, string $format, string $ipAddress, ?int $userId, array $options = []): DownloadTask
    {
        $this->checkConcurrency($ipAddress, $userId);

        $task = DownloadTask::create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'video_url' => $url,
            'format' => $format,
            'status' => DownloadStatus::pending,
            'meta_data' => $options,
        ]);

        DownloadJob::dispatch($task);

        return $task;
    }

    public function checkConcurrency(string $ipAddress, ?int $userId): void
    {
        // Check system-wide concurrency limit (max 10 concurrent downloads)
        $systemActiveCount = DownloadTask::query()
            ->whereIn('status', [DownloadStatus::pending, DownloadStatus::downloading])
            ->count();

        if ($systemActiveCount >= 10) {
            throw new DownloadConcurrencyException('System is at maximum capacity. Please try again later.');
        }

        // Check per-user concurrency limit (max 1 concurrent download)
        $query = DownloadTask::query()
            ->whereIn('status', [DownloadStatus::pending, DownloadStatus::downloading]);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        } else {
            $query->where('ip_address', $ipAddress);
        }

        if ($query->exists()) {
            throw new DownloadConcurrencyException('You already have an active download in progress.');
        }
    }
}
