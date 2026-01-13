<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Domain\Downloads\Services\VideoInfoService;
use App\Domain\Downloads\Services\YtDlpService;
use App\Livewire\VideoDownloader;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\HasFakeYtDlp;

final class VideoDownloaderTest extends TestCase
{
    use HasFakeYtDlp;

    public function testItValidatesUrl(): void
    {
        Livewire::test(VideoDownloader::class)
            ->set('url', 'not-a-url')
            ->call('fetchInfo')
            ->assertHasErrors(['url']);
    }

    public function testItPopulatesMetadataAndDefaults(): void
    {
        config(['cache.default' => 'array']);
        Cache::flush();

        $binary = $this->createFakeBinary(
            "#!/bin/sh\n" .
            "if [ \"$1\" = \"--dump-json\" ]; then\n" .
            "  echo '{\"id\":\"abc123\",\"title\":\"Test Video\",\"thumbnail\":\"https://example.com/thumb.jpg\",\"duration\":3661," .
            "\"formats\":[{\"format_id\":\"22\",\"ext\":\"mp4\",\"vcodec\":\"avc1\",\"height\":720,\"width\":1280,\"filesize\":1048576}," .
            "{\"format_id\":\"18\",\"ext\":\"mp4\",\"vcodec\":\"avc1\",\"height\":360,\"width\":640,\"filesize\":524288}]," .
            "\"subtitles\":{\"en\":[{\"ext\":\"vtt\",\"url\":\"https://example.com/en.vtt\"}]}}'\n" .
            "  exit 0\n" .
            "fi\n" .
            "exit 1\n"
        );

        $service = new VideoInfoService(new YtDlpService($binary));
        app()->instance(VideoInfoService::class, $service);

        Livewire::test(VideoDownloader::class)
            ->set('url', 'https://www.youtube.com/watch?v=abc123')
            ->call('fetchInfo')
            ->assertSet('metadata.id', 'abc123')
            ->assertSet('metadata.title', 'Test Video')
            ->assertSet('metadata.duration_formatted', '01:01:01')
            ->assertSet('selectedFormat', 'mp4')
            ->assertSet('selectedLanguage', 'en');
    }

    public function testItValidatesSelectedFormatOnDownload(): void
    {
        Livewire::test(VideoDownloader::class)
            ->set('metadata', [
                'formats' => [
                    ['format_id' => '22', 'ext' => 'mp4'],
                    ['format_id' => '18', 'ext' => 'mov'],
                ],
                'subtitles' => ['en'],
            ])
            ->set('selectedFormat', 'avi')
            ->call('startDownload')
            ->assertHasErrors(['selectedFormat']);
    }

    public function testItRequiresSubtitleLanguageWhenSubtitlesRequested(): void
    {
        Livewire::test(VideoDownloader::class)
            ->set('metadata', [
                'formats' => [
                    ['format_id' => '22', 'ext' => 'mp4'],
                ],
                'subtitles' => ['en', 'zh-Hans'],
            ])
            ->set('selectedFormat', 'mp4')
            ->set('downloadSubtitles', true)
            ->set('selectedLanguage', '')
            ->call('startDownload')
            ->assertHasErrors(['selectedLanguage']);
    }

    public function testHomePageRendersDownloader(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeLivewire(VideoDownloader::class);
    }
}
