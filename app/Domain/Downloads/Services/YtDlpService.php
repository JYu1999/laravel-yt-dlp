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
        $process->setTimeout(5);

        $output = $this->runProcess($process);
        $decoded = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    public function downloadVideo(string $url, string $outputPath, ?string $format = null, array $options = [], ?callable $onProgress = null): string
    {
        $this->assertValidUrl($url);

        // Ensure output template has an extension placeholder if not present
        $outputTemplate = str_contains($outputPath, '%(ext)s')
            ? $outputPath
            : $outputPath . '.%(ext)s';

        $command = [
            $this->binary,
            '--newline', // Ensure progress parsing works correctly
            '--no-playlist',
            '--no-colors',
        ];

        // Format Selection Logic: Force MP4
        // We prioritize native MP4 video (h264) + m4a audio (aac) to avoid slow transcoding.
        // Fallback to best mp4 container, then generic best.
        $command = array_merge($command, [
            '-f', 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best',
            '--merge-output-format', 'mp4',
        ]);

        // Subtitle Logic
        if (!empty($options['subtitles'])) {
            $command[] = '--write-subs';
            // Use user-provided languages or fail back to 'all'
            // NOTE: yt-dlp uses --sub-langs, not --sub-lang
            $langs = $options['subtitle_langs'] ?? ['all'];
            if (is_array($langs)) {
                $langs = implode(',', $langs);
            }
            $command[] = '--sub-langs';
            $command[] = $langs;
            $command[] = '--convert-subs';
            $command[] = 'srt';
        }

        // Output Template & Path
        $command = array_merge($command, [
            '--print', 'after_move:filepath', // Critical for resolving final path
            '-o', $outputTemplate,
            $url,
        ]);

        $process = $this->buildProcess($command);
        $process->setTimeout(600); // 5 minutes timeout

        $callback = null;

        if ($onProgress !== null) {
            $callback = function (string $type, string $buffer) use ($onProgress): void {
                if ($type === Process::ERR) {
                    return;
                }

                // Parse progress from yt-dlp standard output
                // Example: [download]  23.5% of 10.00MiB at  2.50MiB/s ETA 00:03
                if (preg_match('/\[download\]\s+(\d+(?:\.\d+)?)%.*?ETA\s+([0-9:]+)/', $buffer, $matches)) {
                    $onProgress((float) $matches[1], $matches[2]);
                }
            };
        }

        $output = $this->runProcess($process, $callback);
        
        // Pass $format to resolution logic to help guess extension if needed
        $resolvedPath = $this->resolveDownloadedPath($output, $outputPath, $outputTemplate, $format);

        if ($resolvedPath === null) {
            throw new RuntimeException("Download process finished but file not found at: {$outputPath}. Output: " . substr($output, 0, 200) . "...");
        }

        return $resolvedPath;
    }

    private function buildProcess(array $command): Process
    {
        return new Process($command);
    }

    private function runProcess(Process $process, ?callable $callback = null): string
    {
        $process->run($callback);

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

    private function resolveDownloadedPath(string $output, string $outputPath, string $outputTemplate, ?string $requestedFormat = null): ?string
    {
        // 1. Try to read from --print output (lines that look like file paths)
        $lines = preg_split('/\R/', trim($output)) ?: [];
        foreach (array_reverse($lines) as $line) {
            $candidate = trim($line);
            if ($candidate !== '' && file_exists($candidate) && !str_starts_with($candidate, '[download]')) {
                return $candidate;
            }
        }

        // 2. Direct check if outputPath exists (unlikely if it has templates)
        if (file_exists($outputPath)) {
            return $outputPath;
        }

        // 3. Construct probable path based on template and requested format
        $basePath = str_replace(['%(ext)s', '.%(ext)s'], '', $outputTemplate);
        
        // If user requested mp4, check that first
        if ($requestedFormat === 'mp4' && file_exists($basePath . '.mp4')) {
            return $basePath . '.mp4';
        }

        // 4. Check common extensions
        $extensions = ['mp4', 'webm', 'mkv', 'avi', 'flv', 'm4a', 'mp3'];
        foreach ($extensions as $ext) {
            if (file_exists($basePath . '.' . $ext)) {
                return $basePath . '.' . $ext;
            }
        }

        // 5. Last resort: glob search (risky if multiple files match)
        // Only do this if we are desperate
        $dir = dirname($outputPath);
        if (is_dir($dir)) {
             $possibleFiles = glob($basePath . '.*');
             if ($possibleFiles) {
                 // Return the most recently modified one
                 usort($possibleFiles, fn($a, $b) => filemtime($b) <=> filemtime($a));
                 return $possibleFiles[0];
             }
        }

        return null;
    }

    private function assertValidUrl(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Invalid URL.');
        }
    }
}
