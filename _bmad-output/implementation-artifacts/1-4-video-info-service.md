# Story 1.4: Create Video Info Service

Status: done

## Story

As a User,
I want to submit a YouTube link and get video information,
so that I can check video details (title, duration, size) and select formats before downloading.

## Acceptance Criteria

1. Given a user enters a YouTube URL on the download page, when the URL is submitted, then the system validates the URL and immediately shows "Invalid link or video does not exist" for invalid links.
2. Given a valid YouTube URL, when the system fetches metadata via `yt-dlp --dump-json --skip-download --no-playlist`, then the response is returned within 5 seconds.
3. Given metadata is returned, when the UI renders results, then it shows title, thumbnail, duration (HH:MM:SS), estimated filesize, and available formats filtered to MP4/MOV.
4. Given subtitles are available, when the user checks "Download Subtitles", then the UI shows a language dropdown.
5. Given a successful metadata fetch, when the same URL is requested again within 15 minutes, then the result is served from Redis cache key `video_info:{md5_url}`.

## Tasks / Subtasks

- [x] Create `VideoInfoService` to wrap yt-dlp metadata fetch (AC: 2, 5)
  - [x] Add `getVideoInfo(string $url): array` that runs `yt-dlp --dump-json --skip-download --no-playlist`
  - [x] Wrap results in `Cache::remember` with 900s TTL using `video_info:{md5_url}` key
- [x] Create `VideoMetadata` DTO and mapping logic (AC: 3)
  - [x] Map id, title, thumbnail, duration, formats, subtitles
  - [x] Filter formats to mp4/mov with video codec, sort by resolution
- [x] Wire Livewire `VideoDownloader` to fetch info (AC: 1-4)
  - [x] Validate URL with `active_url` or regex
  - [x] Set `$metadata`, `$selectedFormat`, `$downloadSubtitles`, `$selectedSubtitleLanguage`
  - [x] Add loading state and error display
- [x] Add tests for service and component (AC: 1-5)
  - [x] Unit: `VideoInfoServiceTest` with mocked Process output
  - [x] Feature: `VideoDownloaderTest` for validation and state updates

## Dev Notes

- Use Symfony Process with argument escaping; still validate URL input early.
- Error handling should surface friendly messages for common `yt-dlp` failures (unavailable, geo-restricted).
- Keep yt-dlp invocation in service layer, not in Livewire component.

### Project Structure Notes

- Services in `app/Services` or `app/Domain/Downloads/Services` (match current pattern).
- Livewire components in `app/Livewire` and Blade in `resources/views/livewire`.
- Use Flux UI components and `wire:loading` for loading state.

### References

- `_bmad-output/planning-artifacts/epics.md` (Epic 1, Story 1.4)
- `_bmad-output/planning-artifacts/architecture.md` (Tech stack, patterns, naming, caching)
- `_bmad-output/project-context.md` (Strict types, response formats, Livewire rules)

## Dev Agent Record

### Agent Model Used

N/A

### Debug Log References

- docker compose run --rm app vendor/bin/phpunit --filter VideoInfoServiceTest
- docker compose run --rm app vendor/bin/phpunit --filter VideoMetadataTest
- docker compose run --rm app vendor/bin/phpunit --filter VideoDownloaderTest
- docker compose run --rm app vendor/bin/phpunit

### Completion Notes List

- Added VideoInfoService cache wrapper over YtDlpService with 900s TTL and md5 cache key.
- Added unit test covering caching behavior with fake yt-dlp binary.
- Added VideoMetadata DTO with yt-dlp mapping/filtering and format ordering.
- Added unit test verifying mapping, filtering, and subtitle ordering.
- Added VideoDownloader Livewire component with URL validation, loading state, and error handling.
- Added Livewire feature test for validation and metadata state defaults.
- Confirmed service and component tests for AC 1-5 coverage.

### Review Fixes (2026-01-14)
- Updated `YtDlpService` timeout to 5s to strictly match AC 2.
- Updated `video-downloader.blade.php` to use `wire:model.blur` (Livewire 3 fix).

### File List

- app/Domain/Downloads/Services/VideoInfoService.php
- app/Domain/Downloads/DTO/VideoMetadata.php
- app/Http/Requests/VideoInfoRequest.php
- app/Livewire/VideoDownloader.php
- tests/Unit/Domain/Downloads/Services/VideoInfoServiceTest.php
- tests/Unit/Domain/Downloads/VideoMetadataTest.php
- tests/Feature/Livewire/VideoDownloaderTest.php
- resources/views/livewire/video-downloader.blade.php
- _bmad-output/implementation-artifacts/sprint-status.yaml
- _bmad-output/implementation-artifacts/1-4-video-info-service.md

## Change Log

- 2026-01-14: Implemented video info service, metadata DTO, Livewire component, and tests for Story 1.4.
