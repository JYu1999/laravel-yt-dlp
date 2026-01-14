# Story 1.6: Queue System & Download Job

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a System,
I want to process video downloads asynchronously using a queue system,
so that the user interface remains responsive and the system can handle multiple downloads efficiently without blocking.

## Acceptance Criteria

1.  **Download Task Data Model:**
    -   A `DownloadTask` model exists with fields: `id`, `user_id` (nullable), `ip_address`, `video_url`, `format`, `status`, `file_path`, `title`, `meta_data` (JSON), `error_message`, timestamps.
    -   `status` is an Enum with values: `pending`, `downloading`, `completed`, `failed`.
    -   Database table `download_tasks` is created via migration.

2.  **Concurrency Control:**
    -   **System-wide:** Limited by the number of queue workers (default configuration handles this, but explicitly documented as 10 max). Redis throttle can also be used.
    -   **Per-user:** Users (identified by ID or IP) are limited to 1 active download (`pending` or `downloading`) at a time.
    -   If a user tries to start a second download, the request is rejected with a clear message.

3.  **Download Job Implementation:**
    -   A `DownloadJob` class handles the actual processing.
    -   **Input:** Receives a `DownloadTask` instance.
    -   **Process:**
        -   Updates task status to `downloading`.
        -   Calls `YtDlpService` to perform the download.
        -   (Stub/Preparation) Emits `DownloadProgressUpdated` events (to be consumed by Reverb in 1.7).
    -   **Success:**
        -   Updates task status to `completed`.
        -   Updates `file_path` with the downloaded file location.
        -   Emits `DownloadCompleted` event.
    -   **Failure:**
        -   Updates task status to `failed`.
        -   Records `error_message`.
        -   Emits `DownloadFailed` event.

4.  **UI Integration:**
    -   The `VideoDownloader` Livewire component initiates the process.
    -   Instead of just validating, it now:
        -   Checks per-user concurrency.
        -   Creates a `DownloadTask` record (status: `pending`).
        -   Dispatches `DownloadJob` to the queue.
        -   Transitions UI to a "Job Started" state (providing Task ID).

5.  **Events System:**
    -   Event classes `DownloadProgressUpdated`, `DownloadCompleted`, `DownloadFailed` are created.
    -   These events implement `ShouldBroadcast` (preparing for 1.7).

## Tasks / Subtasks

-   [x] **Domain Modeling**
    -   [x] Create `DownloadStatus` Enum (`pending`, `downloading`, `completed`, `failed`).
    -   [x] Create `DownloadTask` Model & Migration (`download_tasks` table).
    -   [x] Run migration.
-   [x] **Events Implementation**
    -   [x] Create `DownloadProgressUpdated` event (props: `DownloadTask $task`, `float $percentage`, `string $eta`).
    -   [x] Create `DownloadCompleted` event (props: `DownloadTask $task`, `string $downloadUrl`).
    -   [x] Create `DownloadFailed` event (props: `DownloadTask $task`, `string $error`).
-   [x] **Job Implementation**
    -   [x] Create `DownloadJob` in `app/Jobs`.
    -   [x] Implement `handle()` method with status updates and `YtDlpService` call.
    -   [x] Add try/catch block for error handling and status updates.
    -   [x] Implement progress callback in `YtDlpService` call (pass closure to service or handle within job if service supports it). *Note: `YtDlpService` might need update to support progress callback.*
-   [x] **Service/Action Layer**
    -   [x] Create `CreateDownload` Action (or method in Service) to handle concurrency check, model creation, and job dispatch.
    -   [x] Implement `checkConcurrency(string $ip, ?int $userId)` logic.
-   [x] **Livewire Integration**
    -   [x] Update `VideoDownloader::startDownload` to use the `CreateDownload` action.
    -   [x] Handle concurrency exception (show error message).
    -   [x] On success, store `taskId` in component state (to eventually trigger redirection or show progress UI).

## Dev Notes

-   **Concurrency Strategy:** Use `Redis::funnel` or simple DB count check for per-user concurrency. DB check `DownloadTask::where(...)->whereIn('status', ['pending', 'downloading'])->count()` is sufficient for MVP.
-   **YtDlpService Update:** You might need to modify `YtDlpService::downloadVideo` to accept a `callable $onProgress` so the Job can broadcast events during the download. `Symfony\Component\Process\Process` supports a callback during run.
-   **Events:** Even though Reverb integration is next, make these events implement `ShouldBroadcast` now. Channel name should be `download.{id}`.
-   **Testing:**
    -   Unit test `DownloadJob` (mock `YtDlpService`).
    -   Feature test concurrency limit (try starting 2 downloads).

