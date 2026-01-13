<?php

declare(strict_types=1);

namespace App\Domain\Downloads\Enums;

enum DownloadStatus: string
{
    case pending = 'pending';
    case downloading = 'downloading';
    case completed = 'completed';
    case failed = 'failed';
}
