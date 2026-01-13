<?php

declare(strict_types=1);

namespace App\Domain\Downloads\DTO;

final readonly class VideoMetadata
{
    /**
     * @param array<int, array<string, mixed>> $formats
     * @param array<int, string> $subtitles
     */
    public function __construct(
        public ?string $id,
        public ?string $title,
        public ?string $thumbnail,
        public ?int $duration,
        public array $formats,
        public array $subtitles,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromYtDlp(array $payload): self
    {
        return new self(
            isset($payload['id']) ? (string) $payload['id'] : null,
            isset($payload['title']) ? (string) $payload['title'] : null,
            isset($payload['thumbnail']) ? (string) $payload['thumbnail'] : null,
            is_numeric($payload['duration'] ?? null) ? (int) $payload['duration'] : null,
            self::mapFormats($payload['formats'] ?? []),
            self::mapSubtitles($payload['subtitles'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'thumbnail' => $this->thumbnail,
            'duration' => $this->duration,
            'formats' => $this->formats,
            'subtitles' => $this->subtitles,
        ];
    }

    /**
     * @param mixed $formats
     * @return array<int, array<string, mixed>>
     */
    private static function mapFormats(mixed $formats): array
    {
        if (!is_array($formats)) {
            return [];
        }

        $filtered = [];

        foreach ($formats as $format) {
            if (!is_array($format)) {
                continue;
            }

            $ext = strtolower((string) ($format['ext'] ?? ''));
            $vcodec = strtolower((string) ($format['vcodec'] ?? ''));

            if (!in_array($ext, ['mp4', 'mov'], true)) {
                continue;
            }

            if ($vcodec === '' || $vcodec === 'none') {
                continue;
            }

            $filtered[] = [
                'format_id' => isset($format['format_id']) ? (string) $format['format_id'] : null,
                'ext' => $ext !== '' ? $ext : null,
                'vcodec' => $vcodec !== '' ? $vcodec : null,
                'acodec' => isset($format['acodec']) ? (string) $format['acodec'] : null,
                'height' => isset($format['height']) && is_numeric($format['height']) ? (int) $format['height'] : null,
                'width' => isset($format['width']) && is_numeric($format['width']) ? (int) $format['width'] : null,
                'filesize' => self::resolveFilesize($format),
                'format' => isset($format['format']) ? (string) $format['format'] : null,
            ];
        }

        usort($filtered, static function (array $left, array $right): int {
            $heightLeft = $left['height'] ?? -1;
            $heightRight = $right['height'] ?? -1;

            if ($heightLeft === $heightRight) {
                $widthLeft = $left['width'] ?? -1;
                $widthRight = $right['width'] ?? -1;

                return $widthRight <=> $widthLeft;
            }

            return $heightRight <=> $heightLeft;
        });

        return $filtered;
    }

    /**
     * @param array<string, mixed> $format
     */
    private static function resolveFilesize(array $format): ?int
    {
        $size = $format['filesize'] ?? $format['filesize_approx'] ?? null;

        return is_numeric($size) ? (int) $size : null;
    }

    /**
     * @param mixed $subtitles
     * @return array<int, string>
     */
    private static function mapSubtitles(mixed $subtitles): array
    {
        if (!is_array($subtitles)) {
            return [];
        }

        $languages = array_keys($subtitles);
        sort($languages, SORT_STRING);

        return $languages;
    }
}
