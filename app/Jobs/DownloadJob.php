<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Downloads\Enums\DownloadStatus;
use App\Domain\Downloads\Models\DownloadTask;
use App\Domain\Downloads\Services\YtDlpService;
use App\Events\DownloadCompleted;
use App\Events\DownloadFailed;
use App\Events\DownloadProgressUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class DownloadJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600;

    public function __construct(public readonly DownloadTask $task)
    {
    }

    public function handle(YtDlpService $service): void
    {
        $this->task->update([
            'status' => DownloadStatus::downloading,
        ]);

        $outputPath = storage_path('app/downloads/task-' . $this->task->id);
        $directory = dirname($outputPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        try {
            $options = $this->task->meta_data ?? [];

            $filePath = $service->downloadVideo(
                url: $this->task->video_url,
                outputPath: $outputPath,
                format: $this->task->format,
                options: $options,
                onProgress: function (float $percentage, string $eta): void {
                    event(new DownloadProgressUpdated($this->task, $percentage, $eta));
                }
            );

            $this->task->update([
                'status' => DownloadStatus::completed,
                'file_path' => $filePath,
                'error_message' => null,
            ]);

            event(new DownloadCompleted($this->task, $filePath));
        } catch (Throwable $exception) {
            $this->task->update([
                'status' => DownloadStatus::failed,
                'error_message' => $exception->getMessage(),
            ]);

            event(new DownloadFailed($this->task, $exception->getMessage()));
        }
    }
}
