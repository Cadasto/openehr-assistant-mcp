---
kind: requirement
id: REQ-F-001
title: CKM archetype and template search and retrieval
status: draft
implementation: landed
---

# REQ-F-001 — CKM archetype and template search and retrieval

## Capability

Search and retrieve archetypes and templates from the openEHR Clinical Knowledge Manager (CKM), with relevance
scoring and result sizing, so an agent reuses published models before it authors new ones.

## Acceptance criteria

- [x] An agent finds archetypes and templates in CKM by keyword, and fetches one by identifier.
- [x] Search results carry a relevance score, respect the caller's result limit, and report a total match count.
- [x] An archetype comes back in the format the caller asks for, ADL by default; a format the tool does not
  offer is refused.
- [x] No test reaches the live CKM ([REQ-N-002](REQ-N-002-tests-and-mocked-http.md)).

## Out of scope

- Writing to CKM: uploading, reviewing, or changing assets.

## Canonical specification

- [SPEC-TOOLS §1](../specifications/knowledge-tools.md#ckm-retrieval-req-f-001)

## Notes

- Formerly `REQ-F1`; renumbered once, in the move to the sdd 0.15.0 layout.
- Decision: [ADR-0002](../decisions/0002-single-ckmclient-http-boundary.md), one CKM HTTP boundary.
