<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Downloads\DTO;

use App\Domain\Downloads\DTO\VideoMetadata;
use PHPUnit\Framework\TestCase;

final class VideoMetadataTest extends TestCase
{
    public function testItFiltersAndSortsFormatsCorrectly(): void
    {
        $payload = [
            'formats' => [
                ['format_id' => '1', 'ext' => 'mp4', 'height' => 360, 'vcodec' => 'avc1'], // Keep, low res
                ['format_id' => '2', 'ext' => 'webm', 'height' => 1080, 'vcodec' => 'vp9'], // Drop (webm)
                ['format_id' => '3', 'ext' => 'mp4', 'height' => 1080, 'vcodec' => 'avc1'], // Keep, high res
                ['format_id' => '4', 'ext' => 'mp4', 'vcodec' => 'none'], // Drop (video only/audio only or none)
                ['format_id' => '5', 'ext' => 'mov', 'height' => 720, 'vcodec' => 'avc1'], // Keep, mov
            ],
        ];

        $metadata = VideoMetadata::fromYtDlp($payload);
        $formats = $metadata->formats;

        $this->assertCount(3, $formats);
        
        // Assert sorting: Highest resolution first
        $this->assertEquals('3', $formats[0]['format_id']); // 1080p mp4
        $this->assertEquals('5', $formats[1]['format_id']); // 720p mov
        $this->assertEquals('1', $formats[2]['format_id']); // 360p mp4
    }

    public function testItHandlesMissingOrInvalidFormatData(): void
    {
        $payload = [
            'formats' => [
                ['format_id' => '1', 'ext' => 'mp4', 'vcodec' => 'avc1'], // Keep (no height/width is ok, implicit sort)
                [], // Empty array
                'not-an-array', // Invalid type
                ['ext' => 'mp4'], // Missing vcodec
            ],
        ];

        $metadata = VideoMetadata::fromYtDlp($payload);
        
        $this->assertCount(1, $metadata->formats);
        $this->assertEquals('1', $metadata->formats[0]['format_id']);
    }

    public function testItMapsSubtitlesKeysOnly(): void
    {
        $payload = [
            'subtitles' => [
                'en' => [['url' => '...']],
                'fr' => [['url' => '...']],
                'zh-Hans' => [['url' => '...']],
            ],
        ];

        $metadata = VideoMetadata::fromYtDlp($payload);
        
        $this->assertEquals(['en', 'fr', 'zh-Hans'], $metadata->subtitles);
    }

    public function testItHandlesEmptyPayloadGracefully(): void
    {
        $metadata = VideoMetadata::fromYtDlp([]);
        
        $this->assertNull($metadata->id);
        $this->assertNull($metadata->title);
        $this->assertEmpty($metadata->formats);
        $this->assertEmpty($metadata->subtitles);
    }
}
