<?php

declare(strict_types=1);

namespace App\Domain\Downloads\Support;

use Illuminate\Contracts\Filesystem\Filesystem;

final class SubtitleUrlResolver
{
    private const SUBTITLE_EXTENSIONS = ['srt', 'vtt', 'ass', 'ssa'];

    /**
     * Resolve subtitle URLs for a given file path.
     *
     * @return array<int, string>
     */
    public static function resolve(Filesystem $disk, ?string $filePath): array
    {
        if ($filePath === null) {
            return [];
        }

        $basePath = preg_replace('/\.[^.]+$/', '', $filePath) ?? $filePath;
        $matches = glob($basePath . '.*');

        if ($matches === false) {
            return [];
        }

        $urls = [];
        $diskPath = $disk->path('');

        foreach ($matches as $candidate) {
            $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));

            if (!in_array($extension, self::SUBTITLE_EXTENSIONS, true)) {
                continue;
            }

            $relative = ltrim(str_replace($diskPath, '', $candidate), '/');
            $urls[] = $disk->url($relative);
        }

        return $urls;
    }
}
