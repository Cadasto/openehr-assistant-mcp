---
kind: guide
---

# CI — how the methodology is enforced

CI is *operational process*, not part of the binding contract, but it is where drift is caught
mechanically. Two enabling policies:

1. **One set of checks.** PR validation runs the same `composer` scripts that `make ci` runs in the dev
   container, so a green `make ci` predicts a green pull request. `make ci` covers every PR-validation step
   except the production image build, which `make build` runs.
2. **Reproducible toolchain.** Locally every check runs in the dev container ([development.md](development.md));
   in CI the workflow pins PHP 8.4 (`setup-php`) and Python 3.12 (`setup-python`, for the drift gate).

## Jobs

[`pr-validation.yml`](../.github/workflows/pr-validation.yml) runs on every `pull_request` event:

| Step | Enforces | Local equivalent |
|---|---|---|
| **spec-check** | `sdd-check` self-test, then the check of the documents: descriptor, document kinds, links and anchors, changelog bullets, generated blocks | `make spec-check` |
| **PHPStan** | static analysis at level 8 | `composer check:phpstan` |
| **PHPUnit** | the test suite, including the guard tests that make each [constitution](architecture.md) MUST bind ([testing.md](testing.md)) | `composer test` |
| **Docker build** | the production image builds | `make build` |

Not in CI: the MCP conformance suite needs the running stack, so it runs locally with `make conformance`.
Pushing a tag runs [`release.yml`](../.github/workflows/release.yml), which builds and pushes the image to
GHCR.

On this profile the constitution binds through the test suite, not through `spec-check`: a MUST sentence
holds because a named test fails when it breaks. Before a done-claim, run `make ci` and read its output.
`/sdd-review` writes findings into the branch's findings file and mirrors the blocking ones to the PR's
inline threads; `/sdd-close` performs the close-out inside the implementing PR.
