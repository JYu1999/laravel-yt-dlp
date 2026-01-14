<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Domain\Downloads\Models\DownloadTask;
use App\Domain\Downloads\Enums\DownloadStatus;
use App\Events\DownloadCompleted;
use App\Events\DownloadFailed;
use App\Events\DownloadProgressUpdated;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use PHPUnit\Framework\TestCase;

final class DownloadEventsTest extends TestCase
{
    public function testDownloadProgressUpdatedBroadcastsOnTaskChannel(): void
    {
        $task = new DownloadTask();
        $task->id = 101;
        $task->status = DownloadStatus::downloading;

        $event = new DownloadProgressUpdated($task, 12.5, '00:10');

        self::assertInstanceOf(ShouldBroadcast::class, $event);
        self::assertSame('download.101', (string) $event->broadcastOn());
        self::assertSame('download.progress.updated', $event->broadcastAs());
        self::assertSame([
            'status' => 'downloading',
            'percentage' => 12.5,
            'eta' => '00:10',
        ], $event->broadcastWith());
        self::assertSame(12.5, $event->percentage);
        self::assertSame('00:10', $event->eta);
    }

    public function testDownloadCompletedBroadcastsOnTaskChannel(): void
    {
        $task = new DownloadTask();
        $task->id = 202;
        $task->status = DownloadStatus::completed;

        $event = new DownloadCompleted($task, '/downloads/video.mp4');

        self::assertInstanceOf(ShouldBroadcast::class, $event);
        self::assertSame('download.202', (string) $event->broadcastOn());
        self::assertSame('download.completed', $event->broadcastAs());
        self::assertSame([
            'status' => 'completed',
            'download_url' => '/downloads/video.mp4',
        ], $event->broadcastWith());
        self::assertSame('/downloads/video.mp4', $event->downloadUrl);
    }

    public function testDownloadFailedBroadcastsOnTaskChannel(): void
    {
        $task = new DownloadTask();
        $task->id = 303;
        $task->status = DownloadStatus::failed;

        $event = new DownloadFailed($task, 'Process failed');

        self::assertInstanceOf(ShouldBroadcast::class, $event);
        self::assertSame('download.303', (string) $event->broadcastOn());
        self::assertSame('download.failed', $event->broadcastAs());
        self::assertSame([
            'status' => 'failed',
            'error' => 'Process failed',
        ], $event->broadcastWith());
        self::assertSame('Process failed', $event->error);
    }
}
