---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8]
inputDocuments:
  - "_bmad-output/planning-artifacts/prd.md"
workflowType: 'architecture'
lastStep: 8
status: 'complete'
completedAt: '2026-01-13T18:18:26+0800'
project_name: 'laravel-yt-dlp'
user_name: 'jyu'
date: '2026-01-13T17:00:38+0800'
---

# Architecture Decision Document

_This document builds collaboratively through step-by-step discovery. Sections are appended as we work through each architectural decision together._

## Project Context Analysis

### Requirements Overview

**Functional Requirements:**
- Core download flow: link validation, video info display, format/subtitle selection, real-time progress, delivery, and failure handling.
- Users and quotas: anonymous/registered/admin roles, quotas and rate limits, quota visibility and enforcement messaging.
- Operations and monetization: AdSense display rules, admin dashboard stats and ops tools.
- Abuse prevention: user-agent checks, abuse alerts, account/IP blocking, CAPTCHA and email verification.
- Compliance and terms: disclaimers and terms acceptance required.
- Maintenance and cleanup: yt-dlp version management, logs/records retention and cleanup.

**Non-Functional Requirements:**
- Performance: video info ≤ 5s, progress updates ≥ 500ms, download start ack ≤ 1s.
- Reliability: success rate ≥ 95%, availability ≥ 99%.
- Security: HTTPS, bcrypt, UA blocking, anomaly alerts.
- Concurrency: system ≤ 10 concurrent downloads, per-user ≤ 1.
- Data retention: anonymous 24h, registered 90d, IP/logs 30d.

**Scale & Complexity:**
- Primary domain: full-stack web app with real-time updates
- Complexity level: medium
- Estimated architectural components: 6-8

### Technical Constraints & Dependencies

- Laravel 12 + MPA/SSR (SEO-first)
- Dockerized dev and deployment
- yt-dlp as the core download engine (strong dependency)
- WebSocket (Laravel Reverb) for real-time progress and admin telemetry
- Streaming downloads, no server-side persistent video storage
- Strict concurrency and quotas; possible proxy/rotating IP to reduce blocking risk

### Cross-Cutting Concerns Identified

- Quota and rate limiting strategy
- Abuse detection and enforcement (UA, IP, anomaly alerts)
- Compliance and disclaimers
- Real-time progress and notifications
- Data retention and automated cleanup
- SEO and indexability

## Starter Template Evaluation

### Primary Technology Domain

Full-stack web application (Laravel MPA + real-time features) based on project requirements.

### Starter Options Considered

- **Fresh Laravel app (no starter kit)**  
  Minimal baseline, but auth scaffolding and UI components require manual setup.
- **Laravel Livewire starter kit (Blade-first)**  
  Official starter, Blade-first with Livewire 3, matches the chosen stack.
- **Laravel React / Vue starter kits (Inertia)**  
  SPA-oriented; not aligned with Blade-first preference.

### Selected Starter: Laravel Livewire Starter Kit (Blade-first)

**Rationale for Selection:**
- Aligns with Blade-first preference and Laravel 12 stack.
- Officially maintained with current Laravel starter kits.
- Includes auth scaffolding and baseline UI structure, reducing setup cost.
- Compatible with real-time progress updates alongside Reverb.

**Initialization Command:**

```bash
composer global require laravel/installer
laravel new laravel-yt-dlp
```

Select the **Livewire starter kit** during `laravel new` prompts. Then:

```bash
cd laravel-yt-dlp
npm install && npm run build
composer run dev
```

**Architectural Decisions Provided by Starter:**

**Language & Runtime:**
- PHP 8.4 + Laravel 12
- Blade templates + Livewire 3 for interactive UI

**Styling Solution:**
- Tailwind CSS
- Flux UI component library (Livewire starter kit default)

**Build Tooling:**
- Vite for asset bundling
- Standard Laravel asset pipeline

**Testing Framework:**
- Default Laravel testing stack (PHPUnit)
- Optional future: Pest (if desired)

**Code Organization:**
- Standard Laravel structure (`app/`, `routes/`, `resources/`)
- Livewire components in `app/Livewire` and Blade in `resources/views`

