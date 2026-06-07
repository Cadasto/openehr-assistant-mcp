# ADR-0002 — Single `CkmClient` external HTTP boundary, mocked in tests

- **Status:** Accepted
- **Requirements:** REQ-F1 (CKM access), REQ-N2 (deterministic, offline tests)
- **Related:** [architecture.md](../architecture.md)

## Context

The only external dependency the server reaches at runtime is the openEHR
Clinical Knowledge Manager (CKM) REST API. If HTTP calls were scattered across
the tool services, every test that exercised those services would either hit the
live CKM (slow, flaky, network-dependent, rate-limited) or need its own ad-hoc
HTTP stubbing.

## Decision

Funnel **all** outbound HTTP through a single `src/Apis/CkmClient` (Guzzle-based,
`request` / `requestAsync`). Tool services depend on this client, not on Guzzle
directly. In tests, mock `CkmClient` (or its underlying handler) so no test ever
performs a live network call.

## Consequences

- **Positive:** one seam to mock; CKM-touching tests are fast, deterministic, and
  run offline / in CI without network access.
- **Positive:** cross-cutting HTTP concerns (timeout, SSL verification, base URL
  from `CKM_API_BASE_URL`) are configured in one place.
- **Negative:** all CKM interaction must route through this client; a service
  that bypassed it would break the testability invariant and should be rejected
  in review.
- Reinforced by REQ-N2: "mock external HTTP to CKM — never hit live APIs."
