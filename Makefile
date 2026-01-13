.PHONY: test-unit test-feature test

test-unit:
	docker compose exec app vendor/bin/phpunit --testsuite Unit

test-feature:
	docker compose exec app vendor/bin/phpunit --testsuite Feature

test:
	docker compose exec app vendor/bin/phpunit
