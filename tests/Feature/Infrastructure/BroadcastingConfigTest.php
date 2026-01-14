<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

final class BroadcastingConfigTest extends TestCase
{
    public function testReverbConnectionIsConfigured(): void
    {
        $config = config('broadcasting.connections.reverb');

        self::assertIsArray($config);
        self::assertSame('reverb', $config['driver'] ?? null);
        self::assertArrayHasKey('key', $config);
        self::assertArrayHasKey('secret', $config);
        self::assertArrayHasKey('app_id', $config);
        self::assertArrayHasKey('options', $config);
        self::assertIsArray($config['options']);
        self::assertArrayHasKey('host', $config['options']);
        self::assertArrayHasKey('port', $config['options']);
        self::assertArrayHasKey('scheme', $config['options']);
    }
}
