---
kind: guide
---

# CI — how the methodology is enforced

CI is *operational process*, not part of the normative contract, but it is where SDD drift is caught
mechanically. Two enabling policies:

1. **One set of checks.** PR validation runs the same `composer` scripts that `make ci` runs in the dev
   container, so a green `make ci` predicts a green pull request. The full PR gate is `make ci`.
2. **Reproducible toolchain.** Locally every check runs in the dev container ([development.md](development.md));
   in CI the runner provides PHP 8.4 (`setup-php`) and Python 3 for the drift gate.

## Jobs

[`pr-validation.yml`](../.github/workflows/pr-validation.yml) runs on every `pull_request` event:

| Step | Enforces | Local equivalent |
|---|---|---|
| **spec-check** | `sdd-check` self-test, then the check: map ↔ tree, index = map, document kinds, links and fragments, RFC-2119 and one-home, changelog bullets, generated blocks ([SPEC-ENGINEERING §4](specifications/engineering.md#drift-gate-req-n-008)) | `make spec-check` |
| **PHPStan** | static analysis at level 8 ([SPEC-ENGINEERING §2](specifications/engineering.md#static-analysis-req-n-003)) | `composer check:phpstan` |
| **PHPUnit** | the test suite, guard tests included ([testing.md](testing.md)) | `composer test` |
| **Docker build** | the production image builds | `make build` |

Not in CI: the MCP conformance suite needs the running stack, so it runs locally with `make conformance`
([SPEC-SURFACE §7](specifications/mcp-surface.md#conformance-req-n-006)). Pushing a tag runs
[`release.yml`](../.github/workflows/release.yml), which builds and pushes the image to GHCR.

The non-negotiable SDD gate is **`spec-check`**: it turns "we have specs" into "our specs can't silently
rot", and it runs on **both** lanes. Before a done-claim, run `make ci` and read its output, then
`/sdd-trace` for traceability drift: `/sdd-trace` reports it in session; `spec-check` fails on it.
`/sdd-review` writes findings into the branch's findings file and mirrors the blocking ones to the PR's
inline threads; `/sdd-close` performs the close-out inside the implementing PR.