### Project Structure Notes

-   **Models:** `app/Domain/Downloads/Models/DownloadTask.php`
-   **Enums:** `app/Domain/Downloads/Enums/DownloadStatus.php`
-   **Jobs:** `app/Jobs/DownloadJob.php`
-   **Events:** `app/Events/`

### References

-   [Story 1.3](1-3-integrate-yt-dlp.md) - `YtDlpService` implementation.
-   [Story 1.5](1-5-download-page-ui.md) - UI Component.
-   [PRD - Concurrency](../planning-artifacts/prd.md) - "Max 1 concurrent download per user".

## Change Log

- 2026-01-14: Implemented queue-backed download flow with tasks, events, job, concurrency checks, and Livewire integration; added tests.
- 2026-01-14: Code review fixes - Added system-wide concurrency limit (max 10), cleanup on failure, user relationship, TODO comments for future improvements.

## Dev Agent Record

### Agent Model Used

GPT-5

### Debug Log References

- Tests (Docker): `docker compose exec app vendor/bin/phpunit`
- Tests (Docker): `docker compose exec app vendor/bin/phpunit tests/Unit/Jobs/DownloadJobTest.php`
- Tests (Docker): `docker compose exec app vendor/bin/phpunit tests/Unit/Domain/Downloads/Actions/CreateDownloadTest.php`
- Tests (Docker): `docker compose exec app vendor/bin/phpunit tests/Feature/Livewire/VideoDownloaderTest.php`

### Completion Notes List

- Implemented `DownloadStatus` enum and `DownloadTask` model/migration; added tests for enum values and download_tasks schema (phpunit targeted run).
- Added broadcastable download events with channel `download.{id}` and unit tests (phpunit in Docker).
- Implemented `DownloadJob` with status transitions, progress/completion/failure events, and updated `YtDlpService` to parse progress output (phpunit in Docker).
- Added `CreateDownload` action with per-user/IP concurrency checks, plus unit tests (phpunit in Docker).
- Updated Livewire downloader to dispatch jobs, surface concurrency errors, and store task IDs; added feature tests (phpunit in Docker).
- **Code Review (2026-01-14):** Fixed 3 HIGH + 4 MEDIUM + 3 LOW issues:
  - HIGH: Implemented system-wide concurrency limit (max 10 concurrent downloads) in CreateDownload action
  - HIGH: Added comprehensive tests for system-level concurrency (testItBlocksDownloadWhenSystemReachesMaxConcurrency, testItAllowsDownloadWhenSystemBelowMaxConcurrency)
  - MEDIUM: Added cleanup logic in DownloadJob to remove partial files on failure (cleanupPartialDownload method)
  - LOW: Removed duplicate metadata assignment in VideoDownloader
  - LOW: Added user() relationship to DownloadTask model
  - LOW: Changed directory permissions from 0775 to 0755 for better security
  - Added TODO comments for future improvements: streaming delivery, signed URLs, automatic cleanup based on retention policy

### File List

- app/Domain/Downloads/Enums/DownloadStatus.php
- app/Domain/Downloads/Exceptions/DownloadConcurrencyException.php
- app/Domain/Downloads/Models/DownloadTask.php
- app/Domain/Downloads/Actions/CreateDownload.php
- app/Events/DownloadCompleted.php
- app/Events/DownloadFailed.php
- app/Events/DownloadProgressUpdated.php
- app/Jobs/DownloadJob.php
- app/Livewire/VideoDownloader.php
- database/migrations/0001_01_01_000003_create_download_tasks_table.php
- app/Domain/Downloads/Services/YtDlpService.php
- Makefile
- resources/views/livewire/video-downloader.blade.php
- tests/Feature/Domain/Downloads/DownloadTaskMigrationTest.php
- tests/Feature/Livewire/VideoDownloaderTest.php
- tests/Unit/Domain/Downloads/Enums/DownloadStatusTest.php
- tests/Unit/Events/DownloadEventsTest.php
- tests/Unit/Jobs/DownloadJobTest.php
- tests/Unit/Domain/Downloads/Actions/CreateDownloadTest.php
