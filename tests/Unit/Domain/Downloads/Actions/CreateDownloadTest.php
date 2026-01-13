<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Downloads\Actions;

use App\Domain\Downloads\Actions\CreateDownload;
use App\Domain\Downloads\Enums\DownloadStatus;
use App\Domain\Downloads\Exceptions\DownloadConcurrencyException;
use App\Domain\Downloads\Models\DownloadTask;
use App\Jobs\DownloadJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class CreateDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function testItCreatesTaskAndDispatchesJob(): void
    {
        Queue::fake();

        $action = new CreateDownload();

        $task = $action->handle(
            url: 'https://example.com/video',
            format: 'mp4',
            ipAddress: '192.168.0.10',
            userId: null,
        );

        self::assertSame(DownloadStatus::pending, $task->status);
        self::assertSame('192.168.0.10', $task->ip_address);
        self::assertSame('https://example.com/video', $task->video_url);
        self::assertSame('mp4', $task->format);

        Queue::assertPushed(DownloadJob::class, function (DownloadJob $job) use ($task): bool {
            return $job->task->is($task);
        });
    }

    public function testItBlocksSecondDownloadForSameUserId(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        DownloadTask::create([
            'user_id' => $user->id,
            'ip_address' => '192.168.0.10',
            'video_url' => 'https://example.com/first',
            'format' => 'mp4',
            'status' => DownloadStatus::pending,
        ]);

        $action = new CreateDownload();

        $this->expectException(DownloadConcurrencyException::class);

        $action->handle(
            url: 'https://example.com/second',
            format: 'mp4',
            ipAddress: '192.168.0.10',
            userId: $user->id,
        );
    }

    public function testItBlocksSecondDownloadForSameIpWhenAnonymous(): void
    {
        Queue::fake();

        DownloadTask::create([
            'user_id' => null,
            'ip_address' => '10.0.0.2',
            'video_url' => 'https://example.com/first',
            'format' => 'mp4',
            'status' => DownloadStatus::downloading,
        ]);

        $action = new CreateDownload();

        $this->expectException(DownloadConcurrencyException::class);

        $action->handle(
            url: 'https://example.com/second',
            format: 'mp4',
            ipAddress: '10.0.0.2',
            userId: null,
        );
    }
}
