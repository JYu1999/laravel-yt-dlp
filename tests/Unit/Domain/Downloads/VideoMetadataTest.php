<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Downloads;

use App\Domain\Downloads\DTO\VideoMetadata;
use Tests\TestCase;

final class VideoMetadataTest extends TestCase
{
    public function testItMapsAndFiltersVideoMetadata(): void
    {
        $payload = [
            'id' => 'abc123',
            'title' => 'Test Video',
            'thumbnail' => 'https://example.com/thumb.jpg',
            'duration' => 3661,
            'formats' => [
                ['format_id' => '18', 'ext' => 'mp4', 'vcodec' => 'avc1', 'height' => 360, 'width' => 640],
                ['format_id' => '140', 'ext' => 'm4a', 'vcodec' => 'none', 'height' => null, 'width' => null],
                ['format_id' => '22', 'ext' => 'mp4', 'vcodec' => 'avc1', 'height' => 720, 'width' => 1280],
                ['format_id' => '37', 'ext' => 'mp4', 'vcodec' => 'avc1', 'height' => 1080, 'width' => 1920],
                ['format_id' => '99', 'ext' => 'mov', 'vcodec' => 'avc1', 'height' => 480, 'width' => 854],
                ['format_id' => 'bad', 'ext' => 'mp4', 'vcodec' => 'none', 'height' => 720, 'width' => 1280],
            ],
            'subtitles' => [
                'zh-Hant' => [['ext' => 'vtt', 'url' => 'https://example.com/zh.vtt']],
                'en' => [['ext' => 'vtt', 'url' => 'https://example.com/en.vtt']],
            ],
        ];

        $metadata = VideoMetadata::fromYtDlp($payload);

        self::assertSame('abc123', $metadata->id);
        self::assertSame('Test Video', $metadata->title);
        self::assertSame('https://example.com/thumb.jpg', $metadata->thumbnail);
        self::assertSame(3661, $metadata->duration);
        self::assertSame(['en', 'zh-Hant'], $metadata->subtitles);
        self::assertSame(['37', '22', '99', '18'], array_column($metadata->formats, 'format_id'));
    }
}
