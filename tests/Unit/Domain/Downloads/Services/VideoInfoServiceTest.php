<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Downloads\Services;

use App\Domain\Downloads\Services\VideoInfoService;
use App\Domain\Downloads\Services\YtDlpService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\HasFakeYtDlp;

final class VideoInfoServiceTest extends TestCase
{
    use HasFakeYtDlp;

    public function testItCachesVideoInfoByUrl(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();

        $counterPath = sys_get_temp_dir() . '/yt-dlp-counter-' . uniqid('', true);
        $this->tempFiles[] = $counterPath;

        $script = <<<'SCRIPT'
#!/bin/sh
count=0
if [ -f "__COUNTER__" ]; then
  count=$(cat "__COUNTER__")
fi
count=$((count + 1))
echo "$count" > "__COUNTER__"
if [ "$1" = "--dump-json" ]; then
  echo '{"id":"abc123","title":"Test"}'
  exit 0
fi
exit 1
SCRIPT;

        $binary = $this->createFakeBinary(str_replace('__COUNTER__', $counterPath, $script));

        $ytDlp = new YtDlpService($binary);

        $service = new VideoInfoService($ytDlp);
        $url = 'https://example.com/watch?v=abc123';

        $first = $service->getVideoInfo($url);
        $second = $service->getVideoInfo($url);

        $expectedKey = 'video_info:' . md5($url);

        self::assertSame(['id' => 'abc123', 'title' => 'Test'], $first);
        self::assertSame(['id' => 'abc123', 'title' => 'Test'], $second);
        self::assertSame('1', trim((string) file_get_contents($counterPath)));
        self::assertTrue(Cache::has($expectedKey));
    }
}
