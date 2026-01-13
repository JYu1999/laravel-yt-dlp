<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Domain\Downloads\Enums\DownloadStatus;
use App\Domain\Downloads\Models\DownloadTask;
use App\Domain\Downloads\Services\YtDlpService;
use App\Events\DownloadCompleted;
use App\Events\DownloadFailed;
use App\Events\DownloadProgressUpdated;
use App\Jobs\DownloadJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\HasFakeYtDlp;

final class DownloadJobTest extends TestCase
{
    use RefreshDatabase;
    use HasFakeYtDlp;

    public function testItMarksTaskCompletedAndBroadcastsEvents(): void
    {
        Event::fake([
            DownloadProgressUpdated::class,
            DownloadCompleted::class,
            DownloadFailed::class,
        ]);

        $task = DownloadTask::create([
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'video_url' => 'https://example.com/video',
            'format' => 'mp4',
            'status' => DownloadStatus::pending,
        ]);

        $expectedPath = storage_path('app/downloads/task-' . $task->id . '.mp4');

        $binary = $this->createFakeBinary(
            "#!/bin/sh\n" .
            "# Find the -o argument value\n" .
            "output=\"\"\n" .
            "while [ \$# -gt 0 ]; do\n" .
            "  if [ \"\$1\" = \"-o\" ]; then\n" .
            "    output=\"\$2\"\n" .
            "    break\n" .
            "  fi\n" .
            "  shift\n" .
            "done\n" .
            "\n" .
            "if [ -z \"\$output\" ]; then\n" .
            "  exit 1\n" .
            "fi\n" .
            "\n" .
            "echo \"[download] 55.5% of 10.00MiB at 1.00MiB/s ETA 00:42\"\n" .
            "resolved=\$(echo \"\$output\" | sed 's/%(ext)s/mp4/')\n" .
            "touch \"\$resolved\"\n" .
            "echo \"\$resolved\"\n" .
            "exit 0\n"
        );

        $service = new YtDlpService($binary);

        (new DownloadJob($task))->handle($service);

        $task->refresh();

        self::assertSame(DownloadStatus::completed, $task->status);
        self::assertSame($expectedPath, $task->file_path);

        Event::assertDispatched(DownloadProgressUpdated::class, function (DownloadProgressUpdated $event) use ($task): bool {
            return $event->task->is($task)
                && $event->percentage === 55.5
                && $event->eta === '00:42';
        });
        Event::assertDispatched(DownloadCompleted::class, function (DownloadCompleted $event) use ($task, $expectedPath): bool {
            return $event->task->is($task)
                && $event->downloadUrl === $expectedPath;
        });
        Event::assertNotDispatched(DownloadFailed::class);

        if (file_exists($expectedPath)) {
            unlink($expectedPath);
        }
    }

    public function testItMarksTaskFailedWhenDownloadErrors(): void
    {
        Event::fake([
            DownloadCompleted::class,
            DownloadFailed::class,
        ]);

        $task = DownloadTask::create([
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'video_url' => 'https://example.com/video',
            'format' => 'mp4',
            'status' => DownloadStatus::pending,
        ]);

        $expectedPath = storage_path('app/downloads/task-' . $task->id . '.mp4');

        $binary = $this->createFakeBinary("#!/bin/sh\nexit 2\n");

        $service = new YtDlpService($binary);

        (new DownloadJob($task))->handle($service);

        $task->refresh();

        self::assertSame(DownloadStatus::failed, $task->status);
        self::assertSame('yt-dlp command failed', $task->error_message);

        Event::assertDispatched(DownloadFailed::class, function (DownloadFailed $event) use ($task): bool {
            return $event->task->is($task)
                && $event->error === 'yt-dlp command failed';
        });
        Event::assertNotDispatched(DownloadCompleted::class);
    }
}
