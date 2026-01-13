# Story 1.5: Download Page UI

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a User,
I want a simple download page where I can input a YouTube link, view video details, and select download options,
so that I can customize my download (format, subtitles) and start the process easily.

## Acceptance Criteria

1. **Initial State & Input:**
   - Given the user visits the home page, then a large YouTube link input and "Get Video Info" button are displayed.
   - The design is clean, centered, and mobile-responsive (full width on mobile, max 1200px centered on desktop).

2. **Video Info Display:**
   - Given video metadata is successfully fetched (from Story 1.4), then the video title, thumbnail, duration, and estimated file size are displayed below the input.
   - The display must be visually appealing (e.g., card layout using Flux UI).

3. **Format Selection:**
   - Given video info is displayed, then a format selection dropdown (or radio group) is shown.
   - Options are "MP4" and "MOV" (filtered from metadata).
   - Default selection is MP4.

4. **Subtitle Selection:**
   - Given video info is displayed, then a "Download Subtitles" checkbox is shown.
   - When checked, a "Subtitle Language" dropdown appears.
   - The dropdown lists available languages from metadata.
   - Default language logic: Try to match browser locale or default to English/Chinese.

5. **Start Download Action:**
   - Given valid options are selected, then a prominent "Start Download" button is displayed.
   - When clicked, it triggers the download initiation (which will be connected to the backend queue in Story 1.6).
   - For this story, the button should validate the state and show a loading indicator or basic confirmation/error if inputs are invalid.

6. **Responsive Design:**
   - The UI must adapt to mobile (<640px) and desktop (>1024px) screens.
   - Touch targets on mobile must be at least 44x44px.

## Tasks / Subtasks

- [x] Update `VideoDownloader` Livewire Component State
  - [x] Add properties: `$selectedFormat` (string), `$downloadSubtitles` (bool), `$selectedLanguage` (string)
  - [x] Add validation rules for these properties
- [x] Implement Format Selection UI
  - [x] Add Flux Select/Radio component for MP4/MOV
  - [x] Bind to `$selectedFormat`
- [x] Implement Subtitle Selection UI
  - [x] Add Flux Checkbox for subtitles
  - [x] Add Flux Select for languages (conditional visibility using Alpine/Livewire `if`)
  - [x] Populate languages from `$metadata['subtitles']`
- [x] Implement "Start Download" Button UI
  - [x] Add primary Flux Button
  - [x] Add `wire:click="startDownload"`
  - [x] Add `startDownload` method stub in component (validates input)
  - [x] Add loading state (`wire:loading`) to button
- [x] Enhance Responsive Layout
  - [x] Ensure input/button stack on mobile and align on desktop
  - [x] Use Tailwind grid/flex utilities for layout

## Dev Notes

- **Leverage Existing Component**: You are modifying `app/Livewire/VideoDownloader.php` and `resources/views/livewire/video-downloader.blade.php` created in Story 1.4.
- **Flux UI**: Use the Flux UI components installed in the starter kit. Check `resources/views/components` or vendor folder for available components (e.g., `x-flux::button`, `x-flux::input`).
- **Livewire 3**: Remember to use `wire:model.blur` or `wire:model.live` appropriately. For format/subtitle toggles, `wire:model.live` might provide better UX for conditional fields.
- **Validation**: Ensure `selectedFormat` is valid (exists in available formats) and `selectedLanguage` is valid if subtitles are checked.

### Project Structure Notes

- **View**: `resources/views/livewire/video-downloader.blade.php`
- **Component**: `app/Livewire/VideoDownloader.php`
- **Styling**: `resources/css/app.css` (Tailwind)

### References

- [Story 1.4](1-4-video-info-service.md) - Context on `VideoDownloader` and `VideoMetadata` structure.
- [Epics - Story 1.5](../planning-artifacts/epics.md) - detailed FRs.
- [Project Context](../project-context.md) - Rules on Blade/Livewire usage.

## Change Log

- 2026-01-14: Implemented download UI controls, validation, and responsive layout; added tests.
- 2026-01-14: Routed homepage to VideoDownloader and added coverage for homepage rendering.
- 2026-01-14: Configured Livewire page layout and added `layouts.public` for unauthenticated rendering.
- 2026-01-14: Scoped Tailwind sources to view templates (including Flux stubs) for utilities and added Makefile build target.
- 2026-01-14: Restored Tailwind `@import` flow with explicit sources/exclusions to fix styles without stray tokens.
- 2026-01-14: Applied Flux theme variables, default dark mode, and boosted download confirmation visibility.
- 2026-01-14: Added a Node 24 service with a dedicated `node_modules` volume and updated `make build` to run `npm ci`.

## Dev Agent Record

### Agent Model Used

GPT-5

### Debug Log References

### Completion Notes List

- Added format/subtitle state validation and download stub handling with defaults for format and subtitle language.
- Refined the Livewire UI layout, format/subtitle selectors, and added start download button with loading/notice states.
- Tests: `VideoDownloaderTest` updated to cover defaults and validation.
- Routed `/` to the VideoDownloader Livewire component and added a homepage rendering test.
- Bound the Livewire page component to a dedicated `layouts.public` layout for guest-safe rendering.
- Scoped Tailwind utility sources to view templates and introduced `make build`.
- Applied Flux theme variables, default dark mode, and increased download-ready callout prominence.
- Switched `make build` to use a Node container with a persistent `node_modules` volume and `npm ci`.
- Restored Tailwind `@import` flow with explicit sources/exclusions; updated subtitle checkbox label rendering.

### File List

- app/Livewire/VideoDownloader.php
- resources/views/livewire/video-downloader.blade.php
- resources/views/layouts/public.blade.php
- resources/views/layouts/app.blade.php
- resources/views/layouts/guest.blade.php
- resources/css/app.css
- tests/Feature/Livewire/VideoDownloaderTest.php
- routes/web.php
- tailwind.config.js
- Makefile
- docker-compose.yml
- _bmad-output/implementation-artifacts/sprint-status.yaml
- _bmad-output/implementation-artifacts/1-5-download-page-ui.md
