# Contributing to openehr-assistant-mcp

Thank you for your interest in contributing! This document covers the contribution
**process**; setup, environment, and conventions are kept in the canonical docs and
linked below so there is a single source of truth.

## Table of contents

- [Code of Conduct](#code-of-conduct)
- [Getting help](#getting-help)
- [Setting up & running](#setting-up--running)
- [Testing & quality](#testing--quality)
- [Conventions](#conventions)
- [Commits & pull requests](#commits--pull-requests)
- [Branching & versioning](#branching--versioning)
- [Security](#security)

## Code of Conduct

Please be respectful and constructive. By participating, you agree to uphold a
professional and inclusive environment. Report unacceptable behavior privately via
the repository's security/contact channels (see [Security](#security)).

## Getting help

- **Usage questions** — open a GitHub Discussion (if enabled) or a Question issue with a minimal reproducible example.
- **Bugs** — open an Issue with expected vs actual behavior, steps to reproduce, environment, and logs.
- **Feature requests** — explain the use-case and proposed API/UX.

## Setting up & running

The runtime is **Docker-only** (no host PHP/Composer). Quickstart:

```bash
cp .env.example .env
make up-dev      # start dev containers
make install     # install Composer deps in the container
```

- Local & hosted setup, plus MCP client configurations → **[docs/install.md](docs/install.md)**
- Dev environment, Makefile shortcuts, configuration, MCP Inspector, troubleshooting → **[docs/development.md](docs/development.md)**

## Testing & quality

Run the full check (PHPStan + tests) before pushing:

```bash
make ci
```

Test/coverage/conformance commands and conventions → **[docs/testing.md](docs/testing.md)**.

- Tests live under `tests/` (namespace `Cadasto\OpenEHR\MCP\Assistant\Tests`), named `*Test.php`, mirroring `src/`.
- **Mock external HTTP to CKM** — never hit live APIs in tests.

## Conventions

Before adding or changing a capability, read:

- **[docs/conventions.md](docs/conventions.md)** — coding standard (PSR-12), namespaces, and MCP authoring conventions (tools, prompts, resources, completion providers).
- **[docs/architecture.md](docs/architecture.md)** — components, layers, and the design decisions behind them.

## Commits & pull requests

- Use [Conventional Commits](https://www.conventionalcommits.org/) with a scope: `feat(tools):`, `fix(resources):`, `docs:`, `refactor:`, `test:`, `chore:`.
- Descriptive title; body explains what, why, how, and risks.
- One logical change per PR; split large changes.
- Link related issues with keywords (e.g. `Fixes #123`).

**PR checklist**

- [ ] Tests added/updated
- [ ] Docs updated if needed (incl. `CHANGELOG.md`)
- [ ] No debug code or leftover comments
- [ ] `make ci` passes locally and CI is green

## Branching & versioning

- Default branch: `main`. Create feature branches: `feat/short-description` or `fix/short-description`.
- SemVer; `APP_VERSION` lives in `src/constants.php` (bump on breaking MCP interface changes).
- Keep `CHANGELOG.md` in [Keep a Changelog](https://keepachangelog.com/) format — `## [Unreleased]` entries short and high-level.

## Security

Do **not** open public issues for security vulnerabilities. Report privately via
GitHub security advisories or the contact in `SECURITY.md` (if present); otherwise
email the maintainers.

Thank you for contributing!
