<?php

declare(strict_types=1);

namespace Tests\Traits;

trait HasFakeYtDlp
{
    /**
     * @var array<int, string>
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    private function createFakeBinary(string $contents): string
    {
        $path = sys_get_temp_dir() . '/yt-dlp-fake-' . uniqid('', true);
        $this->tempFiles[] = $path;

        file_put_contents($path, $contents);
        chmod($path, 0700);

        return $path;
    }
}
