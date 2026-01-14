<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

final class FrontendEchoConfigTest extends TestCase
{
    public function testEchoConfigurationIsPresent(): void
    {
        $echoPath = base_path('resources/js/echo.js');

        $this->assertFileExists($echoPath);

        $contents = file_get_contents($echoPath);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString("broadcaster: 'reverb'", $contents);
        $this->assertStringContainsString('VITE_REVERB_APP_KEY', $contents);
        $this->assertStringContainsString('VITE_REVERB_HOST', $contents);
        $this->assertStringContainsString('VITE_REVERB_PORT', $contents);
        $this->assertStringContainsString('VITE_REVERB_SCHEME', $contents);
    }

    public function testFrontendDependenciesIncludeEchoAndPusher(): void
    {
        $packagePath = base_path('package.json');

        $this->assertFileExists($packagePath);

        $contents = file_get_contents($packagePath);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('"laravel-echo"', $contents);
        $this->assertStringContainsString('"pusher-js"', $contents);
    }
}
