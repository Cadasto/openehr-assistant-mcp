# Contributing to openehr-assistant-mcp

Thank you for your interest in contributing! This document explains how to set up your environment, propose changes, and follow our conventions so that we can review and merge your work efficiently.

This guide is self‑contained; you do not need to read `guidelines.md`. The most relevant development and testing instructions have been incorporated here per GitHub best practices for CONTRIBUTING files.


## Table of contents
- [Code of Conduct](#code-of-conduct)
- [Getting help and asking questions](#getting-help-and-asking-questions)
- [Project setup (Docker)](#project-setup-docker)
- [Environment configuration](#environment-configuration)
- [Running the server (stdio and streamable HTTP)](#running-the-server-stdio-and-streamable-http)
- [Running tests and coverage](#running-tests-and-coverage)
- [Static analysis and code style](#static-analysis-and-code-style)
- [MCP conventions (Tools and Prompts)](#mcp-conventions-tools-and-prompts)
- [Troubleshooting tips](#troubleshooting-tips)
- [Commit messages and pull requests](#commit-messages-and-pull-requests)
- [Branching, issues, and release notes](#branching-issues-and-release-notes)
- [Versioning](#versioning)
- [Security](#security)


## Code of Conduct
Please be respectful and constructive. By participating, you agree to uphold a professional and inclusive environment. If you encounter unacceptable behavior, contact the maintainers privately via the repository’s security/contact channels.


## Getting help and asking questions
- For usage questions, open a GitHub Discussion (if enabled) or a Question issue with a minimal reproducible example.
- For bugs, open an Issue and include: expected behavior, actual behavior, steps to reproduce, environment details, and logs if relevant.
- For feature requests, explain the use‑case and proposed API/UX.


## Project setup (Docker)
Prerequisites:
- Docker and Docker Compose

Recommended developer workflow (inside Docker):
1. `git clone <your-fork-url>`
2. `cd openehr-assistant-mcp`
3. Option A — Docker Compose directly:
   - `docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d mcp --build --force-recreate`
   
   Option B — Makefile shortcuts:
   - `make up-dev` (uses both docker-compose files)
4. `cp .env.example .env`
5. Install dependencies inside the dev container:
   - Compose: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer install`
   - Make: `make install`

Notes
- Always execute PHP/Composer inside the `mcp` dev container to match PHP 8.4 and extensions.
- On Windows/WSL2, edit within the WSL filesystem for performance.


## Environment configuration
Edit `.env` and set at least:
- `CKM_API_BASE_URL` (default `https://ckm.openehr.org/ckm/rest`)
- `LOG_LEVEL` (set to `debug` during development if needed)
- `HTTP_TIMEOUT` (float seconds), `HTTP_SSL_VERIFY` (bool or CA path)


## Running the server (stdio and streamable HTTP)
Inside the dev container:
- Streamable HTTP (default): available at `http://openehr-assistant-mcp.local:8343/mcp_openehr` when the dev service is up (`make up-dev` or the compose command above)
- Stdio (one-off run):
  - Compose: `docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm mcp php public/index.php --transport=stdio`
  - Make: `make run-stdio`

The entrypoint script is `public/index.php`. Available transports:
- `--transport=stdio`
- `--transport=streamable-http` (default if omitted)


## Running tests and coverage
- Full test suite:
  - Compose: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer test`
  - Make (inside shell): `make sh` then run `composer test`
- Run a subset: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp vendor\bin\phpunit --filter SomeTest`
- Coverage HTML report: `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer test:coverage`

Testing guidelines
- Tests live under `tests/` with namespace `Cadasto\\OpenEHR\\MCP\\Assistant\\Tests` (see `tests/phpunit.xml`).
- Name files `*Test.php`.

Example smoke test:
```php
<?php
declare(strict_types=1);

namespace Cadasto\OpenEHR\MCP\Assistant\Tests;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function test_truth(): void
    {
        $this->assertTrue(true);
    }
}
```

To run a single test class:
```powershell
docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp vendor\bin\phpunit --filter SmokeTest
```


## Static analysis and code style
- Coding standard: PSR-12. Use PHP CS Fixer (or IDE) if available.
- Static analysis (PHPStan): `docker compose -f docker-compose.yml -f docker-compose.dev.yml exec mcp composer check:phpstan`
- Keep methods small; use typed signatures; add phpdoc where types aren’t obvious.


## MCP conventions (Tools and Prompts)
- Tools live in `src\Tools`; annotate public methods with `#[McpTool(name: '...')]` for discovery by `modelcontextprotocol/php-sdk` in `public/index.php`.
- Prompts live in `src\Prompts`; keep prompt descriptions focused on guiding tool orchestration.
- Constants and versioning live in `src\constants.php` (see `APP_VERSION`).


## Troubleshooting tips
- Port `8343` conflicts: adjust published port in `docker-compose.dev.yml`.
- Coverage requires Xdebug; use the `composer test:coverage` script which sets `XDEBUG_MODE`.
- SSL issues in dev: set `HTTP_SSL_VERIFY=false` (do not use in production).
- Windows/WSL2: Prefer editing within the WSL filesystem for performance.
- To manage containers quickly, use the Makefile: `make up`, `make up-dev`, `make logs`, `make ps`, `make down`, `make clean`.


## Commit messages and pull requests
- Use conventional commits when possible (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`, `chore:`).
- Write descriptive titles and include context in the body: what, why, how, and risks.
- One logical change per PR. Large changes can be split into smaller PRs.
- Run the full test suite and static analysis locally before pushing.
- Link related issues using GitHub keywords (e.g., `Fixes #123`).

PR checklist:
- Tests added/updated
- Docs updated if needed
- No debug code or leftover comments
- All checks (CI) pass


## Branching, issues, and release notes
- Default branch: `main`
- Create feature branches from `main`: `feature/short-description` or `fix/short-description`
- We follow SemVer for releases and maintain a `CHANGELOG.md` (Keep a Changelog format recommended).


## Versioning
- Application version is defined in `src\constants.php` (`APP_VERSION`). Update it when making breaking MCP Tool interface changes.


## Security
Do not open public issues for security vulnerabilities. Instead, please report privately using GitHub’s security advisories or the contact method listed in `SECURITY.md` if present. If not available, email the maintainers.

Thank you for contributing!