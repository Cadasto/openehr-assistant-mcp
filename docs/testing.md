---
kind: guide
---

# Testing and validation

This page is for contributors checking a change before they push: how to run the
PHPUnit suite, PHPStan, coverage, the MCP conformance suite, and the SDD drift gate.
It is the operational companion to the [SDD docs](README.md) and satisfies REQ-N-002,
REQ-N-003, and REQ-N-006. Tests are the verification end of the
[traceability chain](specifications/traceability.yaml): `src/` classes have a
mirrored `tests/…/*Test`.

All commands run **inside the dev container** (see [development.md](development.md)).
Start the stack first with `make up-dev && make install`.

## Test suite (PHPUnit, REQ-N-002)

```bash
docker compose --env-file .env -f .docker/docker-compose.yml -f .docker/docker-compose.dev.yml \
  exec -u 1000:1000 app composer test
```

Conventions:

- Tests live under `tests/`, namespace `Cadasto\OpenEHR\MCP\Assistant\Tests\`,
  files named `*Test.php`, mirroring the `src/` layout 1:1.
- **Mock external HTTP to CKM** via `CkmClient`; never hit live APIs
  ([ADR-0002](decisions/0002-single-ckmclient-http-boundary.md)).
- Run a subset with the filter: `composer test -- --filter CkmServiceTest` (the PHPUnit config lives at `tests/phpunit.xml`, so a bare `vendor/bin/phpunit` does not find it).

### Guard tests

| Test | Guards |
|------|--------|
| `tests/Prompts/PromptCompositionTest.php` | Prompt size vs baselines in `tests/fixtures/prompt_lengths_before_shared.json` (REQ-N-007) |
| `tests/Prompts/PromptPolicySeparationTest.php` | Global policy stays in `server-instructions.md`, not prompt files (REQ-F-010) |
| `tests/Tools/InputSchemaGuardTest.php` | Every `#[McpTool]` input schema is closed and self-consistent (REQ-N-009) |
| `tests/Tools/OutputSchemaConformanceTest.php` | Tool output conforms to its declared `outputSchema`, also through the SDK's runtime check, which turns a mismatch into an `isError` result (REQ-N-009) |
| `tests/Content/InstallDocContractTest.php` | `docs/install.md` keeps the path, hosted-endpoint section, and relative links the website relies on (REQ-N-010) |

## Static analysis (PHPStan, REQ-N-003)

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
automatically. HTML output is written to `var/phpunit/code-coverage`.

## MCP conformance (REQ-N-006)

The server must pass the official MCP conformance suite over HTTP. The stack must
be up (`make up-dev`); the suite runs via the dev-only `node` service:

```bash
make conformance
```

Results are written to `conformance/`; expected/known failures are listed in
`tests/conformance-baseline.yml`.

## SDD drift gate (REQ-N-008)

`make spec-check` (`composer check:spec` in the container) runs
`scripts/sdd-check.py`, the sdd plugin's gate, vendored and version-pinned in
[.sdd.yaml](.sdd.yaml): first its self-test, then the check. The check validates
[traceability.yaml](specifications/traceability.yaml) against the tree and fails on
a missing artefact, a dangling path or canonical anchor, a disagreement with the
requirements index, a broken documentation link, or a hand-edited generated index
([SPEC-ENGINEERING §4](specifications/engineering.md#drift-gate-req-n-008)). When you
add or move a `REQ-*`, a capability class, or its test, update the map in the same
change and regenerate the indexes with `python3 scripts/sdd-check.py generate --root .`
in the dev container.

```bash
make spec-check
```

Never edit `scripts/sdd-check.py` by hand; upgrade it by re-vendoring with
`/sdd-scaffold --upgrade`, which also bumps the pin.

## Before pushing

Run **`make ci`**, which runs `composer check:spec`, `composer check:phpstan`, and
`composer test` in the dev container, as PR validation does (REQ-N-002, REQ-N-003,
REQ-N-008). Use
[Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/); keep
`## [Unreleased]` CHANGELOG entries short and high-level (see [AGENTS.md](../AGENTS.md)).
