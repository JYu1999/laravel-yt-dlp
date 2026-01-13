<?php

declare(strict_types=1);

namespace App\Domain\Downloads\Services;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Process\Process;

final class YtDlpService
{
    private string $binary;

    public function __construct(string $binary = 'yt-dlp')
    {
        $this->binary = $binary;
    }

    public function getVersion(): string
    {
        $process = $this->buildProcess([$this->binary, '--version']);
        $process->setTimeout(30);

        return trim($this->runProcess($process));
    }

    /**
     * @return array<string, mixed>
     */
    public function getVideoInfo(string $url): array
    {
        $this->assertValidUrl($url);

        $process = $this->buildProcess([
            $this->binary,
            '--dump-json',
            '--no-playlist',
            '--skip-download',
            $url,
        ]);
        $process->setTimeout(60);

        $output = $this->runProcess($process);
        $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    public function downloadVideo(string $url, string $outputPath): string
    {
        $this->assertValidUrl($url);

        $process = $this->buildProcess([
            $this->binary,
            '-f',
            'bv+ba/b',
            '-o',
            $outputPath,
            $url,
        ]);
        $process->setTimeout(300);

        $this->runProcess($process);

        if (!file_exists($outputPath)) {
            throw new RuntimeException("Download process finished but file not found at: {$outputPath}");
        }

        return $outputPath;
    }

    private function buildProcess(array $command): Process
    {
        return new Process($command);
    }

    private function runProcess(Process $process): string
    {
        $process->run();

        if (!$process->isSuccessful()) {
            $message = trim($process->getErrorOutput());

            if ($message === '') {
                $message = trim($process->getOutput());
            }

            if ($message === '') {
                $message = 'yt-dlp command failed';
            }

            throw new RuntimeException($message);
        }

        return $process->getOutput();
    }

    private function assertValidUrl(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Invalid URL.');
        }
    }
}
