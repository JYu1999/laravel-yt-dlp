# Story 1.7: Reverb Real-Time Progress

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a User,
I want to see real-time download progress updates,
So that I know the download is in progress and can estimate when it will complete.

## Acceptance Criteria

1. **WebSocket Connection Setup:**
   - Front-end establishes WebSocket connection to Laravel Reverb via Laravel Echo.
   - Connection uses channel `download.{id}` where `{id}` is the `DownloadTask` ID.
   - Connection is established automatically when a download task is created.

2. **Progress Event Handling:**
   - Frontend listens for `DownloadProgressUpdated` events on the channel.
   - Events contain: `status`, `percentage` (float 0-100), `eta` (string).
   - Progress bar updates in real-time (≥ every 500ms per NFR3).
   - Percentage and ETA are displayed to the user.

3. **Completion Event Handling:**
   - Frontend listens for `DownloadCompleted` event.
   - Upon completion, display success notification with download link.
   - Trigger automatic file download via browser (or show download button).

4. **Failure Event Handling:**
   - Frontend listens for `DownloadFailed` event.
   - Display user-friendly error message (not raw exception).
   - Allow user to retry the download.

5. **Polling Fallback (Graceful Degradation):**
   - If WebSocket connection fails, automatically fall back to polling mode.
   - Polling interval: every 2 seconds via API endpoint `GET /api/downloads/{id}`.
   - Seamless transition without user intervention.

6. **Broadcasting Configuration:**
   - Configure `BROADCAST_CONNECTION=reverb` in `.env.example`.
   - Configure Reverb server settings (host, port, app credentials).
   - Add Reverb configuration to `config/broadcasting.php`.

## Tasks / Subtasks

- [x] **Broadcasting Configuration** (AC: 6)
  - [x] Install Laravel Reverb package if not already installed
  - [x] Publish Reverb configuration (`php artisan reverb:install`)
  - [x] Update `.env.example` with Reverb settings (APP_ID, KEY, SECRET)
  - [x] Set `BROADCAST_CONNECTION=reverb` in `.env.example`
  - [x] Configure `config/broadcasting.php` for Reverb driver
  - [x] Verify Reverb container in docker-compose.yml is configured correctly

- [x] **Channel Authorization** (AC: 1)
  - [x] Create `routes/channels.php` if not exists
  - [x] Define public channel authorization for `download.{id}`
  - [x] Public channels don't require auth (anonymous users can listen)

- [x] **Event Payload Enhancement** (AC: 2, 3, 4)
  - [x] Verify `DownloadProgressUpdated` includes all required fields
  - [x] Verify `DownloadCompleted` includes download URL
  - [x] Verify `DownloadFailed` includes user-friendly error message
  - [x] Add `broadcastAs()` method to events for clean event names
  - [x] Add `broadcastWith()` method to format payload properly

- [x] **Frontend: Laravel Echo Setup** (AC: 1)
  - [x] Install Laravel Echo and Pusher.js (`npm install laravel-echo pusher-js`)
  - [x] Configure Echo in `resources/js/echo.js` or `bootstrap.js`
  - [x] Set Vite environment variables for Reverb connection (VITE_REVERB_*)
  - [x] Update `.env.example` with VITE_REVERB_* variables

- [x] **Frontend: Livewire Progress Component** (AC: 2, 3, 4)
  - [x] Create `DownloadProgress` Livewire component (or extend VideoDownloader)
  - [x] Add JavaScript to subscribe to `download.{taskId}` channel
  - [x] Handle `DownloadProgressUpdated` event - update progress bar
  - [x] Handle `DownloadCompleted` event - show success + trigger download
  - [x] Handle `DownloadFailed` event - show error message
  - [x] Add progress bar UI (percentage, ETA display)

- [x] **Frontend: Polling Fallback** (AC: 5)
  - [x] Create API endpoint `GET /api/downloads/{id}` for status polling
  - [x] Implement fallback logic: if Echo connection fails, switch to polling
  - [x] Polling interval: 2 seconds
  - [x] Detect connection loss and automatically switch modes

