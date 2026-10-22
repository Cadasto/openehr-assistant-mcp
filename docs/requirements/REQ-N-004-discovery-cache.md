---
kind: requirement
id: REQ-N-004
title: Cached discovery and fast startup
status: draft
implementation: landed
---

# REQ-N-004 — Cached discovery and fast startup

## Capability

MCP capability discovery is cached at startup to keep server boot fast under repeated client connections.

## Acceptance criteria

- [x] A second boot reuses the discovery cache instead of scanning attributes again.
- [x] A version bump starts from an empty cache namespace.
- [x] A broken capability fails the server build, not a later request.

## Out of scope

- Pruning cache namespaces left by earlier versions.

## Canonical specification

- [SPEC-SURFACE §6](../specifications/mcp-surface.md#discovery-cache-req-n-004)

## Notes

- Formerly `REQ-N4`; renumbered once, in the move to the sdd 0.15.0 layout.
- Decision: [ADR-0001](../decisions/0001-attribute-driven-discovery.md), attribute-driven discovery.
