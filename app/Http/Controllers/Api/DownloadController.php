<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Downloads\Models\DownloadTask;
use App\Domain\Downloads\Support\SubtitleUrlResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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

        $disk = Storage::disk('public');

        return response()->json([
            'data' => [
                'id' => $task->id,
                'status' => $task->status->value,
                'percentage' => $task->progress_percentage,
                'eta' => $task->progress_eta,
                'download_url' => $this->resolveDownloadUrl($task->file_path),
                'error' => $task->error_message,
                'subtitles' => SubtitleUrlResolver::resolve($disk, $task->file_path),
            ],
        ]);
    }

    private function resolveDownloadUrl(?string $filePath): ?string
    {
        if ($filePath === null) {
            return null;
        }

        $disk = Storage::disk('public');
        $relativePath = ltrim(str_replace($disk->path(''), '', $filePath), '/');

        return $disk->url($relativePath);
    }
}
