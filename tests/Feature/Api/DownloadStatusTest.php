<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Downloads\Enums\DownloadStatus;
use App\Domain\Downloads\Models\DownloadTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DownloadStatusTest extends TestCase
{
    use RefreshDatabase;

    public function testItReturnsDownloadStatusPayload(): void
    {
        $task = DownloadTask::create([
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'video_url' => 'https://example.com/video',
            'format' => 'mp4',
            'status' => DownloadStatus::downloading,
            'progress_percentage' => 44.5,
            'progress_eta' => '00:20',
        ]);

        $response = $this->getJson('/api/downloads/' . $task->id);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $task->id,
                    'status' => 'downloading',
                    'percentage' => 44.5,
                    'eta' => '00:20',
                    'download_url' => null,
                    'error' => null,
                ],
            ]);
    }

    public function testItReturnsNotFoundEnvelopeWhenTaskMissing(): void
    {
        $response = $this->getJson('/api/downloads/999');

        $response->assertNotFound()
            ->assertJson([
                'error' => [
                    'code' => 'not_found',
                    'message' => 'Download task not found.',
                ],
            ]);
    }
}
