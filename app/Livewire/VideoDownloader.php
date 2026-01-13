<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Downloads\DTO\VideoMetadata;
use App\Domain\Downloads\Services\VideoInfoService;
use App\Http\Requests\VideoInfoRequest;
use Illuminate\View\View;
use Livewire\Component;
use RuntimeException;

final class VideoDownloader extends Component
{
    public string $url = '';

    /**
     * @var array<string, mixed>
     */
    public array $metadata = [];

    public ?string $selectedFormat = null;

    public bool $downloadSubtitles = false;

    public ?string $selectedSubtitleLanguage = null;

    public ?string $error = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return (new VideoInfoRequest())->rules();
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'url.required' => 'Invalid link or video does not exist',
            'url.url' => 'Invalid link or video does not exist',
            'url.regex' => 'Invalid link or video does not exist',
        ];
    }

    public function fetchInfo(VideoInfoService $videoInfoService): void
    {
        $this->resetErrorBag();
        $this->error = null;
        $this->metadata = [];
        $this->selectedFormat = null;
        $this->selectedSubtitleLanguage = null;

        $this->validate();

        try {
            $payload = $videoInfoService->getVideoInfo($this->url);
            $metadata = VideoMetadata::fromYtDlp($payload)->toArray();
            $metadata['duration_formatted'] = $this->formatDuration($metadata['duration'] ?? null);
            $metadata['estimated_filesize'] = $this->formatBytes($this->resolveEstimatedFilesize($metadata['formats'] ?? []));

            $this->metadata = $metadata;
            $this->selectedFormat = $metadata['formats'][0]['format_id'] ?? null;
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            $this->error = $this->resolveFriendlyError($message);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $formats
     */
    private function resolveEstimatedFilesize(array $formats): ?int
    {
        foreach ($formats as $format) {
            $size = $format['filesize'] ?? null;
            if (is_int($size) && $size > 0) {
                return $size;
            }
        }

        return null;
    }

    private function resolveFriendlyError(string $message): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'unavailable') || str_contains($lower, 'not available')) {
            return 'Video unavailable or removed.';
        }

        if (str_contains($lower, 'geo') || str_contains($lower, 'country')) {
            return 'Video is not available in your region.';
        }

        return 'Unable to fetch video metadata.';
    }

    private function formatDuration(?int $seconds): ?string
    {
        if ($seconds === null || $seconds < 0) {
            return null;
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    private function formatBytes(?int $bytes): ?string
    {
        if ($bytes === null || $bytes <= 0) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;
        $index = 0;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return sprintf('%.2f %s', $value, $units[$index]);
    }

    public function render(): View
    {
        return view('livewire.video-downloader');
    }
}
