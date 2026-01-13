<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use Tests\TestCase;

final class DockerComposeTest extends TestCase
{
    public function testDockerPhpDockerfileExistsAndHasRequiredExtensions(): void
    {
        $dockerfilePath = base_path('docker/php/Dockerfile');

        $this->assertFileExists($dockerfilePath);

        $contents = file_get_contents($dockerfilePath);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('FROM php:8.4-fpm', $contents);
        $this->assertStringContainsString('pdo_pgsql', $contents);
        $this->assertStringContainsString('pgsql', $contents);
        $this->assertStringContainsString('redis', $contents);
        $this->assertStringContainsString('pcntl', $contents);
        $this->assertStringContainsString('bcmath', $contents);
        $this->assertStringContainsString('intl', $contents);
    }

    public function testDockerPhpDockerfileInstallsYtDlp(): void
    {
        $dockerfilePath = base_path('docker/php/Dockerfile');

        $this->assertFileExists($dockerfilePath);

        $contents = file_get_contents($dockerfilePath);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('yt-dlp', $contents);
        $this->assertStringContainsString('/usr/local/bin/yt-dlp', $contents);
    }

    public function testNginxConfigExistsAndHasPhpAndReverbProxying(): void
    {
        $nginxConfigPath = base_path('docker/nginx/conf.d/default.conf');

        $this->assertFileExists($nginxConfigPath);

        $contents = file_get_contents($nginxConfigPath);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('fastcgi_pass app:9000;', $contents);
        $this->assertStringContainsString('proxy_pass http://reverb:6001;', $contents);
        $this->assertStringContainsString('proxy_set_header Upgrade $http_upgrade;', $contents);
    }

    public function testDockerComposeDefinesCoreServicesAndVolumes(): void
    {
        $composePath = base_path('docker-compose.yml');

        $this->assertFileExists($composePath);

        $contents = file_get_contents($composePath);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('app:', $contents);
        $this->assertStringContainsString('web:', $contents);
        $this->assertStringContainsString('db:', $contents);
        $this->assertStringContainsString('redis:', $contents);
        $this->assertStringContainsString('reverb:', $contents);
        $this->assertStringContainsString('worker:', $contents);
        $this->assertStringContainsString('postgres:17.7', $contents);
        $this->assertStringContainsString('redis:8.4.0', $contents);
        $this->assertStringContainsString('nginx:1.28.1', $contents);
        $this->assertStringContainsString('pgdata:', $contents);
        $this->assertStringContainsString('redisdata:', $contents);
        $this->assertStringContainsString('laravel-yt-dlp', $contents);
    }

    public function testEnvFilesUseDockerServiceNames(): void
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        $this->assertFileExists($envPath);
        $this->assertFileExists($examplePath);

        $envContents = file_get_contents($envPath);
        $exampleContents = file_get_contents($examplePath);

        $this->assertNotFalse($envContents);
        $this->assertNotFalse($exampleContents);

        $this->assertStringContainsString('DB_CONNECTION=pgsql', $envContents);
        $this->assertStringContainsString('DB_HOST=db', $envContents);
        $this->assertStringContainsString('DB_PORT=5432', $envContents);
        $this->assertStringContainsString('REDIS_HOST=redis', $envContents);
        $this->assertStringContainsString('CACHE_STORE=redis', $envContents);
        $this->assertStringContainsString('QUEUE_CONNECTION=redis', $envContents);
        $this->assertStringContainsString('SESSION_DRIVER=redis', $envContents);
        $this->assertStringContainsString('REVERB_HOST=0.0.0.0', $envContents);

        $this->assertStringContainsString('DB_CONNECTION=pgsql', $exampleContents);
        $this->assertStringContainsString('DB_HOST=db', $exampleContents);
        $this->assertStringContainsString('DB_PORT=5432', $exampleContents);
        $this->assertStringContainsString('REDIS_HOST=redis', $exampleContents);
        $this->assertStringContainsString('CACHE_STORE=redis', $exampleContents);
        $this->assertStringContainsString('QUEUE_CONNECTION=redis', $exampleContents);
        $this->assertStringContainsString('SESSION_DRIVER=redis', $exampleContents);
        $this->assertStringContainsString('REVERB_HOST=0.0.0.0', $exampleContents);
    }
}
