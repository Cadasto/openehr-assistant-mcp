---
kind: requirement
id: REQ-F-002
title: Guide discovery, retrieval and ADL idioms
status: draft
implementation: landed
---

# REQ-F-002 — Guide discovery, retrieval and ADL idioms

## Capability

Discover and retrieve implementation guides by category, and look up ADL constraint idioms, so an agent works
from the project's curated modelling guidance instead of its own recollection.

## Acceptance criteria

- [x] An agent searches the guides and fetches one by category and name.
- [x] An agent looks up the ADL idiom snippets for a modelling pattern.
- [x] Guide search and example search split a query into the same search terms.

## Out of scope

- Authoring or editing guides through the server; guides ship with each release.

## Canonical specification

- [SPEC-TOOLS §2](../specifications/knowledge-tools.md#guides-req-f-002)

## Notes

- Formerly `REQ-F2`; renumbered once, in the move to the sdd 0.15.0 layout.
