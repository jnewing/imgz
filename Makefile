# imgz - developer task runner.
#
# Local dev runs the database in Docker and the app (PHP + Vite + queue + logs)
# on the host via `composer run dev`. `make dev` does both in one command.

DC_DEV  ?= docker compose -f docker-compose.dev.yml

.DEFAULT_GOAL := help

.PHONY: help dev dev-up dev-down dev-fresh psql wayfinder test lint typecheck ci release release-dry

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

dev: dev-up ## Start the dev database, then run the app (serve + queue + logs + vite)
	composer run dev

dev-up: ## Bring up the dev database in the background
	$(DC_DEV) up -d

dev-down: ## Stop the dev database
	$(DC_DEV) down

dev-fresh: ## Wipe the dev database volume and re-migrate + seed (destructive)
	$(DC_DEV) down -v
	$(DC_DEV) up -d
	@echo "Waiting for Postgres to be ready..."
	@until $(DC_DEV) exec -T db pg_isready -U $${DB_USERNAME:-imgz} >/dev/null 2>&1; do sleep 1; done
	php artisan migrate:fresh --seed

psql: ## Open a psql shell on the dev database
	$(DC_DEV) exec db psql -U $${DB_USERNAME:-imgz} -d $${DB_DATABASE:-imgz}

wayfinder: ## Regenerate Wayfinder TypeScript route/action helpers
	php artisan wayfinder:generate --with-form

test: ## Run the Pest test suite
	php artisan test --compact

lint: ## Fix PHP + JS/Vue style (Pint + ESLint)
	vendor/bin/pint
	npm run lint

typecheck: ## Run static analysis (Larastan) and Vue type checking
	composer run types:check
	npm run types:check

ci: lint typecheck test ## Run the full local quality gate

release: ## Build and push the production image (bumps patch; pass ARGS=--minor etc.)
	scripts/release.sh $(ARGS)

release-dry: ## Build the production image without pushing
	scripts/release.sh --dry-run $(ARGS)
