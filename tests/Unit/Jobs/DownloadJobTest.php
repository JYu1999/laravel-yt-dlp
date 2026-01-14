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

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $outputDir = 'downloads/task-' . $task->id;
        $videoFileName = 'Test Video.mp4';
        $expectedPath = $disk->path($outputDir . '/' . $videoFileName);
        $expectedUrl = $disk->url($outputDir . '/' . $videoFileName);

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
            "# Resolve %(title)s to 'Test Video' and %(ext)s to 'mp4'\n" .
            "resolved=\$(echo \"\$output\" | sed 's/%(title)s/Test Video/' | sed 's/%(ext)s/mp4/')\n" .
            "mkdir -p \$(dirname \"\$resolved\")\n" .
            "touch \"\$resolved\"\n" .
            "echo \"\$resolved\"\n" .
            "exit 0\n"
        );

        $service = new YtDlpService($binary);

        (new DownloadJob($task))->handle($service);

        $task->refresh();

        self::assertSame(DownloadStatus::completed, $task->status);
        self::assertSame($expectedPath, $task->file_path);
        self::assertSame(100.0, $task->progress_percentage);
        self::assertNull($task->progress_eta);

        Event::assertDispatched(DownloadProgressUpdated::class, function (DownloadProgressUpdated $event) use ($task): bool {
            return $event->task->is($task)
                && $event->percentage === 55.5
                && $event->eta === '00:42';
        });
        Event::assertDispatched(DownloadCompleted::class, function (DownloadCompleted $event) use ($task, $expectedUrl): bool {
            return $event->task->is($task)
                && $event->downloadUrl === $expectedUrl;
        });
        Event::assertNotDispatched(DownloadFailed::class);

        // Clean up created files and directory
        if (file_exists($expectedPath)) {
            unlink($expectedPath);
        }
        $taskDir = $disk->path($outputDir);
        if (is_dir($taskDir)) {
            @rmdir($taskDir);
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
        self::assertSame('Download failed. Please try again.', $task->error_message);
        self::assertNull($task->progress_percentage);
        self::assertNull($task->progress_eta);

        Event::assertDispatched(DownloadFailed::class, function (DownloadFailed $event) use ($task): bool {
            return $event->task->is($task)
                && $event->error === 'Download failed. Please try again.';
        });
        Event::assertNotDispatched(DownloadCompleted::class);
    }
}
