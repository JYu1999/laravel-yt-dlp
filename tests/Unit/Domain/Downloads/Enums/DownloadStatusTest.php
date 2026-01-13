<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Downloads\Enums;

use App\Domain\Downloads\Enums\DownloadStatus;
use PHPUnit\Framework\TestCase;

final class DownloadStatusTest extends TestCase
{
    public function testItDefinesExpectedStatuses(): void
    {
        self::assertSame('pending', DownloadStatus::pending->value);
        self::assertSame('downloading', DownloadStatus::downloading->value);
        self::assertSame('completed', DownloadStatus::completed->value);
        self::assertSame('failed', DownloadStatus::failed->value);
    }
}
