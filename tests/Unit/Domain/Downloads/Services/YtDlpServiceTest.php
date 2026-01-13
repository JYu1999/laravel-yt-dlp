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

    public function testItDownloadsVideoWithMp4Format(): void
    {
        $outputPath = sys_get_temp_dir() . '/yt-dlp-mp4-' . uniqid('', true);
        $expectedPath = $outputPath . '.mp4';
        $this->tempFiles[] = $expectedPath;

        $binary = $this->createFakeBinary(
            "#!/bin/sh\n" .
            "count=0\n" .
            "for arg in \"$@\"; do\n" .
            "  if [ \"\$arg\" = \"--recode-video\" ]; then\n" .
            "    count=$((count+1))\n" .
            "  fi\n" .
            "  if [ \"\$arg\" = \"mp4\" ]; then\n" .
            "    count=$((count+1))\n" .
            "  fi\n" .
            "done\n" .
            "if [ \$count -lt 2 ]; then\n" .
            "  echo \"Error: Missing --recode-video mp4\" >&2\n" .
            "  exit 1\n" .
            "fi\n" .
            $this->createMockScript($expectedPath)
        );

        $service = new YtDlpService($binary);
        
        $result = $service->downloadVideo('https://example.com/watch?v=abc123', $outputPath, 'mp4');
        
        self::assertSame($expectedPath, $result);
        self::assertFileExists($expectedPath);
    }

    public function testItDownloadsVideoWithSubtitles(): void
    {
        $outputPath = sys_get_temp_dir() . '/yt-dlp-subs-' . uniqid('', true);
        $expectedPath = $outputPath . '.mp4';
        $this->tempFiles[] = $expectedPath;

        $binary = $this->createFakeBinary(
            "#!/bin/sh\n" .
            "has_subs=0\n" .
            "has_langs=0\n" .
            "for arg in \"$@\"; do\n" .
            "  if [ \"\$arg\" = \"--write-subs\" ]; then\n" .
            "    has_subs=1\n" .
            "  fi\n" .
            "  if [ \"\$arg\" = \"en,zh\" ]; then\n" .
            "    has_langs=1\n" .
            "  fi\n" .
            "done\n" .
            "if [ \$has_subs -eq 0 ] || [ \$has_langs -eq 0 ]; then\n" .
            "  echo \"Error: Missing subtitle args\" >&2\n" .
            "  exit 1\n" .
            "fi\n" .
            $this->createMockScript($expectedPath)
        );

        $service = new YtDlpService($binary);
        
        $result = $service->downloadVideo('https://example.com/watch?v=abc123', $outputPath, null, [
            'subtitles' => true,
            'subtitle_langs' => ['en', 'zh'],
        ]);
        
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
