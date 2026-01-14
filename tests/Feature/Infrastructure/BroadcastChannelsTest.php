<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

final class BroadcastChannelsTest extends TestCase
{
    public function testDownloadChannelIsPublic(): void
    {
        $channelsPath = base_path('routes/channels.php');

        $this->assertFileExists($channelsPath);

        $contents = file_get_contents($channelsPath);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("download.{id}", $contents);
        $this->assertStringContainsString('return true', $contents);
    }
}
