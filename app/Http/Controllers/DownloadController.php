<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Downloads\Models\DownloadTask;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadController extends Controller
{
    public function stream(Request $request, DownloadTask $task): BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired signature.');
        }

        $downloadDir = \Illuminate\Support\Facades\Storage::disk('local')->path('downloads/task-' . $task->id);
        $type = $request->query('type', 'video');
        
        if ($type === 'subtitle') {
            $filename = $request->query('filename');
            if (!$filename) {
                abort(404);
            }
            $filePath = $downloadDir . '/' . basename($filename);
        } else {
            $filePath = $task->file_path;
        }

        if (! $filePath || ! file_exists($filePath)) {
            abort(404, 'File not found or already downloaded.');
        }

        // Security check: Ensure the file is within the allowed directory
        if (! str_starts_with($filePath, $downloadDir)) {
             abort(403, 'Access denied.');
        }
        
        $filename = basename($filePath);

        return response()->download($filePath, $filename)->deleteFileAfterSend();
    }
}