**Development Experience:**
- Hot reload via Vite
- Built-in auth scaffolding
- Official docs and long-term maintenance

**Note:** Project initialization using this command should be the first implementation story.

## Core Architectural Decisions

### Decision Priority Analysis

**Critical Decisions (Block Implementation):**
- Hosting: AWS EC2 single instance + Docker Compose
- Database: PostgreSQL 17.7 (Dockerized)
- Cache/Queue: Redis 8.4.0 (Dockerized)
- Auth: Laravel Fortify (session-based) + Email Verification
- Real-time: Laravel Reverb + Echo + fallback polling

**Important Decisions (Shape Architecture):**
- Blade-first UI with Livewire 3 starter kit
- REST-style JSON APIs for download lifecycle
- Queue-based download execution and progress broadcasting
- Security posture: CSRF/HTTPS, throttle, UA checks, CAPTCHA, signed URLs

**Deferred Decisions (Post-MVP):**
- Multi-instance scaling / ECS migration
- Managed services (RDS / ElastiCache)
- Full IaC adoption (Terraform/CDK)

### Data Architecture

- Database: PostgreSQL 17.7 in Docker Compose (local + production)
- ORM + Migrations: Eloquent + Laravel migrations
- Data modeling: relational schema; no event sourcing/CQRS
- Cache/Queue: Redis 8.4.0 in Docker Compose
- Cache TTL: video metadata ~15 minutes (configurable)
- Validation: Form Request + Validator

### Authentication & Security

- Auth stack: Laravel Fortify (session-based), email verification required
- Authorization: Gates/Policies + `is_admin` flag
- Security middleware: CSRF + HTTPS + auth middleware
- Abuse controls: throttle by user/IP, UA checks, CAPTCHA at registration
- Secure delivery: signed URLs for downloads
- Encryption: bcrypt for passwords; encrypted casts for sensitive fields

### API & Communication Patterns

- API style: REST + JSON via Laravel controllers
- Download flow: `POST /downloads` create job, `GET /downloads/{id}` status/progress
- Error contract: `{ code, message, details? }` + standard HTTP codes
- Rate limiting: throttle + quota checks
- Real-time: Laravel Reverb + Echo; fallback polling every 2s
- Worker communication: queue jobs; progress via Redis + events

### Frontend Architecture

- Blade + Livewire 3 for interactive UI
- Livewire component state as primary state management
- Minimal JS (Alpine/vanilla) only where needed (e.g., progress UI)
- SSR via Blade; Reverb updates UI in real time

### Infrastructure & Deployment

- Hosting: AWS EC2 single instance + Docker Compose
- Web stack: Nginx 1.28.1 (stable) + `php:8.4-fpm`
- Data services: PostgreSQL 17.7 and Redis 8.4.0 as Compose services
- Logging/monitoring: instance logs + optional CloudWatch agent
- CI/CD: GitHub Actions → build images → deploy via SSH/compose
- IaC: deferred (no Terraform/CDK initially)

### Decision Impact Analysis

**Implementation Sequence:**
1) Provision EC2 + Docker Compose baseline
2) Set up Laravel + Livewire starter
3) Configure Postgres/Redis containers
4) Implement download pipeline + queue + Reverb events
5) Add auth, quota, and abuse controls
6) Add admin/ops dashboard and monitoring

**Cross-Component Dependencies:**
- Reverb requires queue workers + Redis
- Quota enforcement touches auth, DB, and middleware
- Signed URLs require download pipeline + storage strategy

## Implementation Patterns & Consistency Rules

### Pattern Categories Defined

**Critical Conflict Points Identified:**
6 areas where AI agents could make different choices

### Naming Patterns

**Database Naming Conventions:**
- Tables: snake_case plural (e.g., `download_tasks`)
- Columns: snake_case (e.g., `user_id`)
- Foreign keys: `<table>_id` (e.g., `download_task_id`)
- Indexes: `idx_<table>_<column>` (e.g., `idx_download_tasks_user_id`)

**API Naming Conventions:**
- REST resources are plural (e.g., `/downloads`)
- Route params use `{id}`
- Query params use snake_case
- Headers use `X-` prefix only when required by convention

