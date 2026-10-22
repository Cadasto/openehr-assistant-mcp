---
kind: specification
spec: SPEC-ENGINEERING
status: draft
mode: implementation-aligned
---

# SPEC-ENGINEERING — engineering constraints

> **Normative.** Uses RFC-2119 keywords: **MUST/SHALL** (absolute), **SHOULD** (strong, exceptions need a reason),
> **MAY** (optional). Statements without a keyword are *informative*. *Implementation-aligned:* each section states
> what the build and CI enforce today, and a change to either updates its section in the same pull request.

## §1 — Tests and the HTTP boundary

<a id="tests-and-http-boundary-req-n-002"></a>

All outbound HTTP MUST go through the single CKM client; other components MUST NOT make network requests.

Tests MUST NOT call CKM or any other live service. A test that needs CKM MUST mock the CKM client.

Each application class SHOULD have a mirrored `*Test` in the test tree.

**Implements:** REQ-N-002

## §2 — Static analysis and style

<a id="static-analysis-req-n-003"></a>

Code MUST pass PHPStan at level 8 before it merges; pull-request validation runs it on every change.

Code SHOULD follow PSR-12.

**Implements:** REQ-N-003

## §3 — Docker-only runtime

<a id="docker-only-runtime-req-n-005"></a>

Every build, test and run command the repository documents MUST be runnable inside the project's containers.
Repository tooling MUST NOT assume PHP, Composer or Python on the host.

**Implements:** REQ-N-005

## §4 — Traceability drift gate

<a id="drift-gate-req-n-008"></a>

The traceability map MUST be validated on every pull request by the vendored `sdd-check` gate, at the version
the SDD descriptor pins.

The gate MUST fail the build on a requirement without a record or an index row, a cited path or canonical
anchor that does not resolve, an enforced record without evidence, or a broken relative link in the
documentation.

The vendored gate MUST NOT be edited by hand; it changes only by re-vendoring it from the sdd plugin.

**Implements:** REQ-N-008
