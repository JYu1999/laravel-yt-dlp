<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Domain\Downloads\Services\VideoInfoService;
use App\Domain\Downloads\Services\YtDlpService;
use App\Domain\Downloads\Enums\DownloadStatus;
use App\Domain\Downloads\Models\DownloadTask;
use App\Livewire\VideoDownloader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Traits\HasFakeYtDlp;

final class VideoDownloaderTest extends TestCase
{
    use HasFakeYtDlp;
    use RefreshDatabase;

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
            ->assertSet('selectedLanguage', 'en');
            // Format assertions removed as format selection is now hidden
    }

    public function testItTogglesSubtitleVisibility(): void
    {
        Livewire::test(VideoDownloader::class)
            ->set('metadata', [
                'formats' => [['format_id' => '22', 'ext' => 'mp4']],
                'subtitles' => ['en', 'fr'],
            ])
            ->set('downloadSubtitles', false)
            ->assertDontSeeHtml('Subtitle Language')
            ->set('downloadSubtitles', true)
            ->assertSeeHtml('Subtitle Language')
            ->assertSeeHtml('<option value="en">en</option>')
            ->assertSeeHtml('<option value="fr">fr</option>');
    }

    // Removed testItValidatesSelectedFormatOnDownload as format selection is removed

    public function testItRequiresSubtitleLanguageWhenSubtitlesRequested(): void
    {
        Livewire::test(VideoDownloader::class)
            ->set('metadata', [
                'formats' => [
                    ['format_id' => '22', 'ext' => 'mp4'],
                ],
                'subtitles' => ['en', 'zh-Hans'],
            ])
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

    public function testItStartsDownloadAndStoresTaskId(): void
    {
        Queue::fake();

        $component = Livewire::test(VideoDownloader::class)
            ->set('url', 'https://example.com/video')
            ->set('metadata', [
                'formats' => [
                    ['format_id' => '22', 'ext' => 'mp4'],
                ],
            ])
            ->call('startDownload');

        $taskId = $component->get('taskId');
        self::assertNotNull($taskId);
        $component->assertSet('downloadNotice', 'Download started. Task ID: ' . $taskId);
    }

    public function testItShowsConcurrencyError(): void
    {
        Queue::fake();

        DownloadTask::create([
            'user_id' => null,
            'ip_address' => '127.0.0.1',
            'video_url' => 'https://example.com/first',
            'format' => 'mp4',
            'status' => DownloadStatus::pending,
        ]);

        Livewire::test(VideoDownloader::class)
            ->set('url', 'https://example.com/second')
            ->set('metadata', [
                'formats' => [
                    ['format_id' => '22', 'ext' => 'mp4'],
                ],
            ])
            ->call('startDownload')
            ->assertSet('downloadError', 'You already have an active download in progress.')
            ->assertSet('taskId', null);
    }

    public function testItUpdatesProgressFromBroadcastPayload(): void
    {
        Livewire::test(VideoDownloader::class)
            ->set('taskId', 10)
            ->call('handleProgressUpdated', [
                'status' => 'downloading',
                'percentage' => 22.5,
                'eta' => '00:30',
            ])
            ->assertSet('progressStatus', 'downloading')
            ->assertSet('progressPercentage', 22.5)
            ->assertSet('progressEta', '00:30');
    }

    public function testItMarksDownloadCompletedFromBroadcastPayload(): void
    {
        Livewire::test(VideoDownloader::class)
            ->set('taskId', 11)
            ->call('handleDownloadCompleted', [
                'status' => 'completed',
                'download_url' => '/downloads/video.mp4',
                'subtitles' => ['/downloads/video.en.srt'],
            ])
            ->assertSet('progressStatus', 'completed')
            ->assertSet('progressPercentage', 100.0)
            ->assertSet('downloadUrl', '/downloads/video.mp4')
            ->assertSet('subtitleUrls', ['/downloads/video.en.srt'])
            ->assertSet('downloadNotice', 'Download completed. Your file is ready.');
    }

    public function testItMarksDownloadFailedFromBroadcastPayload(): void
    {
        Livewire::test(VideoDownloader::class)
            ->set('taskId', 12)
            ->call('handleDownloadFailed', [
                'status' => 'failed',
                'error' => 'Download failed. Please try again.',
            ])
            ->assertSet('progressStatus', 'failed')
            ->assertSet('downloadError', 'Download failed. Please try again.');
    }
}
