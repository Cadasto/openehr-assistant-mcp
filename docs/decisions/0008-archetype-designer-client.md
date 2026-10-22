# ADR-0008 — A second HTTP boundary for the Archetype Designer API, configured per deployment

- **Status:** Proposed
- **Requirements:** REQ-F11 (Archetype Designer read access), REQ-N2 (deterministic, offline tests)
- **Related:** [ADR-0002](0002-single-ckmclient-http-boundary.md), [architecture.md](../architecture.md)

## Context

Modellers author archetypes and templates in openEHR Archetype Designer; CKM only
holds published, governed content. To review work in progress, agents need read
access to Designer repositories. The Designer server publishes an OpenAPI
description (`/designer/v3/api-docs`, "designed to be used by the designer client").
Unlike CKM it requires authentication: HTTP Basic with a Designer *local* account,
plus a `Repository-Token` header (the repository host's token, e.g. GitHub) on
repository-scoped calls.

ADR-0002 funnels all outbound HTTP through `CkmClient`. Pointing that client at a
second API would mix two base URLs and two credential models in one class.

## Decision

Add `src/Apis/ArchetypeDesignerClient`, built like `CkmClient` (Guzzle, `request` /
`requestAsync`, injectable for tests), as the single boundary to the Designer API.
ADR-0002's rule generalises to "one client per upstream API; tool services never use
Guzzle directly".

- Credentials, the default repository and the base URL come from environment
  variables (`ARCHETYPE_DESIGNER_*`); nothing is hard-coded and credentials are never
  logged.
- The tools are always registered; without credentials they fail with an actionable
  tool error instead of disappearing, so the capability set does not depend on
  deployment config (keeping the discovery cache valid).
- Start read-only. Write operations (save, branch, pull request) follow behind an
  explicit opt-in.

## Consequences

- **Positive:** the CKM and Designer seams stay independently mockable; tests stay
  offline.
- **Positive:** a deployment opts in by setting credentials; public deployments
  without them are unaffected.
- **Negative:** credentials are per server, not per user, so every client of a
  deployment sees the same repositories. Per-user access depends on an auth layer for
  the MCP endpoint itself and is out of scope here.
- **Negative:** the Designer API is built for its own web client, not as a public
  API; it can change without notice. The tools only depend on the small read subset.
