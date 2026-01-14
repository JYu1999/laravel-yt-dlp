<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Downloads\Models\DownloadTask;
use Illuminate\Http\JsonResponse;

final class DownloadController
{
    public function show(string $id): JsonResponse
    {
        $task = DownloadTask::find($id);

        if ($task === null) {
            return response()->json([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Download task not found.',
                ],
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $task->id,
                'status' => $task->status->value,
                'percentage' => $task->progress_percentage,
                'eta' => $task->progress_eta,
                'download_url' => $this->resolveDownloadUrl($task->file_path),
                'error' => $task->error_message,
                'subtitles' => $this->resolveSubtitleUrls($task->file_path),
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function resolveSubtitleUrls(?string $filePath): array
    {
        if ($filePath === null) {
            return [];
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
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
    private function resolveDownloadUrl(?string $filePath): ?string
    {
        if ($filePath === null) {
            return null;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        
        // If file doesn't exist on disk, we can't serve it.
        // However, we still return the URL if the file path is set, 
        // to avoid race conditions or if checking existence is too slow.
        // But strictly for path conversion:
        
        $relativePath = ltrim(str_replace($disk->path(''), '', $filePath), '/');
        
        return $disk->url($relativePath);
    }
}
