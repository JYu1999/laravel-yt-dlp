# Story 1.1: 使用 Laravel Livewire Starter Kit 初始化项目

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a 开发者,
I want 使用 Laravel Livewire Starter Kit 初始化项目,
So that 我有一个具备认证脚手架、UI 组件和 Tailwind CSS 的基础项目结构。

## Acceptance Criteria

1. **Project Initialization**
    - Project structure matches **Laravel 12** standards.
    - **Livewire 3** is installed and configured.
    - **Flux UI** component library is installed and configured.
    - **Tailwind CSS** is configured and building correctly via Vite.
    - **Authentication scaffolding** (Login, Register, Dashboard) is present and functional.

2. **Environment Configuration**
    - `npm install` runs successfully.
    - `npm run build` compiles assets without errors.
    - `php artisan serve` launches the application, and the welcome/auth pages are accessible.

3. **Codebase State**
    - `composer.json` contains `livewire/livewire` (and `livewire/flux` if applicable).
    - `package.json` contains `tailwindcss` and `vite`.
    - Routes for auth are registered (e.g., `/login`, `/register`).

## Tasks / Subtasks

- [x] **Analyze Current State**
    - [x] Check `composer.json` and `package.json` in the current directory (`laravel-yt-dlp`).
    - [x] Determine if the project was initialized with the "Livewire Starter Kit" or if it is a bare skeleton.
    - [x] **Note:** Initial analysis suggests it might be a bare skeleton (missing `livewire/livewire`).

- [x] **Apply Starter Kit / Install Dependencies**
    - [x] If bare skeleton: Install **Livewire** (`composer require livewire/livewire`).
    - [x] If bare skeleton: Install **Flux UI** (Follow official installation for Laravel 12 Livewire starter).
    - [x] If bare skeleton: Install/Scaffold **Authentication** (likely via `php artisan breeze:install livewire` or equivalent if that matches the "Livewire Starter Kit" definition, OR manually setup Fortify/Livewire auth components if Flux provides them).
    - [x] **Critical:** Ensure the resulting stack matches the "Laravel Livewire Starter Kit" with Flux UI.

- [x] **Verify Setup**
    - [x] Run `npm install && npm run build`.
    - [x] Verify `php artisan serve` works.
    - [x] Verify Login/Register pages render correctly with Flux UI components.

## Dev Notes

- **Existing Directory Warning:** You are working in an existing directory `laravel-yt-dlp`. `laravel new` might not work if the directory is not empty. You may need to install dependencies manually or move files if you decide to re-initialize in a temp folder and copy over.
- **Flux UI:** This is a key requirement. Ensure it's the correct library as specified in the architecture (Livewire-first UI kit).
- **Authentication:** The PRD mentions "Fortify" in later stories (Story 2.1), but this story (1.1) mentions "built-in auth scaffolding". Often starter kits use Breeze/Jetstream which use Fortify under the hood or their own auth logic. **Prioritize the "Starter Kit" outcome** (functional auth pages). If the Starter Kit provides auth, use it. If not, wait for Story 2.1 to configure Fortify, but this story AC says "认证脚手架（登录、注册页面）已可用", so at least the UI/Routes should be there.

### Project Structure Notes

- **Laravel 12 Standard:** Follow default directory structure.
- **Livewire:** Components in `app/Livewire`, views in `resources/views/livewire`.

### References

- [Architecture: Starter Template](../planning-artifacts/architecture.md#starter-template--project-initialization)
- [PRD: Story 1.1](../planning-artifacts/epics.md#story-11-使用-laravel-livewire-starter-kit-初始化项目)

## Dev Agent Record

### Agent Model Used

{{agent_model_name_version}}

### Architectural Decisions

- **Livewire Volt:** The project uses Livewire Volt (functional API) for authentication components (`resources/views/pages/auth`) and some UI elements. This deviates from standard class-based Livewire but is consistent with the Laravel 12 Livewire Starter Kit default. Future components should align with this pattern where appropriate or document exceptions.

### Debug Log References

### Completion Notes List

- Completed analysis: composer/package show Laravel skeleton, no `livewire/livewire` present.
- Installed Livewire, Flux, Breeze + Volt; wired Flux assets and Tailwind 4 build pipeline; auth routes and views scaffolded.
- Added `AuthPagesTest` to verify auth routes; full test suite passing (`php artisan test`).
- `php artisan serve` verified by user locally (SQLite).

### Implementation Plan

- Install Livewire/Flux + auth scaffolding, then wire Flux assets into layouts.
- Align Tailwind/Vite config to Tailwind 4 build pipeline.
- Add auth route tests and run full suite.

### File List

- _bmad-output/implementation-artifacts/1-1-init-project-livewire-starter-kit.md
- _bmad-output/implementation-artifacts/sprint-status.yaml
- app/Http/Controllers/Auth/VerifyEmailController.php
- app/Livewire/Actions/Logout.php
- app/Livewire/Forms/LoginForm.php
- app/Providers/VoltServiceProvider.php
- app/View/Components/AppLayout.php
- app/View/Components/GuestLayout.php
- bootstrap/providers.php
- composer.json
- composer.lock
- package.json
- package-lock.json
- postcss.config.js
- resources/css/app.css
- resources/views/components/action-message.blade.php
- resources/views/components/application-logo.blade.php
- resources/views/components/auth-session-status.blade.php
- resources/views/components/danger-button.blade.php
- resources/views/components/dropdown.blade.php
- resources/views/components/dropdown-link.blade.php
- resources/views/components/input-error.blade.php
- resources/views/components/input-label.blade.php
- resources/views/components/modal.blade.php
- resources/views/components/nav-link.blade.php
- resources/views/components/primary-button.blade.php
- resources/views/components/responsive-nav-link.blade.php
- resources/views/components/secondary-button.blade.php
- resources/views/components/text-input.blade.php
- resources/views/dashboard.blade.php
- resources/views/layouts/app.blade.php
- resources/views/layouts/guest.blade.php
- resources/views/livewire/layout/navigation.blade.php
- resources/views/livewire/pages/auth/confirm-password.blade.php
- resources/views/livewire/pages/auth/forgot-password.blade.php
- resources/views/livewire/pages/auth/login.blade.php
- resources/views/livewire/pages/auth/register.blade.php
- resources/views/livewire/pages/auth/reset-password.blade.php
- resources/views/livewire/pages/auth/verify-email.blade.php
- resources/views/livewire/profile/delete-user-form.blade.php
- resources/views/livewire/profile/update-password-form.blade.php
- resources/views/livewire/profile/update-profile-information-form.blade.php
- resources/views/livewire/welcome/navigation.blade.php
- resources/views/profile.blade.php
- resources/views/welcome.blade.php
- routes/auth.php
- routes/web.php
- tailwind.config.js
- tests/Feature/Auth/AuthenticationTest.php
- tests/Feature/Auth/EmailVerificationTest.php
- tests/Feature/Auth/PasswordConfirmationTest.php
- tests/Feature/Auth/PasswordResetTest.php
- tests/Feature/Auth/PasswordUpdateTest.php
- tests/Feature/Auth/RegistrationTest.php
- tests/Feature/AuthPagesTest.php
- tests/Feature/ProfileTest.php
- vite.config.js

### Change Log

- 2026-01-13: Initialized Livewire starter stack with Flux UI, auth scaffolding, and Tailwind 4 build pipeline; added auth route coverage tests.
- 2026-01-13: [Review Fix] Refactored `navigation.blade.php` to use Flux UI components.
- 2026-01-13: [Review Fix] Documented Livewire Volt usage in architectural decisions.
