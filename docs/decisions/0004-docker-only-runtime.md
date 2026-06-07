# ADR-0004 — Docker-only runtime; no host PHP/Composer

- **Status:** Accepted
- **Requirements:** REQ-N5 (reproducible runtime)
- **Related:** [development.md](../development.md), [testing.md](../testing.md)

## Context

Maintainers develop on WSL2 on Windows, where a host PHP 8.4 toolchain with the
required extensions is neither guaranteed nor consistent across machines. PHP
version and extension skew is a classic source of "works on my machine"
failures, especially for a server that targets a specific PHP 8.4 feature set.

## Decision

Make Docker the canonical runtime. All `php`, `composer`, and `vendor/bin/*`
commands run **inside** the dev container (`app` service), built from the
multi-stage `.docker/Dockerfile`. Provide `make` targets (`make up-dev`,
`make install`, `make conformance`) and document the full `docker compose … exec`
invocations. The dev container assumes host UID `1000` (adjust the `-u` flag
otherwise).

## Consequences

- **Positive:** every maintainer and CI runs the identical PHP 8.4 + extensions;
  reproducible builds and tests.
- **Negative:** there is no host fallback for routine work — running tools on the
  host will fail. Contributors must start the dev stack first.
- **Optional local path:** a contributor who *does* have PHP 8.4 locally can run
  `composer install/test/check:phpstan` directly, but this is unsupported and not
  the documented default.
- Conformance testing relies on the dev-only `node` service (Node + curl) over
  HTTP — see [testing.md](../testing.md).