**Code Naming Conventions:**
- PHP classes: StudlyCase (e.g., `DownloadJob`)
- Files follow class names (e.g., `DownloadJob.php`)
- Livewire components: StudlyCase; views in kebab-case

### Structure Patterns

**Project Organization:**
- Controllers: `app/Http/Controllers`
- Jobs: `app/Jobs`
- Actions/Services: `app/Actions`, `app/Services`
- Events/Listeners: `app/Events`, `app/Listeners`
- Livewire: `app/Livewire` + `resources/views/livewire`
- API routes: `routes/api.php`; Web routes: `routes/web.php`

**File Structure Patterns:**
- Config stays in `config/`
- Migrations in `database/migrations/`
- Seeds/Factories in `database/seeders` and `database/factories`

### Format Patterns

**API Response Formats:**
- Success: `{ data, meta? }`
- Error: `{ error: { code, message, details? } }`
- HTTP status codes must match semantics

**Data Exchange Formats:**
- JSON fields use snake_case
- Date/time as ISO 8601 UTC strings
- Booleans use true/false

### Communication Patterns

**Event System Patterns:**
- Event classes: StudlyCase (e.g., `DownloadProgressUpdated`)
- Broadcast channels: `download.{id}`
- Event payloads include `status`, `percentage`, `eta`

**State Management Patterns:**
- Livewire component state is the source of truth
- Minimal JS state outside Livewire components

### Process Patterns

**Error Handling Patterns:**
- Centralized exception handling to JSON for API routes
- User-facing errors have friendly messages + internal codes

**Loading State Patterns:**
- Status values: `queued`, `downloading`, `completed`, `failed`
- Progress bar always shows percentage + ETA when available

### Enforcement Guidelines

**All AI Agents MUST:**
- Use snake_case for DB and JSON fields
- Follow standard response envelopes
- Keep routing and component organization consistent

**Pattern Enforcement:**
- Validate in code review and tests
- Update this section when patterns change

### Pattern Examples

**Good Examples:**
- `POST /downloads` → `{ data: { id, status } }`
- `DownloadProgressUpdated` with payload `{ status, percentage, eta }`

**Anti-Patterns:**
- Mixing camelCase and snake_case in JSON
- Custom per-controller error formats

## Project Structure & Boundaries

### Complete Project Directory Structure

```
laravel-yt-dlp/
├── app/
│   ├── Actions/
│   │   ├── Downloads/
│   │   └── Users/
│   ├── Domain/
│   │   ├── Downloads/
│   │   │   ├── DTOs/
│   │   │   ├── Enums/
│   │   │   ├── Policies/
│   │   │   └── Services/
│   │   ├── Users/
│   │   └── Admin/
│   ├── Events/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Web/
│   │   │   └── Api/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Livewire/
│   ├── Models/
│   ├── Policies/
│   ├── Providers/
│   ├── Services/
│   └── Support/
├── bootstrap/
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── livewire/
│       ├── components/
│       ├── layouts/
│       └── pages/
├── routes/
│   ├── api.php
│   ├── web.php
│   └── channels.php
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── docker/
│   ├── nginx/
│   └── php/
├── docker-compose.yml
├── .env.example
└── README.md
```

### Architectural Boundaries

**API Boundaries:**
- `routes/api.php`: download APIs (create jobs, check status)
- `routes/web.php`: page routes (download form, progress, admin)

**Component Boundaries:**
- Livewire components: `app/Livewire` + `resources/views/livewire`
- Blade pages: `resources/views/pages`

**Service Boundaries:**
- Download flow: `app/Domain/Downloads/Services`
- Queue jobs: `app/Jobs`
- Broadcast events: `app/Events`

**Data Boundaries:**
- Models: `app/Models`
- Data access abstraction (optional): `app/Repositories`
- Cache access centralized via `app/Services` or `app/Support`

### Requirements to Structure Mapping

**Feature Mapping:**
- Download flow: `Domain/Downloads` + `Jobs` + `Events` + `Livewire`
- User and quotas: `Domain/Users` + `Policies` + `Middleware`
- Admin console: `Domain/Admin` + `Controllers/Web`
- Abuse controls: `Http/Middleware` + `Policies` + `Support`

