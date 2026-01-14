<?php

declare(strict_types=1);

namespace App\Events;

use App\Domain\Downloads\Models\DownloadTask;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class DownloadCompleted implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly DownloadTask $task,
        public readonly string $downloadUrl,
        /** @var array<int, string> */
        public readonly array $subtitles = [],
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('download.' . $this->task->id);
    }

    public function broadcastAs(): string
    {
        return 'download.completed';
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return [
            'status' => $this->task->status?->value ?? 'completed',
            'download_url' => $this->downloadUrl,
            'subtitles' => $this->subtitles,
        ];
    }
}
