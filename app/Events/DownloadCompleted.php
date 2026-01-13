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
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('download.' . $this->task->id);
    }
}
