<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Domain\Downloads\Models\DownloadTask;
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

        $event = new DownloadProgressUpdated($task, 12.5, '00:10');

        self::assertInstanceOf(ShouldBroadcast::class, $event);
        self::assertSame('download.101', (string) $event->broadcastOn());
        self::assertSame(12.5, $event->percentage);
        self::assertSame('00:10', $event->eta);
    }

    public function testDownloadCompletedBroadcastsOnTaskChannel(): void
    {
        $task = new DownloadTask();
        $task->id = 202;

        $event = new DownloadCompleted($task, '/downloads/video.mp4');

        self::assertInstanceOf(ShouldBroadcast::class, $event);
        self::assertSame('download.202', (string) $event->broadcastOn());
        self::assertSame('/downloads/video.mp4', $event->downloadUrl);
    }

    public function testDownloadFailedBroadcastsOnTaskChannel(): void
    {
        $task = new DownloadTask();
        $task->id = 303;

        $event = new DownloadFailed($task, 'Process failed');

        self::assertInstanceOf(ShouldBroadcast::class, $event);
        self::assertSame('download.303', (string) $event->broadcastOn());
        self::assertSame('Process failed', $event->error);
    }
}
