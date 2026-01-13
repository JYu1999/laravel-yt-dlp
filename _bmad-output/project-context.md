---
project_name: 'laravel-yt-dlp'
user_name: 'jyu'
date: '2026-01-13T18:18:26+0800'
sections_completed: ['technology_stack', 'language_rules', 'framework_rules', 'testing_rules', 'quality_rules', 'workflow_rules', 'anti_patterns']
existing_patterns_found: 6
status: 'complete'
rule_count: 34
optimized_for_llm: true
---

# Project Context for AI Agents

_This file contains critical rules and patterns that AI agents must follow when implementing code in this project. Focus on unobvious details that agents might otherwise miss._

---

## Technology Stack & Versions

- PHP 8.4 (required); update `composer.json` to `php: ^8.4`
- Laravel 12.x
- Blade + Livewire 3
- PostgreSQL 17.7 (Dockerized)
- Redis 8.4.0 (Dockerized)
- Laravel Reverb + Echo
- Nginx 1.28.1 (stable)
- Vite 7.0.7
- Tailwind 4.0.0
- laravel-vite-plugin 2.0.0
- PHPUnit 11.5.3

## Critical Implementation Rules

### Language-Specific Rules

- All new PHP files must include `declare(strict_types=1);`
- Public methods must have explicit parameter and return types
- Use enums for status/state fields (e.g., download status)
- Prefer readonly properties for DTOs

### Framework-Specific Rules

- Blade + Livewire only; do not introduce Inertia/React/Vue
- Livewire component state is the source of truth (no duplicate JS state)
- Download progress updates must use Reverb events
- API routes in `routes/api.php`; pages in `routes/web.php`
- Use Form Requests for input validation

### Testing Rules

- Feature tests in `tests/Feature`, unit tests in `tests/Unit`
- API responses must be tested for `{ data }` / `{ error }` envelopes
- Download flow tests must include failure cases (invalid URL, unsupported format, quota exceeded)

### Code Quality & Style Rules

- DB and JSON fields must use `snake_case`
- API success responses: `{ data, meta? }`; errors: `{ error: { code, message, details? } }`
- Livewire components use StudlyCase; views use kebab-case
- Event classes use StudlyCase; broadcast channels use `download.{id}`

### Development Workflow Rules

- Docker Compose is the single entry point for dev/prod bring-up (`docker compose up -d`)
- Pin service versions via Docker image tags (Postgres 17.7, Redis 8.4.0, php:8.4-fpm, Nginx 1.28.1)
- Any new env var must be added to `.env.example`

### Critical Don't-Miss Rules

- Do not persist video files on the server; stream delivery only
- Enforce concurrency: per-user max 1 download; system max 10 concurrent downloads
- Show legal disclaimer + terms; registration must require acceptance
- Block non-browser User-Agent by default; alert on abusive activity

---

## Usage Guidelines

**For AI Agents:**

- Read this file before implementing any code
- Follow ALL rules exactly as documented
- When in doubt, prefer the more restrictive option
- Update this file if new patterns emerge

**For Humans:**

- Keep this file lean and focused on agent needs
- Update when technology stack changes
- Review quarterly for outdated rules
- Remove rules that become obvious over time

Last Updated: 2026-01-13T18:18:26+0800
