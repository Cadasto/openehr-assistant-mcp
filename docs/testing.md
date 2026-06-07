# Testing and Validation

> Operational companion to the [SDD docs](README.md). Satisfies REQ-N2, REQ-N3,
> REQ-N6. Tests are the verification end of the
> [traceability chain](traceability.md): each `src/` class has a mirrored
> `tests/…/*Test`.

All commands run **inside the dev container** (see [development.md](development.md)).
Start the stack first with `make up-dev && make install`.

## Test suite (PHPUnit) — REQ-N2

```bash
docker compose --env-file .env -f .docker/docker-compose.yml -f .docker/docker-compose.dev.yml \
  exec -u 1000:1000 app composer test
```

Conventions:

- Tests live under `tests/`, namespace `Cadasto\OpenEHR\MCP\Assistant\Tests\`,
  files named `*Test.php`, mirroring the `src/` layout 1:1.
- **Mock external HTTP to CKM** via `CkmClient`; never hit live APIs
  ([ADR-0002](decisions/0002-single-ckmclient-http-boundary.md)).
- Run a subset with the filter: `vendor/bin/phpunit --filter CkmServiceTest`.

### Guard tests

| Test | Guards |
|------|--------|
| `tests/Prompts/PromptCompositionTest.php` | Prompt size vs baselines in `tests/fixtures/prompt_lengths_before_shared.json` (REQ-N7) |
| `tests/Prompts/PromptPolicySeparationTest.php` | Global policy stays in `server-instructions.md`, not prompt files (REQ-F10) |

## Static analysis (PHPStan) — REQ-N3

```bash
docker compose --env-file .env -f .docker/docker-compose.yml -f .docker/docker-compose.dev.yml \
  exec -u 1000:1000 app composer check:phpstan
```

## Coverage

```bash
docker compose --env-file .env -f .docker/docker-compose.yml -f .docker/docker-compose.dev.yml \
  exec -u 1000:1000 app composer test:coverage
```

Coverage requires Xdebug; the `test:coverage` script sets `XDEBUG_MODE`
automatically. HTML output is written under the project's coverage directory.

## MCP conformance — REQ-N6

The server must pass the official MCP conformance suite over HTTP. The stack must
be up (`make up-dev`); the suite runs via the dev-only `node` service:

```bash
make conformance
```

Results are written to `conformance/`; expected/known failures are listed in
`tests/conformance-baseline.yml`.

## Before pushing

Run **`composer test`** and **`composer check:phpstan`** (REQ-N2, REQ-N3). Use
[Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/); keep
`## [Unreleased]` CHANGELOG entries short and high-level (see [AGENTS.md](../AGENTS.md)).
