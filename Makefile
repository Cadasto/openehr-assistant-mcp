.PHONY: help up down clean logs ps build build-dev env install up-dev sh run-stdio inspector inspector-stop sync-skills

# Default target
.DEFAULT_GOAL := help

# Colors for output
CYAN := \033[0;36m
GREEN := \033[0;32m
YELLOW := \033[0;33m
RED := \033[0;31m
NC := \033[0m # No Color

# Configuration
DOCKER_COMPOSE ?= docker compose
DOCKER_COMPOSE_DEV ?= docker compose -f docker-compose.yml -f docker-compose.dev.yml

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
	$(DOCKER_COMPOSE_DEV) run --rm -u 1000:1000 mcp composer install

up-dev: ## Start dev container in background
	$(DOCKER_COMPOSE_DEV) up -d --force-recreate

sh: ## Open an interactive shell in dev container
	-$(DOCKER_COMPOSE_DEV) exec -u 1000:1000 mcp sh || $(DOCKER_COMPOSE_DEV) run --rm -it -u 1000:1000 mcp sh

run-stdio: ## Run MCP server (stdio transport) in dev container
	$(DOCKER_COMPOSE_DEV) run --rm mcp php public/index.php --transport=stdio

##@ MCP inspector UI

inspector: ## Run modelcontextprotocol/inspector UI
	docker run --rm -p 6274:6274 -p 6277:6277 -e HOST=0.0.0.0 --name inspector --pull always ghcr.io/modelcontextprotocol/inspector:latest

inspector-stop: ## Stop the modelcontextprotocol/inspector UI container
	docker stop inspector

##@ Cursor / Claude Code skills

sync-skills: ## Sync skills/ to .cursor/skills/ and .claude/skills/ (run after clone or when editing skills)
	@bash scripts/sync-skills.sh