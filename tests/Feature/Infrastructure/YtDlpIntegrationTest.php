<?php

declare(strict_types=1);

namespace Tests\Feature\Infrastructure;

use App\Domain\Downloads\Services\YtDlpService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Tests\Traits\HasFakeYtDlp;

final class YtDlpIntegrationTest extends TestCase
{
    use HasFakeYtDlp;

    public function testItCanGetYtDlpVersion(): void
    {
        $binary = $this->resolveBinaryOrSkip();
        $service = new YtDlpService($binary);

        $version = $service->getVersion();

        self::assertNotSame('', $version);
    }

    public function testItCanFetchVideoMetadata(): void
    {
        $testUrl = getenv('YTDLP_TEST_URL') ?: null;

        if ($testUrl === null) {
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

            return;
        }

        $binary = $this->resolveBinaryOrSkip();
        $service = new YtDlpService($binary);

        $info = $service->getVideoInfo($testUrl);

        self::assertArrayHasKey('id', $info);
    }

    public function testItCanDownloadVideo(): void
    {
        $testUrl = getenv('YTDLP_TEST_URL') ?: null;
        $outputPath = sys_get_temp_dir() . '/yt-dlp-test-' . uniqid('', true) . '.mp4';
        $this->tempFiles[] = $outputPath;

        if ($testUrl === null) {
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

            return;
        }

        $binary = $this->resolveBinaryOrSkip();
        $service = new YtDlpService($binary);

        $result = $service->downloadVideo($testUrl, $outputPath);

        self::assertSame($outputPath, $result);
        self::assertFileExists($outputPath);
    }

    public function testItHandlesProcessFailures(): void
    {
        $binary = $this->createFakeBinary("#!/bin/sh\nexit 2\n");
        $service = new YtDlpService($binary);

        $this->expectException(RuntimeException::class);

        $service->getVersion();
    }

    private function resolveBinaryOrSkip(): string
    {
        $binary = getenv('YTDLP_BINARY') ?: null;

        if (is_string($binary) && $binary !== '') {
            if (!file_exists($binary) || !is_executable($binary)) {
                $this->markTestSkipped('YTDLP_BINARY is set but not executable.');
            }

            return $binary;
        }

        $finder = new ExecutableFinder();
        $resolved = $finder->find('yt-dlp');

        if ($resolved === null) {
            $this->markTestSkipped('yt-dlp not found; run tests inside Docker.');
        }

        return $resolved;
    }
}
