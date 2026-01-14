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
                'download_url' => $task->file_path,
                'error' => $task->error_message,
            ],
        ]);
    }
}
