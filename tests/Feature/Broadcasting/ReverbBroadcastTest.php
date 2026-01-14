<?php

declare(strict_types=1);

namespace Tests\Feature\Broadcasting;

use App\Domain\Downloads\Models\DownloadTask;
use App\Events\DownloadProgressUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class ReverbBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function testBroadcastsProgressEventWithReverbDefault(): void
    {
        config(['broadcasting.default' => 'reverb']);
        Event::fake();

        $task = DownloadTask::create([
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'video_url' => 'https://example.com/video',
            'format' => 'mp4',
        ]);

        $pending = broadcast(new DownloadProgressUpdated($task, 10.0, '00:10'));
        unset($pending);

        Event::assertDispatched(DownloadProgressUpdated::class);
        self::assertSame('reverb', config('broadcasting.default'));
    }
}
