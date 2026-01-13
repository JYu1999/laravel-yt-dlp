<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Downloads\Services;

use App\Domain\Downloads\Services\YtDlpService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Traits\HasFakeYtDlp;

final class YtDlpServiceTest extends TestCase
{
    use HasFakeYtDlp;

    public function testItReturnsVersionFromYtDlp(): void
    {
        $binary = $this->createFakeBinary(
            "#!/bin/sh\n" .
            "if [ \"$1\" = \"--version\" ]; then\n" .
            "  echo \"2025.01.01\"\n" .
            "  exit 0\n" .
            "fi\n" .
            "exit 1\n"
        );

        $service = new YtDlpService($binary);

        self::assertSame('2025.01.01', $service->getVersion());
    }

    public function testItReturnsVideoInfoFromYtDlp(): void
    {
        $binary = $this->createFakeBinary(
            "#!/bin/sh\n" .
            "if [ \"$1\" = \"--dump-json\" ]; then\n" .
            "  echo '{\"id\":\"abc123\",\"title\":\"Test\"}'\n" .
            "  exit 0\n" .
            "fi\n" .
            "exit 1\n"
        );

        $service = new YtDlpService($binary);

        $info = $service->getVideoInfo('https://example.com/watch?v=abc123');

        self::assertSame('abc123', $info['id']);
        self::assertSame('Test', $info['title']);
    }

    public function testItDownloadsVideoToOutputPath(): void
    {
        $outputPath = sys_get_temp_dir() . '/yt-dlp-output-' . uniqid('', true);
        $expectedPath = $outputPath . '.mp4';
        $this->tempFiles[] = $expectedPath;

        $binary = $this->createFakeBinary(
            "#!/bin/sh\n" .
            $this->createMockScript($expectedPath)
        );

        $service = new YtDlpService($binary);

        $result = $service->downloadVideo('https://example.com/watch?v=abc123', $outputPath);

        self::assertSame($expectedPath, $result);
        self::assertFileExists($expectedPath);
    }

    public function testItForcefullyDownloadsMp4(): void
    {
        $outputPath = sys_get_temp_dir() . '/yt-dlp-force-mp4-' . uniqid('', true);
        $expectedPath = $outputPath . '.mp4';
        $this->tempFiles[] = $expectedPath;

        $binary = $this->createFakeBinary(
            "#!/bin/sh\n" .
            "has_format_rule=0\n" .
            "has_merge_rule=0\n" .
            "for arg in \"$@\"; do\n" .
            "  if [ \"\$arg\" = \"bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best\" ]; then\n" .
            "    has_format_rule=1\n" .
            "  fi\n" .
            "  if [ \"\$arg\" = \"--merge-output-format\" ]; then\n" .
            "    has_merge_rule=1\n" .
            "  fi\n" .
            "done\n" .
            "\n" .
            "if [ \$has_format_rule -eq 0 ] || [ \$has_merge_rule -eq 0 ]; then\n" .
            "  echo \"Error: Missing strict MP4 format rules\" >&2\n" .
            "  exit 1\n" .
            "fi\n" .
            $this->createMockScript($expectedPath)
        );

        $service = new YtDlpService($binary);
        
        // Pass 'webm' or any other format - it should be ignored in favor of forced MP4
        $result = $service->downloadVideo('https://example.com/watch?v=abc123', $outputPath, 'webm');
        
        self::assertSame($expectedPath, $result);
        self::assertFileExists($expectedPath);
    }



    private function createMockScript(string $outputPath): string
    {
        return 
            "# Find the -o argument value\n" .
            "output=\"\"\n" .
            "while [ \$# -gt 0 ]; do\n" .
            "  if [ \"\$1\" = \"-o\" ]; then\n" .
            "    output=\"\$2\"\n" .
            "    break\n" .
            "  fi\n" .
            "  shift\n" .
            "done\n" .
            "\n" .
            "if [ -z \"\$output\" ]; then\n" .
            "  echo \"Error: No output path specified\" >&2\n" .
            "  exit 1\n" .
            "fi\n" .
            "\n" .
            "# Create the file\n" .
            "touch \"$outputPath\"\n" .
            "echo \"$outputPath\"\n" .
            "exit 0\n";
    }

    public function testItRejectsInvalidUrls(): void
    {
        $binary = $this->createFakeBinary("#!/bin/sh\nexit 0\n");
        $service = new YtDlpService($binary);

        $this->expectException(InvalidArgumentException::class);

        $service->getVideoInfo('not-a-url');
    }

    public function testItThrowsWhenProcessFails(): void
    {
        $binary = $this->createFakeBinary("#!/bin/sh\nexit 2\n");
        $service = new YtDlpService($binary);

        $this->expectException(RuntimeException::class);

        $service->getVersion();
    }
}
