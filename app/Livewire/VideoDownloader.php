<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domain\Downloads\DTO\VideoMetadata;
use App\Domain\Downloads\Services\VideoInfoService;
use App\Http\Requests\VideoInfoRequest;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

final class VideoDownloader extends Component
{
    private const FALLBACK_LANGUAGES = ['en', 'en-US', 'en-GB', 'zh', 'zh-Hans', 'zh-Hant', 'zh-CN', 'zh-TW'];

    public string $url = '';

    /**
     * @var array<string, mixed>
     */
    public array $metadata = [];

    public ?string $selectedFormat = null;

    public bool $downloadSubtitles = false;

    public ?string $selectedLanguage = null;

    public ?string $error = null;

    public ?string $downloadNotice = null;

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
        $this->downloadNotice = null;
        $this->metadata = [];
        $this->selectedFormat = null;
        $this->selectedLanguage = null;

        $this->validateOnly('url');

        try {
            $payload = $videoInfoService->getVideoInfo($this->url);
            $metadata = VideoMetadata::fromYtDlp($payload)->toArray();
            $metadata['duration_formatted'] = $this->formatDuration($metadata['duration'] ?? null);
            $metadata['estimated_filesize'] = $this->formatBytes($this->resolveEstimatedFilesize($metadata['formats'] ?? []));

            $this->metadata = $metadata;
            $availableFormats = $this->resolveAvailableFormats($metadata['formats'] ?? []);
            $this->selectedFormat = in_array('mp4', $availableFormats, true)
                ? 'mp4'
                : ($availableFormats[0] ?? null);
            $this->selectedLanguage = $this->resolveDefaultSubtitleLanguage($metadata['subtitles'] ?? []);
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            $this->error = $this->resolveFriendlyError($message);
        }
    }

    public function startDownload(): void
    {
        $this->resetErrorBag();
        $this->downloadNotice = null;

        $this->validate($this->downloadRules(), $this->downloadMessages());

        $this->downloadNotice = 'Download request validated.';
    }

    public function updatedDownloadSubtitles(bool $value): void
    {
        if (!$value) {
            $this->selectedLanguage = null;
            return;
        }

        if ($this->selectedLanguage === null) {
            $this->selectedLanguage = $this->resolveDefaultSubtitleLanguage($this->metadata['subtitles'] ?? []);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function downloadRules(): array
    {
        $formats = $this->resolveAvailableFormats($this->metadata['formats'] ?? []);
        $languages = $this->resolveAvailableSubtitleLanguages($this->metadata['subtitles'] ?? []);

        $languageRules = ['nullable', 'string'];

        if ($this->downloadSubtitles) {
            $languageRules[] = 'required';
            $languageRules[] = Rule::in($languages);
        }

        return [
            'selectedFormat' => ['required', 'string', Rule::in($formats)],
            'downloadSubtitles' => ['boolean'],
            'selectedLanguage' => $languageRules,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function downloadMessages(): array
    {
        return [
            'selectedFormat.required' => 'Please choose a format.',
            'selectedFormat.in' => 'Selected format is not available.',
            'selectedLanguage.required' => 'Please choose a subtitle language.',
            'selectedLanguage.in' => 'Selected subtitle language is not available.',
        ];
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

    /**
     * @param array<int, array<string, mixed>> $formats
     * @return array<int, string>
     */
    private function resolveAvailableFormats(array $formats): array
    {
        $available = [];

        foreach ($formats as $format) {
            $ext = strtolower((string) ($format['ext'] ?? ''));
            if ($ext === '') {
                continue;
            }

            $available[$ext] = true;
        }

        return array_keys($available);
    }

    /**
     * @param array<int, string> $languages
     * @return array<int, string>
     */
    private function resolveAvailableSubtitleLanguages(array $languages): array
    {
        $available = [];

        foreach ($languages as $language) {
            if ($language === '') {
                continue;
            }

            $available[$language] = true;
        }

        return array_keys($available);
    }

    /**
     * @param array<int, string> $languages
     */
    private function resolveDefaultSubtitleLanguage(array $languages): ?string
    {
        if ($languages === []) {
            return null;
        }

        $preferred = request()->getPreferredLanguage();

        if (is_string($preferred)) {
            foreach ($languages as $language) {
                if ($language === $preferred) {
                    return $language;
                }

                if (str_starts_with($preferred, $language) || str_starts_with($language, $preferred)) {
                    return $language;
                }
            }
        }

        foreach (self::FALLBACK_LANGUAGES as $fallback) {
            if (in_array($fallback, $languages, true)) {
                return $fallback;
            }
        }

        return $languages[0];
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

    public function getFormatOptionsProperty(): array
    {
        return collect($this->metadata['formats'] ?? [])
            ->pluck('ext')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.video-downloader')
            ->layout('layouts.public');
    }
}
