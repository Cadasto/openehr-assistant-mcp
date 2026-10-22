---
kind: requirement
id: REQ-F-003
title: Curated examples search and retrieval
status: draft
implementation: landed
---

# REQ-F-003 — Curated examples search and retrieval

## Capability

Discover and retrieve curated worked examples (AQL, FLAT, STRUCTURED payloads, gold-standard ADL archetypes),
so an agent imitates a known-good artefact rather than inventing a shape.

## Acceptance criteria

- [x] An agent searches the examples and fetches one by kind and name.
- [x] Every example kind the server offers is reachable through both the tools and the
  [resources](REQ-F-007-resources.md).

## Out of scope

- Validating a caller's own artefact against an example.

## Canonical specification

- [SPEC-TOOLS §3](../specifications/knowledge-tools.md#examples-req-f-003)

## Notes

- Formerly `REQ-F3`; renumbered once, in the move to the sdd 0.15.0 layout.
