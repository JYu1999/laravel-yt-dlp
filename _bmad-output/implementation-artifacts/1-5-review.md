**🔥 CODE REVIEW FINDINGS, jyu!**

**Story:** `1-5-download-page-ui.md`
**Git vs Story Discrepancies:** 0 found (assuming committed changes match file list).
**Issues Found:** 0 High, 2 Medium, 2 Low

## 🟡 MEDIUM ISSUES
1.  **Duplicate Logic in View (Maintainability):**
    *   `resources/views/livewire/video-downloader.blade.php` contains logic to calculate `$formatOptions` (`collect($metadata['formats']...`).
    *   This logic (filtering unique extensions) duplicates the intent of `VideoDownloader::resolveAvailableFormats` and belongs in the component (e.g., as a Computed Property).
    *   If logic changes (e.g., sorting), the view must be updated manually.

2.  **Missing Unit Test for DTO Logic (Test Quality):**
    *   `App\Domain\Downloads\DTO\VideoMetadata::mapFormats` contains critical business logic (filtering for 'mp4'/'mov', sorting by resolution).
    *   This is currently only tested implicitly via `VideoDownloaderTest` mocking the whole service.
    *   A dedicated Unit test for `VideoMetadata` is needed to verify edge cases (e.g., `webm` exclusion, weird sorting) without booting Livewire.

## 🟢 LOW ISSUES
3.  **Missing UI Render Assertions (Test Quality):**
    *   `VideoDownloaderTest` asserts component state (`assertSet`) but not the rendered HTML.
    *   It doesn't verify that the "MOV" option is actually visible to the user, or that the subtitle dropdown is hidden when the checkbox is unchecked (it only tests validation rules).

4.  **Hardcoded Fallback Languages (Code Quality):**
    *   `VideoDownloader::resolveDefaultSubtitleLanguage` has a hardcoded list (`['en', 'en-US', ...]`).
    *   This should be extracted to a constant or configuration to be easily maintainable.
