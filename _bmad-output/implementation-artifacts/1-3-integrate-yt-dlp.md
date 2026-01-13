# Story 1.3: Integrate yt-dlp and verify basic functionality

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Developer,
I want to integrate the `yt-dlp` tool into the application's Docker environment,
So that the system can execute commands to download YouTube videos and retrieve metadata.

## Acceptance Criteria

1.  **Environment Configuration**
    -   `yt-dlp` is installed and executable in the `app` (PHP-FPM) and `worker` service containers.
    -   Python 3 (required runtime) is installed.
    -   `yt-dlp` is available in the system PATH (e.g., `/usr/local/bin/yt-dlp`).
    -   `yt-dlp` version is verifiable via command line (`yt-dlp --version`).

2.  **PHP Integration**
    -   A service class (e.g., `VideoDownloadService` or `YtDlpService`) is created to wrap `yt-dlp` execution.
    -   The service uses `Symfony\Component\Process\Process` to execute commands securely.
    -   The service can retrieve video metadata (JSON format) for a given URL.
    -   The service can download a video to a temporary storage path.

3.  **Verification**
    -   A functional test verifies that `yt-dlp` is installed and reachable.
    -   A test successfully retrieves metadata for a real or mocked YouTube URL.
    -   A test successfully downloads a short sample video (or dry-run).

## Tasks / Subtasks

-   [x] **Docker Environment Update**
    -   [x] Modify `docker/php/Dockerfile` to install Python 3 and pip (or just Python 3 if using binary).
    -   [x] Add installation step for `yt-dlp` (via `curl` to `/usr/local/bin` is recommended for stability/updates, or `pip`).
        -   *Recommendation:* `curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp && chmod a+rx /usr/local/bin/yt-dlp`
    -   [x] Ensure `www-data` user has execution permissions.
    -   [x] Rebuild containers (`docker compose up -d --build`).

-   [x] **PHP Service Implementation**
    -   [x] Create `app/Domain/Downloads/Services/YtDlpService.php`.
    -   [x] Implement method `getVersion()`: returns string.
    -   [x] Implement method `getVideoInfo(string $url)`: returns DTO or array.
        -   Use arguments: `--dump-json`, `--no-playlist`, `--skip-download`.
    -   [x] Implement method `downloadVideo(string $url, string $outputPath)`: returns file path.
        -   Use arguments: `-o`, format selection (best video+best audio).

-   [x] **Testing & Verification**
    -   [x] Create `tests/Feature/Infrastructure/YtDlpIntegrationTest.php`.
    -   [x] Test 1: `it_can_get_yt_dlp_version`.
    -   [x] Test 2: `it_can_fetch_video_metadata` (Use a stable, non-copyrighted test video URL if possible, or mock the Process output).
    -   [x] Test 3: `it_handles_process_failures` (e.g., invalid URL).

## Dev Notes

-   **Installation Method:** Using the standalone binary is often cleaner than pip in Docker, but ensure dependencies (Python 3) are present.
-   **Timeouts:** `yt-dlp` operations can take time. Ensure `Process` timeout is set appropriately (default is 60s, downloads might need more).
-   **Security:** Always sanitize user input (URLs) before passing to `Process`, although `Process` handles argument escaping well. Validate it's a valid URL first.
-   **Mocking:** For CI/testing, you don't want to actually hit YouTube every time. Create a `YtDlpAdapter` interface so you can swap it with a `FakeYtDlpAdapter` that returns canned JSON responses for unit tests.
-   **Cookies:** YouTube often requires cookies/User-Agent to avoid throttling/blocking. For this basic integration story, standard requests might work, but prepare to support cookies (Netscape format) in the future.

### Project Structure Notes

-   Service Location: `app/Domain/Downloads/Services/`
-   Test Location: `tests/Feature/Infrastructure/`

### References

-   [yt-dlp GitHub Repository](https://github.com/yt-dlp/yt-dlp)
-   [Symfony Process Component](https://symfony.com/doc/current/components/process.html)
-   [Previous Story: Docker Setup](1-2-configure-docker-compose.md)

## Dev Agent Record

### Agent Model Used

GPT-5

### Debug Log References

- `docker compose up -d --build` required escalated Docker daemon access; rebuild completed after retry.
- `docker compose exec app vendor/bin/phpunit --filter YtDlpIntegrationTest` passed inside container.

### Completion Notes List

- Added yt-dlp install step in `docker/php/Dockerfile`, including curl + executable permissions.
- Added Dockerfile test coverage for yt-dlp install.
- Tests: `vendor/bin/phpunit`.
- Added `YtDlpService` using Symfony Process with URL validation and timeouts.
- Added unit tests using a fake yt-dlp binary for version/info/download/validation coverage.
- Added feature integration tests for yt-dlp version/metadata/download, with optional env overrides.
- Integration tests honor `YTDLP_BINARY` and `YTDLP_TEST_URL` for real runs; otherwise use a fake binary.

### File List

- docker/php/Dockerfile
- tests/Feature/Infrastructure/DockerComposeTest.php
- app/Domain/Downloads/Services/YtDlpService.php
- tests/Unit/Domain/Downloads/Services/YtDlpServiceTest.php
- tests/Feature/Infrastructure/YtDlpIntegrationTest.php
- tests/Traits/HasFakeYtDlp.php
- _bmad-output/implementation-artifacts/1-3-integrate-yt-dlp.md
- _bmad-output/implementation-artifacts/sprint-status.yaml
- Makefile

### Change Log

- 2026-01-13: Integrated yt-dlp into Docker image, added service wrapper, and added unit/feature tests.
- 2026-01-13: Added Makefile targets for unit, feature, and full test runs inside Docker.
- 2026-01-13: [Code Review Fix] Added ffmpeg to Dockerfile (required for best video+audio merge).
- 2026-01-13: [Code Review Fix] Refactored duplicate test logic into `Tests\Traits\HasFakeYtDlp`.
- 2026-01-13: [Code Review Fix] Added file existence check to `downloadVideo` method.