- [x] **Testing**
  - [x] Unit test: Verify events broadcast to correct channel
  - [x] Unit test: Verify event payloads contain required data
  - [x] Feature test: End-to-end broadcast test with Reverb
  - [x] Feature test: API polling endpoint returns correct status

## Dev Notes

### Architecture Compliance

- **Real-time Stack:** Laravel Reverb + Laravel Echo (per architecture.md)
- **Broadcast Channel:** `download.{id}` - public channel (no auth required for MVP)
- **Events:** Already implemented in Story 1.6 with `ShouldBroadcast` interface
- **Fallback:** Polling via REST API as backup

### Previous Story Context (1.6)

Events are already created and implement `ShouldBroadcast`:
- `app/Events/DownloadProgressUpdated.php` - broadcasts to `download.{task.id}`
- `app/Events/DownloadCompleted.php` - broadcasts to `download.{task.id}`
- `app/Events/DownloadFailed.php` - broadcasts to `download.{task.id}`

The `DownloadJob` already emits progress events:
```php
event(new DownloadProgressUpdated($this->task, $percentage, $eta));
event(new DownloadCompleted($this->task, $filePath));
event(new DownloadFailed($this->task, $exception->getMessage()));
```

### Docker Environment

Reverb container is already configured in `docker-compose.yml`:
```yaml
reverb:
  command: php artisan reverb:start
  ports:
    - "6001:6001"
```

### Current State Analysis

1. **Events:** Already broadcast-ready (implement `ShouldBroadcast`)
2. **Reverb Container:** Already in docker-compose.yml
3. **BROADCAST_CONNECTION:** Currently set to `log` - needs to be `reverb`
4. **Echo/Frontend:** Not yet configured - needs installation
5. **API Endpoint:** Not yet created for polling fallback

### Library Versions (from project-context.md)

- Laravel Reverb: Included with Laravel 12
- Laravel Echo: Latest stable
- Pusher.js: Latest stable (Echo uses Pusher protocol)
- Vite: 7.0.7

### Technical Requirements

