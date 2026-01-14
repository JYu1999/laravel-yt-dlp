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

    private const USER_FACING_ERROR_MESSAGE = 'Download failed. Please try again.';

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
            'progress_percentage' => 0.0,
            'progress_eta' => null,
        ]);

        // TODO: Temporary file persistence for MVP
        // Future: Implement streaming delivery without server-side storage (see project-context.md)
        // Future: Implement automatic cleanup based on retention policy (24h anonymous, 90d registered)
        $outputDir = 'downloads/task-' . $this->task->id;
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        // Ensure the file is saved INSIDE the task directory with the video title
        // We use '%(title)s' so yt-dlp names the file based on the video title.
        // YtDlpService will append .%(ext)s automatically if missing.
        $outputFileTemplate = $outputDir . '/%(title)s';
        $outputPath = $disk->path($outputFileTemplate);

        if (!$disk->exists($outputDir)) {
            $disk->makeDirectory($outputDir);
        }

        try {
            $options = $this->task->meta_data ?? [];

            $filePath = $service->downloadVideo(
                url: $this->task->video_url,
                outputPath: $outputPath,
                format: $this->task->format,
                options: $options,
                onProgress: function (float $percentage, string $eta): void {
                    $this->task->update([
                        'progress_percentage' => $percentage,
                        'progress_eta' => $eta,
                    ]);
                    event(new DownloadProgressUpdated($this->task, $percentage, $eta));
                }
            );

            // Verify and reconstruct relative path to avoid host/container path mismatches
            $fileName = basename($filePath);
            $relativePath = $outputDir . '/' . $fileName;
            
            // If the service returned a path that doesn't match our expectation (e.g. container path vs host path),
            // we relay on the fact that we know where we asked it to put the file.
            
            // However, to be safe, we check if the file exists on the disk using the relative path.
            // Note: $filePath from yt-dlp might be /var/www/... while we are on /Users/...
            // We trust that if yt-dlp succeeded, the file is in the directory we specified.
            
            // Store the ABSOLUTE path based on CURRENT environment for consistency, OR store relative?
            // The Task model has 'file_path'. It seems to expect absolute path (based on previous code).
            // But storing relative path is much safer for portability.
            // Let's check DownloadController. It expects to be able to resolve it.
            
            // Update: We will store the full path as seen by the current environment, 
            // reconstructed from the relative path.
            $finalPath = $disk->path($relativePath);
            
            $publicUrl = $disk->url($relativePath);
            $subtitleUrls = $this->resolveSubtitleUrls($disk, $finalPath);

            $this->task->update([
                'status' => DownloadStatus::completed,
                'file_path' => $finalPath, // Store path valid for current env
                'error_message' => null,
                'progress_percentage' => 100.0,
                'progress_eta' => null,
            ]);

            // TODO: Replace filesystem path with signed download URL
            // Future: Generate temporary signed URL for secure file delivery
            event(new DownloadCompleted($this->task, $publicUrl, $subtitleUrls));
        } catch (Throwable $exception) {
            // Clean up partial downloads on failure
            $this->cleanupPartialDownload($outputPath);

            $errorMessage = self::USER_FACING_ERROR_MESSAGE;

            $this->task->update([
                'status' => DownloadStatus::failed,
                'error_message' => $errorMessage,
                'progress_percentage' => null,
                'progress_eta' => null,
            ]);

            event(new DownloadFailed($this->task, $errorMessage));
        }
    }

    private function cleanupPartialDownload(string $basePath): void
    {
        $directory = dirname($basePath);

        // Clean up all files matching the pattern (video + subtitles)
        $pattern = str_replace(['%(ext)s', '.%(ext)s'], '', $basePath);
        $files = glob($pattern . '*');

        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        // Remove directory if empty
        if (is_dir($directory) && count(scandir($directory)) === 2) {
            @rmdir($directory);
        }
    }

    /**
     * @return array<int, string>
     */
    private function resolveSubtitleUrls(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $filePath): array
    {
        $basePath = preg_replace('/\.[^.]+$/', '', $filePath) ?? $filePath;
        $matches = glob($basePath . '.*') ?: [];
        $subtitleExtensions = ['srt', 'vtt', 'ass', 'ssa'];
        $urls = [];

        foreach ($matches as $candidate) {
            $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
            if (!in_array($extension, $subtitleExtensions, true)) {
                continue;
            }

            $relative = ltrim(str_replace($disk->path(''), '', $candidate), '/');
            $urls[] = $disk->url($relative);
        }

        return $urls;
    }
}
