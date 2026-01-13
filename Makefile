.PHONY: test-unit test-feature test build migrate migrate-fresh frontend

test-unit:
	docker compose exec app vendor/bin/phpunit --testsuite Unit

test-feature:
	docker compose exec app vendor/bin/phpunit --testsuite Feature

test:
	docker compose exec app vendor/bin/phpunit

build:
	docker compose run --rm node sh -lc "npm ci && npm run build"

migrate:
	docker compose exec app php artisan migrate

migrate-fresh:
	docker compose exec app php artisan migrate:fresh

frontend:
	docker compose run --rm node sh -lc "npm ci && npm run build"
