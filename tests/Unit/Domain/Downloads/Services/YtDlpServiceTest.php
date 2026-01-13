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
        $this->tempFiles[] = $outputPath;

        $binary = $this->createFakeBinary(
            "#!/bin/sh\n" .
            "if [ \"$1\" = \"-f\" ]; then\n" .
            "  touch \"$4\"\n" .
            "  exit 0\n" .
            "fi\n" .
            "exit 1\n"
        );

        $service = new YtDlpService($binary);

        $result = $service->downloadVideo('https://example.com/watch?v=abc123', $outputPath);

        self::assertSame($outputPath, $result);
        self::assertFileExists($outputPath);
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
