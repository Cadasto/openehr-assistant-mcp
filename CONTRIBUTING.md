# Contributing to openehr-assistant-mcp

Thank you for your interest in contributing! This document covers the contribution
**process**. Setup, environment, and conventions live in the canonical docs linked
below, so each has a single source of truth.

## Table of contents

- [Code of Conduct](#code-of-conduct)
- [Getting help](#getting-help)
- [Setting up and running](#setting-up-and-running)
- [Testing and quality](#testing-and-quality)
- [Conventions](#conventions)
- [Commits and pull requests](#commits-and-pull-requests)
- [Branching and versioning](#branching-and-versioning)
- [Security](#security)

## Code of Conduct

Please be respectful and constructive. By participating, you agree to uphold a
professional and inclusive environment. Report unacceptable behaviour privately via
the repository's security/contact channels (see [Security](#security)).

## Getting help

- **Usage questions**: open a GitHub Discussion (if enabled) or a Question issue with a minimal reproducible example.
- **Bugs**: open an issue with expected and actual behaviour, steps to reproduce, environment, and logs.
- **Feature requests**: explain the use case and the proposed API or UX.

## Setting up and running

The runtime is **Docker-only** (no host PHP/Composer). Quickstart:

```bash
cp .env.example .env
make up-dev      # start dev containers
make install     # install Composer deps in the container
```

- Local and hosted setup, plus MCP client configurations → **[docs/install.md](docs/install.md)**
- Dev environment, Makefile shortcuts, configuration, MCP Inspector, troubleshooting → **[docs/development.md](docs/development.md)**

## Testing and quality

Run the full check (spec-check, PHPStan, and tests) before pushing:

```bash
make ci
```

Test, coverage, and conformance commands and conventions → **[docs/testing.md](docs/testing.md)**.

- Tests live under `tests/` (namespace `Cadasto\OpenEHR\MCP\Assistant\Tests`), named `*Test.php`, mirroring `src/`.
- **Mock external HTTP to CKM**; never hit live APIs in tests.

## Conventions

Before adding or changing a capability, read:

- **[docs/conventions.md](docs/conventions.md)**: coding standard (PSR-12), namespaces, and MCP authoring conventions (tools, prompts, resources, completion providers).
- **[docs/architecture.md](docs/architecture.md)**: components, layers, and the design decisions behind them.

## Commits and pull requests

- Use [Conventional Commits](https://www.conventionalcommits.org/) with a scope: `feat(tools):`, `fix(resources):`, `docs:`, `refactor:`, `test:`, `chore:`.
- Descriptive title; body explains what, why, how, and risks.
- One logical change per PR; split large changes.
- Link related issues with keywords (for example `Fixes #123`).

**PR checklist**

- [ ] Tests added/updated
- [ ] Docs updated if needed (incl. `CHANGELOG.md`)
- [ ] No debug code or leftover comments
- [ ] `make ci` passes locally and CI is green

## Branching and versioning

- Default branch: `main`. Create feature branches: `feat/short-description` or `fix/short-description`.
- SemVer; `APP_VERSION` lives in `src/constants.php` (bump on breaking MCP interface changes).
- Keep `CHANGELOG.md` in [Keep a Changelog](https://keepachangelog.com/) format, with `## [Unreleased]` entries short and high-level.
- At a release, update the version badge in `README.md` to the new `APP_VERSION`.

## Security

Do **not** open public issues for security vulnerabilities. Report privately through
GitHub security advisories, or email the maintainers.

Thank you for contributing!
