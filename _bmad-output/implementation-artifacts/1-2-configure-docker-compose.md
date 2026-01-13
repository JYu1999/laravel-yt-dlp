# Story 1.2: Configure Docker Compose Development Environment

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Developer,
I want to configure a complete Docker Compose development environment,
So that the local development environment matches production and includes all necessary services.

## Acceptance Criteria

1.  **Service Configuration**
    -   `docker-compose.yml` is created at the project root.
    -   **Services included:**
        -   `app`: PHP 8.4 FPM (Custom Dockerfile).
        -   `web`: Nginx 1.28.1 (Stable).
        -   `db`: PostgreSQL 17.7.
        -   `redis`: Redis 8.4.0.
        -   `reverb`: Laravel Reverb service (WebSocket).
        -   `worker`: Queue worker service.
    -   **Networking:** All services can communicate via a shared bridge network (`laravel-yt-dlp`).
    -   **Persistence:** Docker volumes configured for PostgreSQL (`pgdata`) and Redis (`redisdata`).

2.  **Application Integration**
    -   `Dockerfile` created for the PHP application (likely in `docker/php/Dockerfile`).
    -   Nginx configuration created (likely in `docker/nginx/conf.d/default.conf`).
    -   Laravel application connects successfully to PostgreSQL (`DB_HOST=db`).
    -   Laravel application connects successfully to Redis (`REDIS_HOST=redis`).
    -   Reverb is accessible and configured (`REVERB_HOST`, `REVERB_PORT`, etc.).

3.  **Functionality**
    -   `docker compose up -d` starts all services without errors.
    -   Application is accessible via `http://localhost` (or configured port).
    -   Modifications to local PHP files are reflected in the container (volume mounting).

## Tasks / Subtasks

-   [x] **Infrastructure Setup**
    -   [x] Create `docker/php/Dockerfile`:
        -   Base image: `php:8.4-fpm`.
        -   Install extensions: `pdo_pgsql`, `pgsql`, `redis`, `pcntl` (for queues), `bcmath`, `intl`.
        -   Install Composer.
        -   Ensure permissions for `www-data`.
    -   [x] Create `docker/nginx/conf.d/default.conf`:
        -   Configure Nginx to proxy PHP requests to the `app` service on port 9000.
        -   Configure WebSocket proxying for Reverb (upgrade headers).
    -   [x] Create `docker-compose.yml`:
        -   Define `app` service (build from `docker/php`).
        -   Define `web` service (image `nginx:1.28.1`, depends on `app`).
        -   Define `db` service (image `postgres:17.7`, env vars for user/pass/db).
        -   Define `redis` service (image `redis:8.4.0`).
        -   Define `reverb` service (reuse `app` image, command `php artisan reverb:start`).
        -   Define `worker` service (reuse `app` image, command `php artisan queue:work`).
        -   Map ports: Web (80/8080), Reverb (8080/6001), DB (5432), Redis (6379).
        -   Configure volumes for code (`.:/var/www/html`) and data persistence.

-   [x] **Configuration Updates**
    -   [x] Update `.env` (and `.env.example`) to use Docker service names:
        -   `DB_CONNECTION=pgsql`
        -   `DB_HOST=db`
        -   `DB_PORT=5432`
        -   `REDIS_HOST=redis`
        -   `CACHE_STORE=redis`
        -   `QUEUE_CONNECTION=redis`
        -   `SESSION_DRIVER=redis`
        -   `REVERB_HOST=0.0.0.0` (internal binding)
    -   [x] Ensure `database/database.sqlite` is not used; switch to Postgres.

-   [x] **Verification**
    -   [x] Run `docker compose up -d --build`.
    -   [x] Verify all containers are running (`docker compose ps`).
    -   [x] Run migrations: `docker compose exec app php artisan migrate`.
    -   [x] Verify access to the welcome page via browser.

## Dev Notes

-   **Architecture Compliance:** strictly adhere to the versions specified in the architecture document (Postgres 17.7, Redis 8.4.0, PHP 8.4, Nginx 1.28.1).
-   **Reverb Proxying:** Ensure Nginx is configured to handle WebSocket upgrades for Reverb traffic, or expose Reverb directly if preferred (Architecture mentions Nginx reverse proxy).
-   **User Permissions:** Be mindful of file permissions between the host (macOS) and the container. Mapping user IDs or using a consistent user (e.g., `www-data`) is key.
-   **Future Proofing:** Prepare the `Dockerfile` to accept `yt-dlp` installation in the next story (Story 1.3), but you don't need to install it yet unless it's trivial to add `python3` and `pip` now. (Adding Python/Pip now is a good idea to save build time later).

### Project Structure Notes

-   Place Docker related files in `docker/` directory to keep root clean, or follow standard conventions if different.
-   `docker-compose.yml` should be in the root.

### References

-   [Architecture: Infrastructure & Deployment](../planning-artifacts/architecture.md#infrastructure--deployment)
-   [PRD: Story 1.2](../planning-artifacts/epics.md#story-12-配置-docker-compose-开发环境)
-   [Laravel Reverb Documentation](https://laravel.com/docs/11.x/reverb) (check for 12.x specifics if available, otherwise 11.x is close)

## Dev Agent Record

### Agent Model Used

{{agent_model_name_version}}

### Architectural Decisions

-   **Unified Dockerfile:** Use a single `Dockerfile` for `app`, `reverb`, and `worker` services to ensure environment consistency and reduce build times. The `command` directive in `docker-compose.yml` will differentiate their behavior.
-   **Nginx for Reverb:** Use Nginx as a reverse proxy for Reverb to consolidate traffic on port 80/443 (future) and simplify CORS/SSL handling.

### Implementation Plan

-   Define Docker runtime (PHP, Nginx, Postgres, Redis) with pinned versions.
-   Ensure service connectivity via compose networking and env updates.
-   Add tests that assert infrastructure files and env wiring.

### Debug Log References

-   `docker compose up -d --build` failed: permission denied to Docker daemon socket (`/Users/zhanjieyu/.orbstack/run/docker.sock`).
-   User ran `docker compose up -d --build`, `docker compose ps`, `docker compose exec app php artisan migrate`, and verified `http://localhost:8080`.
-   User ran `docker compose exec app php artisan test` (all tests passing in container).

### Completion Notes List

-   Implemented Dockerfile, Nginx config, and compose services; updated `.env`/`.env.example` to Postgres/Redis and Reverb defaults; added infra tests.
-   Ran `php artisan test` and `docker compose exec app php artisan test` (all tests passing). Docker verification steps completed by user and confirmed working.
-   [AI-Review] Added explicit security headers (X-Frame-Options, X-XSS-Protection, X-Content-Type-Options) to Nginx configuration.

### File List

-   docker/php/Dockerfile
-   docker/nginx/conf.d/default.conf
-   docker-compose.yml
-   .env
-   .env.example
-   tests/Feature/Infrastructure/DockerComposeTest.php
-   _bmad-output/implementation-artifacts/1-2-configure-docker-compose.md
-   _bmad-output/implementation-artifacts/sprint-status.yaml