1. **Reverb Configuration:**
   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_APP_ID=laravel-yt-dlp
   REVERB_APP_KEY=your-app-key
   REVERB_APP_SECRET=your-app-secret
   REVERB_HOST=reverb
   REVERB_PORT=6001
   REVERB_SCHEME=http

   VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
   VITE_REVERB_HOST="localhost"
   VITE_REVERB_PORT="${REVERB_PORT}"
   VITE_REVERB_SCHEME="${REVERB_SCHEME}"
   ```

2. **Echo Configuration (resources/js/echo.js):**
   ```javascript
   import Echo from 'laravel-echo';
   import Pusher from 'pusher-js';

   window.Pusher = Pusher;
   window.Echo = new Echo({
       broadcaster: 'reverb',
       key: import.meta.env.VITE_REVERB_APP_KEY,
       wsHost: import.meta.env.VITE_REVERB_HOST,
       wsPort: import.meta.env.VITE_REVERB_PORT ?? 6001,
       wssPort: import.meta.env.VITE_REVERB_PORT ?? 6001,
       forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
       enabledTransports: ['ws', 'wss'],
   });
   ```

3. **Channel Definition (routes/channels.php):**
   ```php
   // Public channel - no authorization needed for MVP
   Broadcast::channel('download.{id}', function () {
       return true; // Public access
   });
   ```

4. **API Endpoint for Polling:**
   ```php
   // routes/api.php
   Route::get('/downloads/{id}', [DownloadController::class, 'show']);
   ```

### Progress UI Requirements

- Progress bar component showing percentage (0-100%)
- ETA display (e.g., "~2 minutes remaining")
- Status indicator (queued → downloading → completed/failed)
- Auto-trigger file download on completion
- User-friendly error messages on failure

### Project Structure Notes

- **Livewire Components:** `app/Livewire/VideoDownloader.php` (extend or create new)
- **Views:** `resources/views/livewire/video-downloader.blade.php`
- **JS:** `resources/js/echo.js` or `resources/js/bootstrap.js`
- **API Controllers:** `app/Http/Controllers/Api/DownloadController.php`
- **Routes:** `routes/channels.php`, `routes/api.php`

### References

- [Story 1.6](1-6-queue-system-download-job.md) - Events and Job implementation
- [Architecture](../planning-artifacts/architecture.md) - Real-time communication patterns
- [PRD](../planning-artifacts/prd.md) - NFR3: Progress updates ≥ every 500ms
- [Project Context](../../_bmad-output/project-context.md) - Laravel Reverb + Echo stack

## Change Log

- 2026-01-14: Story created by SM Agent - comprehensive context analysis completed
- 2026-01-14: Dev implementation completed (Reverb config, Echo UI, polling fallback, tests)

## Dev Agent Record

### Agent Model Used

GPT-5

### Debug Log References

- `php artisan reverb:install` aborted on interactive prompt after publishing config/channels; config files are present.
### Implementation Plan

- Install Reverb and publish config, then align env examples and add config coverage tests.

### Completion Notes List

- ✅ Broadcasting config in place (Reverb install/publish, env example updates, config + channels wiring); tests: `php artisan test`.
- ✅ Public download channel authorization set for `download.{id}`; tests: `php artisan test`.
- ✅ Event payloads now include status + clean event names; failure event uses user-facing error; tests: `php artisan test`.
- ✅ Echo configured in frontend with Reverb envs; tests: `php artisan test`.
- ✅ Livewire progress UI wired to Echo events and auto-download; tests: `docker compose exec app php artisan test`.
- ✅ Polling fallback with API status endpoint and progress persistence; tests: `docker compose exec app php artisan test`.
- ✅ Broadcast + polling tests added (Reverb broadcast check, API status endpoint); tests: `docker compose exec app php artisan test`.
- ✅ Fix Livewire event handlers to accept empty payloads; set browser Reverb host to localhost in `.env`.
- ✅ Align Reverb server port for container (REVERB_SERVER_PORT) and add host fallback in Echo config.

### File List

- .env
- .env.example
- bootstrap/app.php
- composer.json
- composer.lock
- config/broadcasting.php
- config/reverb.php
- _bmad-output/implementation-artifacts/1-7-reverb-real-time-progress.md
- _bmad-output/implementation-artifacts/sprint-status.yaml
- app/Events/DownloadCompleted.php
- app/Events/DownloadFailed.php
- app/Events/DownloadProgressUpdated.php
- app/Jobs/DownloadJob.php
- app/Livewire/VideoDownloader.php
- app/Domain/Downloads/Models/DownloadTask.php
- app/Http/Controllers/Api/DownloadController.php
- bootstrap/app.php
- database/migrations/2026_01_14_000001_add_progress_fields_to_download_tasks_table.php
- .env
- package.json
- package-lock.json
- resources/js/bootstrap.js
- resources/js/echo.js
- resources/views/livewire/video-downloader.blade.php
- routes/channels.php
- routes/api.php
- tests/Feature/Broadcasting/ReverbBroadcastTest.php
- tests/Feature/Infrastructure/BroadcastChannelsTest.php
- tests/Feature/Infrastructure/BroadcastingConfigTest.php
- tests/Feature/Infrastructure/DockerComposeTest.php
- tests/Feature/Infrastructure/FrontendEchoConfigTest.php
- tests/Feature/Api/DownloadStatusTest.php
- tests/Feature/Domain/Downloads/DownloadTaskMigrationTest.php
- tests/Feature/Livewire/VideoDownloaderTest.php
- tests/Unit/Jobs/DownloadJobTest.php
- tests/Unit/Events/DownloadEventsTest.php
- tests/Unit/Jobs/DownloadJobTest.php
