---
kind: adr
id: ADR-0006
title: "Machine-checked traceability with a spec-check drift gate"
status: deprecated
date: 2026-07-19
---

# ADR-0006 — Machine-checked traceability with a `spec-check` drift gate

## Status

Deprecated. The traceability map and the PHP `spec-check` gate this record introduced were retired
when the repository moved to the sdd plugin's informative profile: the code leads, `docs/` describes
it, and only the [constitution](../architecture.md) binds. The vendored `scripts/sdd-check.py` gate
now checks the documents (REQ-N8). The record is kept for history.

## Context

The repo runs a lightweight Specification-Driven Development paradigm: a
`REQ-*` index, a design narrative, ADRs, and a hand-maintained traceability
matrix (`traceability.md`). Kept purely by hand, that matrix
drifts silently — a requirement's code is moved, a class is renamed, or a merge
(e.g. consolidating two prompts into one) leaves a stale count or a dangling
path, and nothing fails. The methodology's non-negotiable gate is exactly this:
*a requirement with no code, code with no test, or a cited path that no longer
exists is a mechanically detectable drift signal.* Without it, "we have specs"
never becomes "our specs cannot silently rot."

## Decision

Add a machine-readable map, `traceability.yaml` (one record
per `REQ-*` → packages / tests / ADRs), and a `spec-check` gate
(`src/Sdd/SpecCheck.php`, entrypoint
`scripts/spec-check.php`) that validates the map
against the working tree. The gate fails when a cited package/test/plan path is
missing, an ADR reference does not resolve, a `landed` requirement lists neither
code nor tests, or the requirement id sets in the map, the index, and the
human matrix disagree. Repo conventions (identifier style, paths, the
`ground_truth` source) are declared once in [.sdd.yaml](../.sdd.yaml). The gate
is wired as `composer check:spec` → `make spec-check`, runs inside `make ci`, and
is a required step in PR validation. Following the repo convention, the tooling
is a PHP class under `src/` with a `scripts/*.php` entrypoint (not a shell
script), and it is itself covered by a test.

## Consequences

- **Positive:** requirement ↔ code ↔ test ↔ ADR links are enforced on every PR;
  moved or renamed artefacts break the build instead of rotting the docs.
- **Positive:** the map is the single machine-checked source of truth for
  REQ→artefact links; `traceability.md` becomes a
  human-readable rendering, and `.sdd.yaml` lets the `sdd-*` tooling stay
  repo-agnostic.
- **Neutral:** adds a `symfony/yaml` dev dependency (dev/CI only; no runtime cost).
- **Negative / gotcha:** adding or moving a `REQ-*`, a capability class, or its
  test now requires updating `traceability.yaml` in the same change — the gate
  will otherwise fail. This is the intended cost of keeping the chain honest.

## Traceability

- Amends: REQ-N8 (machine-checked traceability); governs the whole `REQ-*` spec set
- Related: [.sdd.yaml](../.sdd.yaml), `traceability.yaml`, `traceability.md`
