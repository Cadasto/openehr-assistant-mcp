.PHONY: help up down clean logs ps build build-dev env install up-dev sh run-stdio conformance spec-check ci inspector inspector-stop docs-build docs-check docs-serve docs-clean

# Default target
.DEFAULT_GOAL := help

# Colors for output
CYAN := \033[0;36m
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
NC := \033[0m # No Color

# Configuration
DOCKER_COMPOSE ?= docker compose --env-file .env -f .docker/docker-compose.yml
DOCKER_COMPOSE_DEV ?= docker compose --env-file .env -f .docker/docker-compose.yml -f .docker/docker-compose.dev.yml

##@ General

help: ## Display this help message
	@echo ""
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make $(CYAN)<target>$(NC)\n"} /^[a-zA-Z_0-9-]+:.*?##/ { printf "  $(CYAN)%-20s$(NC) %s\n", $$1, $$2 } /^##@/ { printf "\n$(YELLOW)%s$(NC)\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Container Management

up: ## Start dev containers in background
	$(DOCKER_COMPOSE) up -d --force-recreate

down: ## Stop all services (dev/prod) and keep data
	$(DOCKER_COMPOSE) down

clean: ## Stop and remove containers, networks, and volumes
	$(DOCKER_COMPOSE) down -v --remove-orphans

logs: ## Tail logs and follow log output
	$(DOCKER_COMPOSE) logs -f

ps: ## List running containers for this project
	$(DOCKER_COMPOSE) ps

##@ Build images

build: ## Build production image
	$(DOCKER_COMPOSE) build

build-dev: ## Build dev image
	$(DOCKER_COMPOSE_DEV) build

##@ Development Workflow

env: ## Copy .env.example to .env if not present
	@test -f .env || cp .env.example .env
	@echo ".env ready"

install: ## Install PHP dependencies of dev container
	$(DOCKER_COMPOSE_DEV) run --rm -u 1000:1000 app composer install

up-dev: ## Start dev container in background
	$(DOCKER_COMPOSE_DEV) up -d --force-recreate

sh: ## Open an interactive shell in dev container
	-$(DOCKER_COMPOSE_DEV) exec -u 1000:1000 app sh || $(DOCKER_COMPOSE_DEV) run --rm -it -u 1000:1000 app sh

run-stdio: ## Run MCP server (stdio transport) in dev container
	$(DOCKER_COMPOSE_DEV) run --rm app php public/index.php --transport=stdio

conformance: ## Run MCP conformance tests against server (requires make up-dev; URL: http://ingress:8343/mcp). Results in conformance/
	$(DOCKER_COMPOSE_DEV) run --rm node npx -y @modelcontextprotocol/conformance server --url http://ingress:8343/mcp -o conformance --expected-failures tests/conformance-baseline.yml

##@ Quality & CI

spec-check: ## Validate the SDD traceability map against the tree (drift gate)
	$(DOCKER_COMPOSE_DEV) run --rm -u 1000:1000 app composer check:spec

ci: ## Run CI checks in dev container (spec-check + PHPStan + tests)
	$(DOCKER_COMPOSE_DEV) run --rm -u 1000:1000 app sh -c "composer check:spec && composer check:phpstan && composer test"

##@ Documentation site

# Pinned: the published site is built from this image without human review, and
# an unpinned tag would silently change both the output and CI-vs-local parity.
MKDOCS_IMAGE ?= squidfunk/mkdocs-material:9.7.6
MKDOCS_DIR   := docs/site
DOCS_BUILD   := docs-build
DOCKER_USER  := $(shell id -u):$(shell id -g)
# Run as the invoking user so build output and the plugin cache are not left
# root-owned in the working tree; PYTHONDONTWRITEBYTECODE keeps `__pycache__`
# out of `docs/site/hooks/`. The build is strict — see `strict:` in mkdocs.yml.
MKDOCS_RUN   := docker run --rm -u $(DOCKER_USER) -e PYTHONDONTWRITEBYTECODE=1 \
                  -v "$(CURDIR):/docs" -w /docs/$(MKDOCS_DIR) $(MKDOCS_IMAGE)

docs-build: ## Build product website to docs-build/
	$(MKDOCS_RUN) build -d /docs/$(DOCS_BUILD)

docs-check: docs-build ## Build the site and assert the published output is complete
	@set -e; \
	test -s "$(DOCS_BUILD)/index.html" \
	  || { echo "docs-check: no index.html in $(DOCS_BUILD)/"; exit 1; }; \
	test -s "$(DOCS_BUILD)/stylesheets/cadasto.css" \
	  || { echo "docs-check: brand stylesheet not emitted — is it inside $(MKDOCS_DIR)/pages/?"; exit 1; }; \
	test -s "$(DOCS_BUILD)/assets/logo.svg" \
	  || { echo "docs-check: logo/favicon not emitted — is it inside $(MKDOCS_DIR)/pages/?"; exit 1; }; \
	grep -q 'openehr-assistant-mcp.apps.cadasto.com' "$(DOCS_BUILD)/install/index.html" \
	  || { echo "docs-check: install page has no install content — is pages/install.md still a symlink?"; exit 1; }; \
	! grep -rqE 'https?://fonts\.(googleapis|gstatic)\.com' "$(DOCS_BUILD)" \
	  || { echo "docs-check: remote font URLs in output — the privacy plugin did not localise them"; exit 1; }; \
	echo "docs-check: OK"

docs-serve: ## Serve product website locally on http://127.0.0.1:8000
	docker run --rm -it -u $(DOCKER_USER) -e PYTHONDONTWRITEBYTECODE=1 \
	  -p 127.0.0.1:8000:8000 -v "$(CURDIR):/docs" -w /docs/$(MKDOCS_DIR) \
	  $(MKDOCS_IMAGE) serve -a 0.0.0.0:8000

docs-clean: ## Remove docs-build/ and the MkDocs plugin cache
	@test -n "$(DOCS_BUILD)" || { echo "docs-clean: DOCS_BUILD must not be empty"; exit 1; }
	@rm -rf "$(CURDIR)/$(DOCS_BUILD)" "$(CURDIR)/$(MKDOCS_DIR)/.cache" \
	        "$(CURDIR)/$(MKDOCS_DIR)/hooks/__pycache__" 2>/dev/null \
	  || { echo "docs-clean: output is root-owned (built before the -u fix) — removing in a container"; \
	       docker run --rm -v "$(CURDIR):/docs" alpine:3.20 sh -c \
	         'rm -rf /docs/$(DOCS_BUILD) /docs/$(MKDOCS_DIR)/.cache /docs/$(MKDOCS_DIR)/hooks/__pycache__'; }

##@ MCP inspector UI

inspector: ## Run modelcontextprotocol/inspector UI (prints the auth URL; seeded target http://ingress:8343/mcp)
	$(DOCKER_COMPOSE_DEV) up -d --build inspector
	@printf 'Waiting for MCP Inspector'; \
	for i in $$(seq 1 30); do \
		url=$$($(DOCKER_COMPOSE_DEV) logs inspector 2>/dev/null | grep -oE 'http://[^[:space:]]*:6274/?\?MCP_(INSPECTOR_API|PROXY_AUTH)_TOKEN=[A-Za-z0-9]+' | tail -1); \
		if [ -n "$$url" ]; then \
			printf '\n\nMCP Inspector ready — open:\n  %s\n' "$$(echo "$$url" | sed 's#://0\.0\.0\.0:#://localhost:#')"; \
			exit 0; \
		fi; \
		printf '.'; sleep 1; \
	done; \
	printf '\nTimed out; check manually: $(DOCKER_COMPOSE_DEV) logs inspector\n'

inspector-stop: ## Stop and remove the modelcontextprotocol/inspector UI container
	$(DOCKER_COMPOSE_DEV) rm -sf inspector