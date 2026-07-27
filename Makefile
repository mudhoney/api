DOCKER_EXEC = docker compose exec api
PHPUNIT = $(DOCKER_EXEC) vendor/bin/phpunit --bootstrap tests/autoload.php --testdox

.PHONY: help test event-tests regression-tests test-filter test-file shell exec restart-movies db-shell

.DEFAULT_GOAL := help

help:
	@echo "Available commands:"
	@echo "  make test                  - Run all unit tests"
	@echo "  make event-tests           - Run EventsApi tests"
	@echo "  make regression-tests      - Run regression tests"
	@echo "  make test-filter f=...     - Run tests whose name matches the PHPUnit --filter regex"
	@echo "                               e.g. make test-filter f=testItShouldFallBack"
	@echo "                               e.g. make test-filter f=LegacyEventString"
	@echo "  make test-file p=...       - Run a single test file or directory under tests/unit_tests"
	@echo "                               e.g. make test-file p=tests/unit_tests/validation/LegacyEventStringTest.php"
	@echo "                               e.g. make test-file p=tests/unit_tests/events"
	@echo "  make shell                 - Open bash shell in api container"
	@echo "  make exec cmd=\"...\"        - Run a command in api container"
	@echo "  make restart-movies        - Restart movies container"
	@echo "  make db-shell              - Open MySQL shell"

shell:
	$(DOCKER_EXEC) bash

exec:
	$(DOCKER_EXEC) $(cmd)

test:
	$(PHPUNIT) tests/unit_tests

event-tests:
	$(PHPUNIT) tests/unit_tests/events/

regression-tests:
	$(PHPUNIT) tests/unit_tests/regression/

# Run only tests whose method name matches the given regex.
# Usage: make test-filter f=<regex>      (also accepts: filter=, FILTER=)
test-filter:
	@if [ -z "$(f)$(filter)$(FILTER)" ]; then \
		echo "Error: pass a filter, e.g. make test-filter f=testItShouldFallBack"; exit 1; \
	fi
	$(PHPUNIT) tests/unit_tests --filter '$(f)$(filter)$(FILTER)'

# Run a single test file (or limit to a sub-directory).
# Usage: make test-file p=tests/unit_tests/validation/LegacyEventStringTest.php
test-file:
	@if [ -z "$(p)$(path)$(PATH_)" ]; then \
		echo "Error: pass a path, e.g. make test-file p=tests/unit_tests/validation/LegacyEventStringTest.php"; exit 1; \
	fi
	$(PHPUNIT) '$(p)$(path)$(PATH_)'

restart-movies:
	docker compose restart movies

db-shell:
	docker compose exec database mariadb -u helioviewer -phelioviewer helioviewer