**Cross-Cutting Concerns:**
- Auth: Fortify + `Policies`
- Logging: `Support/Logging`
- Configuration: `config/`

### Integration Points

**Internal Communication:**
- Web UI → Controllers/Livewire → Actions/Services → Jobs/Events

**External Integrations:**
- yt-dlp (service layer)
- Google AdSense (Blade views)

**Data Flow:**
- Web form → validation → job enqueue → progress broadcast → UI update

### File Organization Patterns

**Configuration Files:**
- All config in `config/*`

**Source Organization:**
- Layered structure with Domain modules

**Test Organization:**
- Feature tests: `tests/Feature`
- Unit tests: `tests/Unit`

**Asset Organization:**
- `resources/css`, `resources/js`, `resources/views`

### Development Workflow Integration

**Development Server Structure:**
- Docker Compose starts web/app/queue/reverb/db/redis

**Build Process Structure:**
- Vite build outputs to `public/build`

**Deployment Structure:**
- `docker-compose.yml` orchestrates the environment

## Architecture Validation Results

### Coherence Validation ✅

**Decision Compatibility:**
- Laravel 12 + PHP 8.4 + Blade/Livewire + Reverb are compatible
- PostgreSQL 17.7 and Redis 8.4.0 via Docker Compose fit the deployment model
- Security and rate-limit decisions align with Fortify and middleware stack

**Pattern Consistency:**
- Naming conventions (snake_case) align across DB and JSON
- API response envelopes align with error handling rules
- Event naming and channels align with Reverb usage

**Structure Alignment:**
- Project structure supports domain boundaries and Livewire usage
- Services/Jobs/Events boundaries align with queue-driven architecture
- Docker layout supports local + production parity

### Requirements Coverage Validation ✅

**Feature Coverage:**
- Download flow, subtitles, quotas, admin, ads, abuse controls all map to modules

**Functional Requirements Coverage:**
- FR categories covered via Controllers/Livewire/Jobs/Policies and middleware

**Non-Functional Requirements Coverage:**
- Performance via queues + Reverb
- Security via Fortify, HTTPS, throttling, UA checks
- Concurrency via queue limits and per-user restrictions
- Compliance via disclaimer/terms and retention jobs

### Implementation Readiness Validation ✅

**Decision Completeness:**
- All critical stack decisions documented with versions

**Structure Completeness:**
- Concrete project tree and boundaries defined

**Pattern Completeness:**
- Naming, structure, format, and process patterns defined with examples

### Gap Analysis Results

- No critical gaps identified
- Deferred items: ECS migration, managed DB/Redis, IaC

### Validation Issues Addressed

- None outstanding

### Architecture Completeness Checklist

**✅ Requirements Analysis**

- [x] Project context thoroughly analyzed
- [x] Scale and complexity assessed
- [x] Technical constraints identified
- [x] Cross-cutting concerns mapped

**✅ Architectural Decisions**

- [x] Critical decisions documented with versions
- [x] Technology stack fully specified
- [x] Integration patterns defined
- [x] Performance considerations addressed

**✅ Implementation Patterns**

- [x] Naming conventions established
- [x] Structure patterns defined
- [x] Communication patterns specified
- [x] Process patterns documented

**✅ Project Structure**

- [x] Complete directory structure defined
- [x] Component boundaries established
- [x] Integration points mapped
- [x] Requirements to structure mapping complete

### Architecture Readiness Assessment

**Overall Status:** READY FOR IMPLEMENTATION

**Confidence Level:** high

**Key Strengths:**
- Clear stack choices aligned with requirements
- Consistent patterns to avoid agent conflicts
- Simple deployment path with Docker Compose

**Areas for Future Enhancement:**
- IaC adoption when scaling
- Migration to managed services if load increases

### Implementation Handoff

**AI Agent Guidelines:**

- Follow all architectural decisions exactly as documented
- Use implementation patterns consistently across all components
- Respect project structure and boundaries
- Refer to this document for all architectural questions

**First Implementation Priority:**
Initialize the Laravel app using the Livewire starter kit, then set up Docker Compose services (PostgreSQL, Redis, Nginx, PHP-FPM, Reverb, queue).
