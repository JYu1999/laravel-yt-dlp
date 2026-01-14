<?php

declare(strict_types=1);

namespace App\Domain\Downloads\Support;

use Illuminate\Contracts\Filesystem\Filesystem;

final class SubtitleUrlResolver
{
    private const SUBTITLE_EXTENSIONS = ['srt', 'vtt', 'ass', 'ssa'];

    /**
     * @return array<int, string>
     */
    public static function resolve(string $filePath): array
    {
        if ($filePath === null) {
            return [];
        }

        $basePath = preg_replace('/\.[^.]+$/', '', $filePath) ?? $filePath;
        $matches = glob($basePath . '.*');

        if ($matches === false) {
            return [];
        }

        $paths = [];

        foreach ($matches as $candidate) {
            $extension = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));

            if (!in_array($extension, self::SUBTITLE_EXTENSIONS, true)) {
                continue;
            }

            $paths[] = $candidate;
        }

        return $paths;
    }
}
