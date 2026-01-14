<?php

namespace Tests\Feature;

use App\Domain\Downloads\Models\DownloadTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DownloadStreamingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_download_video_with_valid_signature_and_file_is_deleted_after()
    {
        Storage::fake('local');
        
        $task = DownloadTask::factory()->create();
        $directory = 'downloads/task-' . $task->id;
        $filename = 'video.mp4';
        
        // Create dummy file in storage/app/private (local disk root)
        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->put($directory . '/' . $filename, 'dummy content');
        
        $absolutePath = Storage::disk('local')->path($directory . '/' . $filename);
        $task->update(['file_path' => $absolutePath]);

        $url = URL::signedRoute('download.stream', ['task' => $task->id, 'type' => 'video']);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=video.mp4');
        
        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=video.mp4');
        
        // Use reflection to verify deleteFileAfterSend is set on the BinaryFileResponse
        $baseResponse = $response->baseResponse;
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $baseResponse);
        
        $reflection = new \ReflectionObject($baseResponse);
        $property = $reflection->getProperty('deleteFileAfterSend');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue($baseResponse), 'Response should be configured to delete file after send');
    }
    
    public function test_cannot_download_with_invalid_signature()
    {
        $task = DownloadTask::factory()->create();
        $url = route('download.stream', ['task' => $task->id, 'type' => 'video']); // Unsigned
        
        $response = $this->get($url);
        
        $response->assertStatus(403);
    }
    
    public function test_can_download_subtitle_with_valid_signature()
    {
        Storage::fake('local');
        
        $task = DownloadTask::factory()->create();
        $directory = 'downloads/task-' . $task->id;
        $filename = 'sub.srt';
        
        Storage::disk('local')->makeDirectory($directory);
        Storage::disk('local')->put($directory . '/' . $filename, 'srt content');
        
        $url = URL::signedRoute('download.stream', [
            'task' => $task->id, 
            'type' => 'subtitle',
            'filename' => $filename
        ]);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=sub.srt');
        
        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=sub.srt');
        
        $baseResponse = $response->baseResponse;
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $baseResponse);
        
        $reflection = new \ReflectionObject($baseResponse);
        $property = $reflection->getProperty('deleteFileAfterSend');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue($baseResponse), 'Response should be configured to delete file after send');
    }
    
    public function test_cannot_access_files_outside_task_directory()
    {
        Storage::fake('local');
        
        $task = DownloadTask::factory()->create();
        $directory = 'downloads/task-' . $task->id;
        
        // File outside task dir
        Storage::disk('local')->put('other/secret.txt', 'secret');
        
        // malicious filename check (Controller checks filename, but if we somehow passed a path...)
        // The controller uses $request->query('filename') and does basename() on it for download name,
        // but it constructs path as $downloadDir . '/' . basename($filename);
        // So traversal attempts like '../secret.txt' become 'secret.txt' in the dir.
        // So checking "cannot access outside" is implicitly checking that we only look in that dir.
        
        // Let's test if we try to trick it (Controller forces basename so traversal is impossible via filename param)
        // But let's verify logic for video type which uses $task->file_path
        
        $task->update(['file_path' => Storage::disk('local')->path('other/secret.txt')]);
        
        $url = URL::signedRoute('download.stream', ['task' => $task->id, 'type' => 'video']);
        
        $response = $this->get($url);
        
        $response->assertStatus(403); // Should be Access denied
    }
}
